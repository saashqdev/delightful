from typing import Any, Dict, List, Optional

from openai.types.chat import ChatCompletionMessage, ChatCompletionMessageToolCall

from agentlang.context.tool_context import ToolContext
from agentlang.event.common import BaseEventData
from agentlang.interface.context import AgentContextInterface
from agentlang.tools.tool_result import ToolResult


class BeforeInitEventData(BaseEventData):
    """Event data structure before initialization"""

    tool_context: ToolContext


class AfterInitEventData(BaseEventData):
    """Event data structure after initialization"""

    tool_context: ToolContext
    agent_context: Optional[AgentContextInterface] = None
    success: bool
    error: Optional[str] = None


class BeforeLlmRequestEventData(BaseEventData):
    """Event data structure before requesting the LLM"""

    model_name: str
    chat_history: List[Dict[str, object]]
    tools: Optional[List[Dict[str, object]]] = None
    tool_context: ToolContext


class AfterLlmResponseEventData(BaseEventData):
    """Event data structure after receiving LLM response"""

    model_name: str
    request_time: float  # Request duration (seconds)
    success: bool
    error: Optional[str] = None
    tool_context: ToolContext
    llm_response_message: ChatCompletionMessage  # LLM response message content
    show_in_ui: bool = True  # Whether to display in UI


class BeforeToolCallEventData(BaseEventData):
    """Event data structure before tool call"""

    tool_call: ChatCompletionMessageToolCall
    tool_context: ToolContext
    tool_name: str
    arguments: Dict[str, object]
    tool_instance: Any  # Tool instance, any executable tool type
    llm_response_message: ChatCompletionMessage  # LLM response message content


class AfterToolCallEventData(BaseEventData):
    """Event data structure after tool call"""

    tool_call: ChatCompletionMessageToolCall
    tool_context: ToolContext
    tool_name: str
    arguments: Dict[str, object]
    result: ToolResult
    execution_time: float  # Execution duration (seconds)
    tool_instance: Any  # Tool instance, any executable tool type


class AgentSuspendedEventData(BaseEventData):
    """Agent suspension event data structure"""

    agent_context: AgentContextInterface


class BeforeMainAgentRunEventData(BaseEventData):
    """Event data structure before main agent run"""

    agent_context: AgentContextInterface
    agent_name: str
    query: str


class AfterMainAgentRunEventData(BaseEventData):
    """Event data structure after main agent run"""

    agent_context: AgentContextInterface
    agent_name: str
    agent_state: str
    query: str


class ErrorEventData(BaseEventData):
    """Error event data structure"""
    exception: Exception
    agent_context: AgentContextInterface
    error_message: str
