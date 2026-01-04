import os
from pathlib import Path
from typing import Any, Dict, NamedTuple, Optional

import aiofiles
import aiohttp
from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import generate_safe_filename
from app.core.entity.message.server_message import FileContent, ToolDetail
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class DownloadFromUrlParams(BaseToolParams):
    """Parameters for downloading a file from a URL."""
    url: str = Field(
        ...,
        description="File URL to download; supports HTTP and HTTPS."
    )
    file_path: str = Field(
        ...,
        description=(
            "Path and filename relative to the workspace root. For example, to save under "
            "`webview_reports` as `file.txt`, pass `webview_reports/file.txt`. Use clear "
            "filenames (e.g., `annual_report.pdf`) for easier lookup. Missing directories are created."
        )
    )
    override: bool = Field(
        False,
        description="Whether to overwrite if the file already exists."
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Custom error messages for missing parameters."""
        if field_name == "url":
            return "Missing required parameter 'url'. Please provide a valid download URL."
        elif field_name == "file_path":
            return "Missing required parameter 'file_path'. Please provide a save path."
        return None


class DownloadResult(NamedTuple):
    """Download result details."""
    file_size: int  # File size in bytes
    content_type: str  # Content type
    file_exists: bool  # Whether the file existed already
    file_path: str  # Saved file path
    url: str  # Final URL (after redirects)
    redirect_count: int  # Number of redirects


@tool()
class DownloadFromUrl(AbstractFileTool[DownloadFromUrlParams], WorkspaceGuardTool[DownloadFromUrlParams]):
    """
    URL file download tool.

    - Handles redirects automatically
    - Creates missing files/directories as needed
    - Errors when the file exists and overwrite is disallowed
    - Supports many file types (images, PDFs, archives, etc.)
    """

    async def execute(self, tool_context: ToolContext, params: DownloadFromUrlParams) -> ToolResult:
        """Perform the file download."""
        try:
            # Use parent method to get safe path (handles directory structure)
            full_path, error = self.get_safe_path(params.file_path)
            if error:
                return ToolResult(error=error)

            # Extract parent dir and original filename
            parent_dir = full_path.parent
            original_name = full_path.name

            # Split base name and extension
            base_name, extension = os.path.splitext(original_name)

            # Sanitize base name
            safe_base_name = generate_safe_filename(base_name)

            # If empty, try deriving from URL or use default name
            if not safe_base_name:
                from urllib.parse import urlparse
                try:
                    parsed_url = urlparse(params.url)
                    url_filename = os.path.basename(parsed_url.path)
                    if url_filename: # Ensure URL-derived name is not empty
                        url_base_name, url_extension = os.path.splitext(url_filename)
                        safe_base_name = generate_safe_filename(url_base_name) if url_base_name else "downloaded_file"
                        # Use the extension from the URL if available and original was missing or different
                        if url_extension and (not extension or extension.lower() != url_extension.lower()):
                            extension = url_extension
                    else:
                        safe_base_name = "downloaded_file"
                except Exception:
                    safe_base_name = "downloaded_file" # Fallback default name

            # Recombine safe file name and extension; ensure dot prefix
            if extension and not extension.startswith('.'):
                 extension = '.' + extension
            safe_name = safe_base_name + extension

            # Rebuild final file path
            file_path = parent_dir / safe_name

            # Check existence
            file_exists = file_path.exists()

            # Error when existing and overwrite not allowed
            if file_exists and not params.override:
                return ToolResult(
                    error="File already exists; set override=True to replace it\n"
                    f"file_path: {file_path!s}"
                )

            # Create directories as needed
            await self._create_directories(file_path)

            # Download file
            download_result = await self._download_file(params.url, file_path)

            # Dispatch file event
            event_type = EventType.FILE_UPDATED if download_result.file_exists else EventType.FILE_CREATED
            await self._dispatch_file_event(tool_context, str(file_path), event_type)

            # Build formatted output
            output = (
                f"Download succeeded: {file_path} | "
                f"Size: {self._format_size(download_result.file_size)} | "
                f"Type: {download_result.content_type} | "
                f"Redirects: {download_result.redirect_count}"
            )

            # Return result
            return ToolResult(content=output)

        except Exception as e:
            logger.exception(f"File download failed: {e!s}")
            return ToolResult(error=f"File download failed: {e!s}")

    async def _create_directories(self, file_path: Path) -> None:
        """Create directories required for the file path."""
        directory = file_path.parent

        if not directory.exists():
            os.makedirs(directory, exist_ok=True)
            logger.info(f"Created directory: {directory}")

    async def _download_file(self, url: str, file_path: Path) -> DownloadResult:
        """Download file content."""
        redirect_count = 0
        final_url = url
        content_type = ""
        file_exists = file_path.exists()

        async with aiohttp.ClientSession() as session:
            # Allow redirects and track count
            async with session.get(url, allow_redirects=True) as response:
                # Validate response status
                if response.status != 200:
                    raise Exception(f"Download failed, HTTP status: {response.status}, reason: {response.reason}")

                # Final URL after redirects
                final_url = str(response.url)
                # Count redirects
                if hasattr(response, 'history'):
                    redirect_count = len(response.history)

                # Get content type
                content_type = response.headers.get('Content-Type', 'unknown')

                # Write content to file
                async with aiofiles.open(file_path, 'wb') as f:
                    file_size = 0
                    # Stream in chunks to avoid memory pressure
                    async for chunk in response.content.iter_chunked(8192):
                        await f.write(chunk)
                        file_size += len(chunk)

        logger.info(f"Download complete: {file_path}, size: {file_size} bytes")

        return DownloadResult(
            file_size=file_size,
            content_type=content_type,
            file_exists=file_exists,
            file_path=str(file_path),
            url=final_url,
            redirect_count=redirect_count
        )

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Build a ToolDetail entry from the execution result and arguments.
        """
        if not result.ok:
            return None

        if not arguments or "file_path" not in arguments:
            logger.warning("file_path parameter not provided")
            return None

        file_path = arguments["file_path"]
        file_path_path, _ = self.get_safe_path(file_path)
        if not file_path_path or not file_path_path.exists():
            return None

        file_name = os.path.basename(file_path)

        # Use AbstractFileTool helper to pick display type
        display_type = self.get_display_type_by_extension(file_path)

        # For binary/large files we return file name only (no content)
        return ToolDetail(
            type=display_type,
            data=FileContent(
                file_name=file_name,
                content="" # No content for large/binary files
            )
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Provide a friendly action/remark summary after tool call.
        """
        if not arguments:
            return {"action": "Download file", "remark": "Unknown file"}

        url = arguments.get("url", "")
        remark = url if url else "Unknown URL"

        return {
            "action": "Download file",
            "remark": remark
        }

    def _format_size(self, size_bytes: int) -> str:
        """Format file size for display."""
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size_bytes < 1024.0 or unit == 'TB':
                return f"{size_bytes:.2f} {unit}" if unit != 'B' else f"{size_bytes} {unit}"
            size_bytes /= 1024.0
