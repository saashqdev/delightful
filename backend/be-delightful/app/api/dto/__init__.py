"""
Data Transfer Object (DTO) package, used to define WebSocket message formats
"""

from .base import (
    WebSocketMessage,
)

__all__ = [
    # WebSocket message validation model
    "WebSocketMessage",
    # Agent related messages
    "StartDTO",
    "FinishDTO",
    "ErrorDTO",
    "ThinkingDTO",
    # Tool related messages
    "ToolUsedDTO",
    "FileToolDTO",
    "SearchToolDTO",
    "BrowserToolDTO",
    "PythonToolDTO",
    "TerminateToolDTO",
]
