from enum import Enum
from typing import Generic, TypeVar

from agentlang.event.common import BaseEventData


class EventType(str, Enum):
    """Enumeration of event types."""

    BEFORE_INIT = "before_init"
    AFTER_INIT = "after_init"
    BEFORE_SAFETY_CHECK = "before_safety_check"
    AFTER_SAFETY_CHECK = "after_safety_check"
    AFTER_CLIENT_CHAT = "after_client_chat"
    BEFORE_LLM_REQUEST = "before_llm_request"  # Event before calling LLM
    AFTER_LLM_REQUEST = "after_llm_request"  # Event after calling LLM
    BEFORE_TOOL_CALL = "before_tool_call"  # Event before tool invocation
    AFTER_TOOL_CALL = "after_tool_call"  # Event after tool invocation
    AGENT_SUSPENDED = "agent_suspended"  # Agent termination event
    BEFORE_MAIN_AGENT_RUN = "before_main_agent_run"  # Event before main agent run
    AFTER_MAIN_AGENT_RUN = "after_main_agent_run"  # Event after main agent run

    # Tool-call sub-events
    FILE_CREATED = "file_created"  # File creation event
    FILE_UPDATED = "file_updated"  # File update event
    FILE_DELETED = "file_deleted"  # File deletion event

    ERROR = "error"  # Error event


T = TypeVar("T", bound=BaseEventData)


class Event(Generic[T]):
    """Base event class; all events should inherit from this."""

    def __init__(self, event_type: EventType, data: BaseEventData):
        """Initialize an event.

        Args:
            event_type: Event type
            data: Event payload, either dict or subclass of BaseEventData
        """
        self._event_type = event_type
        self._data = data

    @property
    def event_type(self) -> EventType:
        """Get event type."""
        return self._event_type

    @property
    def data(self) -> T:
        """Get event data."""
        return self._data


class StoppableEvent(Event[T]):
    """Stoppable event with propagation control."""

    def __init__(self, event_type: EventType, data: BaseEventData):
        """Initialize a stoppable event.

        Args:
            event_type: Event type
            data: Event payload
        """
        super().__init__(event_type, data)
        self._propagation_stopped = False

    def stop_propagation(self) -> None:
        """Stop event propagation."""
        self._propagation_stopped = True

    def is_propagation_stopped(self) -> bool:
        """Check whether propagation has been stopped.

        Returns:
            bool: True if propagation is stopped, else False
        """
        return self._propagation_stopped 
