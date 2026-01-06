"""Browser navigation operation group

Contains navigation and scrolling operations.
"""

import os
from typing import Tuple
from urllib.parse import urlparse

from pydantic import Field

from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.tools.use_browser_operations.base import BaseOperationParams, OperationGroup, operation
from delightful_use.delightful_browser import GotoSuccess, DelightfulBrowser, DelightfulBrowserError, ScrollToSuccess

# Logger
logger = get_logger(__name__)

class GotoParams(BaseOperationParams):
    """Parameters for navigating to a URL"""
    url: str = Field(..., description="URL to navigate to")


# Define ScrollToParams for scroll_to operation
class ScrollToParams(BaseOperationParams):
    """Parameters for scrolling to a screen position"""
    screen_number: int = Field(..., description="Target screen number (starting from 1)", ge=1)


class NavigationOperations(OperationGroup):
    """Navigation operation group

    Includes navigation and scrolling operations.
    """
    group_name = "navigation"
    group_description = "Page navigation related operations"

    def _get_document_suggestion(self, url: str) -> str:
        """Provide document-handling suggestion based on URL"""
        # Parse URL and extension
        parsed_url = urlparse(url)
        path = parsed_url.path
        _, ext = os.path.splitext(path)
        ext = ext.lower()

        # Document types best downloaded directly
        document_extensions = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx']

        if ext in document_extensions:
            doc_type = ext[1:].upper()
            return f"For {doc_type} files, download directly instead of opening in-browser for reliable viewing."

        return ""

    def _check_invalid_url(self, url: str) -> Tuple[bool, str]:
        """Check for obviously invalid or placeholder URLs."""
        try:
            parsed_url = urlparse(url)
            domain = parsed_url.netloc.lower()

            # Placeholder/invalid domains
            invalid_domains = [
                'example.com', 'example.org', 'example.net',
                'test.com', 'test.org', 'test.net',
                'domain.com', 'domain.org', 'domain.net',
                'localhost', '127.0.0.1',
                'website.com', 'mywebsite.com', 'yourwebsite.com',
                '{actual_domain}'
            ]

            # Detect invalid domains
            if domain in invalid_domains or domain.startswith('example.') or domain.startswith('test.'):
                return True, f"Detected placeholder domain: {domain}. Please use a real URL."

            # Detect unreplaced placeholders
            if '{actual_domain}' in domain:
                return True, f"Detected placeholder domain: {domain}. Replace '{{actual_domain}}' with the real domain."

            return False, ""
        except Exception as e:
            logger.error(f"URL validation failed: {e!s}")
            return False, ""

    @operation(
        example={
            "operation": "goto",
            "operation_params": {
                "url": "https://www.google.com"
            }
        }
    )
    async def goto(self, browser: DelightfulBrowser, params: GotoParams) -> ToolResult:
        """Navigate to the specified URL.

        If page_id is not provided, a new page is created automatically.
        """
        url = params.url
        page_id_to_use = params.page_id

        # 1. Validate URL
        is_invalid, error_message = self._check_invalid_url(url)
        if is_invalid:
            logger.warning(f"Attempt to access invalid URL: {url}")
            return ToolResult(error=error_message)

        # 2. Validate provided page_id (goto handles None internally)
        if page_id_to_use:
            page, error_result = await self._get_validated_page(browser, params)
            if error_result: return error_result

        # 3. Call DelightfulBrowser.goto
        try:
            result = await browser.goto(page_id=page_id_to_use, url=url)

            # 4. Handle results
            if isinstance(result, DelightfulBrowserError):
                suggestion = self._get_document_suggestion(url)
                error_msg = f"{result.error}{f' {suggestion}' if suggestion else ''}".strip()
                return ToolResult(error=error_msg)
            elif isinstance(result, GotoSuccess):
                suggestion = self._get_document_suggestion(url)
                markdown_content = (
                    f"**action: goto**\n"
                    f"status: success ✓\n"
                    f"URL: `{result.final_url}`\n"
                    f"title: {result.title}\n"
                )
                if suggestion:
                    markdown_content += f"\n**Note**: {suggestion}"
                return ToolResult(content=markdown_content)
            else:
                logger.error(f"goto returned unexpected type: {type(result)}")
                return ToolResult(error="goto returned an unexpected result type.")

        except Exception as e:
            logger.error(f"goto outer handling failed: {e!s}", exc_info=True)
            error_msg = f"Unexpected error while navigating to {url}: {e!s}"
            suggestion = self._get_document_suggestion(url)
            if suggestion: error_msg += f" {suggestion}"
            return ToolResult(error=error_msg)

    @operation(
        example={
            "operation": "scroll_to",
            "operation_params": {
                "screen_number": 2
            }
        }
    )
    async def scroll_to(self, browser: DelightfulBrowser, params: ScrollToParams) -> ToolResult:
        """Scroll to an approximate screen position (based on viewport height)."""
        # 1. Get and validate page
        page, error_result = await self._get_validated_page(browser, params)
        if error_result: return error_result
        page_id = params.page_id or await browser.get_active_page_id()
        if not page_id:
            return ToolResult(error="Cannot determine page ID to scroll")

        # 2. Call DelightfulBrowser.scroll_to
        try:
            result = await browser.scroll_to(
                page_id=page_id,
                screen_number=params.screen_number
            )

            # 3. Handle results
            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, ScrollToSuccess):
                markdown_content = (
                    f"**action: scroll_to**\n"
                    f"status: success ✓\n"
                    f"target screen: {result.screen_number}\n"
                    f"target Y (approx): {result.target_y:.0f}px\n"
                    f"before (x,y): ({result.before.x:.0f}, {result.before.y:.0f})\n"
                    f"after (x,y): ({result.after.x:.0f}, {result.after.y:.0f})"
                )
                return ToolResult(content=markdown_content)
            else:
                logger.error(f"scroll_to returned unexpected type: {type(result)}")
                return ToolResult(error="scroll_to returned an unexpected result type.")

        except Exception as e:
            logger.error(f"scroll_to outer handling failed: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error while scrolling to screen {params.screen_number}: {e!s}")
