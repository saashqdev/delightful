import fnmatch
from pathlib import Path
from typing import Any, Dict, List

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.schema import FileInfo
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class FileSearchParams(BaseToolParams):
    """File search parameters"""
    query: str = Field(
        ...,
        description="Fuzzy filename to search for"
    )


@tool()
class FileSearch(WorkspaceGuardTool[FileSearchParams]):
    """
    Fast file search based on fuzzy matching of file paths.
    Use this tool if you know part of a file path but don't know exactly where it is. Results will be limited to 10. Make your query more specific if you need to filter results further.
    """

    async def execute(self, tool_context: ToolContext, params: FileSearchParams) -> ToolResult:
        """Execute the tool and return results

        Args:
            tool_context: Tool context
            params: File search parameters

        Returns:
            ToolResult: Contains search results
        """
        # Call _run method to get results
        result = self._run(params.query)

        # Return ToolResult
        return ToolResult(content=result)

    def _run(self, query: str) -> str:
        """Run the tool and return search results"""
        try:
            # Get all file paths
            all_files = self._get_all_files(self.base_dir)

            # Filter files using fuzzy matching
            matches = self._fuzzy_match(all_files, query)

            # Limit number of results
            matches = matches[:10]

            if not matches:
                return "No matching files found"

            # Format output
            output = ["Found the following matching files:\n"]
            for file_path in matches:
                stat = file_path.stat()
                rel_path = str(file_path.relative_to(self.base_dir))

                # Create FileInfo object
                file_info = FileInfo(
                    name=file_path.name,
                    path=rel_path,
                    is_dir=False,
                    size=stat.st_size,
                    last_modified=stat.st_mtime,
                    line_count=self._count_lines(file_path)
                    if file_path.suffix in [".py", ".js", ".ts", ".jsx", ".tsx", ".vue", ".md", ".txt"]
                    else None,
                )

                # Format output
                size_str = self._format_size(file_info.size)
                line_str = f", {file_info.line_count} lines" if file_info.line_count is not None else ""
                output.append(f"{file_info.path} ({size_str}{line_str}) - {file_info.format_time()}")

            return "\n".join(output)

        except Exception as e:
            logger.error(f"Error executing file search: {e}", exc_info=True)
            return f"Error executing file search: {e!s}"

    def _get_all_files(self, directory: Path) -> List[Path]:
        """Recursively get all files in directory"""
        files = []
        try:
            for item in directory.rglob("*"):
                if item.is_file():
                    files.append(item)
        except Exception as e:
            logger.warning(f"Error getting file list: {e}")
        return files

    def _fuzzy_match(self, files: List[Path], pattern: str) -> List[Path]:
        """Filter files using fuzzy matching"""
        # 将模式转换为通配符模式
        wildcard_pattern = f"*{pattern}*"

        # 过滤匹配的文件
        matches = []
        for file in files:
            if fnmatch.fnmatch(file.name.lower(), wildcard_pattern.lower()):
                matches.append(file)

        # 按相关性排序（完全匹配优先，然后是文件名长度）
        matches.sort(
            key=lambda x: (
                x.name.lower() != pattern.lower(),  # 完全匹配优先
                len(x.name),  # 较短的文件名优先
                str(x),  # 按路径字母顺序
            )
        )

        return matches

    def _format_size(self, size: int) -> str:
        """Format file size"""
        for unit in ["B", "KB", "MB", "GB"]:
            if size < 1024:
                return f"{size:.1f}{unit}"
            size /= 1024
        return f"{size:.1f}TB"

    def _count_lines(self, file_path: Path) -> int:
        """Count lines in file"""
        try:
            with file_path.open("r", encoding="utf-8") as f:
                return sum(1 for _ in f)
        except:
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        query = arguments.get("query", "") if arguments else ""
        return {
            "action": "Search files locally",
            "remark": query
        }
