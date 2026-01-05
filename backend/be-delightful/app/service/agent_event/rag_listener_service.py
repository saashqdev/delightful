from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.event.file_event import FileEventData
from app.service.agent_event.base_listener_service import BaseListenerService

logger = get_logger(__name__)

class RagListenerService:
    """
    RAG event listener service for handling file-related events, supporting Retrieval-Augmented Generation (RAG) system
    """

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register file-related event listeners for the agent context
        
        Args:
            agent_context: Agent context object
        """
        # Create mapping from event types to handler functions
        event_listeners = {
            EventType.FILE_CREATED: RagListenerService._handle_file_created,
            EventType.FILE_UPDATED: RagListenerService._handle_file_updated,
            EventType.FILE_DELETED: RagListenerService._handle_file_deleted
        }

        # Use base class method to batch register listeners
        BaseListenerService.register_listeners(agent_context, event_listeners)

        logger.info("Registered all file-related event listeners for RAG system")

    @staticmethod
    async def _handle_file_created(event: Event[FileEventData]) -> None:
        """
        Handle file creation event
        
        Args:
            event: File creation event object containing FileEventData
        """
        # Event handling logic to be implemented by user
        pass

    @staticmethod
    async def _handle_file_updated(event: Event[FileEventData]) -> None:
        """
        Handle file update event
        
        Args:
            event: File update event object containing FileEventData
        """
        # Event handling logic to be implemented by user
        pass

    @staticmethod
    async def _handle_file_deleted(event: Event[FileEventData]) -> None:
        """
        Handle file deletion event
        
        Args:
            event: File deletion event object containing FileEventData
        """
        # Event handling logic to be implemented by user
        pass
