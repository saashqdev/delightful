# -*- coding: utf-8 -*-
"""
This module defines a class for compressing chat history.
Compresses multi-turn conversations into summaries to reduce token consumption while preserving key information.
"""

from datetime import datetime
from typing import List, Optional

from agentlang.chat_history.chat_history_models import (
    AssistantMessage,
    ChatMessage,
    CompressionConfig,
    CompressionInfo,
    ToolMessage,
)
from agentlang.context.application_context import ApplicationContext
from agentlang.logger import get_logger

logger = get_logger(__name__)

class ChatHistoryCompressor:
    """
    Chat history compressor for compressing multi-turn conversations into summaries.

    This class implements the core logic for chat history compression, including:
    1. Determining which messages need to be compressed
    2. Implementing message compression
    3. Building LLM compression prompts
    4. Calling LLM for compression
    5. Calculating message token counts
    """

    def __init__(self, compression_config: CompressionConfig):
        """
        Initialize chat history compressor

        Args:
            compression_config: Compression configuration parameters
        """
        self.config = compression_config
        self.last_compression_message_count = 0
        self.last_compression_token_count = 0

    def should_compress(self,
                         message_count: int,
                         token_count: int,
                         force: bool = False) -> bool:
        """
        Determine if compression is needed

        Args:
            message_count: Current message count
            token_count: Current token count
            force: Whether to force compression, ignoring thresholds

        Returns:
            bool: Whether compression is needed
        """
        # If compression is not enabled, return directly
        if not self.config.enable_compression and not force:
            logger.debug("Compression is not enabled, skipping compression")
            return False

        # If forcing compression, return True directly
        if force:
            return True

        # Check if message count threshold is exceeded
        need_compression = message_count > self.config.message_threshold
        # Check if token count threshold is exceeded
        need_compression = need_compression or (token_count > self.config.token_threshold)

        # Check if in cooldown period (message increment since last compression is less than cooldown value)
        in_cooldown = (
            message_count - self.last_compression_message_count
            < self.config.compression_cooldown
        )
        logger.info(f"{self.config.agent_name} is in message compression cooldown: {in_cooldown}, message count: {message_count}, message increment since last compression: {message_count - self.last_compression_message_count}, cooldown value: {self.config.compression_cooldown}")

        # Need compression and not in cooldown
        need_compression = need_compression and not in_cooldown

        if not need_compression:
            logger.debug(f"No need to compress: message_count={message_count}, token_count={token_count}, "
                        f"thresholds: message_count={self.config.message_threshold}, "
                        f"token_count={self.config.token_threshold}")

        return need_compression

    def _filter_messages_to_compress(self,
                                    all_messages: List[ChatMessage]) -> tuple[List[ChatMessage], List[ChatMessage], List[ChatMessage]]:
        """
        Filter messages to compress and messages to preserve

        Args:
            all_messages: All messages list

        Returns:
            tuple: (first system message to preserve, messages to compress list, recent messages list)
        """
        # Save all_messages to a json file
        import json
        import os

        # Ensure logs directory exists
        path_manager = ApplicationContext.get_path_manager()
        logs_dir = os.path.join(path_manager.get_chat_history_dir(), 'compressed')
        os.makedirs(logs_dir, exist_ok=True)

        # Save to logs directory
        # Add timestamp to filename
        import datetime
        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        log_file_path = os.path.join(logs_dir, f'{self.config.agent_name}_{self.config.agent_id}_{timestamp}_messages.json')
        with open(log_file_path, 'w') as f:
            json.dump([msg.to_dict() for msg in all_messages], f, ensure_ascii=False)

        # Take first two messages directly (don't compress)
        to_preserved = all_messages[:2] if len(all_messages) >= 2 else all_messages.copy()

        # Get all messages after the second one
        messages_without_to_preserved = all_messages[2:] if len(all_messages) >= 2 else []

        # Filter all non-system messages
        to_compress = [
            msg for msg in messages_without_to_preserved
            if msg.role != "system"
        ]

        # Ensure to preserve the most recent N turns
        preserve_count = min(len(to_compress), self.config.preserve_recent_turns)
        if preserve_count > 0:
            recent_messages = to_compress[-preserve_count:]
            to_compress = to_compress[:-preserve_count]

        # Check if first message in recent_messages is a Tool Message, if so move it to the end of to_compress, because AssistantMessage with tool_calls needs to be together with corresponding ToolMessage
        if recent_messages and isinstance(recent_messages[0], ToolMessage):
            to_compress.append(recent_messages.pop(0))

        return to_preserved, to_compress, recent_messages

    async def compress_messages(self, to_compress: List[ChatMessage], to_preserved: List[ChatMessage]) -> Optional[AssistantMessage]:
        """
        Compress messages.

        Args:
            messages (List[ChatMessage]): List of messages to compress

        Returns:
            Optional[AssistantMessage]: Compressed assistant message, or None if compression fails
        """
        if not to_compress:
            return None

        try:
            # Calculate token count before compression
            original_tokens = sum(self._count_message_tokens(msg) for msg in to_compress)

            # Create system prompt for compression
            system_prompt = self._build_compression_system_prompt(to_preserved)

            # Create user prompt for compression
            user_prompt = self._build_compression_user_prompt(to_compress)

            # Call LLM for compression
            compressed_content = await self._call_llm_for_compression(system_prompt, user_prompt)
            if not compressed_content:
                logger.warning("LLM returned empty compressed content")
                return None

            # Create compressed message
            compressed_message = AssistantMessage(
                content=compressed_content,
                created_at=datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                show_in_ui=False
            )

            # Calculate token count after compression
            compressed_tokens = self._count_message_tokens(compressed_message)

            # Add compression info
            compressed_message.compression_info = CompressionInfo.create(
                message_count=len(to_compress),
                original_tokens=original_tokens,
                compressed_tokens=compressed_tokens
            )

            return compressed_message

        except Exception as e:
            logger.exception(f"Error occurred while compressing messages: {e}")
            return None

    def _build_compression_system_prompt(self, to_preserved: List[ChatMessage]) -> str:
        """
        Build system prompt for compression.

        Returns:
            str: System prompt content
        """
        # Only take the first user message from to_preserved
        user_message = ""
        for msg in to_preserved:
            if msg.role == "user":
                user_message = msg.content
                break
        return f"""You are a professional conversation compression assistant. Your task is to compress multi-turn conversations into a concise but information-complete summary while preserving all key information and context.

The following is the user's message, you should extract valid information based on the user's needs:
```
{user_message}
```

When compressing, please follow these principles:
1. [EXTREMELY IMPORTANT] Preserve complete information about user requirements and task background, especially attempted solutions, encountered problems, and error messages
2. [EXTREMELY IMPORTANT] For repeated attempts, you must preserve the key steps, failure reasons, and differences of each attempt
3. [EXTREMELY IMPORTANT] Preserve all key facts, data, query results, API returns, important technical details, and parameter settings
4. [EXTREMELY IMPORTANT] Preserve all file creation and modification information, including operation type, filename, path, and final content
5. [EXTREMELY IMPORTANT] Maintain the complete logical flow and causal relationships of the conversation to ensure subsequent operations can understand previous steps
6. Use concise language to replace verbose expressions, but never simplify key technical steps and solutions
7. For code snippets, preserve complete core functionality implementation and key parts, especially erroneous code segments
8. For tool calls, fully preserve operation types, parameters, and key results, simplify process descriptions
9. Preserve accurate descriptions of all entity names, professional terms, and technical terms, do not generalize

Special attention: If you find the user is making multiple attempts to solve the same problem, or there is a circular retry pattern, you must completely preserve the details and changes of each attempt to avoid losing the key context that led to the retry. For conversation parts containing important decisions, breakpoints, or turning points, they should be fully preserved.

Your reply should be a detailed summary without any explanation or meta-description, directly output the compressed content. It's better to compress less than to lose any key information that might affect subsequent conversations."""

    def _build_compression_user_prompt(self, messages: List[ChatMessage]) -> str:
        """
        Build user prompt for compression.

        Args:
            messages (List[ChatMessage]): List of messages to compress

        Returns:
            str: User prompt content
        """
        # Convert messages to text format
        messages_text = ""
        for msg in messages:
            role = msg.role.upper()
            content = msg.content if msg.content else ""

            # Handle tool calls
            if hasattr(msg, 'tool_calls') and msg.tool_calls:
                tool_calls_text = []
                for tool_call in msg.tool_calls:
                    if hasattr(tool_call, 'function') and tool_call.function:
                        func = tool_call.function
                        tool_calls_text.append(f"[Call tool: {func.name}, arguments: {func.arguments}]")
                if tool_calls_text:
                    content += "\n" + "\n".join(tool_calls_text)

            messages_text += f"[{role}]: {content}\n\n"

        # Build complete prompt
        target_ratio = self.config.target_compression_ratio
        prompt = f"""Please compress the following multi-turn conversation into a concise but information-complete summary. Target compression ratio is approximately {int(target_ratio*100)}%.

Original conversation content:
{messages_text}

Compressed summary (direct output):"""

        return prompt


    async def _call_llm_for_compression(self, system_prompt: str, user_prompt: str) -> Optional[str]:
        """
        Asynchronously call LLM for compression.

        Args:
            system_prompt (str): System prompt
            user_prompt (str): User prompt

        Returns:
            Optional[str]: Compressed content, or None if failed
        """
        try:
            from agentlang.llms.factory import LLMFactory

            # Build LLM request messages
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt}
            ]

            # Get model ID from config
            model_id = self.config.llm_for_compression

            logger.info(f"Using model {model_id} for chat history compression")

            # Call LLM
            response = await LLMFactory.call_with_tool_support(
                model_id=model_id,
                messages=messages,
                tools=None,
                stop=None
            )

            # Handle response
            if not response or not hasattr(response, 'choices') or len(response.choices) == 0:
                logger.warning("LLM compression call returned empty response")
                return None

            # Extract compressed content
            compressed_content = response.choices[0].message.content

            return compressed_content
        except Exception as e:
            logger.exception(f"Error occurred while calling LLM for compression: {e!r}")
            return None

    def _count_message_tokens(self, message) -> int:
        """
        Calculate token count for a single message

        Args:
            message: Message object to calculate tokens for

        Returns:
            int: Token count of the message
        """
        try:
            # If message already has token_usage info, use it directly
            if (hasattr(message, "token_usage") and
                message.token_usage and
                (hasattr(message.token_usage, "completion_tokens") or
                 hasattr(message.token_usage, "prompt_tokens"))):
                # Calculate total tokens
                total_tokens = 0
                if hasattr(message.token_usage, "completion_tokens"):
                    total_tokens += message.token_usage.completion_tokens or 0
                if hasattr(message.token_usage, "prompt_tokens"):
                    total_tokens += message.token_usage.prompt_tokens or 0
                return total_tokens

            # Otherwise calculate based on message content
            import tiktoken

            # Get encoder, default to gpt-3.5-turbo encoder
            try:
                encoding = tiktoken.encoding_for_model("gpt-3.5-turbo")
            except KeyError:
                # If model doesn't exist, use cl100k_base encoder
                encoding = tiktoken.get_encoding("cl100k_base")

            # Calculate token count for message content
            content = message.content or ""

            # Consider token count for tool calls
            tool_calls_tokens = 0
            if hasattr(message, "tool_calls") and message.tool_calls:
                for tool_call in message.tool_calls:
                    if hasattr(tool_call, "function") and tool_call.function:
                        # Calculate tokens for function name and arguments
                        function_name = tool_call.function.name or ""
                        function_args = tool_call.function.arguments or "{}"
                        tool_calls_tokens += len(encoding.encode(function_name))
                        tool_calls_tokens += len(encoding.encode(function_args))

            # Total tokens = content tokens + tool call tokens + base message structure tokens (approximately 4)
            return len(encoding.encode(content)) + tool_calls_tokens + 4

        except Exception as e:
            # Give warning and return estimated value when calculation fails
            logger.warning(f"Failed to calculate message token count: {e!s}")
            # Use simple character count estimation, assuming approximately 1 token per 3.5 characters
            content_len = len(message.content or "")
            return max(1, int(content_len / 3.5))

    def update_compression_stats(self, message_count: int, token_count: int) -> None:
        """
        Update compression state tracking data

        Args:
            message_count: Current message count
            token_count: Current token count
        """
        self.last_compression_message_count = message_count
        self.last_compression_token_count = token_count
