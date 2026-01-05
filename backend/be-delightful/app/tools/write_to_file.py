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


class WriteToFileParams(BaseToolParams):
    """Write to file parameters"""
    file_path: str = Field(
        ...,
        description="File path to write to, relative to working directory or absolute path. Do not include working directory, for example if you want to write file to .workspace/todo.md, just pass todo.md"
    )
    content: str = Field(
        ...,
        description="Complete content to write to file"
    )
    overwrite: bool = Field(
        False,
        description="Whether to allow overwriting existing file. Defaults to false, overwrite not allowed."
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error information"""
        if field_name == "content":
            error_message = (
                "Missing required parameter 'content'. This may be because the content you provided is too long and exceeds the token limit.\n"
                "Suggestions:\n"
                "1. Reduce the amount of output content and complete file content in batches\n"
                "2. Split large files into multiple smaller files\n"
                "3. Ensure clearly providing content parameters when calling"
            )
            return error_message
        return None


class WriteResult(NamedTuple):
    """Write result details"""
    content: str  # Content to write
    file_exists: bool  # Whether file already exists
    total_lines: int  # Total number of lines
    file_size: int  # File size (bytes)
    has_syntax_errors: bool  # Whether there are syntax errors
    syntax_errors: list  # Syntax error list


@tool()
class WriteToFile(AbstractFileTool[WriteToFileParams], WorkspaceGuardTool[WriteToFileParams]):
    """
    Write to file tool

    This tool can write content to a file at the specified path. If the file does not exist, it will create the file.

    - If the file does not exist, the file and necessary directories will be automatically created.
    - If the file already exists, the default behavior is to return an error. To overwrite an existing file, set the overwrite parameter to true.
    - To reduce the amount of content output at once, it is recommended to complete file content in batches, first call write_to_file to write a new file, then call replace_in_file to modify and append file content.
    - If you need to copy or move files, use the shell_exec tool to execute cp or mv commands.
    - If you need to make partial modifications to an existing file, be sure to use the replace_in_file tool.
    """

    async def execute(self, tool_context: ToolContext, params: WriteToFileParams) -> ToolResult:
        """
        Execute file write operation

        Args:
            tool_context: Tool context
            params: Parameters object containing file path, content and overwrite option

        Returns:
            ToolResult: Contains operation result
        """
        try:
            # Use parent class method to get safe file path
            file_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(error=error)

            # Check if file exists
            file_exists = file_path.exists()

            # If file already exists and overwrite not allowed
            if file_exists and not params.overwrite:
                return ToolResult(
                    error=f"File {file_path!s} already exists. If you need to overwrite, set `overwrite=true` in parameters."
                )

            # Create directories (if needed)
            await self._create_directories(file_path)

            # Write file content
            await self._write_file(file_path, params.content)

            # Trigger file event
            event_type = EventType.FILE_UPDATED if file_exists else EventType.FILE_CREATED
            await self._dispatch_file_event(tool_context, str(file_path), event_type)

            # Run syntax check
            valid, errors = SyntaxChecker.check_syntax(str(file_path), params.content)

            # Calculate file statistics information
            write_result = self._calculate_write_stats(
                params.content,
                file_exists, # Pass original file existence status
                not valid,
                errors
            )

            # Generate formatted output
            action_verb = "File overwritten" if file_exists else "File created"
            output = (
                f"{action_verb}: {file_path} | "
                f"{write_result.total_lines} lines | "
                f"Size: {write_result.file_size} bytes"
            )

            # If there are syntax errors, add to result
            if not valid:
                errors_str = "\n".join(errors)
                output += f"\n\nWarning: File has syntax problems:\n{errors_str}"
                logger.warning(f"File {file_path} has syntax problems: {errors}")

            # Return operation result
            return ToolResult(content=output)

        except Exception as e:
            logger.exception(f"Write file failed: {e!s}")
            return ToolResult(error=f"Write file failed: {e!s}. This may be because the content you provided is too long and exceeds the token limit.\n"
                "Suggestions:\n"
                "1. Reduce the amount of output content and append file content in batches\n"
                "2. Split large content into multiple append operations\n"
                "3. Ensure clearly providing content parameters when calling")

    async def _create_directories(self, file_path: Path) -> None:
        """Create directory structure required for file"""
        directory = file_path.parent

        if not directory.exists():
            os.makedirs(directory, exist_ok=True)
            logger.info(f"Created directory: {directory}")

    async def _write_file(self, file_path: Path, content: str) -> None:
        """Write file content"""
        # Handle possible empty lines at end of content
        if not content.endswith("\n"):
            content += "\n"

        async with aiofiles.open(file_path, "w", encoding="utf-8") as f:
            await f.write(content)

        logger.info(f"File write completed: {file_path}")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get corresponding ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution parameters dictionary

        Returns:
            Optional[ToolDetail]: Tool details object, may be None
        """
        if not result.ok:
            return None

        if not arguments or "file_path" not in arguments or "content" not in arguments:
            logger.warning("file_path or content parameters not provided")
            return None

        file_path = arguments["file_path"]
        content = arguments["content"]
        file_name = os.path.basename(file_path)

        # Use AbstractFileTool method to get display type
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
        Get friendly action and remark after tool call
        """
        if not arguments or "file_path" not in arguments:
            return {
                "action": "Write to file",
                "remark": "Write failed, retry"
            }

        file_path = arguments["file_path"]
        file_name = os.path.basename(file_path)

        # Determine whether it's create or overwrite
        # Here we cannot directly access file_exists variable, but can infer from parameters
        # If call successful and either overwrite=True or file originally didn't exist, then operation successful
        # We can judge from result.content, but this is not robust enough
        # A more reliable (but not perfect) method is to check if ToolResult content contains "overwrite"
        # Or we can consider putting operation type (create/overwrite) in ToolResult metadata or directly modify content during successful execute
        # For simplicity, we temporarily infer through parameters and ToolResult content
        action = "Create and write to file"
        if result.ok and result.content:
            # Determine whether it's create or overwrite by checking ToolResult output information
            if "File overwritten:" in result.content:
                action = "Overwrite file"
            elif "File created:" in result.content:
                action = "Create and write to file"

        # A more accurate way is to determine operation type in execute method and pass it
        # But currently we rely on result.content

        return {
            "action": action,
            "remark": file_name
        }

    def _calculate_write_stats(self, content: str, file_exists: bool, has_syntax_errors: bool, syntax_errors: list) -> WriteResult:
        """
        Calculate write operation statistics information

        Args:
            content: Content to write
            file_exists: Whether file already exists
            has_syntax_errors: Whether there are syntax errors
            syntax_errors: Syntax error list

        Returns:
            WriteResult: Result object containing write statistics information
        """
        # Ensure content ends with newline
        normalized_content = content
        if not normalized_content.endswith("\n"):
            normalized_content += "\n"

        # Calculate number of lines
        total_lines = normalized_content.count('\n') + (0 if normalized_content.endswith('\n') or not normalized_content else 1)

        # Calculate file size
        file_size = len(normalized_content.encode('utf-8'))

        return WriteResult(
            content=content,
            file_exists=file_exists,
            total_lines=total_lines,
            file_size=file_size,
            has_syntax_errors=has_syntax_errors,
            syntax_errors=syntax_errors
        )
