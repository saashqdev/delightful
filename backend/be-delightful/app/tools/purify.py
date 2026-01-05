import re
from typing import Any, Dict, Optional, Set

import aiofiles
from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.llms.factory import LLMFactory
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.token_estimator import truncate_text_by_token
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)

# Reference summarize.py, set default max token count
DEFAULT_MAX_TOKENS = 24_000
# Default model to use
DEFAULT_MODEL_ID = "deepseek-chat"

class PurifyParams(BaseToolParams):
    """Purify tool parameters"""
    file_path: str = Field(
        ...,
        description="File path to be purified"
    )
    criteria: Optional[str] = Field(
        default=None,
        description="""Optional user-defined purification criteria, e.g., 'remove all lines containing advertisements' or 'keep only main content' """
    )


@tool()
class Purify(BaseTool[PurifyParams]):
    """
    Purification tool for cleaning text files, removing irrelevant lines (such as ads, navigation, headers/footers, copyright notices, unnecessary comments, excessive blank lines, etc.).
    Users can provide optional custom purification criteria.

    Example usage:
    ```
    {
        "file_path": "./path/to/your/document.txt",
        "criteria": "Keep only main paragraphs, remove all list items and code blocks"
    }
    ```
    Or, without providing criteria to use general standards:
    ```
    {
        "file_path": "./path/to/another/document.md"
    }
    ```
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: PurifyParams
    ) -> ToolResult:
        """Tool execution entry point"""
        return await self.execute_purely(params)

    async def execute_purely(
        self,
        params: PurifyParams
    ) -> ToolResult:
        """Execute purification core logic"""
        file_path = params.file_path
        criteria = params.criteria
        file_name = file_path.split('/')[-1]

        try:
            logger.info(f"Starting file purification: {file_path}, custom criteria: {'Yes' if criteria else 'No'}")

            # 1. Read file content
            original_content: str
            try:
                async with aiofiles.open(file_path, mode='r', encoding='utf-8') as f:
                    original_content = await f.read()
            except FileNotFoundError:
                logger.error(f"Purification failed: File not found {file_path}")
                return ToolResult(error=f"File not found: {file_path}")
            except Exception as e:
                logger.exception(f"Purification failed: Error reading file {file_path}")
                return ToolResult(error=f"Error reading file: {e!s}")

            if not original_content.strip():
                logger.warning(f"File {file_path} is empty or contains only whitespace, no purification needed.")
                return ToolResult(
                    content=f"File '{file_name}' is empty or contains only whitespace, no purification needed.",
                    extra_info={"file_path": file_path, "file_name": file_name, "purified": False}
                )

            # 2. Call internal method to get purified content
            purified_content = await self._get_purified_content(
                original_content=original_content,
                criteria=criteria,
            )

            if purified_content is None:
                # _get_purified_content has already logged the error internally
                return ToolResult(error="Purification processing failed, please check logs for details")

            # 3. Return result
            logger.info(f"File {file_path} purification completed")
            return ToolResult(
                content=purified_content,
                extra_info={"file_path": file_path, "file_name": file_name, "purified": True}
            )

        except Exception as e:
            logger.exception(f"Unexpected error during purification operation: {e!s}")
            return ToolResult(error=f"Purification operation failed: {e!s}")


    async def _get_purified_content(
        self,
        original_content: str,
        criteria: Optional[str],
    ) -> Optional[str]:
        """Core purification logic: add line numbers, call LLM, parse results, filter content"""
        try:
            # Preprocessing: split original content by lines
            original_lines = original_content.splitlines() # splitlines() automatically handles various line endings
            if not original_lines:
                return original_content # If split result is empty list, original content might be empty or only newlines

            # Add line numbers (1-based)
            lines_with_numbers = [f"{i+1}: {line}" for i, line in enumerate(original_lines)]
            content_with_line_numbers = "\n".join(lines_with_numbers)

            # (Optional) Truncation handling
            truncated_content, is_truncated = truncate_text_by_token(content_with_line_numbers, DEFAULT_MAX_TOKENS)
            if is_truncated:
                logger.warning(f"Purification content was truncated: original_lines={len(original_lines)}, truncated_length={len(truncated_content)}")
                # Note: If truncated, LLM can only see partial lines, returned line numbers are also based on this part, may result in incomplete purification

            # Build Prompt
            system_prompt = (
                "You are a text purification assistant, typically used for web content purification.\n"
                "Your task is to analyze the following text content with line numbers and identify lines that need to be deleted.\n"
                "You need to carefully delete the following: advertisements, noise in web pages (such as navigation bars or footer content that appears on every webpage, rather than information unique to the current page), repeatedly appearing information (keep the most important one), consecutive multiple blank lines and other format-only lines.\n"
                "You must be cautious when deleting, strive to avoid deleting valuable information, only delete very obvious junk information, do not purify for the sake of purifying.\n"
                "You must ensure the text after deletion is coherent and does not break the original structure and logic.\n"
                "Important: Strictly follow the format requirements, only output the list of line numbers to be deleted, separated by English commas, ensure it only contains numbers and commas, do not include any other text, explanations, spaces or newlines. For example: 3,5,10,11,25\n"
                "If all lines need to be kept, please return an empty string."
            )

            user_prompt_parts = []
            if criteria:
                user_prompt_parts.append(f"Please pay special attention to the following user requirements: ```\n{criteria}\n```")

            user_prompt_parts.append(f"\nText content to analyze is as follows:\n---\n{truncated_content}\n---")
            user_prompt = "\n".join(user_prompt_parts)

            # Build messages
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt}
            ]

            # Call LLM
            logger.debug(f"Sending purification request to LLM: model={DEFAULT_MODEL_ID}")
            response = await LLMFactory.call_with_tool_support(
                model_id=DEFAULT_MODEL_ID,
                messages=messages,
                tools=None,
                stop=None,
            )

            # Check if response is valid, content might be None or empty string
            if not response or not response.choices or len(response.choices) == 0 or response.choices[0].message is None:
                logger.error("LLM returned an invalid response structure")
                return None

            # content might be None or string
            llm_output_content = response.choices[0].message.content
            llm_output = llm_output_content.strip() if llm_output_content is not None else ""

            logger.debug(f"LLM returned raw output for line numbers to delete: '{llm_output}'")

            # Parse response, extract line numbers
            lines_to_remove_set: Set[int] = set()
            if llm_output: # Only attempt to parse when LLM returns a non-empty string
                # Use regex to extract all numbers
                line_numbers_str = re.findall(r'\d+', llm_output)
                # Check if it only contains numbers and commas (and possible spaces, already removed by strip)
                # If extracted numbers concatenated are inconsistent with original output (after removing non-numeric non-comma chars), it means there are illegal characters
                if not all(c.isdigit() or c == ',' for c in llm_output.replace(" ", "")):
                   logger.warning(f"LLM returned content contains characters other than numbers and commas: '{llm_output}'. Attempting to extract only numbers.")

                if not line_numbers_str and llm_output: # Returned non-empty but unable to extract numbers
                     logger.error(f"LLM returned non-empty content that cannot be parsed into a line number list: '{llm_output}'. Purification failed.")
                     return None

                for num_str in line_numbers_str:
                    try:
                        line_num = int(num_str)
                        if line_num > 0: # Line numbers start from 1
                             lines_to_remove_set.add(line_num)
                    except ValueError:
                        # This should theoretically never happen, because re.findall(r'\d+') only returns numeric strings
                        logger.warning(f"Error trying to convert '{num_str}' to integer, ignored. LLM raw output: '{llm_output}'")

            logger.info(f"Parsed line numbers to delete ({len(lines_to_remove_set)} lines): {sorted(list(lines_to_remove_set))}")

            # Debug log: use opt(lazy=True) for lazy evaluation, print complete deleted line content
            logger.opt(lazy=True).debug(
                "Lines to be deleted ({} lines):\n{}",
                lambda: len(lines_to_remove_set), # Parameter 1: line count
                lambda: "\n".join( # Parameter 2: concatenated content
                    f"  - Line {line_num}: {original_lines[line_num - 1]}"
                    if 0 < line_num <= len(original_lines) else f"  - Line {line_num}: (invalid line number)"
                    for line_num in sorted(list(lines_to_remove_set))
                )
            )

            # Filter content
            purified_lines = []
            for i, line in enumerate(original_lines):
                # Current line number is i + 1
                if (i + 1) not in lines_to_remove_set:
                    purified_lines.append(line)

            # Reassemble content
            final_content = "\n".join(purified_lines)

            # If truncated, add note
            if is_truncated:
                final_content += "\n\n(Note: Due to excessive original content length, this purification result is based on partial content)"

            return final_content

        except Exception as e:
            logger.exception(f"Content purification processing failed: {e!s}")
            return None

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """Generate tool detail for frontend display"""
        if not result.ok or not result.content or not result.extra_info:
            return None

        file_name = result.extra_info.get("file_name", "file")
        purified = result.extra_info.get("purified", False)

        if not purified: # When file is empty or processing failed, don't show special details
             # Can return a simple text hint, or None
             return ToolDetail(type=DisplayType.TEXT, data=result.content)

        # For successfully purified results, display as Markdown file
        try:
            # Try to keep original file extension, if unable to determine use .txt
            base_name, _, extension = file_name.rpartition('.')
            if not extension or len(extension) > 5: # Simple check if it's a valid extension
                extension = "txt"
            purified_file_name = f"{base_name or file_name}_purified.{extension}"

            return ToolDetail(
                type=DisplayType.MD, # Or decide based on file type? Simplified to MD here
                data=FileContent(
                    file_name=purified_file_name,
                    content=result.content # result.content is already the purified text
                )
            )
        except Exception as e:
            logger.error(f"Failed to generate purification tool detail: {e!s}")
            return None # Don't display details if error occurs

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Get friendly action and remark after tool call"""
        action = "Purify file"
        remark = "File purification completed" # Default remark

        if arguments and "file_path" in arguments:
            file_path = arguments["file_path"]
            file_name = file_path.split('/')[-1]
            if result.ok and result.extra_info and result.extra_info.get("purified", False):
                 remark = f"Purification of '{file_name}' completed"
            elif result.ok and result.extra_info and not result.extra_info.get("purified", False):
                 remark = f"File '{file_name}' does not need purification" # File is empty case
            elif not result.ok:
                 remark = f"Purification of '{file_name}' failed: {result.error or 'unknown error'}"
            else:
                 remark = f"Attempted to purify '{file_name}'" # Default success, but no special info

        return {
            "action": action,
            "remark": remark
        }

# Note: Confirm if aiofiles is in requirements.txt
# pip install aiofiles
