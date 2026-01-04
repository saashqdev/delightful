"""
Todo list file listener service

Used to listen for todo.md file events and add todo items to the agent context
"""

import os
import re
from typing import Any, Dict, List

from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from agentlang.utils.snowflake import Snowflake
from app.core.context.agent_context import AgentContext
from app.core.entity.event.event_context import EventContext
from app.core.entity.event.file_event import FileEventData
from app.service.agent_event.base_listener_service import BaseListenerService

logger = get_logger(__name__)


class TodoListenerService:
    """
    Todo list file listener service
    
    Listens for creation and update events of todo.md files, parses todo items, and creates snowflake IDs for each todo item
    """

    # Singleton pattern instance
    _instance = None

    # Snowflake ID generator
    _snowflake_service = None

    @classmethod
    def get_instance(cls) -> 'TodoListenerService':
        """
        Get singleton instance
        
        Returns:
            TodoListenerService: Singleton instance
        """
        if cls._instance is None:
            cls._instance = TodoListenerService()
        return cls._instance

    def __init__(self):
        """Initialize todo list listener service"""
        if self._snowflake_service is None:
            # Initialize snowflake ID generator
            TodoListenerService._snowflake_service = Snowflake.create_default()
        logger.info("Todo list listener service initialization complete")

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register todo list file event listeners for the agent context
        
        Args:
            agent_context: Agent context object
        """
        # Create mapping from event types to handler functions
        event_listeners = {
            EventType.FILE_CREATED: TodoListenerService._handle_file_event,
            EventType.FILE_UPDATED: TodoListenerService._handle_file_event
        }

        # Use base class method to batch register listeners
        BaseListenerService.register_listeners(agent_context, event_listeners)

        logger.info("Registered todo list file event listeners for agent context")

    @staticmethod
    async def _handle_file_event(event: Event[FileEventData]) -> None:
        """
        Handle file events (creation and updates)
        
        Args:
            event: File event object containing FileEventData
        """
        # Get file path and agent context
        filepath = event.data.filepath
        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)

        # Get event context
        event_context = event.data.tool_context.get_extension_typed("event_context", EventContext)
        if not event_context:
            logger.warning("Cannot get event context: EventContext not registered")
            return

        # Check if file is todo.md
        filename = os.path.basename(filepath)
        if filename.lower() != "todo.md":
            return

        # Log file event processing
        event_type_name = "creation" if event.event_type == EventType.FILE_CREATED else "update"
        logger.info(f"Processing todo list file {event_type_name} event: {filepath}")

        # Parse todo items
        try:
            todo_items = TodoListenerService._parse_todo_file(filepath)

            event_context.steps_changed = True
            logger.info("Detected todo.md file update, setting steps_changed flag to True")

            # Process todo items
            if todo_items:
                TodoListenerService._process_todo_items(todo_items, agent_context)
        except Exception as e:
            logger.error(f"Error parsing todo list file: {e}")

    @staticmethod
    def _parse_todo_file(filepath: str) -> List[Dict[str, Any]]:
        """
        Parse todo list file and extract todo items
        
        Args:
            filepath: File path
            
        Returns:
            List[Dict[str, Any]]: List of todo items, each item is a dictionary containing text and completed fields
        """
        if not os.path.exists(filepath):
            logger.warning(f"Todo list file does not exist: {filepath}")
            return []

        try:
            with open(filepath, 'r', encoding='utf-8') as file:
                content = file.read()

            # Use regex to match todo items
            # Support two formats: "- [ ] task" and "- [x] task"
            todo_pattern = r'- \[([ xX])\] (.*?)(?=\n- \[|\n\n|$)'
            matches = re.findall(todo_pattern, content, re.DOTALL)

            todo_items = []
            for status, text in matches:
                text = text.strip()
                if text:
                    completed = status.lower() == 'x'
                    todo_items.append({
                        'text': text,
                        'completed': completed
                    })

            logger.info(f"Parsed {len(todo_items)} todo items from file {filepath}")
            return todo_items
        except Exception as e:
            logger.error(f"Error reading or parsing todo list file: {e}")
            return []

    @staticmethod
    def _process_todo_items(todo_items: List[Dict[str, Any]], agent_context: AgentContext) -> None:
        """
        Process todo items, directly replace todo items in agent context with the latest todo_items
        
        Args:
            todo_items: List of todo items
            agent_context: Agent context
        """
        instance = TodoListenerService.get_instance()
        snowflake_service = TodoListenerService._snowflake_service

        # Create new todo items dictionary
        new_todo_dict = {}

        # Generate new record for each todo item without checking if it already exists
        for item in todo_items:
            todo_text = item['text']
            completed = item['completed']

            # Generate new snowflake ID for each todo item
            snowflake_id = snowflake_service.get_id()
            new_todo_dict[todo_text] = {
                'id': snowflake_id,
                'completed': completed
            }
            logger.info(f"Adding todo item: {todo_text}, ID: {snowflake_id}, completed: {completed}")

        # Directly replace todo items dictionary in agent context
        agent_context.shared_context.update_field("todo_items", new_todo_dict)
        logger.info(f"Completely replaced todo items, total {len(new_todo_dict)} items") 
