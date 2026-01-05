import os
from pathlib import Path
from typing import Any, Dict, Optional

import aiofiles
import aiofiles.os  # Keep this for os.path.exists etc.
from markitdown import MarkItDown, StreamInfo
from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.token_estimator import num_tokens_from_string
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.markitdown_plugins.csv_plugin import CSVConverter

# Keep Excel and CSV converters if needed for other types
from app.tools.markitdown_plugins.excel_plugin import ExcelConverter
from app.tools.workspace_guard_tool import WorkspaceGuardTool

# Import the new local PDF converter utility
from app.utils.pdf_converter_utils import convert_pdf_locally

logger = get_logger(__name__)

# Set maximum token limit
MAX_TOTAL_TOKENS = 30000


class ReadFileParams(BaseToolParams):
    """File reading parameters"""
    file_path: str = Field(..., description="The file path to read, either relative to working directory or absolute path")
    offset: int = Field(0, description="Starting line number to read (0-based)")
    limit: int = Field(200, description="Number of lines or pages to read, default 200 lines, set to -1 to read entire file")


@tool()
class ReadFile(AbstractFileTool[ReadFileParams], WorkspaceGuardTool[ReadFileParams]):
    """File content reading tool

    This tool can read file contents at specified paths, supporting text files, PDF, DOCX, and other formats.

    Supported file types:
    - Text files (.txt, .md, .py, .js, etc.)
    - PDF files (.pdf)
        - For reading scanned documents, use the more expensive convert_pdf tool to convert to Markdown format first
        - When reading PDF files, Markdown mapping files are automatically created, and subsequent reads will use these Markdown mapping files instead of the original files
        - You can use the read operation as a PDF conversion tool; for non-scanned PDF files, the automatically converted Markdown files can be used freely
    - Word documents (.docx)
    - Jupyter Notebooks (.ipynb)
    - Excel files (.xls, .xlsx)
    - CSV files (.csv)

    Notes:
    - Reading files outside the working directory is prohibited
    - Binary files may not be read correctly
    - Large files will be rejected; you must read partial content in segments to understand file overview
    - For Excel and CSV files, it is recommended to process data with code rather than use text content directly
    - To avoid overly long content, if a single read exceeds 30,000 tokens, content will be automatically truncated; if you need to read the complete content, you can read multiple times
    """

    # Maximum row limit for Excel processing
    EXCEL_MAX_ROWS = 1000
    EXCEL_MAX_PREVIEW_ROWS = 50

    md = MarkItDown()
    # Remove PDFConverter registration here, it's handled in the util
    md.register_converter(ExcelConverter())
    md.register_converter(CSVConverter())

    async def execute(self, tool_context: ToolContext, params: ReadFileParams) -> ToolResult:
        """
        Execute file reading operation

        Args:
            tool_context: Tool context
            params: File reading parameters

        Returns:
            ToolResult: Contains file content or error information
        """
        return await self.execute_purely(params)

    async def execute_purely(self, params: ReadFileParams) -> ToolResult:
        """
        Execute file reading operation without tool context parameters

        Args:
            params: File reading parameters

        Returns:
            ToolResult: Contains file content or error information
        """
        try:
            # Use parent class method to get safe file path
            file_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(error=error)

            original_file_name = file_path.name # Store original name before path changes
            read_path = file_path  # Default to read the original file path
            cache_just_created = False # Flag to indicate if cache was created in this call
            # Mark whether cache was created in this call

            # --- PDF cache handling logic ---
            if file_path.suffix.lower() == '.pdf':
                cache_md_path = file_path.with_suffix('.md')
                try:
                    cache_exists = await aiofiles.os.path.exists(cache_md_path)

                    if cache_exists:
                        logger.info(f"Using cache file: {cache_md_path} to read PDF content: {file_path}")
                        read_path = cache_md_path # Set read path to cache file
                        cache_just_created = False
                    else:
                        logger.info(f"Cache file {cache_md_path} does not exist, attempting local conversion: {file_path}")
                        # Call utility function to get Markdown text
                        markdown_content = await convert_pdf_locally(file_path)

                        if markdown_content is None:
                            # Conversion failed
                            logger.error(f"Local PDF conversion failed, unable to read: {file_path}")
                            return ToolResult(error=f"Unable to convert PDF file '{file_path.name}' to Markdown.")

                        # Conversion successful, write cache file
                        try:
                            async with aiofiles.open(cache_md_path, "w", encoding="utf-8") as cache_f:
                                await cache_f.write(markdown_content)
                            logger.info(f"Successfully created PDF cache file: {cache_md_path}")
                            cache_just_created = True # Mark cache as just created
                            read_path = cache_md_path # Set read path to newly created cache
                        except Exception as write_e:
                            logger.exception(f"Failed to write PDF cache file ({cache_md_path}): {write_e!s}")
                            # Even if writing cache fails, try to return converted content but don't set read_path
                            # Or return an error? Here we choose to return an error because we can't guarantee consistency
                            return ToolResult(error=f"PDF conversion succeeded but writing cache file '{cache_md_path.name}' failed: {write_e!s}")

                except Exception as e:
                    # Capture errors during cache checking or conversion/write process
                    logger.exception(f"Error processing PDF ({file_path}): {e!s}")
                    return ToolResult(error=f"Error processing PDF: {e!s}")
            # --- PDF cache handling logic ends ---


            # Check if the final read_path is valid
            if not await aiofiles.os.path.exists(read_path):
                 # This might happen if PDF conversion failed silently, cache was deleted, or it's another non-existent file
                 # If PDF conversion fails silently, cache is deleted, or this is another non-existent file, this may happen
                 return ToolResult(error=f"Unable to find file to read: {read_path} (original request: {original_file_name})")
            if await aiofiles.os.path.isdir(read_path):
                # If the PDF cache path is a directory, also report an error
                return ToolResult(error=f"Read path is a directory: {read_path} (original request: {original_file_name}), please use list_dir tool to get directory contents")

            # --- Content reading logic ---
            read_extension = read_path.suffix.lower()
            # Define non-text extensions that need MarkItDown processing (excluding .pdf and .md)
            markitdown_extensions = {".ipynb", ".csv", ".xlsx", ".xls", ".docx"} # Add or remove required formats

            content: str = ""
            is_binary = await self._is_binary_file(read_path)

            # Determine whether to use MarkItDown
            use_markitdown = (
                read_extension in markitdown_extensions or
                (is_binary and read_extension not in {".md", ".txt", ".py", ".js", ".json", ".yaml", ".html", ".css"}) # Example common text types
            )

            if use_markitdown:
                 logger.info(f"File {read_path} (original: {original_file_name}) using markitdown for reading")
                 try:
                     # MarkItDown requires binary reading
                     async with aiofiles.open(read_path, "rb") as f:
                         # Pass original offset and limit to MarkItDown (except during PDF cache creation phase)
                         # Note: offset and limit passed here are from user's original request
                         result = self.md.convert(f, stream_info=StreamInfo(extension=read_extension), offset=params.offset, limit=params.limit)
                         if not result or not result.markdown:
                             logger.warning(f"MarkItDown conversion returned empty content: {read_path}")
                             content = "[File conversion result is empty]"
                         else:
                             content = result.markdown
                 except Exception as e:
                     logger.exception(f"Failed to read file using MarkItDown ({read_path}): {e!s}")
                     return ToolResult(error=f"File conversion failed: {e!s}")
            else:
                 # Use text reading logic (including reading .md cache)
                 logger.info(f"File {read_path} (original: {original_file_name}) using text reading logic")
                 if params.limit is None or params.limit <= 0:
                     content = await self._read_text_file(read_path)
                 else:
                     content = await self._read_text_file_with_range(
                         read_path, params.offset, params.limit
                     )
            # --- Content reading logic ends ---


            # Calculate token count and handle truncation
            content_tokens = num_tokens_from_string(content)
            total_chars = len(content)
            content_truncated = False

            if content_tokens > MAX_TOTAL_TOKENS:
                logger.info(f"File {read_path.name} (original: {original_file_name}) content token count ({content_tokens}) exceeds limit ({MAX_TOTAL_TOKENS}), truncating")
                content_truncated = True

                # Use binary search to find the best truncation point
                left, right = 0, len(content)
                best_content = ""
                best_tokens = 0

                while left <= right:
                    mid = (left + right) // 2
                    truncated = content[:mid]
                    tokens = num_tokens_from_string(truncated)

                    if tokens <= MAX_TOTAL_TOKENS:
                        best_content = truncated
                        best_tokens = tokens
                        left = mid + 1
                    else:
                        right = mid - 1

                content = best_content
                content_tokens = best_tokens
                truncation_note = f"\n\n[Content truncated: original token count exceeds {MAX_TOTAL_TOKENS} limit]"
                content += truncation_note

            # Add file metadata - use original_file_name as file name shown to user, read_path for internal info
            shown_chars = len(content)
            truncation_status = " (Truncated)" if content_truncated else ""
            meta_info = f"# File: {original_file_name}\n\n**File Info**: Total characters: {total_chars}, Display characters: {shown_chars}{truncation_status}, Tokens: {content_tokens}"
            if str(read_path) != str(file_path): # Only add if reading from a different path (e.g., cache)
                meta_info += f" (Read from: `{read_path.name}`)" # Use backticks for filename
            meta_info += "\n\n---\n\n" # Correct newline escaping
            raw_content = content # Store original content without meta_info
            extra_info = {
                "raw_content": raw_content,
                "original_file_path": str(file_path),
                "read_path": str(read_path),
                "cache_just_created": cache_just_created # Also include cache creation status
            }

            # --- If applicable, append cache creation notification here ---
            if cache_just_created:
                cache_note = f"\n\n*Note: First read, the contents of source PDF file '{original_file_name}' have been converted to Markdown file '{read_path.name}' and cached. Subsequent reads of this PDF will directly use this cache file.*"
                # Ensure cache_note is added to final content
                # If content is truncated, append to truncated content
                # Append cache note to the raw_content BEFORE meta_info is prepended
                raw_content += cache_note
            # --- Cache notification ends ---

            # Construct final content with meta info prepended to the potentially modified raw_content
            content_with_meta = meta_info + content # Use potentially truncated content

            return ToolResult(
                content=content_with_meta,
                extra_info=extra_info
            )

        except Exception as e:
            logger.exception(f"Failed to read file (original request: {params.file_path}): {e!s}")
            return ToolResult(error=f"Failed to read file: {e!s}")

    async def _is_binary_file(self, file_path: Path) -> bool:
        """Check whether the file is a binary file"""
        try:
            # Read first 4KB of file to determine if it is binary
            chunk_size = 4 * 1024
            async with aiofiles.open(file_path, "rb") as f:
                chunk = await f.read(chunk_size)

                # If exactly at 4KB boundary, might truncate UTF-8 multi-byte chars, read more bytes
                if len(chunk) == chunk_size:
                    # Seek back to start
                    await f.seek(0)
                    # Read more bytes to ensure complete UTF-8 characters
                    chunk = await f.read(chunk_size + 4)

            # Check for NULL byte (characteristic of binary files)
            if b"\x00" in chunk:
                return True

            # Try UTF-8 decoding, if it fails might be binary file
            try:
                chunk.decode("utf-8")
                return False
            except UnicodeDecodeError:
                # Try ignore error handling; if content mostly parses as text, don't consider it binary
                decoded = chunk.decode("utf-8", errors="ignore")
                # If decoded text length is at least 25% of original data, consider it text file
                if len(decoded) > len(chunk) * 0.25:
                    return False
                return True
        except Exception:
            # On error (e.g. permission denied), assume not binary or handle appropriately
            # When error occurs (e.g. permission denied), handle appropriately or assume not binary
            logger.warning(f"Unable to determine if file is binary: {file_path}", exc_info=True)
            return False # Default to not binary if unsure
            # If uncertain, default to non-binary

    async def _read_text_file(self, file_path: Path) -> str:
        """Read entire text file content"""
        async with aiofiles.open(file_path, "r", encoding="utf-8", errors="replace") as f:
            return await f.read()

    async def _read_text_file_with_range(self, file_path: Path, offset: int, limit: int) -> str:
        """Read text file content within specified range

        Args:
            file_path: File path
            offset: Starting line number (0-based)
            limit: Number of lines to read, if negative read to end of file

        Returns:
            String containing line number info and content within range, return prompt if range invalid
        """
        # Count total lines and read specified range content
        all_lines = []
        target_lines = []

        async with aiofiles.open(file_path, "r", encoding="utf-8", errors="replace") as f:
            line_idx = 0
            async for line in f:
                all_lines.append(line)
                # Apply offset and limit based on line index
                if limit > 0: # If limit is positive, read limit lines starting from offset
                    if offset <= line_idx < offset + limit:
                        target_lines.append(line)
                elif offset <= line_idx: # If limit is non-positive (<=0 or None), read from offset to end
                    target_lines.append(line)
                line_idx += 1

        total_lines = len(all_lines)
        start_line = offset + 1  # Convert to 1-indexed for user understanding

        # Build result header information
        if not target_lines:
            if offset >= total_lines:
                header = f"# Read content is empty: starting line {start_line} exceeds total file lines {total_lines}\n\n"
            else:
                # Calculate the intended end line based on limit
                # Based on limit, calculate intended end line number
                end_line_intended = (offset + limit) if limit > 0 else total_lines
                header = f"# Read content is empty: specified range from line {start_line} to {end_line_intended} has no content (file has {total_lines} lines)\n\n"
            return header
        else:
            # Actual end line is offset + number of lines read
            # Actual end line is offset + number of lines read
            end_line_actual = offset + len(target_lines)
            header = f"# Showing lines {start_line} to {end_line_actual} (file has {total_lines} lines)\n\n"

        content = "".join(target_lines)

        # Add ellipsis annotations
        has_prefix = offset > 0
        has_suffix = end_line_actual < total_lines

        if has_prefix:
            prefix_lines = offset
            prefix = f"# ... Previous {prefix_lines} lines ...\n\n"
            content = prefix + content

        if has_suffix:
            suffix_lines = total_lines - end_line_actual
            suffix = f"\n\n# ... {suffix_lines} more lines below ..."
            content = content + suffix

        return header + content

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get corresponding ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution arguments dictionary

        Returns:
            Optional[ToolDetail]: Tool detail object, may be None
        """
        if not result.ok or not result.extra_info or "raw_content" not in result.extra_info:
            return None

        # Get path from extra_info
        original_file_path_str = result.extra_info.get("original_file_path")
        read_path_str = result.extra_info.get("read_path")

        if not original_file_path_str or not read_path_str:
             logger.warning("Unable to get original_file_path or read_path from extra_info, trying fallback from arguments")
             # Can try fallback from arguments or return None directly
             if arguments and "file_path" in arguments:
                 original_file_path_str = arguments["file_path"]
                 # If no read_path, can only guess it's the same as original
                 read_path_str = read_path_str or original_file_path_str
             else:
                  logger.error("Unable to determine file path information, unable to generate ToolDetail")
                  return None


        original_file_name = os.path.basename(original_file_path_str)

        # Determine display type based on the ACTUAL file read (could be .md cache)
        # Use AbstractFileTool's method based on the path that was actually read
        display_type = self.get_display_type_by_extension(read_path_str)

        # If the read path was a generated .md (from PDF cache), force TEXT or MARKDOWN display type
        if read_path_str.endswith('.md') and original_file_path_str.endswith('.pdf'):
             display_type = DisplayType.TEXT # Or potentially a specific MARKDOWN type if exists
             # Or use specific MARKDOWN type if it exists

        return ToolDetail(
            type=display_type,
            data=FileContent(
                # Show the original requested filename to the user
                # Show original requested filename to user
                file_name=original_file_name,
                # Content is the raw content (without meta) from the read file (could be .md)
                # Content is raw content (without metadata) from read file (could be .md)
                content=result.extra_info["raw_content"]
            )
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        # Use the original file path requested by the user
        file_path_str = arguments.get("file_path", "")
        file_name = os.path.basename(file_path_str) if file_path_str else "file"

        if not result.ok:
            # Keep error reporting simple, using the original file name
            # Use original file name to keep error reporting concise
            return {
                "action": "Read file",
                "remark": f"Failed to read '{file_name}': {result.error or result.content}" # Use error if available
                # Use error if available, otherwise use content
            }

        # Success message always refers to the original requested file
        # Success message always refers to original requested file
        return {
            "action": "Read file",
            "remark": f"'{file_name}'"
        }
