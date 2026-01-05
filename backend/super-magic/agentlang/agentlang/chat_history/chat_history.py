# -*- coding: utf-8 -*-
"""
This module defines classes for managing chat history.
"""

import json
import os
from dataclasses import asdict
from datetime import datetime
from typing import Any, Dict, List, Optional, Union

import tiktoken

from agentlang.chat_history.chat_history_compressor import ChatHistoryCompressor

# Import types and tools from new module
from agentlang.chat_history.chat_history_models import (
    AssistantMessage,
    ChatMessage,
    CompressionConfig,
    FunctionCall,
    SystemMessage,
    ToolCall,
    ToolMessage,
    UserMessage,
    format_duration_to_str,
    parse_duration_from_str,
)
from agentlang.llms.token_usage.models import TokenUsage
from agentlang.logger import get_logger

logger = get_logger(__name__)

# ==============================================================================
# ChatHistory class
# ==============================================================================

class ChatHistory:
    """
    Manages Agent chat history, provides loading, saving, adding and querying messages.
    Stores messages using strongly-typed ChatMessage object list.
    """

    def __init__(self, agent_name: str, agent_id: str, chat_history_dir: str,
                 compression_config: Optional[CompressionConfig] = None):
        """
        Initialize ChatHistory.

        Args:
            agent_name (str): Agent name, used to construct filename.
            agent_id (str): Agent unique ID, used to construct filename.
            chat_history_dir (str): Directory to store chat history files.
            compression_config (Optional[CompressionConfig]): Compression config, uses default if not provided.
        """
        if not agent_name:
            raise ValueError("agent_name cannot be empty")
        if not agent_id:
            raise ValueError("agent_id cannot be empty")
        if not chat_history_dir:
            raise ValueError("chat_history_dir cannot be empty")

        self.agent_name = agent_name
        self.agent_id = agent_id
        self.chat_history_dir = chat_history_dir
        self.messages: List[ChatMessage] = []

        # Set compression config, use default if not provided
        self.compression_config = compression_config or CompressionConfig()
        self.compression_config.agent_name = agent_name
        self.compression_config.agent_id = agent_id

        # Compression state tracking
        self._last_compression_message_count = 0
        self._last_compression_token_count = 0

        os.makedirs(self.chat_history_dir, exist_ok=True) # Ensure directory exists
        self._history_file_path = self._build_chat_history_filename()
        self.load() # Try loading history on initialization

        # Instantiate compressor
        self.compressor = ChatHistoryCompressor(self.compression_config)

    @property
    def count(self) -> int:
        """
        Get message count in chat history.

        Returns:
            int: Message count
        """
        return len(self.messages)

    @property
    def tokens_count(self) -> int:
        """
        Calculate total token count consumed in chat history.
        Prioritizes using existing token_usage data in messages, for messages without token_usage,
        uses tiktoken to calculate token count and saves to message's token_usage attribute.

        Returns:
            int: Total token count
        """
        encoding = None
        try:
            # Try to load encoder, if failed then handle in subsequent steps
            encoding = tiktoken.get_encoding("cl100k_base")
        except Exception as e:
            logger.warning(f"Failed to load tiktoken encoder: {e!s}, will use fallback calculation method for messages without token_usage")

        total_tokens = 0
        history_updated = False

        for i, msg in enumerate(self.messages):
            msg_tokens = 0

            # 1. Prioritize using existing token_usage data
            if isinstance(msg, AssistantMessage) and msg.token_usage is not None:
                # For AssistantMessage use token_usage object (unified as TokenUsage type)
                # If total_tokens exists, use it; otherwise use output_tokens or input_tokens
                if hasattr(msg.token_usage, "total_tokens") and msg.token_usage.total_tokens > 0:
                    msg_tokens = msg.token_usage.total_tokens
                elif hasattr(msg.token_usage, "output_tokens") and msg.token_usage.output_tokens > 0:
                    msg_tokens = msg.token_usage.output_tokens
                elif hasattr(msg.token_usage, "input_tokens") and msg.token_usage.input_tokens > 0:
                    msg_tokens = msg.token_usage.input_tokens

                if msg_tokens > 0:
                    total_tokens += msg_tokens
                    continue  # Valid token_usage data exists, skip tiktoken calculation

            # 2. No valid token_usage data, use tiktoken calculation
            if encoding:
                try:
                    # Calculate tokens of message content
                    content = getattr(msg, 'content', '') or ''
                    content_tokens = len(encoding.encode(content))

                    # Estimate message metadata tokens (role, etc.)
                    metadata_tokens = 4  # Approximately 4 tokens per message for basic metadata like role

                    # Handle tool call messages
                    if isinstance(msg, AssistantMessage) and msg.tool_calls:
                        tool_tokens = 0
                        for tc in msg.tool_calls:
                            # Calculate tokens of tool name and parameters
                            tool_name = tc.function.name
                            tool_args = tc.function.arguments

                            # Calculate tokens for tool call
                            tool_tokens += len(encoding.encode(tool_name)) + len(encoding.encode(tool_args)) + 10
                        content_tokens += tool_tokens

                    # Handle tool result message
                    if isinstance(msg, ToolMessage):
                        metadata_tokens += 4  # Extra token for tool result message

                    msg_tokens = content_tokens + metadata_tokens

                    # 3. Save calculation result to message's token_usage attribute
                    if isinstance(msg, AssistantMessage):
                        if msg.token_usage is None:
                            # Use new TokenUsage class to create object
                            # As estimate, allocate all msg_tokens to output_tokens
                            msg.token_usage = TokenUsage(
                                input_tokens=0,
                                output_tokens=msg_tokens,
                                total_tokens=msg_tokens
                            )
                            history_updated = True

                    total_tokens += msg_tokens

                except Exception as e:
                    logger.warning(f"Failed to calculate token for message {i+1} using tiktoken: {e!s}")
                    # Use fallback plan when calculation fails: estimate based on content length
                    try:
                        content = getattr(msg, 'content', '') or ''
                        # Rough estimate: 1 token ≈ 4 characters
                        estimated_tokens = len(content) // 4 + 5  # 5 is base overhead
                        total_tokens += estimated_tokens
                        logger.warning(f"Using length estimation method to calculate token: {estimated_tokens}")
                    except Exception as est_err:
                        logger.error(f"Fallback token estimation also failed: {est_err!s}")
                        # If even estimation fails, add minimum value
                        total_tokens += 5

            else:
                # If no encoding available, use character length estimation
                try:
                    content = getattr(msg, 'content', '') or ''
                    # Rough estimate: 1 token ≈ 4 characters
                    estimated_tokens = len(content) // 4 + 5  # 5 is base overhead
                    total_tokens += estimated_tokens
                except Exception as est_err:
                    logger.error(f"Fallback token estimation failed: {est_err!s}")
                    total_tokens += 5

        # If token_usage was updated, save chat history
        if history_updated:
            try:
                self.save()
                logger.debug("Updated message token_usage data and saved chat history")
            except Exception as e:
                logger.warning(f"Failed to save updated token_usage data: {e!s}")

        return total_tokens

    def _build_chat_history_filename(self) -> str:
        """Build complete file path for chat history"""
        filename = f"{self.agent_name}<{self.agent_id}>.json"
        return os.path.join(self.chat_history_dir, filename)

    def _build_tools_list_filename(self) -> str:
        """Build complete file path for tools list file"""
        filename = f"{self.agent_name}<{self.agent_id}>.tools.json"
        return os.path.join(self.chat_history_dir, filename)

    def exists(self) -> bool:
        """Check if history record file exists"""
        return os.path.exists(self._history_file_path)

    def load(self) -> None:
        """
        Load chat history from JSON file.
        Will look for 'duration' string field and try to parse as duration_ms (float).
        Will look for 'show_in_ui' field, default to True if not present.
        """
        if not self.exists():
            logger.info(f"Chat history file does not exist: {self._history_file_path}, will initialize as empty history.")
            self.messages = []
            return

        try:
            with open(self._history_file_path, "r", encoding='utf-8') as f:
                history_data = json.load(f)

            loaded_messages = []
            if isinstance(history_data, list):
                for msg_dict in history_data:
                    if not isinstance(msg_dict, dict):
                        logger.warning(f"Skipping invalid entry (not a dict) when loading history: {msg_dict}")
                        continue

                    role = msg_dict.get("role")
                    # Create a copy for instantiation, only including dataclass-defined fields
                    args_dict = {} # Start from empty dict, only add necessary fields
                    # Common fields (removed separate token fields)
                    for key in [
                        "content", "role", "tool_calls", "tool_call_id",
                        # "created_at", "system", "prompt_tokens", "completion_tokens", "cached_tokens",
                        #"cache_write_tokens", "cache_hit_tokens" #<-- Removed
                    ]:
                         if key in msg_dict:
                              args_dict[key] = msg_dict[key]
                              # # Type check and convert token fields to prevent loading old invalid data <-- Removed
                              # if key.endswith("_tokens"):
                              #     try:
                              #         args_dict[key] = int(msg_dict[key]) if msg_dict[key] is not None else None
                              #     except (ValueError, TypeError):
                              #         logger.warning(f"Invalid value for token field '{key}' when loading history: {msg_dict[key]}, will ignore.")
                              #         args_dict[key] = None
                              # else:
                              #      args_dict[key] = msg_dict[key]

                    # Handle show_in_ui (replacing is_internal)
                    # Default to True unless explicitly set to False
                    show_ui_value = msg_dict.get("show_in_ui", msg_dict.get("is_internal") == False if "is_internal" in msg_dict else True)
                    args_dict["show_in_ui"] = bool(show_ui_value)

                    # Special handling for duration: parse from 'duration' string to 'duration_ms' float
                    parsed_duration_ms = None
                    duration_str = msg_dict.get("duration")
                    if duration_str is not None:
                        parsed_duration_ms = parse_duration_from_str(duration_str)
                        if parsed_duration_ms is None:
                             logger.warning(f"Failed to parse 'duration' field when loading history: {duration_str}, will ignore. Message: {msg_dict}")

                    # If parsing succeeded, add to args_dict (only for assistant and tool)
                    if role in ["assistant", "tool"] and parsed_duration_ms is not None:
                        args_dict["duration_ms"] = parsed_duration_ms
                    # Compatibility with old duration_ms float field (if it exists and duration string does not)
                    elif role in ["assistant", "tool"] and "duration_ms" in msg_dict and duration_str is None:
                        try:
                             legacy_duration_ms = float(msg_dict["duration_ms"])
                             args_dict["duration_ms"] = legacy_duration_ms
                             logger.debug(f"Loaded duration from old duration_ms field: {legacy_duration_ms}")
                        except (ValueError, TypeError):
                             logger.warning(f"Cannot convert old duration_ms field {msg_dict['duration_ms']} to float, ignored.")

                    try:
                        # Convert back to corresponding dataclass based on role
                        if role == "system":
                            message = SystemMessage(**args_dict)
                        elif role == "user":
                            message = UserMessage.from_dict(msg_dict) if UserMessage.from_dict else UserMessage(**args_dict)
                        elif role == "assistant":
                            message = AssistantMessage.from_dict(msg_dict)
                        elif role == "tool":
                            message = ToolMessage.from_dict(msg_dict)
                        else:
                            logger.warning(f"Unknown role found when loading history: {role}, skipping this message: {msg_dict}")
                            continue
                        loaded_messages.append(message)
                    except TypeError as e:
                        logger.warning(f"Failed to convert message when loading history (field mismatch or type error): {args_dict} (original: {msg_dict}), error: {e}")
                    except Exception as e:
                        logger.error(f"Error processing message when loading history: {msg_dict}, error: {e}", exc_info=True)

                self.messages = loaded_messages
                logger.info(f"Successfully loaded {len(self.messages)} chat messages from {self._history_file_path}.")
            else:
                logger.warning(f"Invalid chat history file format (not a list): {self._history_file_path}")
                self.messages = []

        except json.JSONDecodeError as e:
            logger.error(f"Failed to parse chat history file JSON: {self._history_file_path}, error: {e}")
            self.messages = [] # Clear on parse failure
        except Exception as e:
            logger.error(f"Unknown error occurred when loading chat history: {self._history_file_path}, error: {e}", exc_info=True)
            self.messages = [] # Clear on other errors

    def save(self) -> None:
        """
        Save current chat history to JSON file.
        For Assistant and Tool messages, converts duration_ms (float) to 'duration' (str) for storage.
        Includes show_in_ui field.
        Optional fields equal to None or default values will be omitted to reduce redundancy.
        """
        try:
            history_to_save = []
            for message in self.messages:
                # Convert dataclass to dict (use to_dict method to ensure model-layer logic is applied)
                if hasattr(message, 'to_dict') and callable(message.to_dict):
                    msg_dict = message.to_dict()
                else:
                    # Fallback (should not execute in theory, since all message types have to_dict)
                    msg_dict = asdict(message)
                    logger.warning(f"Message object missing to_dict method: {type(message)}")

                # 1. Handle duration (remove duration_ms, add duration str)
                if isinstance(message, (AssistantMessage, ToolMessage)):
                    duration_ms = msg_dict.pop('duration_ms', None) # Always remove ms field
                    if duration_ms is not None:
                        duration_str = format_duration_to_str(duration_ms)
                        if duration_str:
                            msg_dict['duration'] = duration_str
                # Ensure other types also don't have duration_ms
                elif 'duration_ms' in msg_dict:
                     msg_dict.pop('duration_ms')

                # 2. Remove optional fields with default values (show_in_ui, content, tool_calls, system already handled in to_dict)
                # Here we additionally check for None values that to_dict may still retain (e.g., failed token_usage conversion)
                # And ensure compression_info is removed when None
                keys_to_remove = []
                for key, value in msg_dict.items():
                    # Remove fields with None value (unless it's content or tool_calls which can be None)
                    if value is None and key not in ['content', 'tool_calls']:
                        keys_to_remove.append(key)
                    # Special handling for compression_info, also remove if it's None
                    elif key == 'compression_info' and value is None:
                         keys_to_remove.append(key)
                    # Check if token_usage is None or empty dict
                    elif key == 'token_usage' and (value is None or (isinstance(value, dict) and not value)):
                        keys_to_remove.append(key)

                for key in keys_to_remove:
                    msg_dict.pop(key)

                # Remove ID field from message dict, as it's only for runtime
                msg_dict.pop('id', None)

                history_to_save.append(msg_dict)

            # Use indent for pretty JSON output
            history_json = json.dumps(history_to_save, indent=4, ensure_ascii=False)
            with open(self._history_file_path, "w", encoding='utf-8') as f:
                f.write(history_json)
            # logger.debug(f"Chat history saved to: {self._history_file_path}")
        except Exception as e:
            logger.error(f"Error saving chat history to {self._history_file_path}: {e}", exc_info=True)

    def save_tools_list(self, tools_list: List[Dict[str, Any]]) -> None:
        """
        Save tools list to a .tools.json file with the same name as the chat history file.

        Args:
            tools_list (List[Dict[str, Any]]): The tools list to save.
        """
        try:
            tools_file_path = self._build_tools_list_filename()
            # Use indent for pretty JSON output
            tools_json = json.dumps(tools_list, indent=4, ensure_ascii=False)
            with open(tools_file_path, "w", encoding="utf-8") as f:
                f.write(tools_json)
            logger.debug(f"Tools list saved to: {tools_file_path}")
        except Exception as e:
            logger.error(f"Error saving tools list to {tools_file_path}: {e}", exc_info=True)

    def _validate_and_standardize(self, message: ChatMessage) -> ChatMessage:
        """Internal method: validate and standardize message, return processed message or raise ValueError"""
        # ... (basic validation for role unchanged) ...
        if not hasattr(message, 'role') or not message.role:
            raise ValueError("Message missing 'role' field")

        is_assistant_with_tools = isinstance(message, AssistantMessage) and message.tool_calls
        content_missing = not hasattr(message, 'content') or message.content is None

        # Check if content is missing (unless it's an assistant message with tool_calls)
        if content_missing and not is_assistant_with_tools:
            if isinstance(message, ToolMessage):
                 raise ValueError(f"ToolMessage content cannot be empty: {message}")
            else:
                 logger.warning(f"Message content is None or missing, role: {message.role}. Will use placeholder. Original message: {message}")
                 message.content = " " # Use space as placeholder
                 message.show_in_ui = False # <--- Messages with modified content should not be shown

        # Check if content is empty string (standardize for all types)
        if hasattr(message, 'content') and isinstance(message.content, str) and message.content.strip() == "":
             logger.warning(f"Message content is empty string, role: {message.role}. Will use placeholder. Original message: {message}")
             message.content = " " # Use space as placeholder
             message.show_in_ui = False # <--- Messages with modified content should not be shown

        # ... (specific type validation for tool_call_id, Assistant tool_calls structure unchanged) ...
        if isinstance(message, ToolMessage):
            if not message.tool_call_id:
                raise ValueError(f"ToolMessage missing 'tool_call_id': {message}")

        if isinstance(message, AssistantMessage) and message.tool_calls:
             for tc in message.tool_calls:
                 if not isinstance(tc, ToolCall) or not tc.id or not tc.function or not tc.function.name:
                     raise ValueError(f"AssistantMessage contains invalid ToolCall structure: {tc}")
                 # Ensure arguments is a string
                 if not isinstance(tc.function.arguments, str):
                     try:
                         # Try to serialize, raise error if it fails
                         tc.function.arguments = json.dumps(tc.function.arguments, ensure_ascii=False)
                     except Exception as e:
                          raise ValueError(f"Cannot convert ToolCall arguments to JSON string: {tc.function.arguments}, error: {e}")

        # If content is empty string but has tool_calls, ensure content is "Continue" (API doesn't allow empty or None)
        if isinstance(message, AssistantMessage) and message.content == " " and message.tool_calls:
             message.content = "Continue"

        # Ensure created_at exists and format is correct
        if not hasattr(message, 'created_at') or not isinstance(message.created_at, str):
             message.created_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        # Ensure show_in_ui exists and is boolean
        if not hasattr(message, 'show_in_ui') or not isinstance(message.show_in_ui, bool):
             logger.warning(f"Message missing valid 'show_in_ui' field, will set to True. Message: {message}")
             message.show_in_ui = True

        return message

    def _should_skip_message(self, message: ChatMessage) -> bool:
        """
        Determine if this message should be skipped from being added.
        When a message's show_in_ui is false and content is identical to the most recent consecutive messages, it will be skipped.

        Args:
            message (ChatMessage): Message to be added

        Returns:
            bool: Whether this message should be skipped
        """

        # If message list is empty, don't skip
        if not self.messages:
            return False

        # Get current message content
        current_content = getattr(message, 'content', '')

        # Check starting from the last message
        for prev_msg in reversed(self.messages):
            # If previous message has different role, break check
            if prev_msg.role != message.role:
                break

            # If content is different, break check
            prev_content = getattr(prev_msg, 'content', '')
            if prev_content != current_content:
                break

            # Found a message with same content and same role, should skip
            return True

        # No matching condition found, don't skip
        return False

    async def add_message(self, message: ChatMessage) -> bool:
        """
        Add a message to chat history and check if compression is needed.

        Args:
            message (ChatMessage): Message object to add.

        Returns:
            bool: Whether compression was performed

        Raises:
            ValueError: If message is invalid.
        """
        try:
            validated_message = self._validate_and_standardize(message)

            # Check if this message should be skipped
            if self._should_skip_message(validated_message):
                return False

            self.messages.append(validated_message)
            self.save()

            # Asynchronously check and perform compression
            compressed = await self.check_and_compress_if_needed()
            return compressed

        except ValueError as e:
            logger.error(f"Failed to add invalid message asynchronously: {e}")
            raise # Re-raise exception to let caller know addition failed
        except Exception as e:
            logger.error(f"Unexpected error occurred while adding message asynchronously: {e}", exc_info=True)
            # Decide whether to raise exception based on strategy
            return False

    # --- Convenient add methods --- (updated parameter name to show_in_ui)

    async def append_system_message(self, content: str, show_in_ui: bool = False) -> None:
        """Add a system message"""
        message = SystemMessage(content=content, show_in_ui=show_in_ui)
        await self.add_message(message)

    async def append_user_message(self, content: str, show_in_ui: bool = True) -> None:
        """Add a user message"""
        message = UserMessage(content=content, show_in_ui=show_in_ui)
        await self.add_message(message)

    async def append_assistant_message(self,
                                 content: Optional[str],
                                 tool_calls_data: Optional[List[Union[ToolCall, Dict]]] = None,
                                 show_in_ui: bool = True,
                                 duration_ms: Optional[float] = None,
                                 # --- Only accept TokenUsage object ---
                                 token_usage: Optional[TokenUsage] = None
                                 ) -> None:
        """
        Add an assistant message.

        Args:
            content (Optional[str]): Message content.
            tool_calls_data (Optional[List[Union[ToolCall, Dict]]]): List of tool calls.
            show_in_ui (bool): Whether to display this message in UI.
            duration_ms (Optional[float]): LLM call duration (milliseconds).
            token_usage (Optional[TokenUsage]): Token usage information object.
        """
        processed_tool_calls: Optional[List[ToolCall]] = None
        contains_finish_task = False # Initialize outside loop
        if tool_calls_data:
            processed_tool_calls = []
            for tc_data in tool_calls_data:
                tool_call_obj = None
                function_name = None

                if isinstance(tc_data, ToolCall):
                    # Already a ToolCall object, check and standardize arguments
                    if not isinstance(tc_data.function.arguments, str):
                        try:
                            tc_data.function.arguments = json.dumps(tc_data.function.arguments, ensure_ascii=False)
                        except Exception as e:
                            logger.warning(f"Failed to standardize AssistantMessage ToolCall arguments: {tc_data.function.arguments}, error: {e}. Skipping this ToolCall.")
                            continue
                    tool_call_obj = tc_data
                    function_name = tc_data.function.name

                elif isinstance(tc_data, dict):
                    # Create ToolCall object from dictionary
                    try:
                        function_data = tc_data.get("function", {})
                        if not isinstance(function_data, dict):
                             raise ValueError("Tool call 'function' field must be a dictionary")

                        arguments_raw = function_data.get("arguments")
                        arguments_str = None
                        # Ensure arguments is JSON string
                        if isinstance(arguments_raw, str):
                            arguments_str = arguments_raw
                        else:
                             arguments_str = json.dumps(arguments_raw or {}, ensure_ascii=False) # Serialize if None or non-string

                        # Get required fields
                        func_name = function_data.get("name")
                        tool_id = tc_data.get("id")
                        tool_type = tc_data.get("type", "function") # Default to function

                        if not func_name or not tool_id:
                             raise ValueError("Tool call missing required 'id' or 'function.name'")

                        function_call = FunctionCall(name=func_name, arguments=arguments_str)
                        tool_call_obj = ToolCall(id=tool_id, type=tool_type, function=function_call)
                        function_name = func_name

                    except Exception as e:
                        logger.error(f"Failed to create ToolCall from dictionary: {tc_data}, error: {e}", exc_info=True)
                        continue # Skip this invalid tool_call
                else:
                     logger.warning(f"Cannot handle tool_call data type: {type(tc_data)}, skipped: {tc_data}")
                     continue # Skip unhandleable types

                # If successfully processed, add to list and check for finish_task
                if tool_call_obj:
                    processed_tool_calls.append(tool_call_obj)
                    if function_name == "finish_task":
                         contains_finish_task = True

        # If contains finish_task, force not show in UI
        if contains_finish_task:
             show_in_ui = False

        message = AssistantMessage(
            content=content,
            tool_calls=processed_tool_calls if processed_tool_calls else None,
            show_in_ui=show_in_ui,
            duration_ms=duration_ms,
            token_usage=token_usage
        )
        await self.add_message(message)

    async def append_tool_message(self,
                            content: str,
                            tool_call_id: str,
                            system: Optional[str] = None,
                            show_in_ui: bool = True,
                            duration_ms: Optional[float] = None) -> None:
        """
        Add a tool message.

        Args:
            content (str): Tool result content.
            tool_call_id (str): Corresponding ToolCall ID.
            system (Optional[str]): Internal system flag.
            show_in_ui (bool): Whether to display this message in UI.
            duration_ms (Optional[float]): Tool execution duration (milliseconds).
        """
        if not tool_call_id:
             raise ValueError("Must provide tool_call_id when adding ToolMessage")
        message = ToolMessage(
            content=content,
            tool_call_id=tool_call_id,
            system=system,
            show_in_ui=show_in_ui,
            duration_ms=duration_ms
        )
        await self.add_message(message)

    # --- Query methods --- (modified get_messages filtering logic)

    def get_messages(self, include_hidden_in_ui: bool = False) -> List[ChatMessage]:
        """
        Get message list, can optionally include messages not displayed in UI.

        Args:
            include_hidden_in_ui (bool): Whether to include messages marked as show_in_ui=False. Defaults to False.

        Returns:
            List[ChatMessage]: List of message objects meeting the criteria.
        """
        if include_hidden_in_ui:
            return list(self.messages) # Return a copy of all messages
        else:
            # Only return messages where show_in_ui is True
            return [msg for msg in self.messages if msg.show_in_ui]

    def get_messages_for_llm(self) -> List[Dict[str, Any]]:
        """
        Build a whitelist-safe message list for LLM APIs (dict format).
        Only includes fields the API understands; internal fields (show_in_ui, duration_ms,
        token_usage, created_at, system/tool flags) are excluded.
        """
        llm_messages = []
        # Iterate all stored messages
        for message in self.messages:
            # Whitelist mode: only include fields required by the API
            llm_msg: Dict[str, Any] = {"role": message.role}

            role = message.role

            if role == "system":
                # System messages only need role and content
                content = getattr(message, 'content', ' ')  # Ensure content exists
                llm_msg["content"] = content if content and content.strip() else " "

            elif role == "user":
                # User message only needs role and content
                content = getattr(message, 'content', ' ')
                llm_msg["content"] = content if content and content.strip() else " "

            elif role == "assistant":
                # Assistant message can have content, tool_calls, or both
                has_content = False
                content = getattr(message, 'content', None)
                # Only add content when it exists and is non-empty
                if content and content.strip():
                    llm_msg["content"] = content
                    has_content = True

                tool_calls = getattr(message, 'tool_calls', None)
                has_tool_calls = False
                if tool_calls:
                    # Format tool_calls
                    formatted_tool_calls = []
                    for tc in tool_calls:
                         # Ensure tc is ToolCall object and structure is valid
                         if isinstance(tc, ToolCall) and isinstance(tc.function, FunctionCall) and tc.id and tc.function.name:
                             arguments_str = tc.function.arguments
                             # Ensure arguments is string
                             if not isinstance(arguments_str, str):
                                  try:
                                      arguments_str = json.dumps(arguments_str, ensure_ascii=False)
                                  except Exception:
                                       logger.warning(f"Cannot serialize assistant tool_call arguments in get_messages_for_llm: {arguments_str}. Will use empty JSON object string.")
                                       arguments_str = "{}"

                             formatted_tool_calls.append({
                                "id": tc.id,
                                "type": tc.type,
                                "function": {
                                    "name": tc.function.name,
                                    "arguments": arguments_str
                                }
                             })
                         else:
                              logger.warning(f"Skipping invalid assistant tool_call structure in get_messages_for_llm: {tc}")

                    if formatted_tool_calls:
                        llm_msg["tool_calls"] = formatted_tool_calls
                        has_tool_calls = True

                # Sanity check: Assistant message must have at least content or tool_calls
                # If neither, force add space content to avoid API error
                if not has_content and not has_tool_calls:
                    logger.warning(f"Assistant message prepared for LLM has neither valid content nor tool calls: {message}. Forcing addition of space content.")
                    llm_msg["content"] = " "

            elif role == "tool":
                # Tool message needs role, content, and tool_call_id
                content = getattr(message, 'content', ' ')
                llm_msg["content"] = content if content and content.strip() else " "
                tool_call_id = getattr(message, 'tool_call_id', None)
                if tool_call_id:
                    llm_msg["tool_call_id"] = tool_call_id
                else:
                    logger.error(f"Tool message prepared for LLM is missing tool_call_id: {message}. This may cause API error.")
                    # Continue adding message even if id is missing, let API layer handle the error

            # Other role types should not appear here, ignore if they do
            else:
                logger.warning(f"Encountered unknown role in get_messages_for_llm: {role}, skipped.")
                continue # Skip this message

            # --- Whitelist construction complete, no need to pop any fields --- #
            llm_messages.append(llm_msg)

        return llm_messages

    def get_last_messages(self, n: int = 1) -> Union[Optional[ChatMessage], List[ChatMessage]]:
        """
        Get the last n messages.

        Args:
            n (int): Number of messages to fetch. Defaults to 1.

        Returns:
            Union[Optional[ChatMessage], List[ChatMessage]]:
            - When n=1: last message, or None if empty
            - When n>1: list of the last n messages (or all if fewer)
        """
        if not self.messages:
            return None if n == 1 else []

        if n == 1:
            # Return a single message to match legacy get_last_message()
            return self.messages[-1]
        else:
            # Return list of last n messages
            return self.messages[-min(n, len(self.messages)):]

    def get_last_message(self) -> Optional[ChatMessage]:
        """
        Get the last message.

        Note: kept for backward compatibility; prefer get_last_messages().

        Returns:
            Optional[ChatMessage]: Last message, or None if empty.
        """
        return self.get_last_messages(1)

    def get_second_last_message(self) -> Optional[ChatMessage]:
        """
        Get the second-to-last message.

        Note: kept for backward compatibility; prefer get_last_messages(2)[0].

        Returns:
            Optional[ChatMessage]: Second-to-last message, or None if fewer than two.
        """
        if len(self.messages) >= 2:
            return self.messages[-2]
        return None

    def remove_last_message(self) -> Optional[ChatMessage]:
        """
        Remove the last message and save.

        Returns:
            Optional[ChatMessage]: Removed message, or None if empty.
        """
        if self.messages:
            removed_message = self.messages.pop()
            self.save()
            logger.debug(f"Removed last message: {removed_message}")
            return removed_message
        logger.debug("Tried to remove last message, but history is empty.")
        return None

    def insert_message_before_last(self, message: ChatMessage) -> None:
        """
        Insert a message before the last entry and save.
        If there are fewer than one message, this acts as append.

        Args:
            message (ChatMessage): Message to insert.
        """
        try:
            validated_message = self._validate_and_standardize(message)
            if len(self.messages) > 0:
                 insert_index = len(self.messages) - 1
                 self.messages.insert(insert_index, validated_message)
                 logger.debug(f"Inserted message at index {insert_index}: {validated_message}")
            else:
                 self.messages.append(validated_message)
                 logger.debug(f"History too short, appended message: {validated_message}")

            self.save()
        except ValueError as e:
             logger.error(f"Failed to insert invalid message: {e}")
             raise
        except Exception as e:
            logger.error(f"Unexpected error inserting message: {e}", exc_info=True)
            # Decide whether to re-raise based on policy

    def replace(self, new_messages: List[ChatMessage]) -> None:
        """
        Replace current chat history with a new message list and save.

        Args:
            new_messages (List[ChatMessage]): Messages to replace current history.
        """
        try:
            # Validate each message
            validated_messages = []
            for message in new_messages:
                try:
                    validated_message = self._validate_and_standardize(message)
                    validated_messages.append(validated_message)
                except ValueError as e:
                    logger.warning(f"Skipping invalid message while replacing history: {message}, error: {e}")

            # Replace messages
            self.messages.clear()
            self.messages.extend(validated_messages)

            # Save updated history
            self.save()
            logger.info(f"Chat history replaced with {len(validated_messages)} new messages")
        except Exception as e:
            logger.error(f"Error replacing chat history: {e}", exc_info=True)
            raise

    async def check_and_compress_if_needed(self) -> bool:
        """
        Check whether chat history needs compression and compress if needed.
        Call after adding messages or at other suitable times.

        Returns:
            bool: Whether compression was executed.
        """
        # Get current message and token counts
        current_message_count = self.count
        current_token_count = self.tokens_count

        # Decide whether compression is needed
        if not self.compressor.should_compress(current_message_count, current_token_count):
            return False

        logger.info("Starting chat history compression")
        # Perform compression
        return await self._compress_history()

    async def _compress_history(self) -> bool:
        """
        Internal compression routine.

        Returns:
            bool: Whether compression was executed.
        """
        try:
            original_count = len(self.messages)
            # Identify messages to compress and to preserve
            to_preserved, to_compress, recent_messages = self.compressor._filter_messages_to_compress(self.messages)

            if not to_compress:
                logger.info("No messages need compression")
                return False

            # Compress selected messages
            compressed_message = await self.compressor.compress_messages(to_compress, to_preserved)
            if not compressed_message:
                logger.warning("Compression failed; keeping original messages")
                return False

            # Replace with preserved + compressed + recent messages
            new_messages = to_preserved + [compressed_message] + recent_messages

            # Update message list
            self.replace(new_messages)

            # Update compressor stats
            self.compressor.update_compression_stats(
                message_count=self.count,
                token_count=self.tokens_count
            )

            # Record compression results
            compressed_count = len(new_messages)
            logger.info(
                f"Compression complete: original_count={original_count}, "
                f"compressed_count={compressed_count}, "
                f"ratio={(original_count - compressed_count) / original_count:.1%}"
            )

            return True

        except Exception as e:
            logger.exception(f"Error compressing history: {e}")
            return False

    async def compress_history(self) -> bool:
        """
        Manually trigger chat history compression, ignoring thresholds.

        Returns:
            bool: Whether compression executed successfully.
        """
        logger.info("Manual chat history compression triggered")
        # Get current message and token counts
        current_message_count = self.count
        current_token_count = self.tokens_count

        # Force compression
        if not self.compressor.should_compress(current_message_count, current_token_count, force=True):
            logger.info("Forced compression not needed")
            return False

        return await self._compress_history()

    @staticmethod
    def upgrade_compression_config(chat_history: 'ChatHistory') -> 'ChatHistory':
        """
        Add compression configuration to an existing ChatHistory instance.
        Useful when persisted objects predate compression fields.

        Args:
            chat_history (ChatHistory): Chat history instance to upgrade.

        Returns:
            ChatHistory: Upgraded chat history with compression config.
        """
        # Check for existing compression config
        if hasattr(chat_history, 'compression_config') and chat_history.compression_config:
            logger.debug("Chat history already has compression config; no upgrade needed")
            return chat_history

        # Add default compression config
        logger.info(f"Adding compression config to chat history {chat_history.agent_name}<{chat_history.agent_id}>")
        chat_history.compression_config = CompressionConfig()

        # Create compressor
        chat_history.compressor = ChatHistoryCompressor(chat_history.compression_config)

        return chat_history

    def get_first_user_message(self) -> Optional[str]:
        """
        Get the content of the first user message in history.

        Returns:
            Optional[str]: Content of the first user message, or None if none exist.
        """
        for message in self.messages:
            if message.role == "user":
                return message.content
        return None

    def replace_last_user_message(self, new_content: str) -> bool:
        """
        Replace the content of the last user message.

        Args:
            new_content (str): New message content.

        Returns:
            bool: Whether the replacement succeeded.
        """
        # Search backward for the last user message
        for i in range(len(self.messages) - 1, -1, -1):
            if self.messages[i].role == "user":
            # Found user message; replace content
                self.messages[i].content = new_content
                # Persist change
                self.save()
                logger.debug(f"Replaced last user message content with: {new_content}")
                return True

        # No user message found
        logger.warning("Tried to replace last user message but none were found")
        return False
