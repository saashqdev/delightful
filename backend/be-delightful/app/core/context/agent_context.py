"""
Agent context class

Manages business parameters related to agents
"""

import asyncio
import json
import os
from datetime import datetime, timedelta
from typing import Any, Dict, List, Optional

from agentlang.context.base_agent_context import BaseAgentContext
from agentlang.event.common import BaseEventData
from agentlang.event.event import Event, EventType, StoppableEvent
from agentlang.logger import get_logger
from app.core.config.communication_config import STSTokenRefreshConfig
from app.core.entity.attachment import Attachment
from app.core.entity.message.client_message import ChatClientMessage, InitClientMessage
from app.core.entity.project_archive import ProjectArchiveInfo
from app.core.stream import Stream
from app.paths import PathManager

# Get logger
logger = get_logger(__name__)

class AgentContext(BaseAgentContext):
    """
    Agent context class, contains context information needed for agent execution
    Implements AgentContextInterface interface, providing user and agent-related information
    """

    def __init__(self):
        """
        Initialize agent context
        """
        super().__init__()

        # Initialize fields and register to shared_context
        self._init_shared_fields()

        # Set workspace directory
        try:
            self.set_workspace_dir(str(PathManager.get_workspace_dir()))
        except Exception as e:
            logger.warning(f"Unable to get workspace directory: {e}")
            self.set_workspace_dir(os.path.join(os.getcwd(), ".workspace"))
            self.ensure_workspace_dir()

        # Set chat history directory
        try:
            chat_history_dir = str(PathManager.get_chat_history_dir())
            self.set_chat_history_dir(chat_history_dir)
        except Exception as e:
            logger.warning(f"Unable to get chat history directory: {e}")
            chat_history_dir = os.path.join(os.getcwd(), ".chat_history")
            self.set_chat_history_dir(chat_history_dir)

        # Set default agent name
        self.set_agent_name("delightful")

    def _init_shared_fields(self):
        """Initialize shared fields and register to shared_context"""
        # Check if already initialized
        if hasattr(self.shared_context, 'is_initialized') and self.shared_context.is_initialized():
            logger.warning("SharedContext already initialized, skipping duplicate initialization")
            return

        super()._init_shared_fields()

        # Use register_fields to register all fields at once
        import asyncio
        from typing import Any, Dict, Optional

        from app.core.entity.attachment import Attachment
        from app.core.entity.message.client_message import ChatClientMessage, InitClientMessage
        from app.core.entity.project_archive import ProjectArchiveInfo
        from app.core.stream import Stream

        # Initialize and register shared fields
        self.shared_context.register_fields({
            "streams": ({}, Dict[str, Stream]),
            "todo_items": ({}, Dict[str, Dict[str, Any]]),
            "attachments": ({}, Dict[str, Attachment]),
            "chat_client_message": (None, Optional[ChatClientMessage]),
            "init_client_message": (None, Optional[InitClientMessage]),
            "task_id": (None, Optional[str]),
            "interrupt_queue": (None, Optional[asyncio.Queue]),
            "sandbox_id": ("", str),
            "project_archive_info": (None, Optional[ProjectArchiveInfo]),
            "organization_code": (None, Optional[str])
        })

        # Mark initialization completed
        if hasattr(self.shared_context, 'set_initialized'):
            self.shared_context.set_initialized(True)

        logger.info("Initialized SharedContext shared fields")

    def set_task_id(self, task_id: str) -> None:
        """Set task ID

        Args:
            task_id: Task ID
        """
        self.shared_context.update_field("task_id", task_id)
        logger.debug(f"Updated task ID: {task_id}")

    def get_task_id(self) -> Optional[str]:
        """Get task ID

        Returns:
            Optional[str]: Task ID
        """
        return self.shared_context.get_field("task_id")

    def set_interrupt_queue(self, interrupt_queue: asyncio.Queue) -> None:
        """Set interrupt queue

        Args:
            interrupt_queue: Interrupt queue
        """
        self.shared_context.update_field("interrupt_queue", interrupt_queue)

    def get_interrupt_queue(self) -> Optional[asyncio.Queue]:
        """Get interrupt queue

        Returns:
            Optional[asyncio.Queue]: Interrupt queue
        """
        return self.shared_context.get_field("interrupt_queue")

    def set_sandbox_id(self, sandbox_id: str) -> None:
        """Set sandbox ID

        Args:
            sandbox_id: Sandbox ID
        """
        self.shared_context.update_field("sandbox_id", sandbox_id)
        logger.debug(f"Updated sandbox ID: {sandbox_id}")

    def get_sandbox_id(self) -> str:
        """Get sandbox ID

        Returns:
            str: Sandbox ID
        """
        return self.shared_context.get_field("sandbox_id")

    def set_organization_code(self, organization_code: str) -> None:
        """Set organization code

        Args:
            organization_code: Organization code
        """
        self.shared_context.update_field("organization_code", organization_code)

    def get_organization_code(self) -> Optional[str]:
        """Get organization code

        Returns:
            Optional[str]: Organization code
        """
        return self.shared_context.get_field("organization_code")

    def set_init_client_message(self, init_client_message: InitClientMessage) -> None:
        """Set initialization client message

        Args:
            init_client_message: Initialization client message
        """
        self.shared_context.update_field("init_client_message", init_client_message)

    def get_init_client_message(self) -> Optional[InitClientMessage]:
        """Get initialization client message

        Returns:
            Optional[InitClientMessage]: Initialization client message
        """
        return self.shared_context.get_field("init_client_message")

    def get_init_client_message_metadata(self) -> Dict[str, Any]:
        """Get initialization client message metadata

        Returns:
            Dict[str, Any]: Initialization client message metadata
        """
        init_client_message = self.get_init_client_message()
        if init_client_message is None:
            return {}

        if init_client_message.metadata is None:
            return {}

        return init_client_message.metadata

    def get_init_client_message_sts_token_refresh(self) -> Optional[STSTokenRefreshConfig]:
        """Get initialization client message STS Token refresh configuration

        Returns:
            Optional[STSTokenRefreshConfig]: STS Token refresh configuration
        """
        init_client_message = self.get_init_client_message()
        if init_client_message is None:
            return None

        if init_client_message.sts_token_refresh is None:
            return None

        return init_client_message.sts_token_refresh

    def set_chat_client_message(self, chat_client_message: ChatClientMessage) -> None:
        """Set chat client message

        Args:
            chat_client_message: Chat client message
        """
        self.shared_context.update_field("chat_client_message", chat_client_message)

    def get_chat_client_message(self) -> Optional[ChatClientMessage]:
        """Get chat client message

        Returns:
            Optional[ChatClientMessage]: Chat client message
        """
        return self.shared_context.get_field("chat_client_message")

    def has_stream(self, stream: Stream) -> bool:
        """Check if specified stream exists

        Args:
            stream: Stream instance to check
        """
        stream_id = str(id(stream))
        streams = self.shared_context.get_field("streams")
        return stream_id in streams

    def add_stream(self, stream: Stream) -> None:
        """Add a communication stream to the stream dictionary.

        Args:
            stream: Stream instance to add.

        Raises:
            TypeError: Raised when the passed stream is not an implementation of the Stream interface.
        """
        if not isinstance(stream, Stream):
            raise TypeError("stream must be an implementation of the Stream interface")

        streams = self.shared_context.get_field("streams")
        stream_id = str(id(stream))  # Use stream object's id as key
        streams[stream_id] = stream
        logger.info(f"Added new Stream, current Stream count: {len(streams)}")

    def remove_stream(self, stream: Stream) -> None:
        """Remove a communication stream.

        Args:
            stream: Stream instance to remove.
        """
        streams = self.shared_context.get_field("streams")
        stream_id = str(id(stream))
        if stream_id in streams:
            del streams[stream_id]
            logger.info(f"Removed Stream, type: {type(stream)}, current Stream count: {len(streams)}")

    @property
    def streams(self) -> Dict[str, Stream]:
        """Get dictionary of all communication streams.

        Returns:
            Dict[str, Stream]: Stream dictionary, key is stream ID, value is Stream object.
        """
        return self.shared_context.get_field("streams")

    def set_project_archive_info(self, project_archive_info: ProjectArchiveInfo) -> None:
        """Set project archive info

        Args:
            project_archive_info: Project archive info
        """
        self.shared_context.update_field("project_archive_info", project_archive_info)

    def get_project_archive_info(self) -> Optional[ProjectArchiveInfo]:
        """Get project archive info

        Returns:
            Optional[ProjectArchiveInfo]: Project archive info
        """
        return self.shared_context.get_field("project_archive_info")

    # Override base class method to implement specific event dispatching
    async def dispatch_event(self, event_type: EventType, data: BaseEventData) -> Event[Any]:
        """
        Trigger event of specified type

        Args:
            event_type: Event type
            data: Event data, instance of BaseEventData subclass

        Returns:
            Event: Processed event object
        """
        event = Event(event_type, data)
        return await self.get_event_dispatcher().dispatch(event)

    async def dispatch_stoppable_event(self, event_type: EventType, data: BaseEventData) -> StoppableEvent[Any]:
        """
        Trigger stoppable event

        Args:
            event_type: Event type
            data: Event data, instance of BaseEventData subclass

        Returns:
            StoppableEvent: Processed event object
        """
        event = StoppableEvent(event_type, data)
        return await self.get_event_dispatcher().dispatch(event)

    def get_todo_items(self) -> Dict[str, Dict[str, Any]]:
        """
        Get all todo items

        Returns:
            Dict[str, Dict[str, Any]]: Todo items dictionary, key is todo text, value is dict containing snowflake ID and other info
        """
        return self.shared_context.get_field("todo_items")

    def add_todo_item(self, todo_text: str, snowflake_id: int) -> None:
        """Add new todo item

        Args:
            todo_text: Todo item text content
            snowflake_id: Snowflake ID of the todo item
        """
        todo_items = self.shared_context.get_field("todo_items")
        if todo_text not in todo_items:
            todo_items[todo_text] = {
                'id': snowflake_id,
                'completed': False
            }
            logger.info(f"Added todo item: {todo_text}, ID: {snowflake_id}")

    def update_todo_item(self, todo_text: str, completed: bool = None) -> None:
        """Update todo item status

        Args:
            todo_text: Todo item text content
            completed: Whether completed
        """
        todo_items = self.shared_context.get_field("todo_items")
        if todo_text in todo_items:
            if completed is not None:
                todo_items[todo_text]['completed'] = completed
            logger.info(f"Updated todo item status: {todo_text}, completed: {todo_items[todo_text]['completed']}")

    def get_todo_item_id(self, todo_text: str) -> Optional[int]:
        """
        Get snowflake ID of todo item

        Args:
            todo_text: Todo item text content

        Returns:
            Optional[int]: Snowflake ID of todo item, returns None if not exists
        """
        todo_items = self.shared_context.get_field("todo_items")
        return todo_items.get(todo_text, {}).get('id')

    def has_todo_item(self, todo_text: str) -> bool:
        """
        Check if todo item exists

        Args:
            todo_text: Todo item text content

        Returns:
            bool: Whether todo item exists
        """
        todo_items = self.shared_context.get_field("todo_items")
        return todo_text in todo_items

    def update_activity_time(self) -> None:
        """Update agent activity time"""
        self.shared_context.update_activity_time()

    def is_idle_timeout(self) -> bool:
        """Check if agent is idle timeout

        Returns:
            bool: Returns True if timeout, otherwise False
        """
        return self.shared_context.is_idle_timeout()

    def add_attachment(self, attachment: Attachment) -> None:
        """Add attachment to agent context

        All attachments generated by tools will be added here for batch sending when task completes
        If filename already exists, update the corresponding attachment object

        Args:
            attachment: Attachment object to add
        """
        attachments = self.shared_context.get_field("attachments")
        filename = attachment.filename

        if filename in attachments:
            logger.debug(f"Updating attachment {filename} in agent context")
        else:
            logger.debug(f"Adding new attachment {filename} to agent context, total attachments: {len(attachments) + 1}")

        attachments[filename] = attachment

    def get_attachments(self) -> List[Attachment]:
        """Get all attachments

        Returns:
            List[Attachment]: List of all collected attachments
        """
        attachments = self.shared_context.get_field("attachments")
        return list(attachments.values())

    # Override user-related methods
    def get_user_id(self) -> Optional[str]:
        """Get user ID

        Returns:
            Optional[str]: User ID, returns None if not exists
        """
        metadata = self.get_init_client_message_metadata()
        return metadata.get("user_id")

    def get_metadata(self) -> Dict[str, Any]:
        """Get metadata

        Returns:
            Dict[str, Any]: Context metadata
        """
        return self.get_init_client_message_metadata()

    def _serialize_value(self, value: Any) -> Any:
        """Convert value to serializable format
        
        Args:
            value: Value to serialize
            
        Returns:
            Any: Serialized value
        """
        if value is None:
            return None

        # Handle pathlib.Path objects
        if hasattr(value, "absolute") and callable(getattr(value, "absolute")):
            return str(value)

        # Handle objects with to_dict method
        if hasattr(value, "to_dict") and callable(getattr(value, "to_dict")):
            return value.to_dict()

        # Handle datetime objects
        if isinstance(value, (datetime, timedelta)):
            return str(value)

        # Handle async queue
        if isinstance(value, asyncio.Queue):
            return f"<Queue:{id(value)}>"

        # Handle dictionary
        if isinstance(value, dict):
            return {k: self._serialize_value(v) for k, v in value.items()}

        # Handle list or tuple
        if isinstance(value, (list, tuple)):
            return [self._serialize_value(item) for item in value]

        # Try direct conversion to str
        try:
            json.dumps(value)
            return value
        except (TypeError, OverflowError, ValueError):
            # If cannot serialize, return type and ID info
            return f"<{type(value).__name__}:{id(value)}>"

    def to_dict(self) -> Dict[str, Any]:
        """Convert agent context to dictionary
        
        Returns:
            Dict[str, Any]: Dictionary containing agent context information
        """
        # Basic information
        result = {
            "agent_name": self.get_agent_name(),
            "workspace_dir": self._serialize_value(self.get_workspace_dir()),
            "chat_history_dir": self._serialize_value(self.get_chat_history_dir()),
            "user_id": self.get_user_id(),
            "task_id": self.get_task_id(),
            "sandbox_id": self.get_sandbox_id(),
            "organization_code": self.get_organization_code(),
        }

        # Collection information
        try:
            result["todo_items_count"] = len(self.get_todo_items())
            # Add partial todo_items information
            todo_items = self.get_todo_items()
            if todo_items:
                result["todo_items"] = self._serialize_value({k: v for i, (k, v) in enumerate(todo_items.items()) if i < 5})
                if len(todo_items) > 5:
                    result["todo_items"]["..."] = f"... {len(todo_items) - 5} more items not shown"
        except Exception as e:
            result["todo_items_error"] = str(e)

        try:
            result["attachments_count"] = len(self.get_attachments())
            attachments = self.get_attachments()
            if attachments:
                result["attachments"] = [att.filename for att in attachments[:5]]
                if len(attachments) > 5:
                    result["attachments"].append(f"... {len(attachments) - 5} more attachments not shown")
        except Exception as e:
            result["attachments_error"] = str(e)

        try:
            result["streams_count"] = len(self.streams)
        except Exception as e:
            result["streams_error"] = str(e)

        # Add shared context information
        result["shared_context"] = "<Use shared_context.to_dict() to view detailed information>"

        return result

    def __str__(self) -> str:
        """Custom string representation
        
        Returns:
            str: String representation in dictionary form
        """
        try:
            return json.dumps(self.to_dict(), ensure_ascii=False, indent=2)
        except Exception as e:
            return f"<AgentContext object at {hex(id(self))}: {e!s}>"

    def __repr__(self) -> str:
        """Custom object representation
        
        Returns:
            str: Dictionary form object representation
        """
        return self.__str__()
