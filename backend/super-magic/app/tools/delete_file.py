from typing import Any, Dict

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import safe_delete
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class DeleteFileParams(BaseToolParams):
    """Delete file parameters"""
    file_path: str = Field(
        ...,
        description="File path to delete"
    )


@tool()
class DeleteFile(AbstractFileTool[DeleteFileParams], WorkspaceGuardTool[DeleteFileParams]):
    """
    Delete file tool, used to delete specified files.

    Notes:
    - Confirm file path is correct before deleting.
    - Will return error if file does not exist.
    - Recommend backing up important files before deletion.
    - Can only delete files in working directory
    """

    def __init__(self, **data):
        super().__init__(**data)

    async def execute(self, tool_context: ToolContext, params: DeleteFileParams) -> ToolResult:
        """
        Execute file deletion operation

        Args:
            tool_context: Tool context
            params: Parameter object containing file path

        Returns:
            ToolResult: Contains operation result
        """
        try:
            # Use base class method to get safe file path
            file_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(error=error)

            # Check if file exists
            if not file_path.exists():
                return ToolResult(error=f"File does not exist: {file_path}")

            # Record file path for subsequent event triggering
            file_path_str = str(file_path)

            # Use safe_delete function to handle deletion logic
            await safe_delete(file_path)
            logger.info(f"Successfully requested deletion of path: {file_path}") # safe_delete will log specific method internally

            # Trigger file deleted event
            await self._dispatch_file_event(tool_context, file_path_str, EventType.FILE_DELETED)

            # Return success result
            return ToolResult(content=f"File successfully deleted\nfile_path: {file_path!s}")

        except Exception as e:
            logger.exception(f"Failed to delete file: {e!s}")
            return ToolResult(error=f"Failed to delete file: {e!s}")

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        file_path = arguments.get("file_path", "") if arguments else ""
        return {
            "action": "Delete file",
            "remark": file_path
        }
