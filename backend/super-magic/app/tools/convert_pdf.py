import re
import urllib.parse
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, Optional

import aiofiles
import httpx
from pydantic import Field

from agentlang.config.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import generate_safe_filename
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.paths import PathManager
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.download_from_url import DownloadFromUrl, DownloadFromUrlParams
from app.tools.summarize import Summarize
from app.tools.workspace_guard_tool import WorkspaceGuardTool
from app.utils.pdf_converter_utils import convert_pdf_locally

logger = get_logger(__name__)

# Directory to save Markdown results
DEFAULT_RECORDS_DIR_NAME = "webview_reports"

def get_or_create_records_dir(dir_name: str = DEFAULT_RECORDS_DIR_NAME) -> Path:
    """Get or create records directory"""
    records_dir = PathManager.get_workspace_dir() / dir_name
    records_dir.mkdir(parents=True, exist_ok=True)
    return records_dir

def is_safe_path(base_path: Path, target_path_str: str) -> bool:
    """Check if target path is under base path and safe"""
    try:
        # Use resolve() to handle '..' and similar cases
        target_path = (base_path / target_path_str).resolve(strict=False) # strict=False allows resolving non-existent paths
        # Ensure resolved path is still under base path
        return target_path.is_relative_to(base_path.resolve(strict=True))
    except Exception:
        return False

class ConvertPdfParams(BaseToolParams):
    """PDF conversion tool parameters"""
    input_path: str = Field(
        ...,
        description="Source of input PDF, can be local file path (relative to workspace) or HTTP/HTTPS URL."
    )
    output_path: str = Field(
        "",
        description=(
            "Save path for output Markdown file (optional, relative to workspace). " +
            "If not provided: for URL sources, will auto-generate path and save in `webview_reports` directory; " +
            "(future plan) for local file sources, will save in same directory as source file, same name but with .md extension."
        )
    )
    mode: str = Field(
        "smart", # Default is smart mode
        description="Conversion mode: 'smart' (use external smart API to process URLs, may have higher quality but slower) or 'normal' (use local library to process local files and URLs, faster). If input is local file, will force 'normal' mode."
    )
    override: bool = Field(
        True,
        description="Whether to overwrite when output file already exists. Only effective when `output_path` is specified."
    )

