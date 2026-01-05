import subprocess
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.schema import FileInfo
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class GrepSearchParams(BaseToolParams):
    """Search parameters"""
    query: str = Field(..., description="Regular expression pattern to search for")
    case_sensitive: bool = Field(
        False,
        description="Whether the search is case-sensitive"
    )
    include_pattern: str = Field(
        "",
        description="Glob pattern for files to include (e.g., '*.ts' for TypeScript files)"
    )
    exclude_pattern: str = Field(
        "",
        description="Glob pattern for files to exclude"
    )


@tool()
class GrepSearch(WorkspaceGuardTool[GrepSearchParams]):
    """
    Text-based regular expression search that finds exact pattern matches in files or directories, using ripgrep for efficient searching.
    Results are formatted in ripgrep style, configurable to include line numbers and content.
    To avoid excessive output, results are limited to 50 matches.

    Best suited for finding exact text matches or regular expression patterns.
    More precise than semantic search, for finding specific strings or patterns.
    """

    async def execute(self, tool_context: ToolContext, params: GrepSearchParams) -> ToolResult:
        """Execute the tool and return results

        Args:
            tool_context: Tool context
            params: Search parameters

        Returns:
            ToolResult: Contains search results or error information
        """
        # Call internal method to get results
        result = self._run(
            query=params.query,
            case_sensitive=params.case_sensitive,
            include_pattern=params.include_pattern,
            exclude_pattern=params.exclude_pattern
        )

        # Return ToolResult
        return ToolResult(content=result)

    def _run(self, query: str, case_sensitive: Optional[bool] = None,
             include_pattern: Optional[str] = None,
             exclude_pattern: Optional[str] = None) -> str:
        """Run the tool and return search results"""
        try:
            # Build ripgrep command
            cmd = ["rg", "--line-number", "--max-count", "50", "--json"]

            # Add case sensitivity option
            if case_sensitive is not None:
                if not case_sensitive:
                    cmd.append("--ignore-case")

            # Add include pattern
            if include_pattern:
                cmd.extend(["--glob", include_pattern])

            # Add exclude pattern
            if exclude_pattern:
                cmd.extend(["--glob", f"!{exclude_pattern}"])

            # Add search pattern and directory
            cmd.extend([query, str(self.base_dir)])

            # Execute command
            process = subprocess.run(cmd, capture_output=True, text=True, cwd=str(self.base_dir))

            # Process results
            if process.returncode == 0 or process.returncode == 1:  # 1 means no matches
                output = process.stdout.strip()
                if not output:
                    return "No matches found"

                # Parse JSON output and group by file
                matches = self._parse_ripgrep_output(output)
                if not matches:
                    return "No matches found"

                # Format output
                return self._format_matches(matches)
            else:
                error_msg = process.stderr.strip()
                logger.error(f"ripgrep search failed: {error_msg}")
                return f"Search execution failed: {error_msg}"

        except FileNotFoundError:
            return "Error: ripgrep (rg) command not found. Please ensure ripgrep is installed."
        except Exception as e:
            logger.error(f"Error executing search: {e}", exc_info=True)
            return f"Error executing search: {e!s}"

    def _parse_ripgrep_output(self, output: str) -> Dict[Path, List[Tuple[int, str]]]:
        """Parse ripgrep's JSON output"""
        import json

        matches = {}
        for line in output.splitlines():
            try:
                data = json.loads(line)
                if data.get("type") == "match":
                    path = Path(data["data"]["path"]["text"])
                    line_number = data["data"]["line_number"]
                    content = data["data"]["lines"]["text"].strip()

                    if path not in matches:
                        matches[path] = []
                    matches[path].append((line_number, content))
            except json.JSONDecodeError:
                continue
            except KeyError:
                continue

        return matches

    def _format_matches(self, matches: Dict[Path, List[Tuple[int, str]]]) -> str:
        """Format match results"""
        output = []
        for file_path, lines in matches.items():
            # Get file information
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

            # Add file information
            size_str = self._format_size(file_info.size)
            line_str = f", {file_info.line_count} lines" if file_info.line_count is not None else ""
            output.append(f"\n{file_info.path} ({size_str}{line_str}) - {file_info.format_time()}")

            # Add matching lines
            for line_number, content in lines:
                output.append(f"  {line_number}: {content}")

        if not output:
            return "No matches found"

        return "\n".join(output)

    def _format_size(self, size: int) -> str:
        """Format file size"""
        for unit in ["B", "KB", "MB", "GB"]:
            if size < 1024:
                return f"{size:.1f}{unit}"
            size /= 1024
        return f"{size:.1f}TB"

    def _count_lines(self, file_path: Path) -> Optional[int]:
        """Count file lines"""
        try:
            with file_path.open("r", encoding="utf-8") as f:
                return sum(1 for _ in f)
        except:
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call

        Args:
            tool_name: Tool name
            tool_context: Tool context
            result: Tool execution result
            execution_time: Execution time
            arguments: Execution arguments

        Returns:
            Dict: Dictionary containing action and remark
        """
        query = ""
        if arguments and "query" in arguments:
            query = arguments["query"]

        return {
            "action": "Local file search",
            "remark": query
        }
