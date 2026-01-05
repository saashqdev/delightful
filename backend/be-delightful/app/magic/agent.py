import asyncio
import json
import os
import random
import re  # Add re module import
import string
import time
import traceback
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Optional

from openai.types.chat import ChatCompletion, ChatCompletionMessage, ChatCompletionMessageToolCall

from agentlang.agent.base import BaseAgent
from agentlang.agent.loader import AgentLoader
from agentlang.agent.state import AgentState
from agentlang.chat_history import AssistantMessage, CompressionConfig, FunctionCall, ToolCall, ToolMessage
from agentlang.chat_history.chat_history import ChatHistory
from agentlang.config.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.event.data import (
    AfterLlmResponseEventData,
    AfterMainAgentRunEventData,
    AfterToolCallEventData,
    BeforeLlmRequestEventData,
    BeforeMainAgentRunEventData,
    BeforeToolCallEventData,
    ErrorEventData,
)
from agentlang.event.event import EventType
from agentlang.exceptions import UserFriendlyException
from agentlang.llms.factory import LLMFactory
from agentlang.llms.token_usage.models import TokenUsage
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.parallel import Parallel
from app.core.context.agent_context import AgentContext
from app.paths import PathManager
from app.tools.core.base_tool import BaseTool
from app.tools.core.tool_executor import tool_executor
from app.tools.core.tool_factory import tool_factory
from app.tools.list_dir import ListDir

logger = get_logger(__name__)


