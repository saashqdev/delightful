import difflib
import os
from pathlib import Path
from typing import Any, Dict, List, NamedTuple, Optional, Tuple

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


class ReplaceInFileParams(BaseToolParams):
    """Parameters for file content replacement."""

    file_path: str = Field(
        ...,
        description="File path to modify, relative to the working directory or absolute path"
    )
    diff: str = Field(
        ...,
        description=(
            "Diff content with SEARCH/REPLACE blocks defining what to find and replace. SEARCH block content must EXACTLY "
            "match the file content (including spaces, line breaks, and indentation). It's recommended to use read_file "
            "first to view the content before writing replacements. Do NOT use git diff format."
        )
    )


class ReplaceResult(NamedTuple):
    """Replacement result details."""

    new_content: str  # Content after replacement
    added_lines: int  # Number of lines added
    deleted_lines: int  # Number of lines deleted
    modified_lines: int  # Number of lines modified
    total_changed_lines: int  # Total number of changed lines
    size_change: int  # File size change (bytes)
    blocks_info: List[Dict[str, Any]]  # Information for each replacement block
    diff_view: Optional[str] = None  # New field to store diff view


@tool()
class ReplaceInFile(AbstractFileTool[ReplaceInFileParams], WorkspaceGuardTool[ReplaceInFileParams]):
    # Diff view configuration
    DEFAULT_DIFF_CONTEXT_LINES = 5  # Number of context lines to show in diffs
    DEFAULT_DIFF_OMIT_THRESHOLD = 10  # If a hunk exceeds this length, omit the middle
    DEFAULT_DIFF_OMIT_RETAIN_LINES = 3  # Lines to retain at the start and end when omitting

    # Dynamic description string (not a class docstring)
    description = """
    File Content Precise Replacement Tool for making targeted modifications to existing files.
    Important: Use SEARCH/REPLACE format only - NOT git diff format.
    SEARCH block content must EXACTLY match the file content (character-by-character, including all spaces, line breaks, and indentation).
    It's recommended to use the read_file tool first to view the exact file content before using this tool.
    If replacement fails frequently, try using the write_to_file tool to rewrite the entire file. If the file is large, use the write_to_file tool to rewrite the beginning of the file, then use this tool for appending.
    Please refer to the tool reference in the system prompt for detailed usage instructions and examples.
    """

    def get_prompt_hint(self) -> str:
                """Return XML-style guidance for the tool usage."""

                return """<tool name="replace_in_file">
    <description>
        Precisely modify specific parts of files using SEARCH/REPLACE blocks.
        Use when you need targeted changes rather than replacing entire files.
    </description>
    <parameters>
        <param name="file_path" type="string" required="true">File path to modify</param>
        <param name="diff" type="string" required="true">SEARCH/REPLACE blocks defining content to find and replace</param>
    </parameters>
    <format>
        <block>
            <![CDATA[
<<<<<<< SEARCH
[Exact content to find - must match the file content precisely]
=======
[Content to replace with]
>>>>>>> REPLACE
            ]]>
        </block>
    </format>
    <critical_rules>
        <rule>SEARCH content must match EXACTLY (including whitespace, indentation, newlines and line endings, character-by-character)</rule>
        <rule>SEARCH blocks must contain COMPLETE lines - partial lines or fragments within a line CANNOT be matched</rule>
        <rule>Always use read_file tool first to view the file content and ensure SEARCH block accuracy</rule>
        <rule>SEARCH/REPLACE blocks replace only the first match occurrence</rule>
        <rule>List multiple blocks in the order they appear in the file</rule>
        <rule>Keep blocks concise - include just enough lines to uniquely identify sections</rule>
    </critical_rules>
    <common_errors>
        <error>Match failures typically occur because the SEARCH block doesn't exactly match file content, including invisible spaces or line breaks</error>
        <error>If file contains Markdown or other formatting, ensure you include all markers and formatting characters</error>
        <error>Using shorter SEARCH blocks can reduce the chance of matching errors</error>
    </common_errors>
    <special_operations>
        <operation>To move code: Use two blocks (one to delete, one to insert)</operation>
        <operation>To delete code: Use empty REPLACE section</operation>
        <operation>For full file replacement: Use write_to_file tool instead</operation>
    </special_operations>
    <example>
        <![CDATA[
replace_in_file(
    file_path="app/models.py",
    diff="<<<<<<< SEARCH
class User:
        def __init__(self, name):
                self.name = name
=======
class User:
        def __init__(self, name, email=None):
                self.name = name
                self.email = email
>>>>>>> REPLACE"
)
        ]]>
    </example>
</tool>"""

    async def execute(self, tool_context: ToolContext, params: ReplaceInFileParams) -> ToolResult:
        """
        Replace file content based on SEARCH/REPLACE blocks.
        """
        try:
            file_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(ok=False, content=error)

            if not file_path.exists():
                return ToolResult(ok=False, content=f"File does not exist: {file_path}")

            original_content = await self._read_file(file_path)

            try:
                replace_result = await self._construct_new_file_content(params.diff, original_content, os.path.basename(file_path))
                new_content = replace_result.new_content
            except Exception as e:
                return ToolResult(ok=False, content=(
                    f"Failed to construct new file content: {e!s}.\n"
                    "Suggestions:\n"
                    "1. Check that your SEARCH blocks accurately match the file content\n"
                    "2. Break large changes into multiple smaller SEARCH/REPLACE blocks\n"
                    "3. Ensure blocks are in the order they appear in the file\n"
                    "4. Verify whitespace, indentation, and line endings match exactly\n"
                    "5. CRITICAL: SEARCH blocks must contain COMPLETE lines from the file. Partial lines or fragments within a line CANNOT be matched.\n"
                    "6. Use the correct format: <<<<<<< SEARCH / >>>>>>> REPLACE\n"
                    "7. For complex replacements, first use read_file to examine the exact content"
                ))

            if original_content == new_content:
                return ToolResult(ok=False, content=(
                    f"Replacement failed: No matching content found. Please check SEARCH blocks. File content unchanged: {file_path}\n"
                    "Possible reasons:\n"
                    "1. The SEARCH content doesn't match any part of the file exactly\n"
                    "2. Whitespace or line endings differ from what's in the file\n"
                    "3. The file may have been modified since you last read it\n"
                    "4. You might be using incorrect format - use <<<<<<< SEARCH / >>>>>>> REPLACE format\n"
                    "5. CRITICAL: SEARCH blocks must contain COMPLETE lines from the file. Partial lines or fragments within a line CANNOT be matched.\n\n"
                    "Try reading the file first with read_file tool to see its current content, then use the correct SEARCH/REPLACE format"
                ))

            await self._write_file(file_path, new_content)

            valid, errors = SyntaxChecker.check_syntax(str(file_path), new_content)

            if not valid:
                await self._write_file(file_path, original_content)
                logger.warning(f"Syntax issues detected; rolled back changes for file {file_path}")

                errors_str = "\n".join(errors)
                return ToolResult(ok=False, content=f"Operation rolled back: Syntax issues detected in file:\n{errors_str}")

            await self._dispatch_file_event(tool_context, str(file_path), EventType.FILE_UPDATED)

            file_name = os.path.basename(file_path)
            original_size = len(original_content.encode('utf-8'))
            new_size = len(new_content.encode('utf-8'))
            original_lines = original_content.count('\n') + (0 if original_content.endswith('\n') else 1)
            new_lines = new_content.count('\n') + (0 if new_content.endswith('\n') else 1)

            blocks_summary = []
            for block in replace_result.blocks_info:
                blocks_summary.append(
                    f"- Line {block['start_line']}-{block['end_line']}: {block['description']}"
                    f"({block['stats']})"
                )

            blocks_info_str = "\n".join(blocks_summary)

            output = (
                f"File updated: {file_path} | "
                f"+{replace_result.added_lines} added "
                f"-{replace_result.deleted_lines} deleted "
                f"~{replace_result.modified_lines} modified | "
                f"Total changes: {replace_result.total_changed_lines} lines | "
                f"Size: {'+' if replace_result.size_change > 0 else ''}{replace_result.size_change} bytes"
                f"({original_size}→{new_size}) | "
                f"Lines: {original_lines}→{new_lines}\n\n"
                f"Replacement blocks: \n{blocks_info_str}"
            )
            if replace_result.diff_view:
                output += f"\n\nFile Actual Change Details (Diff, please evaluate if the changes are as expected based on this):\n{replace_result.diff_view}"

            return ToolResult(content=output)

        except Exception as e:
            logger.exception(f"Failed to replace file content: {e!s}")
            return ToolResult(ok=False, content=f"Failed to replace file content: {e!s}")

    async def _read_file(self, file_path: Path) -> str:
        """Read file content"""
        async with aiofiles.open(file_path, "r", encoding="utf-8") as f:
            return await f.read()

    async def _write_file(self, file_path: Path, content: str) -> None:
        """Write file content."""
        async with aiofiles.open(file_path, "w", encoding="utf-8") as f:
            await f.write(content)

    async def _construct_new_file_content(self, diff_content: str, original_content: str, file_name: str) -> ReplaceResult:
        """
        Build new file content and gather change statistics.
        """
        result = ""
        last_processed_index = 0
        added_lines = 0
        deleted_lines = 0
        modified_lines = 0
        blocks_info = []

        def get_line_number(index: int) -> int:
            if index <= 0:
                return 1
            return original_content[:index].count('\n') + 1

        search_replace_blocks = self._parse_search_replace_blocks(diff_content)
        match_strategies = {
            'exact': 0,
            'line': 0,
            'block': 0
        }

        if not search_replace_blocks:
            return ReplaceResult(
                new_content=original_content,
                added_lines=0,
                deleted_lines=0,
                modified_lines=0,
                total_changed_lines=0,
                size_change=0,
                blocks_info=[],
                diff_view=None
            )

        for search_content, replace_content in search_replace_blocks:
            if not search_content.strip():
                if original_content.strip() == "":
                    search_match_index = 0
                    search_end_index = 0
                    strategy = 'exact'
                else:
                    search_match_index = 0
                    search_end_index = len(original_content)
                    strategy = 'exact'
            else:
                search_match_index, search_end_index, strategy = await self._find_match_position_with_strategy(
                    search_content, original_content, last_processed_index
                )

            if search_match_index == -1:
                preview = search_content[:100] + ("..." if len(search_content) > 100 else "")
                context_lines = min(5, len(original_content.split('\n')))
                original_preview = "\n".join(original_content.split('\n')[:context_lines])
                if len(original_content.split('\n')) > context_lines:
                    original_preview += "\n..."

                error_msg = (f"Could not find matching content in file for SEARCH block:\n{preview}\n\n"
                            f"File begins with:\n{original_preview}\n\n"
                            f"Suggestions:\n"
                            f"1. Confirm exact content with read_file tool\n"
                            f"2. Check for invisible characters, different indentation, or line endings\n"
                            f"3. Try using fewer lines in your SEARCH block to make matching easier\n"
                            f"4. Make sure you're using the correct SEARCH/REPLACE format, not git diff format\n"
                            f"5. SEARCH blocks must contain complete lines from the file, not fragments within a line")
                logger.error(error_msg)
                raise ValueError(error_msg)

            if strategy:
                match_strategies[strategy] += 1

            result += original_content[last_processed_index:search_match_index]

            search_lines = search_content.split('\n')
            replace_lines = replace_content.split('\n')

            if search_lines and search_lines[-1] == '':
                search_lines.pop()
            if replace_lines and replace_lines[-1] == '':
                replace_lines.pop()

            search_line_count = len(search_lines)
            replace_line_count = len(replace_lines)

            if search_line_count > replace_line_count:
                deleted_count = search_line_count - replace_line_count
                deleted_lines += deleted_count
                if replace_line_count > 0:
                    modified_lines += replace_line_count
            elif replace_line_count > search_line_count:
                added_count = replace_line_count - search_line_count
                added_lines += added_count
                if search_line_count > 0:
                    modified_lines += search_line_count
            else:
                modified_lines += search_line_count

            start_line = get_line_number(search_match_index)
            end_line = get_line_number(search_end_index)

            if search_line_count == 0 and replace_line_count > 0:
                description = "Added content"
                stats = f"{replace_line_count} added"
            elif search_line_count > 0 and replace_line_count == 0:
                description = "Deleted content"
                stats = f"{search_line_count} deleted"
            else:
                search_content_trimmed = search_content.strip()
                if search_content_trimmed.startswith("def ") or search_content_trimmed.startswith("class "):
                    description = "Function definition" if "def " in search_content_trimmed else "Class definition"
                elif "return" in search_content_trimmed:
                    description = "Return value"
                elif search_line_count >= 5:
                    description = "Code block"
                else:
                    description = "Content"

                stats_parts = []
                if added_count := replace_line_count - min(search_line_count, replace_line_count):
                    stats_parts.append(f"{added_count} added")
                if deleted_count := search_line_count - min(search_line_count, replace_line_count):
                    stats_parts.append(f"{deleted_count} deleted")
                if min(search_line_count, replace_line_count) > 0:
                    stats_parts.append(f"{min(search_line_count, replace_line_count)} modified")

                stats = ", ".join(stats_parts)

            blocks_info.append({
                'start_line': start_line,
                'end_line': end_line,
                'search_lines': search_line_count,
                'replace_lines': replace_line_count,
                'description': description,
                'stats': stats,
                'strategy': strategy
            })

            result += replace_content

            last_processed_index = search_end_index

        result += original_content[last_processed_index:]

        total_changed_lines = added_lines + deleted_lines + modified_lines

        size_change = len(result.encode('utf-8')) - len(original_content.encode('utf-8'))

        strategy_summary = []
        for strategy, count in match_strategies.items():
            if count > 0:
                strategy_name = {
                    'exact': 'Exact',
                    'line': 'Line-level',
                    'block': 'Block anchor'
                }.get(strategy, strategy)
                strategy_summary.append(f"{strategy_name}({count})")

        if strategy_summary:
            for block in blocks_info:
                if 'strategy' in block:
                    del block['strategy']
                original_desc = block.get('description', '')
                strategy_str = ", ".join(strategy_summary)
                block['description'] = f"{original_desc} | Match: {strategy_str}"

        # Ensure the final result ends with a newline if not empty
        if result and not result.endswith('\n'):
            result += '\n'

        diff_view_str = await self._generate_diff_view(
            original_content,
            result,
            file_name,
            context_lines=self.DEFAULT_DIFF_CONTEXT_LINES,
            omit_threshold=self.DEFAULT_DIFF_OMIT_THRESHOLD,
            omit_retain_lines=self.DEFAULT_DIFF_OMIT_RETAIN_LINES
        )

        return ReplaceResult(
            new_content=result,
            added_lines=added_lines,
            deleted_lines=deleted_lines,
            modified_lines=modified_lines,
            total_changed_lines=total_changed_lines,
            size_change=size_change,
            blocks_info=blocks_info,
            diff_view=diff_view_str
        )

    def _parse_search_replace_blocks(self, diff_content: str) -> List[Tuple[str, str]]:
        """
        Parse SEARCH/REPLACE blocks.
        """
        blocks = []
        lines = diff_content.split("\n")

        if lines and (lines[-1].startswith("<") or lines[-1].startswith("=") or lines[-1].startswith(">")):
            if lines[-1] not in ["<<<<<<< SEARCH", "=======", ">>>>>>> REPLACE"]:
                logger.warning(
                    f"Removed possible incomplete marker line: '{lines[-1]}'. "
                    "Valid markers are '<<<<<<< SEARCH', '=======', and '>>>>>>> REPLACE'."
                )
                lines.pop()

        i = 0
        while i < len(lines):
            line = lines[i]

            if line == "<<<<<<< SEARCH":
                search_content = ""
                i += 1

                while i < len(lines) and lines[i] != "=======":
                    search_content += lines[i] + "\n"
                    i += 1

                if i < len(lines) and lines[i] == "=======":
                    replace_content = ""
                    i += 1

                    while i < len(lines) and lines[i] != ">>>>>>> REPLACE":
                        replace_content += lines[i] + "\n"
                        i += 1

                    if i < len(lines) and lines[i] == ">>>>>>> REPLACE":
                        blocks.append((search_content, replace_content))
                    else:
                        raise ValueError(
                            "SEARCH/REPLACE block format error: Missing '>>>>>>> REPLACE' marker.\n"
                            "Each block must end with '>>>>>>> REPLACE' exactly as shown.\n"
                            "Check for typos or missing lines in your diff content."
                        )
                else:
                    raise ValueError(
                        "SEARCH/REPLACE block format error: Missing '=======' separator.\n"
                        "Each block must have a '=======' line between SEARCH and REPLACE sections.\n"
                        "Format should be:\n"
                        "<<<<<<< SEARCH\n"
                        "[content to find]\n"
                        "=======\n"
                        "[content to replace with]\n"
                        ">>>>>>> REPLACE"
                    )

            elif line == ">>>>>>> SEARCH":
                search_content = ""
                i += 1

                while i < len(lines) and lines[i] != "=======":
                    search_content += lines[i] + "\n"
                    i += 1

                if i < len(lines) and lines[i] == "=======":
                    replace_content = ""
                    i += 1

                    while i < len(lines) and lines[i] != "<<<<<<< REPLACE":
                        replace_content += lines[i] + "\n"
                        i += 1

                    if i < len(lines) and lines[i] == "<<<<<<< REPLACE":
                        blocks.append((search_content, replace_content))
                    else:
                        raise ValueError(
                            "SEARCH/REPLACE block format error: Missing '<<<<<<< REPLACE' marker in old format.\n"
                            "Each block must end with '<<<<<<< REPLACE' exactly as shown in old format, or use the new format.\n"
                            "Check for typos or missing lines in your diff content."
                        )
                else:
                    raise ValueError(
                        "SEARCH/REPLACE block format error: Missing '=======' separator.\n"
                        "Each block must have a '=======' line between SEARCH and REPLACE sections."
                    )

            i += 1

        return blocks

    async def _find_match_position_with_strategy(
        self, search_content: str, original_content: str, start_index: int = 0
    ) -> Tuple[int, int, str]:
        """
        Locate a match using multiple strategies and return the strategy used.
        """
        exact_index = original_content.find(search_content, start_index)
        if exact_index != -1:
            return exact_index, exact_index + len(search_content), 'exact'

        line_match = await self._line_trimmed_match(search_content, original_content, start_index)
        if line_match:
            return line_match[0], line_match[1], 'line'

        search_lines = search_content.split("\n")
        if len(search_lines) >= 3:
            block_match = await self._block_anchor_match(search_content, original_content, start_index)
            if block_match:
                return block_match[0], block_match[1], 'block'

        return -1, -1, ''

    async def _line_trimmed_match(
        self, search_content: str, original_content: str, start_index: int
    ) -> Optional[Tuple[int, int]]:
        """
        Perform a line-level match ignoring leading/trailing whitespace.
        """
        original_lines = original_content.split('\n')
        search_lines = search_content.split('\n')

        if search_lines and search_lines[-1] == '':
            search_lines.pop()

        if not search_lines:
            return None

        start_line_num = 0
        current_index = 0
        while current_index < start_index and start_line_num < len(original_lines):
            current_index += len(original_lines[start_line_num]) + 1
            start_line_num += 1

        for i in range(start_line_num, len(original_lines) - len(search_lines) + 1):
            matches = True

            for j in range(len(search_lines)):
                original_trimmed = original_lines[i + j].strip()
                search_trimmed = search_lines[j].strip()

                if original_trimmed != search_trimmed:
                    matches = False
                    break

            if matches:
                match_start_index = 0
                for k in range(i):
                    match_start_index += len(original_lines[k]) + 1

                match_end_index = match_start_index
                for k in range(len(search_lines)):
                    match_end_index += len(original_lines[i + k]) + 1

                return match_start_index, match_end_index

        return None

    async def _block_anchor_match(
        self, search_content: str, original_content: str, start_index: int
    ) -> Optional[Tuple[int, int]]:
        """
        Anchor-based matching that uses the first and last lines to locate longer blocks.
        """
        original_lines = original_content.split('\n')
        search_lines = search_content.split('\n')

        if len(search_lines) < 3:
            return None

        if search_lines and search_lines[-1] == '':
            search_lines.pop()

        first_line_search = search_lines[0].strip()
        last_line_search = search_lines[-1].strip()
        search_block_size = len(search_lines)

        start_line_num = 0
        current_index = 0
        while current_index < start_index and start_line_num < len(original_lines):
            current_index += len(original_lines[start_line_num]) + 1
            start_line_num += 1

        for i in range(start_line_num, len(original_lines) - search_block_size + 1):
            if original_lines[i].strip() != first_line_search:
                continue

            if original_lines[i + search_block_size - 1].strip() != last_line_search:
                continue

            match_start_index = 0
            for k in range(i):
                match_start_index += len(original_lines[k]) + 1

            match_end_index = match_start_index
            for k in range(search_block_size):
                match_end_index += len(original_lines[i + k]) + 1

            return match_start_index, match_end_index

        return None

    async def _process_hunk_for_omission(
        self,
        lines: List[str],
        omit_threshold: int,
        omit_retain_lines: int
    ) -> List[str]:
        """
        Process a contiguous change hunk; omit middle lines if the hunk is too large.
        """
        if not lines:
            return []

        if len(lines) > omit_threshold and len(lines) > 2 * omit_retain_lines:
            hidden_count = len(lines) - 2 * omit_retain_lines
            processed_lines = lines[:omit_retain_lines]
            omission_marker = f"... ({hidden_count} line{'s' if hidden_count > 1 else ''} hidden) ..."
            processed_lines.append(omission_marker)
            processed_lines.extend(lines[-omit_retain_lines:])
            return processed_lines
        return lines

    async def _generate_diff_view(
        self,
        original_content: str,
        new_content: str,
        file_name: str,
        context_lines: int,
        omit_threshold: int,
        omit_retain_lines: int
    ) -> Optional[str]:
        """
        Generate a GitHub-style unified diff string.
        """
        if original_content == new_content:
            return None

        original_lines = original_content.splitlines(keepends=True)
        new_lines = new_content.splitlines(keepends=True)

        diff = list(difflib.unified_diff(
            original_lines,
            new_lines,
            fromfile=f"a/{file_name}",
            tofile=f"b/{file_name}",
            n=context_lines,
            lineterm=""
        ))

        if not diff:
            return None

        processed_diff = []
        if len(diff) > 0 and diff[0].startswith("---"):
            processed_diff.append(diff.pop(0).rstrip("\n"))
        if len(diff) > 0 and diff[0].startswith("+++"):
            processed_diff.append(diff.pop(0).rstrip("\n"))

        current_hunk_lines = []
        current_hunk_type = None  # '+' or '-'

        for line_with_newline in diff:
            line = line_with_newline.rstrip("\n")
            if line.startswith("@@"):
                if current_hunk_lines:
                    processed_diff.extend(
                        await self._process_hunk_for_omission(
                            current_hunk_lines, omit_threshold, omit_retain_lines
                        )
                    )
                    current_hunk_lines = []
                processed_diff.append(line)
                current_hunk_type = None
            elif line.startswith("+") and not line.startswith("+++"):
                if current_hunk_type == '-':
                    processed_diff.extend(
                        await self._process_hunk_for_omission(
                            current_hunk_lines, omit_threshold, omit_retain_lines
                        )
                    )
                    current_hunk_lines = []
                current_hunk_lines.append(line)
                current_hunk_type = '+'
            elif line.startswith("-") and not line.startswith("---"):
                if current_hunk_type == '+':
                    processed_diff.extend(
                        await self._process_hunk_for_omission(
                            current_hunk_lines, omit_threshold, omit_retain_lines
                        )
                    )
                    current_hunk_lines = []
                current_hunk_lines.append(line)
                current_hunk_type = '-'
            else:  # context line or end of hunk
                if current_hunk_lines:
                    processed_diff.extend(
                        await self._process_hunk_for_omission(
                            current_hunk_lines, omit_threshold, omit_retain_lines
                        )
                    )
                    current_hunk_lines = []
                processed_diff.append(line)
                current_hunk_type = None

        # Flush the final accumulated hunk if present.
        if current_hunk_lines:
            processed_diff.extend(
                await self._process_hunk_for_omission(
                    current_hunk_lines, omit_threshold, omit_retain_lines
                )
            )

        # Restore trailing newlines for all but the last line to mimic unified_diff output.
        final_diff_output = []
        for i, line_str in enumerate(processed_diff):
            if i < len(processed_diff) - 1:
                final_diff_output.append(f"{line_str}\n")
            else:
                final_diff_output.append(line_str)

        return "".join(final_diff_output).rstrip("\n")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Provide file content preview for the tool result.

        Args:
            tool_context: tool context
            result: tool execution result
            arguments: tool parameters

        Returns:
            Optional[ToolDetail]: file detail if available, otherwise None
        """

        if not result.ok:
            return None

        if not arguments or "file_path" not in arguments:
            logger.warning("file_path parameter is missing")
            return None

        file_path, error = self.get_safe_path(arguments["file_path"])
        if error:
            logger.error(f"getfile pathfailed: {error}, file_path: {arguments['file_path']}")
            return None

        file_name = os.path.basename(file_path)

        # Use AbstractFileTool helper to derive display type
        display_type = self.get_display_type_by_extension(file_path)

        with open(file_path, "r", encoding="utf-8") as f:
            content = f.read()

        return ToolDetail(
            type=display_type,
            data=FileContent(
                file_name=file_name,
                content=content
            )
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Friendly action and remark after tool call
        """
        if not result.ok:
            return {
                "action": "",
                "remark": ""
            }

        file_path = arguments["file_path"]
        file_name = os.path.basename(file_path)
        return {
            "action": "Update file",
            "remark": file_name
        }
