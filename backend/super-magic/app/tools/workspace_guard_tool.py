from pathlib import Path
from typing import Optional, TypeVar

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.paths import PathManager
from app.tools.core.base_tool import BaseTool
from app.tools.core.base_tool_params import BaseToolParams

logger = get_logger(__name__)

# Define parameter type variable
T = TypeVar('T', bound=BaseToolParams)


class WorkspaceGuardTool(BaseTool[T]):
    """
    Base class for file operation tools, providing working directory restrictions and related safety features

    All tools needing file system access should inherit this class to uniformly enforce working directory restrictions
    """

    # Default to using workspace directory as base directory
    base_dir: Path = PathManager.get_workspace_dir()

    def __init__(self, **data):
        """
        Initialize file operation tool

        Args:
            **data: Additional parameters passed to parent class
        """
        super().__init__(**data)
        if 'base_dir' in data:
            self.base_dir = Path(data['base_dir'])

    def get_safe_path(self, filepath: str) -> tuple[Path, Optional[str]]:
        """
        Get safe file path, ensuring it is within working directory

        Args:
            filepath: File path string

        Returns:
            tuple: (Safe file path object, error information)
                If path is safe, error information is empty string
                If path is unsafe, return None and corresponding error information
        """
        # processfile path
        file_path = Path(filepath)

        # If relative path, resolve relative to base_dir
        if not file_path.is_absolute():
            file_path = self.base_dir / file_path

        # Check if file is within base_dir
        try:
            file_path.relative_to(self.base_dir)
            return file_path, ""
        except ValueError:
            error_msg = f"Security restriction: Access to files outside working directory ({self.base_dir}) is not allowed: {file_path}"
            logger.warning(error_msg)
            return None, error_msg

    async def execute(self, tool_context: ToolContext, params: T) -> ToolResult:
        """
        Default execute method; subclasses should override

        Args:
            tool_context: tool context
            params: toolparameters

        Returns:
            ToolResult: Tool execution result
        """
        raise NotImplementedError("Subclass must implement execute method")
