from typing import Any, Callable, Dict

from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext

logger = get_logger(__name__)

class BaseListenerService:
    """
    Base event listener service, encapsulates common event registration logic
    
    Provides common event registration and management functionality, subclasses only need to focus on specific event handling logic
    """

    @staticmethod
    def register_event_listener(agent_context: AgentContext, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        """
        Register event listener for agent context
        
        Args:
            agent_context: Agent context object
            event_type: Event type
            listener: Event listener function, receives one event parameter
        """
        # Check if agent_context is None
        if agent_context is None:
            logger.warning("Cannot register event listener: agent context is None")
            return

        # Register listener directly to agent context
        agent_context.add_event_listener(event_type, listener)

        logger.info(f"Registered event listener: {event_type}")

    @staticmethod
    def register_listeners(agent_context: AgentContext, event_listeners: Dict[EventType, Callable[[Event[Any]], None]]) -> None:
        """
        Batch register multiple event listeners
        
        Args:
            agent_context: Agent context object
            event_listeners: Dictionary mapping event types to listener functions
        """
        for event_type, listener in event_listeners.items():
            BaseListenerService.register_event_listener(agent_context, event_type, listener)

        logger.info(f"Batch registered {len(event_listeners)} event listeners")
