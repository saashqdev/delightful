

from agentlang.event.common import BaseEventData
from app.core.context.agent_context import AgentContext
from app.core.entity.message.client_message import ChatClientMessage


class AfterClientChatEventData(BaseEventData):
    """Event data structure after client chat"""

    agent_context: AgentContext
    client_message: ChatClientMessage


class BeforeSafetyCheckEventData(BaseEventData):
    """Event data structure before safety check"""

    agent_context: AgentContext
    query: str  # Query content to be checked


class AfterSafetyCheckEventData(BaseEventData):
    """Event data structure after safety check"""

    agent_context: AgentContext
    query: str  # Checked query content
    is_safe: bool  # Whether safe