class Agent(BaseAgent):

    context_prompt = None  # New context_prompt attribute, splits dynamic content from prompt to ensure first system prompt hits cache

    def _setup_agent_context(self, agent_context: Optional[AgentContext] = None) -> AgentContext:
        """
        Set up and initialize Agent context

        Args:
            agent_context: Optional Agent context instance, creates new instance if None

        Returns:
            AgentContext: Configured Agent context instance
        """
        # If no agent_context is passed, create a new instance
        if agent_context is None:
            agent_context = AgentContext()
            logger.info("No agent_context provided, automatically creating new AgentContext instance")

        # Update basic settings in agent context
        agent_context.agent_name = self.agent_name  # Set agent_name
        agent_context.stream_mode = self.stream_mode
        agent_context.use_dynamic_prompt = False
        agent_context._workspace_dir = PathManager.get_workspace_dir()

        # Ensure context has chat_history_dir
        if not hasattr(agent_context, 'chat_history_dir') or not agent_context.chat_history_dir:
            agent_context.chat_history_dir = PathManager.get_chat_history_dir()
            logger.warning(f"chat_history_dir not set in AgentContext, using default: {PathManager.get_chat_history_dir()}")

        return agent_context

    def __init__(self, agent_name: str, agent_context: AgentContext = None, agent_id: str = None):
        self.agent_name = agent_name

        # Set Agent context
        self.agent_context = self._setup_agent_context(agent_context)
        agents_dir = Path(PathManager.get_project_root() / "agents")
        self._agent_loader = AgentLoader(agents_dir=agents_dir)

        # Whether to enable multi-tool calls, disabled by default
        self.enable_multi_tool_calls = config.get("agent.enable_multi_tool_calls", False)
        # Whether to enable parallel tool calls, disabled by default
        self.enable_parallel_tool_calls = config.get("agent.enable_parallel_tool_calls", False)
        # Parallel tool call timeout (seconds), default is no timeout
        self.parallel_tool_calls_timeout = config.get("agent.parallel_tool_calls_timeout", None)

        logger.info(f"Initialize agent: {self.agent_name}")
        self._initialize_agent()

        # After initialization, update llm in context
        self.agent_context.llm = self.llm_name

        # Agent ID handling
        if self.has_attribute("main"):
            if agent_id and agent_id != "main":
                logger.warning("agent_id parameter is not allowed for main Agent")
                raise ValueError("agent_id parameter is not allowed for main Agent")
            agent_id = "main"
            logger.info(f"Using default Agent ID: {agent_id}")

        if agent_id:
            # Do not validate; LLMs are prone to mistakes
            self.id = agent_id
            logger.info(f"Using provided Agent ID: {self.id}")
        else:
            # If agent_id not provided, generate a new one
            self.id = self._generate_agent_id()

        # Check if Agent already exists using base class ACTIVE_AGENTS
        agent_key = (self.agent_name, self.id)
        if agent_key in self.ACTIVE_AGENTS:
            error_message = f"Agent (name='{self.agent_name}', id='{self.id}') already exists and is active."
            logger.error(error_message)
            raise ValueError(error_message)
        self.ACTIVE_AGENTS.add(agent_key)
        logger.info(f"Agent (name='{self.agent_name}', id='{self.id}') added to active registry.")

        # Initialize ChatHistory instance with compression settings
        compression_config = CompressionConfig(
            enable_compression=True,  # Enable compression
            preserve_recent_turns=5,  # Keep the latest 5 messages
            llm_for_compression=self.llm_id, # Pass agent model ID for compression; compression model max_context_tokens must be >= agent model max_context_tokens to avoid failures
            agent_name=self.agent_name,  # Pass agent name
            agent_id=self.id,  # Pass agent ID
            agent_model_id=self.llm_id  # Pass agent model ID
        )
        self.chat_history = ChatHistory(
            self.agent_name,
            self.id,
            self.agent_context.chat_history_dir,
            compression_config=compression_config  # Pass compression config
        )

        # Set chat_history on agent_context so tools can access it
        self.agent_context.chat_history = self.chat_history
        logger.debug("chat_history set on agent_context for tool access")

    def _initialize_agent(self):
        """Initialize agent"""
        # Load agent config from .agent file
        self.load_agent_config(self.agent_name)

        # Extract <context> block content
        context_match = re.search(r"<context>(.*?)</context>", self.system_prompt, re.DOTALL)
        if context_match:
            # Extract and temporarily store context content
            context_content = "Current Context:\n" + context_match.group(1).strip()
            # Remove context block from system prompt, keep a blank line
            self.system_prompt = re.sub(r"\s*<context>.*?</context>\s*", "\n\n", self.system_prompt, count=1, flags=re.DOTALL)
            self.system_prompt = self.system_prompt.strip()
            logger.debug("Extracted <context> block from system prompt")
        else:
            context_content = None
            logger.debug("<context> block not found in system prompt")

        # Collect tool hints
        tool_hints = []
        for tool_name in self.tools.keys():
            tool_instance = tool_factory.get_tool_instance(tool_name)
            if tool_instance and (hint := tool_instance.get_prompt_hint()):
                tool_hints.append((tool_name, hint))
        # Append tool hints to system prompt
        if tool_hints:
            formatted_hints = [f"### {name}\n{hint}" for name, hint in tool_hints]
            for name, _ in tool_hints:
                logger.info(f"Appended {name} tool hint to system prompt")
            self.system_prompt += "\n\n---\n\n## Advanced Tool Usage Instructions:\n> You should strictly follow the examples to use the tools.\n" + "\n\n".join(formatted_hints)

            # Add language usage guidance
            # Only for models like gpt-4.1 series that always speak English
            if self.llm_id in ["gpt-4.1", "gpt-4.1-mini", "gpt-4.1-nano"]:
                self.system_prompt += "\n\n---\n\nYou are a Simplified Chinese expert, skilled at communicating with users in Chinese. Your user is a Chinese person who only speaks Simplified Chinese and doesn't understand English at all. Your thinking process, outputs, explanatory notes when calling tools, and any other content that will be directly shown to the user must all be in Simplified Chinese. When you retrieve English materials, you need to translate them into Simplified Chinese before returning them to the user."

        if not self.system_prompt:
            raise ValueError("Prompt is not set")
        if not self.llm_id:
            raise ValueError("LLM model is not set")
        self.llm_client = LLMFactory.get(self.llm_id)
        model_config = LLMFactory.get_model_config(self.llm_id)
        self.llm_name = model_config.name
        self.model_config = model_config
        # Remove sensitive information like api_key and api_base_url from self.model_config
        self.model_config.api_key = None
        self.model_config.api_base_url = None

        # Prepare variables and apply replacement
        variables = self._prepare_prompt_variables()
        self.system_prompt = self._agent_loader.set_variables(self.system_prompt, variables)

        # If context_content exists, perform variable replacement and save
        if context_content:
            self.context_prompt = self._agent_loader.set_variables(context_content, variables)
            logger.debug("Completed context_prompt variable replacement")

    def _prepare_prompt_variables(self) -> Dict[str, str]:
        """
        Prepare dictionary for replacing variables in prompt

        Returns:
            Dict[str, str]: Dictionary containing variable names and corresponding values
        """
        # Use ListDir tool to generate directory structure
        list_dir_tool = ListDir()
        workspace_dir = self.agent_context._workspace_dir

        # Call _run method to get formatted directory content
        workspace_dir_files_list = list_dir_tool._run(
            relative_workspace_path=".",
            level=5,  # Set reasonable recursion depth
            filter_binary=False,  # Don't filter binary files
            calculate_tokens=True,  # Calculate token count
        )

        # If directory is empty, display empty workspace message
        if "Directory is empty, no files" in workspace_dir_files_list:
            workspace_dir_files_list = "Current working directory is empty, no files"

        # Build variables dictionary
        variables = {
            "current_datetime": datetime.now().strftime("%Y-%m-%d %H:%M:%S %A (Week %W)"),
            "workspace_dir": self.agent_context._workspace_dir,
            "workspace_dir_files_list": workspace_dir_files_list,
            "recommended_max_output_tokens": 4096,
        }

        return variables

    def _generate_agent_id(self) -> str:
        """Generate Agent ID conforming to specification"""
        first_char = random.choice(string.ascii_letters)
        remaining_chars = ''.join(random.choices(string.ascii_letters + string.digits, k=5))
        new_id = first_char + remaining_chars
        # Remove unnecessary validation logic, generation logic already ensures correct format
        logger.info(f"Auto-generated new Agent ID: {new_id}")
        return new_id

    async def run_main_agent(self, query: str):
        """Run main agent"""
        try:
            # Trigger before main agent run event
            await self.agent_context.dispatch_event(EventType.BEFORE_MAIN_AGENT_RUN, BeforeMainAgentRunEventData(
                agent_context=self.agent_context,
                agent_name=self.agent_name,
                query=query,
            ))

            await self.run(query)

            # Trigger after main agent run event
            await self.agent_context.dispatch_event(EventType.AFTER_MAIN_AGENT_RUN, AfterMainAgentRunEventData(
                agent_context=self.agent_context,
                agent_name=self.agent_name,
                agent_state=self.agent_state,
                query=query,
            ))
        except Exception as e:
            logger.error(f"Main agent run exception: {e!s}")
            if isinstance(e, UserFriendlyException):
                await self.agent_context.dispatch_event(EventType.ERROR, ErrorEventData(
                    exception=e,
                    agent_context=self.agent_context,
                    error_message=e.get_user_friendly_message()
                ))
    async def run(self, query: str):
        """Run agent"""
        self.set_agent_state(AgentState.RUNNING)

        logger.info(f"Starting to run agent: {self.agent_name}, query: {query}")

        # Switch to workspace directory
        try:
            # Use os.chdir() instead of os.chroot() to avoid requiring root permissions
            workspace_dir = self.agent_context._workspace_dir
            if os.path.exists(workspace_dir):
                os.chdir(workspace_dir)
                logger.info(f"Switched working directory to: {workspace_dir}")
            else:
                logger.warning(f"Workspace directory does not exist: {workspace_dir}")
        except Exception as e:
            logger.error(f"Error switching working directory: {e!s}")

        # Construct chat_history
        # ChatHistory already loaded history during initialization
        # Check if System Prompt needs to be added (only when history is empty)
        if not self.chat_history.messages:
            logger.info("Chat history is empty, adding main System Prompt")
            await self.chat_history.append_system_message(self.system_prompt)

            # If context_prompt exists, add as second user message, ensuring it doesn't affect first system prompt cache hit
            if self.context_prompt:
                logger.info("Adding Context System Prompt as second user message")
                await self.chat_history.append_user_message(self.context_prompt)

        # Add current user query
        await self.chat_history.append_user_message(query)

        # Choose different Agent Loop method based on stream_mode
        try:
            if self.stream_mode:
                return await self._handle_agent_loop_stream()
            else:
                return await self._handle_agent_loop()
        finally:
            # Remove Agent from active registry, using base class ACTIVE_AGENTS
            agent_key = (self.agent_name, self.id)
            if agent_key in self.ACTIVE_AGENTS:
                self.ACTIVE_AGENTS.remove(agent_key)
                logger.info(f"Agent (name='{self.agent_name}', id='{self.id}') removed from active registry.")
            else:
                # Theoretically should not happen, but log just in case
                logger.warning(f"Attempted to remove Agent (name='{self.agent_name}', id='{self.id}') but not found in active registry.")
            # When task is terminated by user, agent coroutine is forcibly cancelled, need to close all resources here
            await self.agent_context.close_all_resources()


    async def _handle_agent_loop(self) -> None:
        """Handle agent loop"""
        no_tool_call_count = 0
        final_response = None
        run_exception_count = 0
        # last_llm_message is used to get the final message content at loop end
        last_llm_message: Optional[ChatCompletionMessage] = None

        while True:
            # Update activity time for activity tracking
            self.agent_context.update_activity_time()

            try:
                # Check if session needs to be restored
                skip_llm_call, tool_calls_to_execute, llm_response_message, assistant_message_to_restore = await self._check_and_restore_session()

                # Determine if LLM call should be skipped
                if skip_llm_call:
                    # Use restored session
                    tool_calls_to_execute, llm_response_message = await self._restore_session_state(
                        assistant_message_to_restore)
                    last_llm_message = llm_response_message  # Also update last_llm_message
                    if not tool_calls_to_execute or not llm_response_message:
                        final_response = "Internal error occurred while restoring session state."
                        break
                else:
                    # Call LLM to get response
                    llm_response_message, tool_calls_to_execute, token_usage, llm_duration_ms = await self._prepare_and_call_llm()
                    last_llm_message = llm_response_message  # Save for final response at loop end

                    # Handle case with no tool calls
                    if not tool_calls_to_execute and llm_response_message.role == "assistant":
                        no_tool_call_count, should_continue, new_final_response = await self._handle_no_tool_calls(
                            llm_response_message, no_tool_call_count, token_usage, llm_duration_ms)
                        if not should_continue:
                            final_response = new_final_response
                            break
                        continue

                    # Add tool call responses to history
                    await self._add_tool_calls_to_history(llm_response_message, tool_calls_to_execute, token_usage, llm_duration_ms)

                # Execute tool calls and process results
                try:
                    finish_task_detected, final_response_from_tools = await self._execute_and_process_tool_calls(
                        tool_calls_to_execute, llm_response_message)

                    if finish_task_detected:
                        final_response = final_response_from_tools
                        break
                except asyncio.CancelledError:
                    # Catch and handle cancellation from ASK_USER
                    logger.info("Loop cancelled due to ASK_USER request")
                    break  # Exit loop directly

            except Exception as e:
                # Handle exception cases
                should_continue, new_final_response, new_exception_count = await self._handle_agent_loop_exception(
                    e, run_exception_count)
                run_exception_count = new_exception_count
                if new_final_response:
                    final_response = new_final_response
                if not should_continue:
                    break

        # 8. Cleanup after completing loop
        return await self._finalize_agent_loop(final_response, last_llm_message)

    async def _check_and_restore_session(self) -> tuple[bool, List[ToolCall], Optional[ChatCompletionMessage], Optional[AssistantMessage]]:
        """
        Check if previous session state needs restoration and return corresponding execution configuration

        Returns:
            Tuple: (whether to skip LLM call, list of tool calls to execute, LLM response message, assistant message to restore)
        """
        # Initialize default return values
        skip_llm_call = False
        tool_calls_to_execute = []
        llm_response_message = None
        assistant_message_to_restore = None

        # Get last and second-to-last non-internal messages
        last_message = self.chat_history.get_last_message()
        second_last_message = self.chat_history.get_second_last_message()

        # Check if basic conditions for restoration are met
        if last_message and last_message.role == "user" and \
           second_last_message and second_last_message.role == "assistant" and \
           isinstance(second_last_message, AssistantMessage) and second_last_message.tool_calls:

            logger.info("Performing session state restoration check")
            last_user_query_content = last_message.content  # User input content

            # Check if same as first input (may occur in call_agent tool invocation)
            first_user_message = self.chat_history.get_first_user_message()
            if last_user_query_content == first_user_message:
                logger.info("Detected last user input matches first user input, treating as user intent to continue")
                last_user_query_content = "continue"
                # Update user message in history
                self.chat_history.replace_last_user_message("continue")

            # Handle user's request to continue
            if last_user_query_content.lower() in ["", " ", "continue"]:
                return await self._handle_continue_request(second_last_message)
            else:
                # User has made a new request
                return await self._handle_new_request(second_last_message)

        # Does not meet restoration conditions
        logger.debug("Last message is not user message, or second-to-last is not assistant message with tool calls, skipping session state restoration check")
        return skip_llm_call, tool_calls_to_execute, llm_response_message, assistant_message_to_restore

    async def _handle_continue_request(self, second_last_message: AssistantMessage) -> tuple[bool, List[ToolCall], Optional[ChatCompletionMessage], Optional[AssistantMessage]]:
        """
        Handle user request to continue execution

        Args:
            second_last_message: Second-to-last message (assistant message with tool calls)

        Returns:
            Tuple: (whether to skip LLM call, tool calls to execute, LLM response message, assistant message to restore)
        """
        logger.info("Detected user request to continue, attempting to restore last tool call")

        # Check if there are unrecoverable tool calls
        has_unrecoverable_tool_call = False
        has_tool_call_parse_error = False

        for tc in second_last_message.tool_calls:
            if tc.function.name == "call_agent":
                try:
                    # Parse arguments and check if stateful
                    tc_args = json.loads(tc.function.arguments)
                    agent_name_to_call = tc_args.get("agent_name")
                    if agent_name_to_call:
                        agent_to_check = Agent(agent_name_to_call, self.agent_context)
                        if agent_to_check.has_attribute("stateful"):
                            has_unrecoverable_tool_call = True
                            logger.warning(f"Detected unrecoverable call_agent invocation (agent: {agent_name_to_call})")
                            break
                except Exception as e:
                    logger.warning(f"Error checking if call_agent is recoverable: {e!s}")
                    logger.warning(f"Error stack trace: {traceback.format_exc()}")
                    has_unrecoverable_tool_call = True
                    has_tool_call_parse_error = True
                    break

        # Handle recoverable cases
        if not has_unrecoverable_tool_call:
            logger.info("No unrecoverable tool calls detected, preparing to restore session")
            # Remove user's \"continue\" message
            self.chat_history.remove_last_message()
            # Prepare to skip LLM and execute tool calls directly
            return True, [], None, second_last_message
        else:
            # Handle unrecoverable cases
            logger.warning("Detected unrecoverable tool call, will abandon restoration and continue with LLM call")
            # Add interruption prompt
            if not has_tool_call_parse_error:
                message_content = "Current tool call was interrupted by user and is not recoverable, please call tool again."
            else:
                message_content = "Current tool call has parsing error, please check tool parameter format to ensure it is a syntactically correct JSON object, and call tool again."

            # Add interruption messages for all tool calls
            await self._add_interruption_messages(second_last_message.tool_calls, message_content)

            # Remove user's \"continue\" message
            self.chat_history.remove_last_message()
            logger.info("Continue with LLM call")
            return False, [], None, None

    async def _handle_new_request(self, second_last_message: AssistantMessage) -> tuple[bool, List[ToolCall], Optional[ChatCompletionMessage], Optional[AssistantMessage]]:
        """
        Handle case where user makes new request

        Args:
            second_last_message: Second-to-last message (assistant message with tool calls)

        Returns:
            Tuple: (whether to skip LLM call, tool calls to execute, LLM response message, assistant message to restore)
        """
        logger.info("Detected new user request, will interrupt previous tool calls and let LLM handle new request")

        # Add interruption message
        message_content = "Current tool call was interrupted by user, please determine whether to continue previous tool call based on user's new request. If needed, continue with same call parameters, otherwise ignore previous tool call and provide new response based on user's new request"

        # Add interruption messages for all tool calls
        await self._add_interruption_messages(second_last_message.tool_calls, message_content)

        # Continue with LLM call
        return False, [], None, None

    async def _add_interruption_messages(self, tool_calls: List[ToolCall], message_content: str) -> None:
        """
        Add prompt messages for interrupted tool calls

        Args:
            tool_calls: List of tool calls
            message_content: Prompt message content
        """
        for tc in reversed(tool_calls):  # Reverse traversal to ensure correct insertion order
            interrupt_tool_msg = ToolMessage(
                content=message_content,
                tool_call_id=tc.id,
            )
            try:
                self.chat_history.insert_message_before_last(interrupt_tool_msg)
            except ValueError as e:
                logger.error(f"Error inserting tool interruption message (ValueError): {e}")
            except Exception as e:
                logger.error(f"Unknown error occurred while inserting tool interruption message: {e}", exc_info=True)

    async def _restore_session_state(self, assistant_message_to_restore: AssistantMessage) -> tuple[List[ToolCall], Optional[ChatCompletionMessage]]:
        """
        Restore session state from saved assistant message

        Args:
            assistant_message_to_restore: Assistant message to restore

        Returns:
            Tuple: (List of tool calls to execute, Simulated LLM response message)
        """
        logger.info("Skipping LLM call, using tool calls from last session directly")

        # Ensure message and tool calls are valid
        if assistant_message_to_restore and assistant_message_to_restore.tool_calls:
            tool_calls_to_execute = assistant_message_to_restore.tool_calls

            try:
                # Simulate LLM response message for event passing
                openai_tool_calls_for_sim = [
                    ChatCompletionMessageToolCall(
                        id=tc.id, type=tc.type,
                        function={"name": tc.function.name, "arguments": tc.function.arguments}
                    ) for tc in assistant_message_to_restore.tool_calls
                ]

                llm_response_message = ChatCompletionMessage(
                    role="assistant",
                    content=assistant_message_to_restore.content,
                    tool_calls=openai_tool_calls_for_sim
                )

                return tool_calls_to_execute, llm_response_message
            except Exception as e:
                logger.error(f"Error simulating llm_response_message for session restoration: {e}", exc_info=True)
                return [], None
        else:
            logger.error("Attempting to restore session, but assistant_message_to_restore is invalid or has no tool calls.")
            return [], None

    async def _prepare_and_call_llm(self) -> tuple[ChatCompletionMessage, List[ToolCall], Optional[TokenUsage], float]:
        """
        Prepare conversation with LLM, process messages, call LLM and parse response

        Returns:
            Tuple: (
                LLM response message: ChatCompletionMessage object
                Tool call list: Converted ToolCall object list
                Token usage: TokenUsage object
                LLM call duration: milliseconds
            )
        """
        # Use ChatHistory to get formatted message list
        messages_for_llm = self.chat_history.get_messages_for_llm()
        if not messages_for_llm:
            logger.error("Cannot get message list for LLM call (history may be empty or contain only internal messages)")
            self.set_agent_state(AgentState.ERROR)
            raise ValueError("Unable to prepare conversation with LLM.")

        # Record LLM call start time and invoke LLM
        llm_start_time = time.time()
        chat_response = await self._call_llm(messages_for_llm)
        llm_duration_ms = (time.time() - llm_start_time) * 1000

        # Extract token usage data
        token_usage = LLMFactory.token_tracker.extract_chat_history_usage_data(chat_response)

        # Get LLM response message
        llm_response_message = chat_response.choices[0].message

        # Handle case where LLM response content is empty
        if llm_response_message.content is None or llm_response_message.content.strip() == "":
            if llm_response_message.tool_calls:
                logger.debug("LLM response content is empty, but contains tool_calls.")
                # Try to get explanation from tool_call
                for tool_call in llm_response_message.tool_calls:
                    try:
                        arguments = json.loads(tool_call.function.arguments)
                        if "explanation" in arguments:
                            llm_response_message.content = arguments["explanation"]
                            logger.debug(f"Using tool_call explanation as LLM content: {llm_response_message.content}")
                            break
                    except (json.JSONDecodeError, AttributeError, TypeError):
                        continue

                # If still empty, set to empty string
                if llm_response_message.content is None:
                    llm_response_message.content = ""
            else:
                # No tool_calls, content should not be empty
                logger.warning("LLM response message content is empty and has no tool_calls, using default value 'Continue'")
                try:
                    message_dict = llm_response_message.model_dump()
                    formatted_json = json.dumps(message_dict, ensure_ascii=False, indent=2)
                    logger.warning(f"Details:\n{formatted_json}")
                except Exception as e:
                    logger.warning(f"Failed to print LLM response message: {e!s}")
                llm_response_message.content = "Continue"

        # Parse OpenAI's ToolCalls
        openai_tool_calls = self._parse_tool_calls(chat_response)
        logger.debug(f"OpenAI tool_calls from chat_response: {openai_tool_calls}")

        # Normalize and convert to internal ToolCall type
        tool_calls_to_execute = await self._parse_and_convert_tool_calls(openai_tool_calls)

        # Check for multiple tool calls
        if not self.enable_multi_tool_calls and len(tool_calls_to_execute) > 1:
            logger.debug("Detected multiple tool calls, but multi-tool call processing is disabled, keeping only the first one")
            tool_calls_to_execute = [tool_calls_to_execute[0]]

        return llm_response_message, tool_calls_to_execute, token_usage, llm_duration_ms

    async def _parse_and_convert_tool_calls(self, openai_tool_calls: List[ChatCompletionMessageToolCall]) -> List[ToolCall]:
        """
        Convert OpenAI tool calls to internal ToolCall format

        Args:
            openai_tool_calls: List of tool calls returned by OpenAI

        Returns:
            List[ToolCall]: List of tool calls in internal format
        """
        tool_calls_to_execute = []

        for tc_openai in openai_tool_calls:
            # Ensure correct type
            if not isinstance(tc_openai, ChatCompletionMessageToolCall):
                logger.warning(f"Skipping invalid tool_call type: {type(tc_openai)}")
                continue

            try:
                # Parse attributes
                arguments_str = getattr(getattr(tc_openai, 'function', None), 'arguments', None)
                func_name = getattr(getattr(tc_openai, 'function', None), 'name', None)
                tool_id = getattr(tc_openai, 'id', None)
                tool_type = getattr(tc_openai, 'type', 'function')  # Default to function

                # Validate required attributes
                if not all([tool_id, func_name, arguments_str is not None]):
                    logger.warning(f"Skipping incomplete OpenAI ToolCall: {tc_openai}")
                    continue

                # Handle non-string arguments
                if not isinstance(arguments_str, str):
                    logger.warning(f"OpenAI ToolCall arguments is not a string: {arguments_str}, attempting to convert to JSON string")
                    try:
                        arguments_str = json.dumps(arguments_str, ensure_ascii=False)
                    except Exception:
                        logger.error(f"Cannot convert OpenAI ToolCall arguments to JSON string: {arguments_str}, using empty object string")
                        arguments_str = "{}"

                # Create internal FunctionCall
                internal_func = FunctionCall(
                    name=func_name,
                    arguments=arguments_str
                )

                # Create internal ToolCall
                internal_tc = ToolCall(
                    id=tool_id,
                    type=tool_type,
                    function=internal_func
                )

                tool_calls_to_execute.append(internal_tc)
            except AttributeError as ae:
                logger.error(f"Error accessing OpenAI ToolCall attributes: {tc_openai}, error: {ae}", exc_info=True)
            except Exception as e:
                logger.error(f"Error converting OpenAI ToolCall to internal type: {tc_openai}, error: {e}", exc_info=True)

        return tool_calls_to_execute

    async def _add_tool_calls_to_history(self, llm_response_message: ChatCompletionMessage, tool_calls_to_execute: List[ToolCall], token_usage: Optional[TokenUsage], llm_duration_ms: float) -> None:
        """
        Add tool call response to chat history

        Args:
            llm_response_message: LLM response message
            tool_calls_to_execute: List of tool calls
            token_usage: Token usage data
            llm_duration_ms: LLM call duration
        """
        try:
            await self.chat_history.append_assistant_message(
                content=llm_response_message.content,
                tool_calls_data=tool_calls_to_execute,
                duration_ms=llm_duration_ms,
                token_usage=token_usage
            )
        except ValueError as e:
            logger.error(f"Failed to add assistant message with tool calls: {e}")
            self.set_agent_state(AgentState.ERROR)
            raise ValueError(f"Unable to record assistant response ({e})")

    async def _handle_no_tool_calls(self, llm_response_message: ChatCompletionMessage, no_tool_call_count: int, token_usage: Optional[TokenUsage], llm_duration_ms: float) -> tuple[int, bool, Optional[str]]:
        """
        Handle case where LLM response has no tool calls

        Args:
            llm_response_message: LLM response message
            no_tool_call_count: Consecutive no tool call count
            token_usage: Token usage data
            llm_duration_ms: LLM call duration

        Returns:
            Tuple: (Updated no tool call count, Whether to continue execution, Final response)
        """
        no_tool_call_count += 1
        logger.debug(f"Detected no tool calls, checking if loop exit is needed, consecutive count: {no_tool_call_count}")

        # Add LLM response to history
        try:
            await self.chat_history.append_assistant_message(
                content=llm_response_message.content,
                duration_ms=llm_duration_ms,
                token_usage=token_usage
            )
        except ValueError as e:
            logger.error(f"Failed to add assistant response without tool calls: {e}")
            self.set_agent_state(AgentState.ERROR)
            return no_tool_call_count, False, f"Internal error: Unable to record assistant response ({e})"

        # Check if exit condition is met
        if no_tool_call_count >= 3:
            logger.warning("Detected 3 consecutive no tool calls, exiting loop")

            # Add final message to history
            try:
                await self.chat_history.append_assistant_message(
                    content="It looks like our task is coming to an end. Feel free to reach out anytime you have new questions! ✨",
                    show_in_ui=False
                )
            except Exception as e:
                logger.error(f"Error adding no tool call exit message: {e}")

            self.set_agent_state(AgentState.ERROR)
            return no_tool_call_count, False, "Task terminated due to consecutive tool call failures."

        # Not exiting, append internal prompt message
        append_content = self._get_no_tool_call_prompt()

        # Append internal prompt as Assistant message
        try:
            await self.chat_history.append_assistant_message(append_content, show_in_ui=False)
        except ValueError as e:
            logger.error(f"Failed to add internal prompt message: {e}")
            self.set_agent_state(AgentState.ERROR)
            return no_tool_call_count, False, f"Internal error: Unable to add internal prompt ({e})"

        return no_tool_call_count, True, None

    def _get_no_tool_call_prompt(self) -> str:
        """
        Get appropriate no tool call prompt based on Agent attributes

        Returns:
            str: Prompt message content
        """
        if self.has_attribute("main"):
            return "Internal thought (user cannot see): If the task is not complete, I need to continue using tools to solve the problem. If I am sure that I have completed all tasks (e.g., all tasks in todo.md) and delivered the final results to the user in the form of files, then I need to call the finish_task tool to end the task. Next, I will check if my task is complete and decide whether to call the finish_task tool."
        else:
            return "Internal thought (user cannot see): If the task is not complete, I need to continue using tools to solve the problem. If I have confirmed that I have completed the user's requirements or can deliver the final results to the user in the form of files, I need to call the finish_task tool to end the task. Next, I will check if my task is complete and decide whether to call the finish_task tool."

    async def _execute_and_process_tool_calls(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> tuple[bool, Optional[str]]:
        """
        Execute tool calls and process results

        Args:
            tool_calls: List of tool calls
            llm_response_message: LLM response message

        Returns:
            Tuple: (Whether finish_task detected, Final response)
        """
        # Ensure llm_response_message is not empty
        if not llm_response_message:
            logger.error("llm_response_message not set before tool execution!")
            llm_response_message = ChatCompletionMessage(
                role="assistant",
                content="[Internal Error: Missing LLM Response]"
            )

        # Execute tool calls
        tool_call_results = await self._execute_tool_calls(tool_calls, llm_response_message)

        # Process tool call results
        return await self._process_tool_call_results(tool_call_results)

    async def _process_tool_call_results(self, tool_call_results: List[ToolResult]) -> tuple[bool, Optional[str]]:
        """
        Process tool call results

        Args:
            tool_call_results: List of tool call results

        Returns:
            Tuple: (Whether finish_task detected, Final response)
        """
        finish_task_detected = False
        final_response = None

        for result in tool_call_results:
            if not result:  # Skip empty results
                continue

            try:
                # Calculate tool execution duration
                tool_duration_ms = None
                if hasattr(result, 'execution_time') and result.execution_time is not None:
                    try:
                        tool_duration_ms = float(result.execution_time) * 1000
                    except (ValueError, TypeError):
                        logger.warning(f"Unable to convert tool execution time {result.execution_time} to milliseconds.")

                # Append tool call result to chat history
                await self.chat_history.append_tool_message(
                    content=result.content,
                    tool_call_id=result.tool_call_id,
                    system=result.system,
                    duration_ms=tool_duration_ms,
                )

                # Check for special tool calls
                if result.system == "FINISH_TASK":
                    logger.info("Detected FINISH_TASK tool call, exiting loop immediately")
                    final_response = result.content
                    self.set_agent_state(AgentState.FINISHED)
                    finish_task_detected = True
                    break
                elif result.system == "ASK_USER":
                    logger.info("Detected ASK_USER tool call, exiting loop")
                    final_response = result.content
                    self.set_agent_state(AgentState.FINISHED)  # Ask user also counts as completing current round
                    finish_task_detected = True
                    raise asyncio.CancelledError()
                elif result.name == "call_agent" and self.agent_name == "super-magic":
                    logger.info("Detected super-magic agent performed call_agent tool call, will append task confirmation reminder message")
                    await self.chat_history.append_assistant_message(
                        content="Internal thought (user cannot see): I have delegated tasks to other agents, I need to check their work progress and results. If I confirm they have completed their tasks, then I should check if the corresponding tasks in todo.md file are completed. If completed, I need to mark the corresponding task status as done. If not completed, I should think of other methods and continue the task by calling the call_agent tool. Note: Do not let the user know about the existence of other agents.",
                        show_in_ui=False
                    )

            except asyncio.CancelledError:
                # Re-raise CancelledError to ensure loop is terminated
                logger.info("Re-raising CancelledError to terminate loop")
                raise
            except ValueError as ve:
                logger.error(f"Error occurred while processing or appending tool call result: {ve!s}")
            except Exception as e:
                logger.error(f"Unknown error occurred while processing tool result '{getattr(result, 'name', 'unknown')}': {e!r}", exc_info=True)

        # Check if exit is needed
        if finish_task_detected:
            logger.info("Special tool call processed, breaking out of main loop")

        return finish_task_detected, final_response

    async def _handle_agent_loop_exception(self, exception: Exception, current_exception_count: int) -> tuple[bool, Optional[str], int]:
        """
        Handle exceptions in Agent loop

        Args:
            exception: Caught exception
            current_exception_count: Current exception count

        Returns:
            Tuple: (Whether to continue loop, Final response, Updated exception count)
        """
        logger.error(f"Error occurred during Agent loop execution: {exception!r}")
        logger.error(f"Error stack trace: {traceback.format_exc()}")
        self.set_agent_state(AgentState.ERROR)

        # Handle interrupted tool calls
        await self._handle_interrupted_tool_calls(exception)

        # Update exception count
        current_exception_count += 1

        # Calculate retry strategy
        max_retries = 10

        # Use exponential backoff strategy
        wait_time, total_retry_wait_time = self._apply_exponential_backoff(current_exception_count)

        # Determine if retry can continue
        can_continue = current_exception_count < max_retries and total_retry_wait_time < 900

        # Prepare error content
        if can_continue:
            error_content = "Encountered error during task execution, usually due to syntax error, type error or missing parameters in tool arguments, will retry."
            logger.info(f"Will wait {wait_time:.1f} seconds before retry attempt {current_exception_count} (total wait time: {total_retry_wait_time:.1f} seconds)")
        else:
            if current_exception_count >= max_retries:
                error_content = f"Encountered error during task execution, I have silently attempted {current_exception_count} fixes, reaching maximum retry limit of {max_retries} times. I should have completed part of the task, you may need to check my current progress and help me continue the task."
            else:  # Exceeded total wait time
                error_content = "Encountered error during task execution, total wait time has reached the limit, will not continue retrying. I should have completed part of the task, you may need to check my current progress and help me continue the task."

        # Check for special error cases
        result = None  # Ensure result variable exists in local scope
        if 'result' in locals() and result and hasattr(result, 'ok') and not result.ok:
            error_content = result.content  # Use error message from tool
        elif isinstance(exception, json.JSONDecodeError):
            error_content = f"Tool parameter parsing failed, please check JSON format: {exception}"
        elif "Connection" in str(exception):
            error_content = "I encountered a network connection error, possibly because I output excessively large content at once. I will try to output in segments. If it still fails, I will try a different approach to continue the task."
        else:
            error_content = f"Encountered error during task execution: {type(exception).__name__}: {exception!s}"

        # Add error message to history
        try:
            await self.chat_history.append_assistant_message(error_content, show_in_ui=False)
        except Exception as append_err:
            logger.error(f"Failed to add final error message to history: {append_err}")

        # If can continue, execute wait
        if can_continue:
            logger.warning(f"Although encountered error, maximum retry limit not reached yet, waiting {wait_time:.1f} seconds before next loop")
            time.sleep(wait_time)  # Ensure actual wait is executed
            return True, None, current_exception_count
        else:
            logger.warning(f"Reached maximum retry limit ({max_retries}) or maximum wait time (15 minutes), exiting loop")
            return False, error_content, current_exception_count

    async def _handle_interrupted_tool_calls(self, exception: Exception) -> None:
        """
        Handle tool calls interrupted by exception

        Args:
            exception: Caught exception
        """
        # If last message is assistant message with tool calls, add error info for each call
        last_message = self.chat_history.get_last_message()
        if isinstance(last_message, AssistantMessage) and last_message.tool_calls:
            general_error_message = f"Tool call was interrupted due to unexpected error during processing ({exception!s}), please recheck if tool call parameters are correct."
            for tool_call in last_message.tool_calls:
                try:
                    await self.chat_history.append_tool_message(
                        content=general_error_message,
                        tool_call_id=tool_call.id,
                    )
                    logger.info(f"Added error message for interrupted tool call {tool_call.id} ({tool_call.function.name}).")
                except Exception as insert_err:
                    logger.error(f"Failed to insert error message for tool call {tool_call.id}: {insert_err!s}")

    def _apply_exponential_backoff(self, retry_count: int) -> tuple[float, float]:
        """
        Apply exponential backoff strategy to calculate retry wait time

        Args:
            retry_count: Number of retries

        Returns:
            Tuple: (Current wait time, Total wait time)
        """
        # Base wait time is 2 seconds, doubles after each failure, max wait time is 5 minutes
        base_wait_time = 2
        max_wait_time = 300

        # Calculate current wait time
        wait_time = min(base_wait_time * (2 ** (retry_count - 1)), max_wait_time)

        # Calculate total wait time
        if not hasattr(self, '_total_retry_wait_time'):
            self._total_retry_wait_time = 0

        self._total_retry_wait_time += wait_time

        return wait_time, self._total_retry_wait_time

    async def _finalize_agent_loop(self, final_response: Optional[str], last_llm_message: Optional[ChatCompletionMessage]) -> Optional[str]:
        """
        Clean up and process results after completing agent loop

        Args:
            final_response: Final response content
            last_llm_message: Last LLM response message

        Returns:
            str: Final response
        """
        # Handle case where loop ends normally but final response is not set
        if not final_response and last_llm_message:
            # Get the last added message
            last_added_msg = self.chat_history.get_last_message()

            # Check if last_added_msg contains expected content
            if last_added_msg and isinstance(last_added_msg, AssistantMessage) and last_added_msg.content == last_llm_message.content:
                final_response = last_llm_message.content
            else:
                # If last message is not expected content
                if last_llm_message.content:
                    final_response = last_llm_message.content
                    # Ensure final response is recorded (if not added in loop)
                    if not (last_added_msg and isinstance(last_added_msg, AssistantMessage) and last_added_msg.content == final_response):
                        await self.chat_history.append_assistant_message(final_response)
                else:
                    # If last LLM response content is empty (should not happen, unless only tool_calls)
                    logger.info("Loop ended, but last LLM response content is empty.")
                    final_response = None  # Explicitly set to None

        # Record final response
        if final_response:
            logger.info(f"Final response: {final_response}")
        else:
            logger.info("Final response is empty")

        # Update agent state - use is_agent_running instead of direct comparison
        if self.is_agent_running():
            self.set_agent_state(AgentState.FINISHED)

        # Log token usage - only print in non-streaming mode
        if not self.stream_mode:
            self.print_token_usage()

        return final_response

    async def _handle_agent_loop_stream(self) -> None:
        """Handle agent loop streaming"""
        # Currently streaming is not implemented, return None
        return None

    async def _call_llm(self, messages: List[Dict[str, Any]]) -> ChatCompletion:
        """Call LLM"""

        # Convert tool instances to format needed by LLM
        tools_list = []
        if self.tools:
            for tool_name in self.tools.keys():
                tool_instance: BaseTool = tool_factory.get_tool_instance(tool_name)
                # Ensure tool instance is valid
                if tool_instance:
                    tool_param = tool_instance.to_param()
                    tools_list.append(tool_param)
                else:
                    logger.warning(f"Unable to get tool instance: {tool_name}")

        # Save tools list to .tools.json file with same name as chat history
        if self.chat_history and tools_list:
            self.chat_history.save_tools_list(tools_list)

        # Create ToolContext instance
        tool_context = ToolContext(metadata=self.agent_context.get_metadata())
        # Register AgentContext as extension
        tool_context.register_extension("agent_context", self.agent_context)

        await self.agent_context.dispatch_event(
            EventType.BEFORE_LLM_REQUEST,
            BeforeLlmRequestEventData(
                model_name=self.llm_name,
                chat_history=messages, # Pass formatted dict list
                tools=tools_list,
                tool_context=tool_context
            )
        )

        # ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼ Call LLM ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼ #
        start_time = time.time()
        # logger.debug(f"Messages sent to LLM: {messages}")

        # Use LLMFactory.call_with_tool_support method to handle tool calls uniformly
        llm_response: ChatCompletion = await LLMFactory.call_with_tool_support(
            self.llm_id,
            messages, # Pass dict list
            tools=tools_list if tools_list else None,
            stop=self.agent_context.stop_sequences if hasattr(self.agent_context, 'stop_sequences') else None,
            agent_context=self.agent_context
        )

        llm_response_message = llm_response.choices[0].message
        request_time = time.time() - start_time
        # ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲ End LLM Call ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲ #

        # --- Handle case where LLM response content is empty ---
        # ChatHistory normalization should have handled most cases, this is final safeguard
        # Especially handle case where API returns content as None but has tool_calls
        if llm_response_message.content is None or llm_response_message.content.strip() == "":
            if llm_response_message.tool_calls:
                 # If tool_calls exist, content being None is legal, no modification needed
                 # But for logging and subsequent processing, could give internal marker or default value
                 logger.debug("LLM response content is empty, but contains tool_calls.")
                 # Keep llm_response_message.content as None or empty string
                 # If subsequent logic needs non-empty content, handle it there
                 # Here we try to get explanation from tool_call (if it exists)
                 for tool_call in llm_response_message.tool_calls:
                      try:
                           arguments = json.loads(tool_call.function.arguments)
                           if "explanation" in arguments:
                                llm_response_message.content = arguments["explanation"]
                                # Remove explanation from arguments (though modifying response object may not affect history)
                                # del arguments["explanation"]
                                # tool_call.function.arguments = json.dumps(arguments, ensure_ascii=False)
                                logger.debug(f"Using tool_call explanation as LLM content: {llm_response_message.content}")
                                break # Use first one found
                      except (json.JSONDecodeError, AttributeError, TypeError):
                           continue # Ignore parse errors or invalid structure
                 # If still empty, keep original (None or empty)
                 if llm_response_message.content is None:
                     llm_response_message.content = "" # Set to empty string instead of None, simplify subsequent processing

            else:
                 # No tool_calls, content should not be empty
                 logger.warning("LLM response message content is empty and has no tool_calls, using default value 'Continue'")
                 # Use pretty JSON format to print problematic message
                 try:
                     message_dict = llm_response_message.model_dump() # pydantic v2
                     formatted_json = json.dumps(message_dict, ensure_ascii=False, indent=2)
                     logger.warning(f"Details:\n{formatted_json}")
                 except Exception as e:
                     logger.warning(f"Failed to print LLM response message: {e!s}")
                 llm_response_message.content = "Continue" # Force set to Continue


        logger.info(f"LLM response: role={llm_response_message.role}, content='{llm_response_message.content[:100]}...', tool_calls={llm_response_message.tool_calls is not None}")

        await self.agent_context.dispatch_event(
            EventType.AFTER_LLM_REQUEST,
            AfterLlmResponseEventData(
                model_name=self.llm_name,
                request_time=request_time,
                success=True,
                tool_context=tool_context,
                llm_response_message=llm_response_message # Pass original response message
            )
        )

        return llm_response

    async def _execute_tool_calls(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """Execute tool calls with support for parallel execution"""
        if not self.enable_parallel_tool_calls or len(tool_calls) <= 1:
            # Non-parallel mode or single tool call, use original logic
            logger.debug("Using sequential execution mode for tool calls")
            return await self._execute_tool_calls_sequential(tool_calls, llm_response_message)
        else:
            # Parallel mode
            logger.info(f"Using parallel execution mode for {len(tool_calls)} tool calls")
            return await self._execute_tool_calls_parallel(tool_calls, llm_response_message)

    async def _execute_tool_calls_sequential(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """Execute tool calls using sequential mode (original logic)"""
        results = []
        for tool_call in tool_calls:
            result = None
            tool_name = "[unknown]"
            try:
                tool_name = tool_call.function.name
                tool_arguments_str = tool_call.function.arguments

                # Try to parse parameter string to dict for tool execution and event passing
                try:
                    tool_arguments_dict = json.loads(tool_arguments_str)
                    if not isinstance(tool_arguments_dict, dict):
                        logger.warning(f"Tool '{tool_name}' parameters are not dict after parsing, will pass empty dict.")
                        logger.warning(f"Original parameter data: {tool_arguments_str}")
                        logger.warning(f"Parsed result: {tool_arguments_dict}")
                        tool_arguments_for_exec = {}
                    else:
                        tool_arguments_for_exec = tool_arguments_dict
                except json.JSONDecodeError as e:
                    logger.warning(f"Tool '{tool_name}' parameters cannot be parsed as JSON, will pass empty dict.")
                    logger.warning(f"Original parameter data: {tool_arguments_str}")
                    logger.warning(f"Full error message: {e}")
                    tool_arguments_for_exec = {}

                try:
                    # Create tool context, ensure passing metadata from agent_context
                    tool_context = ToolContext(
                        tool_call_id=tool_call.id,
                        tool_name=tool_name,
                        arguments=tool_arguments_for_exec,
                        metadata=self.agent_context.get_metadata()
                    )
                    # Add AgentContext extension
                    tool_context.register_extension("agent_context", self.agent_context)
                    # Add EventContext extension
                    from app.core.entity.event.event_context import EventContext
                    tool_context.register_extension("event_context", EventContext())

                    logger.info(f"Starting tool execution: {tool_name}, parameters: {tool_arguments_for_exec}")

                    # --- Trigger before_tool_call event ---
                    tool_instance = tool_factory.get_tool_instance(tool_name)
                    # Need to convert internal ToolCall back to OpenAI type for event
                    openai_tool_call_for_event = ChatCompletionMessageToolCall(
                         id=tool_call.id, type=tool_call.type,
                         function={"name": tool_name, "arguments": tool_arguments_str}
                    )
                    await self.agent_context.dispatch_event(
                        EventType.BEFORE_TOOL_CALL,
                        BeforeToolCallEventData(
                            tool_call=openai_tool_call_for_event,
                            tool_context=tool_context,
                            tool_name=tool_name,
                            arguments=tool_arguments_for_exec,
                            tool_instance=tool_instance,
                            llm_response_message=llm_response_message
                        )
                    )

                    # --- Execute tool ---
                    result = await tool_executor.execute_tool_call(
                        tool_context=tool_context,
                        arguments=tool_arguments_for_exec
                    )
                    # Ensure result.tool_call_id is set
                    if not result.tool_call_id:
                         result.tool_call_id = tool_call.id

                    # --- Trigger after_tool_call event ---
                    await self.agent_context.dispatch_event(
                        EventType.AFTER_TOOL_CALL,
                        AfterToolCallEventData(
                            tool_call=openai_tool_call_for_event,
                            tool_context=tool_context,
                            tool_name=tool_name,
                            arguments=tool_arguments_for_exec,
                            result=result,
                            execution_time=result.execution_time,
                            tool_instance=tool_instance
                        )
                    )
                except Exception as e:
                    # Print error stack trace
                    print(traceback.format_exc())
                    logger.error(f"Error executing tool '{tool_name}': {e}", exc_info=True)
                    # Create failed ToolResult, ensure tool_call_id is set
                    result = ToolResult(
                        content=f"Failed to execute tool '{tool_name}': {e!s}",
                        tool_call_id=tool_call.id,
                        ok=False
                    )

                results.append(result)
            except AttributeError as attr_err:
                 logger.error(f"Error accessing attributes when processing tool call object: {tool_call}, error: {attr_err!r}", exc_info=True)
                 # If error occurs early in loop, try to create ToolResult with error info
                 tool_call_id_fallback = getattr(tool_call, 'id', None)
                 tool_name_fallback = getattr(getattr(tool_call, 'function', None), 'name', '[unknown_early_error]')
                 if tool_call_id_fallback:
                     results.append(ToolResult(
                         content=f"Failed to process tool call (AttributeError): {attr_err!s}",
                         tool_call_id=tool_call_id_fallback,
                         name=tool_name_fallback,
                         ok=False
                     ))
                 else:
                     # If no id available, cannot create ToolResult, only log
                     logger.error(f"Cannot create tool failure result: missing tool_call_id. Error: {attr_err!s}")

            except Exception as outer_err:
                # Capture other exceptions during tool call object handling (such as attribute access) or result addition
                logger.error(f"Serious error while processing tool call object or adding result: {tool_call}, error: {outer_err}", exc_info=True)
                tool_call_id_fallback = getattr(tool_call, 'id', None)
                tool_name_fallback = getattr(getattr(tool_call, 'function', None), 'name', '[unknown_outer_error]')
                if tool_call_id_fallback:
                    results.append(ToolResult(
                        content=f"Failed to process tool call or result: {outer_err!s}",
                        tool_call_id=tool_call_id_fallback,
                        name=tool_name_fallback,
                        ok=False
                    ))
                else:
                    logger.error(f"Cannot create tool failure result: missing tool_call_id. Error: {outer_err!s}")

        return results

    async def _execute_tool_calls_parallel(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """Execute tool calls using parallel mode"""
        # Create a list containing all tool call information for parallel processing
        tool_tasks = []

        logger.info(f"Preparing to execute {len(tool_calls)} tool calls in parallel, timeout setting: {self.parallel_tool_calls_timeout} seconds")

        # 1. Preprocess all tool calls to generate execution tasks
        for tool_call in tool_calls:
            try:
                tool_name = tool_call.function.name
                tool_arguments_str = tool_call.function.arguments
                tool_call_id = tool_call.id

                # Try to parse parameter string to dict
                try:
                    tool_arguments_dict = json.loads(tool_arguments_str)
                    if not isinstance(tool_arguments_dict, dict):
                        logger.warning(f"Parallel tool call: '{tool_name}' parameters are not dict after parsing, will pass empty dict")
                        logger.warning(f"Original parameter data: {tool_arguments_str}")
                        logger.warning(f"Parsed result: {tool_arguments_dict}")
                        tool_arguments_for_exec = {}
                    else:
                        tool_arguments_for_exec = tool_arguments_dict
                except json.JSONDecodeError as e:
                    logger.warning(f"Parallel tool call: '{tool_name}' parameters cannot be parsed as JSON, will pass empty dict")
                    logger.warning(f"Original parameter data: {tool_arguments_str}")
                    logger.warning(f"Full error message: {e}")
                    tool_arguments_for_exec = {}

                # Create tool context
                tool_context = ToolContext(
                    tool_call_id=tool_call_id,
                    tool_name=tool_name,
                    arguments=tool_arguments_for_exec,
                    metadata=self.agent_context.get_metadata()
                )
                # Register AgentContext as extension
                tool_context.register_extension("agent_context", self.agent_context)

                # Add EventContext extension
                from app.core.entity.event.event_context import EventContext
                tool_context.register_extension("event_context", EventContext())

                # Get tool instance
                tool_instance = tool_factory.get_tool_instance(tool_name)

                # Need to convert internal ToolCall back to OpenAI type for event
                openai_tool_call = ChatCompletionMessageToolCall(
                    id=tool_call_id,
                    type=tool_call.type,
                    function={"name": tool_name, "arguments": tool_arguments_str}
                )

                # Add tool call information to task list
                tool_tasks.append({
                    "tool_call": tool_call,
                    "openai_tool_call": openai_tool_call,
                    "tool_context": tool_context,
                    "tool_name": tool_name,
                    "arguments": tool_arguments_for_exec,
                    "tool_instance": tool_instance
                })

            except Exception as e:
                logger.error(f"Error preprocessing tool call: {e}", exc_info=True)
                # For failed preprocessing, add error result
                try:
                    tool_call_id = getattr(tool_call, 'id', None)
                    tool_name = getattr(getattr(tool_call, 'function', None), 'name', '[unknown]')
                    if tool_call_id:
                        # Create error result
                        error_result = ToolResult(
                            content=f"Preprocessing tool call '{tool_name}' failed: {e!s}",
                            tool_call_id=tool_call_id,
                            name=tool_name,
                            ok=False
                        )
                        # Handle this error result separately
                        error_task = {
                            "error_result": error_result,
                            "is_error": True
                        }
                        tool_tasks.append(error_task)
                except Exception as err:
                    logger.error(f"Error creating tool call error result: {err}", exc_info=True)

        # If no valid tool call tasks, return directly
        if not tool_tasks:
            logger.warning("No valid tool call tasks to execute")
            return []

        # 2. Define async function for single tool execution
        async def execute_single_tool(task_info):
            # Check if it's a preprocessing error result
            if task_info.get("is_error", False):
                return task_info.get("error_result")

            tool_call = task_info["tool_call"]
            openai_tool_call = task_info["openai_tool_call"]
            tool_context = task_info["tool_context"]
            tool_name = task_info["tool_name"]
            arguments = task_info["arguments"]
            tool_instance = task_info["tool_instance"]

            start_time = time.time()

            try:
                # Dispatch before tool call event
                await self.agent_context.dispatch_event(
                    EventType.BEFORE_TOOL_CALL,
                    BeforeToolCallEventData(
                        tool_call=openai_tool_call,
                        tool_context=tool_context,
                        tool_name=tool_name,
                        arguments=arguments,
                        tool_instance=tool_instance,
                        llm_response_message=llm_response_message
                    )
                )

                # Execute tool call
                logger.info(f"Executing tool in parallel: {tool_name}, arguments: {arguments}")
                result = await tool_executor.execute_tool_call(
                    tool_context=tool_context,
                    arguments=arguments
                )

                # Ensure result contains tool_call_id
                if not result.tool_call_id:
                    result.tool_call_id = tool_call.id

                # Calculate execution time
                execution_time = time.time() - start_time
                result.execution_time = execution_time

                # Dispatch after tool call event
                await self.agent_context.dispatch_event(
                    EventType.AFTER_TOOL_CALL,
                    AfterToolCallEventData(
                        tool_call=openai_tool_call,
                        tool_context=tool_context,
                        tool_name=tool_name,
                        arguments=arguments,
                        result=result,
                        execution_time=execution_time,
                        tool_instance=tool_instance
                    )
                )

                return result

            except Exception as e:
                logger.error(f"Error executing tool '{tool_name}' in parallel: {e}", exc_info=True)
                # Calculate execution time (even on error)
                execution_time = time.time() - start_time
                # Create failed ToolResult
                error_result = ToolResult(
                    content=f"Failed to execute tool '{tool_name}': {e!s}",
                    tool_call_id=tool_call.id,
                    name=tool_name,
                    ok=False,
                    execution_time=execution_time
                )
                return error_result

        # 3. Use Parallel class to execute all tool calls in parallel
        parallel = Parallel(timeout=self.parallel_tool_calls_timeout)

        # Add task for each tool call
        for task_info in tool_tasks:
            parallel.add(execute_single_tool, task_info)

        # Execute all tool calls in parallel and collect results
        try:
            results = await parallel.run()
            logger.info(f"Completed parallel execution of {len(results)} tool calls")
            return results
        except asyncio.TimeoutError as e:
            logger.error(f"Parallel tool call execution timeout: {e}")
            # Timeout handling: create timeout error result for each tool call
            timeout_results = []
            for task_info in tool_tasks:
                if task_info.get("is_error", False):
                    # Preserve preprocessing errors
                    timeout_results.append(task_info.get("error_result"))
                else:
                    tool_call = task_info["tool_call"]
                    tool_name = task_info["tool_name"]
                    timeout_result = ToolResult(
                        content=f"Tool '{tool_name}' execution timeout, exceeded {self.parallel_tool_calls_timeout} second limit",
                        tool_call_id=tool_call.id,
                        name=tool_name,
                        ok=False
                    )
                    timeout_results.append(timeout_result)
            return timeout_results
