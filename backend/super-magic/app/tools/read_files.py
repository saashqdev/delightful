from typing import Any, Dict, List, Optional

from pydantic import BaseModel, Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.token_estimator import num_tokens_from_string
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseToolParams, tool
from app.tools.read_file import ReadFile, ReadFileParams
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)

# Set maximum token limit
MAX_TOTAL_TOKENS = 30000

class ReadFilesParams(BaseToolParams):
    """Batch file reading parameters"""
    files: List[str] = Field(..., description="List of file paths to read, relative to working directory or absolute path")
    offset: int = Field(0, description="Starting line number for reading (starting from 0), applies to all files")
    limit: int = Field(200, description="Number of lines or pages to read, default 200 lines, set to -1 to read entire file")


class FileReadingResult(BaseModel):
    """Single file reading result"""
    file_path: str
    content: str  # Complete content (including metadata)
    is_success: bool
    error_message: Optional[str] = None
    tokens: int = 0  # Token count for calculating truncation


@tool()
class ReadFiles(WorkspaceGuardTool[ReadFilesParams]):
    """
    Batch file reading tool

    This tool can read the contents of multiple specified file paths at once, with all files using the same reading parameters.
    Supported file types are the same as the ReadFile tool, including text files, PDF and DOCX formats.

    Supported file types:
    - Text files (.txt, .md, .py, .js, etc.)
    - PDF files (.pdf)
    - Word documents (.docx)
    - Jupyter Notebook (.ipynb)
    - Excel files (.xls, .xlsx)
    - CSV files (.csv)

    Notes:
    - Reading files outside the working directory is prohibited
    - Binary files may not be read correctly
    - Oversized files will be rejected for reading, you must read parts of the content in segments to understand the file overview
    - For Excel and CSV files, it is recommended to use code to process data rather than using text content directly
    - To avoid long content, the total token count exceeding 30000 will be automatically truncated
    """

    async def execute(self, tool_context: ToolContext, params: ReadFilesParams) -> ToolResult:
        """
        Execute batch file reading operation

        Args:
            tool_context: Tool context
            params: Batch file reading parameters

        Returns:
            ToolResult: Contains batch file content or error information
        """
        if not params.files:
            return ToolResult(error="No files specified to read")

        results = []
        read_file_tool = ReadFile()
        read_failure_count = 0
        has_truncation = False

        # Process each file in batch and collect results
        for filepath in params.files:
            try:
                # Construct single file reading parameters
                file_params = ReadFileParams(
                    file_path=filepath,
                    offset=params.offset,
                    limit=params.limit,
                    explanation=params.explanation if hasattr(params, 'explanation') else ""
                )

                # Call ReadFile tool to read single file
                result = await read_file_tool.execute(tool_context, file_params)

                if result.ok:
                    # Directly use complete content returned by ReadFile (including metadata)
                    content = result.content
                    tokens = num_tokens_from_string(content)

                    # Create file reading result object
                    file_result = FileReadingResult(
                        file_path=filepath,
                        content=content,
                        is_success=True,
                        tokens=tokens
                    )

                    results.append(file_result)
                else:
                    results.append(FileReadingResult(
                        file_path=filepath,
                        content="",
                        is_success=False,
                        error_message=result.content,  # When failed, content is actually error message
                        tokens=0
                    ))
                    read_failure_count += 1
            except Exception as e:
                logger.exception(f"Failed to read file: {e!s}")
                results.append(FileReadingResult(
                    file_path=filepath,
                    content="",
                    is_success=False,
                    error_message=f"File reading exception: {e!s}",
                    tokens=0
                ))
                read_failure_count += 1

        # Check if content needs to be truncated to comply with token limit
        total_content_tokens = sum(result.tokens for result in results if result.is_success)

        # Reserve tokens for summary
        header_tokens = 500  # Tokens reserved for header
        available_tokens = MAX_TOTAL_TOKENS - header_tokens

        # If content tokens exceed limit, perform truncation
        if total_content_tokens > available_tokens:
            has_truncation = True
            logger.info(f"Total content tokens ({total_content_tokens}) exceed limit ({available_tokens}), performing truncation")
            results = self._truncate_contents(results, available_tokens)

        # Generate summary information
        total_files = len(params.files)
        success_count = total_files - read_failure_count
        truncation_info = ", content truncated" if has_truncation else ""
        summary = f"Total {total_files} files read, Success: {success_count}, Failed: {read_failure_count}{truncation_info}"

        # Format final result
        formatted_result = self._format_results(results, summary, has_truncation)

        # Record actual token count
        actual_tokens = num_tokens_from_string(formatted_result)
        logger.info(f"Final output tokens: {actual_tokens}")

        return ToolResult(
            content=formatted_result,
            system=summary
        )

    def _truncate_contents(self, results: List[FileReadingResult], available_tokens: int) -> List[FileReadingResult]:
        """
        Truncate content proportionally to comply with token limit

        Args:
            results: List of file reading results
            available_tokens: Number of available tokens

        Returns:
            List of truncated results
        """
        successful_files = [r for r in results if r.is_success]

        if not successful_files:
            return results

        # Calculate total tokens to be truncated
        total_tokens = sum(r.tokens for r in successful_files)
        ratio = available_tokens / total_tokens

        # Allocate tokens proportionally
        for result in successful_files:
            allocated_tokens = max(300, int(result.tokens * ratio))  # Ensure each file has at least some content
            if result.tokens > allocated_tokens:
                # Try to keep only the beginning of the file (including metadata and partial content)
                content = result.content

                # Find suitable truncation point through binary search
                left, right = 0, len(content)
                best_content = ""
                best_tokens = 0

                while left <= right:
                    mid = (left + right) // 2
                    truncated = content[:mid]
                    tokens = num_tokens_from_string(truncated)

                    if tokens <= allocated_tokens:
                        best_content = truncated
                        best_tokens = tokens
                        left = mid + 1
                    else:
                        right = mid - 1

                # Update result
                result.content = best_content + "\n\n[Content truncated...]"
                result.tokens = best_tokens + 10  # Add some tokens for truncation notice

        return results

    def _format_results(self, results: List[FileReadingResult], summary: str, has_truncation: bool) -> str:
        """
        Format reading results for multiple files

        Args:
            results: List of file reading results
            summary: Summary information
            has_truncation: Whether any content has been truncated

        Returns:
            Formatted result text
        """
        formatted_parts = []

        # Add summary information
        formatted_parts.append(f"# Batch File Reading Results\n\n**{summary}**\n")

        if has_truncation:
            formatted_parts.append(f"> **Note**: Due to long content (exceeding {MAX_TOTAL_TOKENS} tokens), some file content has been truncated.\n")

        # Add separator line
        formatted_parts.append("-" * 80 + "\n")

        # Add results for each file
        for idx, result in enumerate(results):
            # Add file separator (except for first file)
            if idx > 0:
                formatted_parts.append("\n" + "=" * 80 + "\n")

            if result.is_success:
                # Directly use content generated by ReadFile (already contains metadata)
                formatted_parts.append(result.content)
            else:
                # Add error information for failed files
                formatted_parts.append(f"## File: {result.file_path}\n\nRead failed: {result.error_message}\n")

        return "\n".join(formatted_parts)

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution parameter dictionary

        Returns:
            Optional[ToolDetail]: Tool detail object, may be None
        """
        if not result.ok:
            return None

        if not arguments or "files" not in arguments:
            logger.warning("No files parameter provided")
            return None

        file_count = len(arguments["files"])

        return ToolDetail(
            type=DisplayType.MD,
            data=FileContent(
                file_name=f"Batch read files (total {file_count} files)",
                content=result.content
            )
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        if not arguments or "files" not in arguments:
            return {
                "action": "Batch read files",
                "remark": "Failed to read multiple files"
            }

        file_count = len(arguments["files"])

        if not result.ok:
            return {
                "action": "Batch read files",
                "remark": f"Failed to batch read {file_count} files"
            }

        return {
            "action": "Batch read files",
            "remark": f"Read {file_count} files"
        }
