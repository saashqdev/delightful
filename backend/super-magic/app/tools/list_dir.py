from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import count_file_lines, count_file_tokens, format_file_size, is_text_file
from agentlang.utils.schema import DirectoryInfo, FileInfo
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class ListDirParams(BaseToolParams):
    """List directory parameters"""
    relative_workspace_path: str = Field(
        ".",
        description="Path relative to workspace root for listing contents."
    )
    level: int = Field(
        3,
        description="Directory depth level to list, default is 3."
    )
    filter_binary: bool = Field(
        False,
        description="Whether to filter binary files such as images, videos, etc., showing only text/code files"
    )


@tool()
class ListDir(WorkspaceGuardTool[ListDirParams]):
    """
    Tool to list directory contents, recommended to use level=3 to get sufficient file information.

    Lists directory contents with support for specified recursion levels. This is a quick discovery tool
    before using more targeted tools (such as semantic search or file reading). It's useful for understanding
    file structure before diving into specific files. Can be used to explore workspaces, project structures, file distribution, etc.

    For text files, displays file size, line count, and token count to facilitate content volume assessment.
    """

    async def execute(self, tool_context: ToolContext, params: ListDirParams) -> ToolResult:
        """Execute tool and return result

        Args:
            tool_context: Tool context
            params: Directory list parameters

        Returns:
            ToolResult: Contains directory contents or error message
        """
        # Validate level reasonability, e.g., limit maximum depth
        max_level = 10  # Set a maximum recursion depth to prevent abuse
        level = params.level
        if level > max_level:
            logger.warning(f"Requested level {level} exceeds maximum {max_level}, limiting to {max_level}.")
            level = max_level
        elif level < 1:
             logger.warning(f"Requested level {level} is less than 1, setting to 1.")
             level = 1

        # Call internal method to get result
        result = self._run(
            relative_workspace_path=params.relative_workspace_path,
            level=level,
            filter_binary=params.filter_binary,
            calculate_tokens=True,
        )

        # Return ToolResult
        return ToolResult(content=result)

    def _run(self, relative_workspace_path: str, level: int, filter_binary: bool, calculate_tokens: bool) -> str:
        """Run tool and return string representation of directory contents"""
        target_path, error = self.get_safe_path(relative_workspace_path)
        if error:
            return error

        if not target_path.exists():
            return f"Error: Path does not exist: {target_path}"

        if not target_path.is_dir():
            return f"Error: Path is not a directory: {target_path}"

        try:
            filter_mode = "Show text/code files only" if filter_binary else "Show all files"
            token_mode = "Calculate token count" if calculate_tokens else "Do not calculate token count"
            # Update initial output line describing new flat format
            output_lines = [f"Contents of directory '{relative_workspace_path}' (Level {level}, {filter_mode}, {token_mode}, Format: path: type attributes timestamp):\n"]

            # Reset statistics
            total_items = 0
            filtered_items = 0

            # Calculate levels starting from 1, where 1 represents the first level (root directory)
            # No longer need to pass prefix parameter
            self._list_directory_recursive(
                target_path, 1, output_lines,
                stats=(total_items, filtered_items),
                max_level=level,
                filter_binary=filter_binary,
                calculate_tokens=calculate_tokens,
                base_dir=self.base_dir
            )

            if filter_binary and filtered_items > 0:
                output_lines.append(f"\nFiltered {filtered_items} binary files (images, videos, and other non-text files)")

            return "".join(output_lines)

        except Exception as e:
            logger.error(f"Error listing directory contents: {e}", exc_info=True)
            return f"Error listing directory contents: {e!s}"

    def _is_text_file(self, file_path: Path) -> bool:
        """Determine if file is a text/code file"""
        return is_text_file(file_path)

    def _list_directory_recursive(
        self, current_path: Path, current_level: int,
                                 output_lines: List[str], # Remove prefix parameter
        stats: Tuple[int, int] = (0, 0),
        max_level: int = 1,
        filter_binary: bool = True,
        calculate_tokens: bool = True,
        base_dir: Path = None
    ) -> Tuple[int, int]:
        """Recursively list directory contents (flat format) and return statistics (total items, filtered items)

        Args:
            current_path: Current path being processed
            current_level: Current level, starting from 1 (1=root directory)
            output_lines: Output line list
            stats: Statistics tuple (total items, filtered items)
            max_level: Maximum recursion level
            filter_binary: Whether to filter binary files
            calculate_tokens: Whether to calculate token count for text files
            base_dir: Base directory path for calculating relative paths

        Returns:
            Updated statistics tuple
        """
        total_items, filtered_items = stats

        # Check if maximum level limit is exceeded (not including equal case)
        if current_level > max_level:
            return total_items, filtered_items

        try:
            items = sorted(
                list(current_path.iterdir()),
                key=lambda x: (not x.is_dir(), x.name.lower())
            )
        except PermissionError:
            # Use new flat error format
            relative_path = str(current_path.relative_to(base_dir)) if base_dir and current_path.is_relative_to(base_dir) else str(current_path)
            output_lines.append(f"{relative_path}/: error Permission denied\n")
            return total_items, filtered_items
        except Exception as e:
            # Use new flat error format
            relative_path = str(current_path.relative_to(base_dir)) if base_dir and current_path.is_relative_to(base_dir) else str(current_path)
            output_lines.append(f"{relative_path}/: error Cannot access directory: {e!s}\n")
            return total_items, filtered_items

        # If filtering binary files, filter first
        if filter_binary:
            filtered_file_items = [item for item in items if item.is_dir() or self._is_text_file(item)]
            filtered_items += len(items) - len(filtered_file_items)
            items = filtered_file_items

        # Check if directory is empty
        if len(items) == 0:
            relative_path = str(current_path.relative_to(base_dir)) if base_dir and current_path.is_relative_to(base_dir) else str(current_path)
            output_lines.append(f"{relative_path}/: Directory is empty, no files\n")
            return total_items, filtered_items

        for i, item in enumerate(items):
            total_items += 1
            # Remove calculation and use of is_last, connector, new_prefix

            relative_item_path = str(item.relative_to(base_dir))

            if item.is_dir():
                # Count number of files in next level directory
                try:
                    sub_items = list(item.iterdir())
                    if filter_binary:
                        sub_items = [sub_item for sub_item in sub_items if sub_item.is_dir() or self._is_text_file(sub_item)]
                    item_count = f"{len(sub_items)} items"
                except (PermissionError, Exception):
                    item_count = "? items"  # If subdirectory cannot be accessed, display as unknown

                info = DirectoryInfo(
                    name=item.name,
                    path=relative_item_path, # Use calculated relative path
                    is_dir=True,
                    item_count=item_count,
                    last_modified=item.stat().st_mtime,
                )
                # Change output format to: path/: d item_count timestamp
                output_lines.append(f"{info.path}/: d {info.item_count:>10} {info.format_time()}\n") # Use >10 for simple right alignment

                # Only continue recursion if current level is less than maximum level
                if current_level < max_level:
                    # Recursively process subdirectory, level+1, no longer pass prefix
                    total_items, filtered_items = self._list_directory_recursive(
                        item, current_level + 1, output_lines, # Remove new_prefix
                        (total_items, filtered_items),
                        max_level, filter_binary, calculate_tokens, base_dir
                    )
            else: # Process files
                try:
                    stat_result = item.stat()
                    file_size = stat_result.st_size

                    # For text files, calculate line count and token count
                    line_count = None
                    token_count = None

                    if self._is_text_file(item):
                        line_count = self._count_lines(item)
                        # Only calculate when token calculation is needed
                        if calculate_tokens:
                            token_count = self._count_tokens(item)

                    info = FileInfo(
                        name=item.name,
                        path=relative_item_path, # Use calculated relative path
                        is_dir=False,
                        size=file_size,
                        line_count=line_count,
                        last_modified=stat_result.st_mtime,
                    )

                    size_str = self._format_size(info.size)
                    # Combine attribute strings, handling None values and spaces
                    attributes = [size_str]
                    if info.line_count is not None:
                        attributes.append(f"{info.line_count} lines")
                    if token_count is not None:
                        attributes.append(f"{token_count} tokens")
                    attributes_str = ", ".join(attributes)

                    # Change output format to: path: - attributes timestamp
                    output_lines.append(f"{info.path}: - {attributes_str:<30} {info.format_time()}\n") # Use <30 for simple left alignment

                except Exception as file_e:
                     # Use new flat error format
                     output_lines.append(f"{relative_item_path}: error Cannot access file: {file_e!s}\n")

        return total_items, filtered_items

    def _count_lines(self, file_path: Path) -> Optional[int]:
        """Calculate file line count"""
        return count_file_lines(file_path)

    def _count_tokens(self, file_path: Path) -> Optional[int]:
        """Calculate file token count"""
        return count_file_tokens(file_path)

    def _format_size(self, size: int) -> str:
        """Format file size"""
        return format_file_size(size)

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call

        Args:
            tool_name: Tool name
            tool_context: Tool context
            result: Tool execution result
            execution_time: Execution time
            arguments: Execution parameters

        Returns:
            Dict: Dictionary containing action and remark
        """
        path = "."
        if arguments and "relative_workspace_path" in arguments:
            path = arguments["relative_workspace_path"]

        return {
            "action": "View files in workspace",
            "remark": path
        }
