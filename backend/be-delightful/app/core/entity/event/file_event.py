"""
File event related data class definitions

Used to define event data structures related to file operations
"""
from agentlang.context.tool_context import ToolContext
from agentlang.event.common import BaseEventData
from agentlang.event.event import EventType


class FileEventData(BaseEventData):
    """File event data class"""

    filepath: str  # File path
    event_type: EventType  # Event type
    tool_context: ToolContext  # Tool context 
    is_screenshot: bool = False  # Whether it's a screenshot
