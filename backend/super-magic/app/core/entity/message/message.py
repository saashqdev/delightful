"""
Define types used in the message passing system
"""

from enum import Enum


class MessageType(str, Enum):
    """Task message type enumeration"""
    CHAT = "chat"  # Chat message
    TASK_UPDATE = "task_update"  # Task update
    THINKING = "thinking"  # Thinking process
    INIT = "init"  # Initialization
    TOOL_CALL = "tool_call"  # Tool call
