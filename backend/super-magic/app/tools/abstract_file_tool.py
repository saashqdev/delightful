import os
from typing import Generic, TypeVar

from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.event.file_event import FileEventData
from app.core.entity.message.server_message import DisplayType
from app.tools.core.base_tool import BaseTool
from app.tools.core.base_tool_params import BaseToolParams

logger = get_logger(__name__)

# Define parameter type variable
T = TypeVar('T', bound=BaseToolParams)


class AbstractFileTool(BaseTool[T], Generic[T]):
    """
    Abstract file tool base class

    Provides common file event dispatch functionality for file operation tools
    """

    async def _dispatch_file_event(self, tool_context: ToolContext, filepath: str, event_type: EventType, is_screenshot: bool = False) -> None:
        """
        Dispatch file event

        Args:
            tool_context: Tool context
            filepath: File path
            event_type: Event type (FILE_CREATED, FILE_UPDATED or FILE_DELETED)
        """
        # Create event data, including tool_context
        event_data = FileEventData(
            filepath=filepath,
            event_type=event_type,
            tool_context=tool_context,
            is_screenshot=is_screenshot
        )

        try:
            agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
            if agent_context:
                await agent_context.dispatch_event(event_type, event_data)
                logger.info(f"File event dispatched: {event_type} - {filepath}")
            else:
                logger.error("AgentContext extension not found in ToolContext")
        except Exception as e:
            # Print stack trace for debugging
            import traceback
            stack_trace = traceback.format_exc()
            logger.error(f"Failed to dispatch file event: {e}, stack trace:\n{stack_trace}")

    def get_display_type_by_extension(self, file_path: str) -> DisplayType:
        """
        Get appropriate DisplayType based on file extension

        Args:
            file_path: File path

        Returns:
            DisplayType: Display type
        """
        file_name = os.path.basename(file_path)
        file_extension = os.path.splitext(file_name)[1].lower()

        display_type = DisplayType.TEXT
        if file_extension in ['.md', '.markdown']:
            display_type = DisplayType.MD
        elif file_extension in ['.html', '.htm']:
            display_type = DisplayType.HTML
        elif file_extension in ['.php', '.py', '.js', '.ts', '.java', '.c', '.cpp', '.h', '.hpp', '.json', '.yaml', '.yml', '.toml', '.ini', '.sh']:
            display_type = DisplayType.CODE

        return display_type
