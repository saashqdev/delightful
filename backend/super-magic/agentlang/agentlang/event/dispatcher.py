import traceback
from typing import Any, Callable, Dict, Iterable, List, Optional, TypeVar

from agentlang.event.event import Event, EventType, StoppableEvent
from agentlang.event.interface import EventDispatcherInterface
from agentlang.logger import get_logger

logger = get_logger(__name__)

T = TypeVar('T')  # Temporarily unbind from BaseEventData to avoid circular import

class ListenerProvider:
    """Listener provider, manages and provides event listeners"""

    def __init__(self):
        """Initialize listener provider"""
        self._listeners: Dict[EventType, List[Callable[[Event[Any]], None]]] = {}

    def add_listener(self, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        """Add an event listener

        Args:
            event_type: Event type
            listener: Listener function taking one event parameter
        """
        if event_type not in self._listeners:
            self._listeners[event_type] = []
        self._listeners[event_type].append(listener)
        logger.info(f"Added event listener: {event_type}, listener: {listener.__name__}")

    def get_listeners_for_event(self, event: Event[Any]) -> Iterable[Callable[[Event[Any]], None]]:
        """Get all listeners for a given event

        Args:
            event: Event instance

        Returns:
            Iterable[Callable]: List of listener functions
        """
        return self._listeners.get(event.event_type, [])


class EventDispatcher(EventDispatcherInterface):
    """Event dispatcher, dispatches events to corresponding listeners"""

    def __init__(self, provider: Optional[ListenerProvider] = None):
        """Initialize event dispatcher

        Args:
            provider: Listener provider instance; creates new one if not provided
        """
        self._provider = provider or ListenerProvider()

    def add_listener(self, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        """Add an event listener

        Args:
            event_type: Event type
            listener: Listener function
        """
        self._provider.add_listener(event_type, listener)

    async def dispatch(self, event: Event[T]) -> Event[T]:
        """Dispatch event to all relevant listeners

        Calls listeners in order synchronously; for stoppable events, checks before each call whether propagation should stop.
        If event data contains a tool context reference, the shared event context can be accessed via the tool context.

        Args:
            event: Event to dispatch

        Returns:
            Event: Processed event object
        """
        listeners = self._provider.get_listeners_for_event(event)

        for listener in listeners:
            # If stoppable event and propagation stopped, return immediately
            if isinstance(event, StoppableEvent) and event.is_propagation_stopped():
                logger.info(f"Event {event.event_type} propagation stopped")
                break

            try:
                # Get listener name
                listener_name = self._get_listener_name(listener)

                # Call listener
                await listener(event)

                # Log success
                logger.debug(f"Listener {listener_name} successfully handled event {event.event_type}")
            except Exception as e:
                # Print traceback
                traceback.print_exc()
                listener_name = self._get_listener_name(listener)
                logger.error(f"Error executing event listener: {listener_name}, error: {e}")
                # Continue with other listeners but log error
                continue

        return event

    def _get_listener_name(self, listener: Callable) -> str:
        """Get listener name for logging
        
        Attempts to get function name; includes class name if it's a method
        
        Args:
            listener: Listener function or method
            
        Returns:
            str: Listener name
        """
        if hasattr(listener, "__qualname__"):
            return listener.__qualname__
        elif hasattr(listener, "__name__"):
            return listener.__name__
        else:
            return str(listener) 
