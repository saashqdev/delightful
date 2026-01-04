"""
Event System Interface Definitions

Defines the basic interfaces for the event system to avoid circular dependencies
"""

from abc import ABC, abstractmethod
from typing import Any, Callable, TypeVar

from agentlang.event.event import Event, EventType

T = TypeVar('T')

class EventDispatcherInterface(ABC):
    """Event dispatcher interface, defines basic methods for event dispatching"""

    @abstractmethod
    def add_listener(self, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        """Add an event listener

        Args:
            event_type: Event type
            listener: Listener function
        """
        pass

    @abstractmethod
    async def dispatch(self, event: Event[T]) -> Event[T]:
        """Dispatch event to all relevant listeners

        Args:
            event: Event to dispatch

        Returns:
            Event: Processed event object
        """
        pass 