@tool()
class ConvertPdf(AbstractFileTool[ConvertPdfParams], WorkspaceGuardTool[ConvertPdfParams]):
    """
    PDF conversion tool, converts specified PDF files (local path or URL) to Markdown format.

    Can specify output Markdown file save path (relative to workspace), if not specified, will be auto-handled.

    Suitable for:
    - Converting online PDF documents to Markdown format for reading or further processing.
    - Extracting text and basic structure from PDFs.

    Supported modes:
    - **smart (default)**: Uses external smart API to process URLs. May provide higher quality conversion results, but only supports URLs and may be slower.
    - **normal**: Uses built-in library for conversion. Supports local files and URLs, faster, but for complex or scanned PDFs effects may not be as good as smart mode.

    Requirements:
    - Input PDF path (`input_path`), can be workspace relative path or URL.
    - (Optional) Conversion mode (`mode`), defaults to 'smart'. If `input_path` is local file, will force 'normal' mode.
    - (Optional) Provide a safe workspace relative path (`output_path`) to save Markdown file. If not provided, will auto-generate path.
    - (Optional) Whether to overwrite existing file (`override`), defaults to true. Only effective when `output_path` is provided.

    Usage examples:
    ```
    {
        "input_path": "documents/report.pdf", // Local file
        "output_path": "webview_reports/converted_report.md"
    }
    ```
    ```
    {
        "input_path": "https://example.com/report.pdf",
        "output_path": "webview_reports/converted_report.md"
    }
    ```
    Or without specifying output path:
    ```
    {
        "input_path": "https://another.example.com/document.pdf",
        "mode": "normal" // Use local library to convert URL
    }
    ```
    ```
    {
        "input_path": "local_files/mydoc.pdf" // Local file; normal mode is enforced automatically
    }
    ```
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: ConvertPdfParams
    ) -> ToolResult:
        """Execute PDF conversion."""
        return await self.execute_purely(params)

    async def execute_purely(
        self,
        params: ConvertPdfParams
    ) -> ToolResult:
        """Execute core PDF conversion logic, no context required."""
        workspace_root = PathManager.get_workspace_dir()
        input_location = params.input_path
        target_output_path_str = params.output_path
        user_mode = params.mode.lower()
        override_output = params.override

        # --- 1. Determine input type and valid mode ---
        is_url = bool(re.match(r'^https?://', input_location))
        effective_mode = user_mode
        pdf_source_path: Optional[Path] = None # Source file path to be used for normal mode processing
        temp_download_path: Optional[Path] = None # Temporary path for URL download in normal mode

        try:
            if not is_url:
                logger.info(f"Input '{input_location}' recognized as local path, forcing 'normal' mode.")
                effective_mode = "normal"
                # Validate local path safety
                safe_path, error = self.get_safe_path(input_location)
                if error:
                    return ToolResult(error=error)
                if not await aiofiles.os.path.exists(safe_path) or await aiofiles.os.path.isdir(safe_path):
                    return ToolResult(error=f"Local file does not exist or is not a file: '{input_location}'")
                pdf_source_path = safe_path
            elif effective_mode == "normal":
                # URL input, but specified normal mode, need to download first
                logger.info(f"URL input '{input_location}', using 'normal' mode, will download file first.")
                # Need to call DownloadFromUrl here
                pass # Download logic will be implemented later
            elif effective_mode != "smart":
                 return ToolResult(error=f"Invalid mode '{params.mode}'. Please choose 'smart' or 'normal'.")

            logger.info(f"Executing PDF conversion: input='{input_location}', mode='{effective_mode}', output to='{target_output_path_str or 'auto-handled'}'")

            markdown_content: Optional[str] = None
            final_output_path: Optional[Path] = None # Absolute path to final Markdown

            # --- 2. Execute conversion (dispatch based on mode) ---
            if effective_mode == "smart":
                # Smart mode: call external API (supports URLs only)
                if not is_url:
                    # Theoretically shouldn't reach here, as local files force normal mode
                    return ToolResult(error="Internal error: Smart mode should not be used for local files.")

                # Get API configuration
                api_key = config.get("pdf_understanding.api_key")
                api_url = config.get("pdf_understanding.api_url")
                if not api_key or not api_url:
                    return ToolResult(error="Smart PDF conversion service not configured, please contact administrator.")

                headers = { "api-key": api_key, "Content-Type": "application/json" }
                payload = { "message": input_location, "conversation_id": "" }
                try:
                    async with httpx.AsyncClient(timeout=config.get("llm.api_timeout", 600)) as client:
                        response = await client.post(api_url, headers=headers, json=payload)
                        response.raise_for_status()
                    response_data = response.json()
                except httpx.HTTPStatusError as e:
                    logger.exception(f"Smart PDF conversion API request failed: status_code={e.response.status_code}, response={e.response.text}")
                    return ToolResult(error="Smart PDF conversion failed, error communicating with processing service.")
                except httpx.RequestError as e:
                    logger.exception(f"Smart PDF conversion API request could not be sent: {e}")
                    return ToolResult(error="Smart PDF conversion failed, unable to connect to processing service.")

                # Parse API response
                # --- API Response Parsing START ---
                if response_data.get("code") == 1000:
                    try:
                        content = response_data["data"]["messages"][0]["message"]["content"]
                        markdown_content = content if content else "<!-- PDF processed (smart mode), but no valid content extracted -->"
                        logger.info("Smart mode API call succeeded and content extracted.")
                    except (KeyError, IndexError, TypeError) as e:
                        logger.error(f"Failed to parse smart PDF conversion API response structure: {e}, response: {response_data}")
                        return ToolResult(error="Could not successfully parse smart PDF processing service response.")
                else:
                    error_message = response_data.get("message", "Unknown API error")
                    logger.error(f"Smart PDF conversion API error: code={response_data.get('code')}, message={error_message}, response: {response_data}")
                    return ToolResult(error="Smart PDF conversion failed, processing service returned error.")
                # --- API Response Parsing END ---

            elif effective_mode == "normal":
                # Normal mode: use local library (supports local files and downloaded URLs)

                if is_url:
                    # --- Download URL ---
                    download_tool = DownloadFromUrl()
                    # Create temporary download directory
                    temp_dir = workspace_root / ".cache" / "pdf_downloads"
                    temp_dir.mkdir(parents=True, exist_ok=True)
                    # Generate temporary filename
                    base_name = self._extract_source_name(input_location)
                    safe_base_name = generate_safe_filename(base_name) or "downloaded_pdf"
                    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
                    temp_pdf_filename = f"{safe_base_name}_{timestamp}.pdf"
                    temp_download_path = temp_dir / temp_pdf_filename

                    logger.info(f"Normal mode downloading URL '{input_location}' to temp file '{temp_download_path}'")
                    download_params = DownloadFromUrlParams(
                        url=input_location,
                        # Provide workspace-relative path to download tool
                        file_path=str(temp_download_path.relative_to(workspace_root)),
                        override=True # Overwrite temp file with same name
                    )
                    download_result = await download_tool.execute_purely(download_params)
                    if not download_result.ok:
                        logger.error(f"Normal mode PDF download failed: {download_result.error}")
                        return ToolResult(error=f"Failed to download PDF file: {download_result.error}")
                    pdf_source_path = temp_download_path # Update source path to downloaded file
                    logger.info(f"PDF successfully downloaded to: {pdf_source_path}")
                    # --- Download end ---

                # --- Call local conversion ---
                if not pdf_source_path or not await aiofiles.os.path.exists(pdf_source_path):
                    return ToolResult(error="Internal error: Cannot find source PDF file for local conversion.")

                logger.info(f"Normal mode calling local conversion to get text: {pdf_source_path}")
                # Directly get Markdown text content
                markdown_content = await convert_pdf_locally(pdf_source_path)

                if markdown_content is None:
                    logger.error(f"Normal mode local conversion failed: {pdf_source_path}")
                    return ToolResult(error=f"Failed to convert PDF file '{pdf_source_path.name}' using local library.")

                logger.info("Normal mode local conversion successful, Markdown text obtained.")
                # In normal mode, md_cache_path is one of the potential output files
                # If user didn't specify output_path, we use it

            # --- 3. Check conversion result ---
            # After processing by smart or normal mode, check if markdown_content has value
            if markdown_content is None: # Sanity check, should have been caught above
                logger.error(f"Failed to successfully get Markdown content (mode: {effective_mode}), input: {input_location}")
                # Return more specific error message
                return ToolResult(error=f"PDF conversion failed (mode: {effective_mode}), failed to get valid content.")

            # --- 4. Generate summary ---
            pdf_source_name = self._extract_source_name(input_location)
            summary = "Unable to generate summary."
            try:
                summarizer = Summarize()
                generated_summary = await summarizer.summarize_content(
                    content=markdown_content,
                    title=pdf_source_name,
                    max_length=500
                )
                if generated_summary:
                    summary = generated_summary
                else:
                     logger.warning(f"Failed to generate summary for PDF '{pdf_source_name}' (empty result); using default message.")
            except Exception as summary_e:
                logger.error(f"Exception while generating summary for PDF '{pdf_source_name}': {summary_e}", exc_info=True)

            # --- 5. Determine save path and save file ---
            workspace_root = PathManager.get_workspace_dir()
            saved_file_relative_path: Optional[str] = None

            try:
                if target_output_path_str:
                    # User specified path
                    safe_output_path, error = self.get_safe_path(target_output_path_str)
                    if error:
                         logger.error(f"Specified output path is unsafe or outside workspace: {target_output_path_str}")
                         return ToolResult(error=f"Specified output path '{target_output_path_str}' is unsafe or invalid: {error}")

                    # Check if file exists and if override is allowed
                    if await aiofiles.os.path.exists(safe_output_path) and not override_output:
                         logger.warning(f"Output file already exists and override not allowed: {safe_output_path}")
                         return ToolResult(error=f"Output file '{target_output_path_str}' already exists. Set override=True to overwrite.")

                    # Ensure parent directory exists
                    safe_output_path.parent.mkdir(parents=True, exist_ok=True)
                    final_output_path = safe_output_path

                else:
                    # User did not specify output path, uniformly generate in webview_reports
                    # if effective_mode == "normal":
                    #     # In Normal mode, if no output specified, result is cache file
                    #     if not md_cache_path: # Sanity check
                    #          return ToolResult(error="Internal error: Cannot determine default output path for normal mode.")
                    #     final_output_path = md_cache_path
                    #     logger.info(f"Normal mode no output path specified, will use cache file: {final_output_path}")
                    #
                    # elif effective_mode == "smart":
                    #     # Smart mode (must be URL source), save in webview_reports
                    #     if not is_url: # Sanity check
                    #         return ToolResult(error="Internal error: Smart mode should only process URLs.")
                    #
                        # New logic: all default outputs go to webview_reports
                        logger.info("No output path specified, will auto-generate filename in webview_reports.")
                        records_dir = get_or_create_records_dir()
                        safe_filename_base = generate_safe_filename(pdf_source_name) or "pdf_content"
                        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
                        filename = f"{safe_filename_base}_{timestamp}.md"
                        saved_file_absolute_path = records_dir / filename
                        final_output_path = records_dir / filename

                # Determine relative path
                saved_file_relative_path = str(final_output_path.relative_to(workspace_root))

                # Execute single write operation
                async with aiofiles.open(final_output_path, "w", encoding="utf-8") as f:
                    await f.write(markdown_content)
                logger.info(f"Conversion result written to final target file: {saved_file_relative_path}")

            except OSError as write_e:
                output_path_display = final_output_path or target_output_path_str or "unknown path"
                logger.error(f"Failed to save Markdown file: {output_path_display}, error: {write_e}", exc_info=True)
                return ToolResult(
                    error=f"Successfully converted PDF, but error occurred while saving Markdown file to '{saved_file_absolute_path}': {write_e}. Converted content in extra_info.",
                    extra_info={
                        "pdf_source_name": pdf_source_name,
                        "saved_file_path": None,
                        "full_content": markdown_content,
                    }
                )
            except Exception as e:
                logger.error(f"Unexpected error occurred while determining or creating save path: {e}", exc_info=True)
                return ToolResult(error="Internal error occurred while processing file save path.")

            # --- 6. Build return result ---
            ai_content = f"**PDF Content Summary**:\n{summary}"
            if saved_file_relative_path:
                ai_content += f"\n\n**Note**: Complete PDF content has been processed (mode: {effective_mode}) and saved to `{saved_file_relative_path}`. For details, use `read_file` tool to read this file."
            else:
                ai_content += "\n\n**Warning**: Complete content has been converted but failed to save to file, cannot be accessed via `read_file`."

            result = ToolResult(
                content=ai_content,
                extra_info={
                    "pdf_source_name": pdf_source_name,
                    "saved_file_path": saved_file_relative_path,
                    "full_content": markdown_content,
                }
            )
            return result

        except Exception as e:
            logger.exception(f"PDF conversion operation unexpectedly failed: {e!s}")
            return ToolResult(error="Unexpected internal error occurred while executing PDF conversion.")
        finally:
             # --- Clean up downloaded temporary file ---
             if temp_download_path and await aiofiles.os.path.exists(temp_download_path):
                 try:
                     await aiofiles.os.remove(temp_download_path)
                     logger.info(f"Cleaned up temporary download file: {temp_download_path}")
                 except OSError as remove_e:
                     logger.warning(f"Failed to clean up temporary download file {temp_download_path}: {remove_e}")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """Generate tool details for frontend display"""
        try:
            full_content = result.extra_info.get("full_content")
            saved_file_path = result.extra_info.get("saved_file_path")
            pdf_source_name = result.extra_info.get("pdf_source_name", "unknown source")

            if not full_content:
                logger.error("Cannot generate tool details: full_content missing or empty in extra_info.")
                return None # Core content missing

            # Determine display filename
            if saved_file_path:
                display_filename = Path(saved_file_path).name # Get filename from relative path
            else:
                 # If save failed, generate a temporary name
                 safe_filename_base = generate_safe_filename(pdf_source_name) or "converted_pdf"
                 display_filename = f"conversion_result_{safe_filename_base}.md"
                 logger.warning(f"Tool detail: saved_file_path is empty, using fallback filename: {display_filename}")


            return ToolDetail(
                type=DisplayType.MD,
                data=FileContent(
                    file_name=display_filename,
                    content=full_content
                )
            )
        except Exception as e:
            logger.error(f"Unexpected error occurred while generating tool details: {e}", exc_info=True)
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Get friendly action and remark after tool call"""
        pdf_source_name = "PDF document"
        if result.extra_info and "pdf_source_name" in result.extra_info:
            pdf_source_name = result.extra_info["pdf_source_name"]
        elif arguments and "input_path" in arguments: # Use input_path
             pdf_source_name = self._extract_source_name(arguments["input_path"])

        remark = f"Converted PDF: {pdf_source_name}"
        if result.ok and result.extra_info and result.extra_info.get("saved_file_path"):
            remark += f", saved to `{result.extra_info['saved_file_path']}`"
        elif result.error:
             remark += " (but encountered issues during processing or saving)"

        return {
            "action": "PDF Conversion",
            "remark": remark
        }

    def _extract_source_name(self, source_location_url: str) -> str:
        """Extract filename for display from PDF source URL"""
        try:
            parsed_url = urllib.parse.urlparse(source_location_url)
            path_part = parsed_url.path
            file_name = path_part.split('/')[-1]
            decoded_name = urllib.parse.unquote(file_name)
            base_name = decoded_name.split('?')[0]
            return base_name if base_name and base_name != '/' else "web PDF document"
        except Exception as e:
            logger.warning(f"Failed to extract filename from URL '{source_location_url}': {e}", exc_info=False)
            return "web PDF document"
