"""
BaseAgent abstract class definition

Defines basic Agent interface and abstract methods, all Agent implementations must inherit this class
"""

import random
import string
from abc import ABC, abstractmethod
from typing import Any, Dict, List, Optional

from openai.types.chat import ChatCompletion, ChatCompletionMessage, ChatCompletionMessageToolCall

from agentlang.agent.loader import AgentLoader
from agentlang.agent.state import AgentState
from agentlang.chat_history import ToolCall
from agentlang.llms.factory import LLMFactory
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.context.agent_context import AgentContext
from app.tools.core.tool_factory import tool_factory

logger = get_logger(__name__)


class BaseAgent(ABC):
    """
    Agent base class, defines interface that all Agent implementations must follow.

    BaseAgent handles:
    1. Basic Agent attribute management
    2. Lifecycle management
    3. LLM interaction
    4. Tool call processing
    """

    # Global active Agent set
    ACTIVE_AGENTS = set()

    # Agent basic attributes
    agent_name = None
    agent_context = None
    stream_mode = False
    attributes = {}

    tools = []
    llm_client = None
    system_prompt = None
    agent_state = AgentState.IDLE
    chat_history = None
    max_iterations = 100
    _agent_loader: AgentLoader = None

    @abstractmethod
    def __init__(self, agent_name: str, agent_context: Optional[AgentContext] = None, agent_id: Optional[str] = None) -> None:
        """Initialize Agent instance.

        Args:
            agent_name: Agent name
            agent_context: Agent context, if None will create a new instance
            agent_id: Agent unique identifier, if None will be auto-generated
        """
        self.agent_name = agent_name
        self.agent_context = agent_context or AgentContext()
        self.agent_context.set_agent_name(agent_name)
        self.id = agent_id or self._generate_agent_id()

    def _generate_agent_id(self) -> str:
        """
        Generate unique Agent ID.

        Returns:
            str: Generated Agent ID
        """
        first_char = random.choice(string.ascii_letters)
        remaining_chars = ''.join(random.choices(string.ascii_letters + string.digits, k=5))
        new_id = first_char + remaining_chars
        logger.info(f"Auto-generated new Agent ID: {new_id}")
        return new_id

    def set_stream_mode(self, stream_mode: bool) -> None:
        """
        Set whether to use stream mode.

        Args:
            stream_mode: Whether to enable stream mode
        """
        self.stream_mode = stream_mode
        if self.agent_context:
            self.agent_context.set_stream_mode(stream_mode)

    def has_attribute(self, attribute_name: str) -> bool:
        """
        Check if an attribute exists.

        Args:
            attribute_name: Attribute name

        Returns:
            bool: Whether the attribute exists
        """
        return attribute_name in self.attributes

    @abstractmethod
    def _initialize_agent(self) -> None:
        """Initialize Agent configuration, tools and LLM.

        This method should be called in constructor, responsible for:
        1. Loading Agent configuration file
        2. Initializing LLM client
        3. Setting up tool collection
        4. Preparing system prompt
        """
        pass

    @abstractmethod
    def _prepare_prompt_variables(self) -> Dict[str, str]:
        """
        Prepare dictionary for replacing variables in prompt.

        Returns:
            Dict[str, str]: Dictionary containing variable names and corresponding values
        """
        pass

    @abstractmethod
    async def run(self, query: str):
        """
        Run Agent to process query.

        Args:
            query: User query/instruction
        """
        pass

    @abstractmethod
    async def run_main_agent(self, query: str):
        """
        Run as main Agent, typically includes additional event handling and error management.

        Args:
            query: User query/instruction
        """
        pass

    @abstractmethod
    async def _handle_agent_loop(self) -> None:
        """
        Handle Agent's main loop logic, including:
        1. LLM calls
        2. Parse LLM responses
        3. Execute tool calls
        4. Process tool results
        5. Add history records
        6. Check loop termination conditions
        """
        pass

    @abstractmethod
    async def _handle_agent_loop_stream(self) -> None:
        """Handle Agent loop in streaming mode."""
        pass

    @abstractmethod
    async def _call_llm(self, messages: List[Dict[str, Any]]) -> ChatCompletion:
        """
        Call LLM to get response.

        Args:
            messages: Message history list

        Returns:
            ChatCompletion: LLM response
        """
        pass

    def _parse_tool_calls(self, chat_response: ChatCompletion) -> List[ChatCompletionMessageToolCall]:
        """
        Parse tool calls from LLM response.

        Args:
            chat_response: LLM response

        Returns:
            List[ChatCompletionMessageToolCall]: Tool call list
        """
        tools = []
        for choice in chat_response.choices:
            if choice.message.tool_calls:
                tools.extend(choice.message.tool_calls)
        return tools

    @abstractmethod
    async def _execute_tool_calls(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """
        Execute tool calls, may be parallel or sequential.

        Args:
            tool_calls: Tool call list
            llm_response_message: LLM response message

        Returns:
            List[ToolResult]: Tool call result list
        """
        pass

    @abstractmethod
    async def _execute_tool_calls_sequential(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """
        Execute tool calls sequentially.

        Args:
            tool_calls: Tool call list
            llm_response_message: LLM response message

        Returns:
            List[ToolResult]: Tool call result list
        """
        pass

    @abstractmethod
    async def _execute_tool_calls_parallel(self, tool_calls: List[ToolCall], llm_response_message: ChatCompletionMessage) -> List[ToolResult]:
        """
        Execute tool calls in parallel.

        Args:
            tool_calls: Tool call list
            llm_response_message: LLM response message

        Returns:
            List[ToolResult]: Tool call result list
        """
        pass

    def set_parallel_tool_calls(self, enable: bool, timeout: Optional[float] = None) -> None:
        """
        Set whether to enable parallel tool calls.

        Args:
            enable: Whether to enable parallel tool calls
            timeout: Parallel execution timeout (seconds), None means no timeout
        """
        self.enable_parallel_tool_calls = enable
        self.parallel_tool_calls_timeout = timeout
        logger.info(f"Set parallel tool calls: enabled={enable}, timeout={timeout}s")

    def register_tools(self, tools_definition: Dict[str, Dict]) -> None:
        """
        Register tools.

        Args:
            tools_definition: Tool definitions
        """
        # Register tools
        for tool_name, tool_config in tools_definition.items():
            # Note: New tool system uses @tool decorator to automatically register tools
            # This is just for backward compatibility, no actual registration operation
            logger.debug(f"Tool {tool_name} already registered via decorator, no manual registration needed")

    def print_token_usage(self) -> None:
        """
        Print token usage report.

        Called at end of session to print token usage statistics for entire session.
        """
        try:
            # 获取格式化报告
            formatted_report = LLMFactory.token_tracker.get_formatted_report()
            logger.info(f"===== Token 使用报告 ({self.agent_name}) =====")
            logger.info(formatted_report)
        except Exception as e:
            logger.error(f"打印Token使用报告时出错: {e!s}")

    def load_agent_config(self, agent_name: str) -> None:
        """
        从 .agent 文件加载 agent 配置并设置相关属性

        从.agent文件中加载模型定义、工具定义、属性定义和提示词，并设置到实例属性中
        """
        logger.info(f"加载 agent 配置: {agent_name}")
        model_id, tools_definition, attributes_definition, prompt = self._agent_loader.load_agent(agent_name)
        self.system_prompt = prompt

        # 检查工具是否存在且可用，若不存在或不可用则忽略并抛出 warning
        valid_tools = {}
        for tool_name in tools_definition.keys():
            try:
                # 获取工具实例（不是工具信息）
                tool_instance = tool_factory.get_tool_instance(tool_name)

                # 检查工具是否可用
                if not tool_instance.is_available():
                    logger.warning(f"工具 '{tool_name}' 不可用（环境变量未配置或依赖缺失），将在 Agent 定义中被忽略")
                    continue

                valid_tools[tool_name] = tools_definition[tool_name]
            except ValueError as e:
                # 工具不存在
                logger.warning(f"工具 '{tool_name}' 不存在，将在 Agent 定义中被忽略: {e}")
                continue
            except Exception as e:
                # 其他错误
                logger.warning(f"加载工具 '{tool_name}' 时发生错误，将在 Agent 定义中被忽略: {e}")
                continue

        self.tools = valid_tools
        self.attributes = attributes_definition
        self.llm_id = model_id
        logger.info(f"加载完成: 模型={model_id}, 工具数量={len(valid_tools)}")

    def set_agent_state(self, state: AgentState) -> None:
        """
        设置 Agent 状态

        Args:
            state: 新的 Agent 状态
        """
        logger.info(f"Agent '{self.agent_name}' 状态变更: {self.agent_state.value} -> {state.value}")
        self.agent_state = state

    def is_agent_running(self) -> bool:
        """
        检查 Agent 是否正在运行

        Returns:
            bool: 如果 Agent 正在运行则返回 True，否则返回 False
        """
        return self.agent_state == AgentState.RUNNING

    def is_agent_finished(self) -> bool:
        """
        检查 Agent 是否已完成

        Returns:
            bool: 如果 Agent 已完成则返回 True，否则返回 False
        """
        return self.agent_state == AgentState.FINISHED

    def is_agent_error(self) -> bool:
        """
        检查 Agent 是否发生错误

        Returns:
            bool: 如果 Agent 发生错误则返回 True，否则返回 False
        """
        return self.agent_state == AgentState.ERROR

    def is_agent_idle(self) -> bool:
        """
        检查 Agent 是否处于空闲状态

        Returns:
            bool: 如果 Agent 处于空闲状态则返回 True，否则返回 False
        """
        return self.agent_state == AgentState.IDLE
