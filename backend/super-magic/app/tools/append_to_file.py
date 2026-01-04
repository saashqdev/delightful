import os
from pathlib import Path
from typing import Any, Dict, NamedTuple, Optional

import aiofiles
from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.syntax_checker import SyntaxChecker
from app.core.entity.message.server_message import FileContent, ToolDetail
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class AppendToFileParams(BaseToolParams):
    """Append to file parameters"""
    file_path: str = Field(
        ...,
        description="File path to append content to, relative to workspace or absolute path. Do not include workspace directory, e.g., to append to .workspace/todo.md, just pass todo.md"
    )
    content: str = Field(
        ...,
        description="Content to append to the file"
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error message"""
        if field_name == "content":
            error_message = (
                "Missing required parameter 'content'. This may be because the content provided is too long and exceeds the token limit.\n"
                "Suggestions:\n"
                "1. Reduce the output content amount, append file content in batches\n"
                "2. Split large content into multiple append operations\n"
                "3. Ensure the content parameter is explicitly provided when calling"
            )
            return error_message
        return None


class AppendResult(NamedTuple):
    """Append result details"""
    content: str  # Appended content
    is_new_file: bool  # Whether it's a new file
    added_lines: int  # Number of added lines
    original_size: int  # Original file size (bytes)
    new_size: int  # New file size (bytes)
    size_change: int  # File size change (bytes)
    original_lines: int  # Original file line count
    new_lines: int  # New file line count


@tool()
class AppendToFile(AbstractFileTool[AppendToFileParams], WorkspaceGuardTool[AppendToFileParams]):
    """
    Append to file tool, can append content to a file at the specified path. Creates the file if it doesn't exist.

    - Reduce the amount of content output at one time, recommend appending file content in batches
    - If the file doesn't exist, will automatically create the file and necessary directories
    - If the file already exists, will append content at the end of the file
    """

    async def execute(self, tool_context: ToolContext, params: AppendToFileParams) -> ToolResult:
        """
        Perform the file append operation.

        Args:
            tool_context: Tool context
            params: Parameter object containing the file path and content

        Returns:
            ToolResult: Contains the operation result
        """
        try:
            # Use parent helper to resolve a safe file path
            file_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(error=error)

            # Check whether the file exists
            file_exists = file_path.exists()

            # Save original content for potential rollback
            original_content = ""
            if file_exists:
                async with aiofiles.open(file_path, "r", encoding="utf-8") as f:
                    original_content = await f.read()

            # Create directories if needed
            await self._create_directories(file_path)

            # Append content to the file
            await self._append_file(file_path, params.content)

            # Read the full content after writing
            final_content = ""
            async with aiofiles.open(file_path, "r", encoding="utf-8") as f:
                final_content = await f.read()

            # Run syntax check on the latest content
            valid, errors = SyntaxChecker.check_syntax(str(file_path), final_content)

            # Roll back changes if syntax check fails
            if not valid:
                # Restore original content
                if file_exists:
                    async with aiofiles.open(file_path, "w", encoding="utf-8") as f:
                        await f.write(original_content)
                    logger.warning(f"Syntax error detected, rolled back changes to {file_path}")
                else:
                    # Delete the new file if it was just created
                    os.remove(file_path)
                    logger.warning(f"Syntax error detected, removed newly created file {file_path}")

                # Build error message
                errors_str = "\n".join(errors)
                error_message = f"Operation rolled back: syntax issues found in the file:\n{errors_str}"

                return ToolResult(error=error_message)

            # Fire file events only after passing syntax check
            if file_exists:
                await self._dispatch_file_event(tool_context, str(file_path), EventType.FILE_UPDATED)
            else:
                await self._dispatch_file_event(tool_context, str(file_path), EventType.FILE_CREATED)

            # Calculate file change statistics
            append_result = self._calculate_append_stats(
                params.content,
                original_content,
                final_content,
                file_exists
            )

            # Build formatted output message
            file_name = os.path.basename(file_path)
            action = "Appended to" if file_exists else "Created"

            output = (
                f"File {action if action == 'Created' else 'updated'}: {file_path} | "
                f"+{append_result.added_lines} lines | "
                f"Size:{'+' if append_result.size_change > 0 else ''}{append_result.size_change} bytes"
                f"({append_result.original_size}\u2192{append_result.new_size}) | "
                f"Lines:{append_result.original_lines}\u2192{append_result.new_lines}"
            )

            # Return the operation result
            return ToolResult(content=output)

        except Exception as e:
            logger.exception(f"Append to file failed: {e!s}")
            return ToolResult(error=f"Append to file failed: {e!s}")

    async def _create_directories(self, file_path: Path) -> None:
        """Create directories required for the file."""
        directory = file_path.parent

        if not directory.exists():
            os.makedirs(directory, exist_ok=True)
            logger.info(f"Created directory: {directory}")

    async def _append_file(self, file_path: Path, content: str) -> None:
        """Append content to the file."""
        # Ensure trailing newline
        if not content.endswith("\n"):
            content += "\n"

        async with aiofiles.open(file_path, "a", encoding="utf-8") as f:
            await f.write(content)

        logger.info(f"Append complete: {file_path}")

    def _calculate_append_stats(self, append_content: str, original_content: str, final_content: str, file_exists: bool) -> AppendResult:
        """
        Calculate statistics for the append operation.

        Args:
            append_content: Content appended
            original_content: Original file content
            final_content: Final file content
            file_exists: Whether the file existed beforehand

        Returns:
            AppendResult: Result object containing append statistics
        """
        # Ensure content ends with newline
        normalized_append = append_content
        if not normalized_append.endswith("\n"):
            normalized_append += "\n"

        # 计算行数
        original_lines = original_content.count('\n') + (0 if original_content.endswith('\n') or not original_content else 1)
        new_lines = final_content.count('\n') + (0 if final_content.endswith('\n') or not final_content else 1)
        added_lines = normalized_append.count('\n') + (0 if normalized_append.endswith('\n') or not normalized_append else 1)

        # 计算文件大小
        original_size = len(original_content.encode('utf-8'))
        new_size = len(final_content.encode('utf-8'))
        size_change = new_size - original_size

        return AppendResult(
            content=append_content,
            is_new_file=not file_exists,
            added_lines=added_lines,
            original_size=original_size,
            new_size=new_size,
            size_change=size_change,
            original_lines=original_lines,
            new_lines=new_lines
        )

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Build the corresponding ToolDetail from the tool result.

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Argument dictionary for the execution

        Returns:
            Optional[ToolDetail]: Tool detail object, possibly None
        """
        if not result.ok:
            return None

        if not arguments or "file_path" not in arguments or "content" not in arguments:
            logger.warning("Missing file_path or content arguments")
            return None

        file_path = arguments["file_path"]
        content = arguments["content"]
        file_name = os.path.basename(file_path)

        # Use AbstractFileTool helper to get display type
        display_type = self.get_display_type_by_extension(file_path)

        return ToolDetail(
            type=display_type,
            data=FileContent(
                file_name=file_name,
                content=content
            )
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Provide a friendly action and remark after tool execution.
        """
        file_path = arguments["file_path"]
        file_name = os.path.basename(file_path)
        return {
            "action": "Appended content to file",
            "remark": file_name
        }
