"""Browser content read operations

Includes actions for reading and converting page content.
"""

import datetime
import os
from pathlib import Path
from typing import Literal, Optional, Union

from pydantic import Field

from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import generate_safe_filename_with_timestamp
from app.paths import PathManager
from app.tools.purify import Purify
from app.tools.summarize import Summarize
from app.tools.use_browser_operations.base import BaseOperationParams, OperationGroup, operation
from app.tools.visual_understanding import VisualUnderstanding, VisualUnderstandingParams
from delightful_use.delightful_browser import (
    DelightfulBrowser,
    DelightfulBrowserError,
    MarkdownSuccess,
    PageStateSuccess,
    ScreenshotSuccess,
)

# Logger
logger = get_logger(__name__)

# Markdown record directory name
MARKDOWN_RECORDS_DIR_NAME = "webview_reports"
# Markdown records directory path (not created yet)
MARKDOWN_RECORDS_DIR = PathManager.get_workspace_dir() / MARKDOWN_RECORDS_DIR_NAME

def get_or_create_markdown_records_dir() -> Path:
    """Ensure markdown record directory exists and return its path."""
    MARKDOWN_RECORDS_DIR.mkdir(exist_ok=True)
    return MARKDOWN_RECORDS_DIR


def _add_markdown_metadata(content: str, title: str, url: str, scope: str, current_screen: Optional[int] = None, summary: Optional[str] = None) -> str:
    """Add metadata header to markdown content."""
    now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    # Build metadata header (summary optional)
    header_lines = [
        f"# {title}",
        "",
        "> This markdown file was generated from webpage content",
        f"> Generated at: {now}",
        f"> Page URL: [{url}]({url})",
        f"> Content scope: {scope}{f' (Screen {current_screen})' if scope == 'viewport' and current_screen is not None else ''}"
    ]
    if summary:
        header_lines.append(f"> Content summary: {summary}")
    header_lines.extend([
        "",
        "## Original content",
        ""
    ])

    header = "\n".join(header_lines)
    return header + content


class ReadAsMarkdownParams(BaseOperationParams):
    """Parameters for reading page as markdown"""
    scope: Literal["viewport", "all"] = Field("viewport", description="Content scope: viewport (current viewport) or all (entire page)")
    purify: Union[bool, str] = Field(
        False,
        description="Whether to purify page content and which standard to use. False: no purify; True: common standard; string: custom criteria. Purify attempts to remove ads/nav/etc. but may remove useful content."
    )


class VisualQueryParams(BaseOperationParams):
    """Parameters for visual query"""
    query: str = Field(..., description="Question/analysis prompt about current page screenshot")


class ContentOperations(OperationGroup):
    """Content operation group for reading/converting page content."""
    group_name = "content"
    group_description = "Page content read related operations"

    @operation(
        example=[
            {
                "operation": "read_as_markdown",
                "operation_params": {
                    "scope": "viewport"
                }
            },
            {
                "operation": "read_as_markdown",
                "operation_params": {
                    "scope": "all"
                }
            },
            {
                "operation": "read_as_markdown",
                "operation_params": {
                    "scope": "all",
                        "purify": "Keep only main body content" # custom purification example
                }
            }
        ]
    )
    async def read_as_markdown(self, browser: DelightfulBrowser, params: ReadAsMarkdownParams) -> ToolResult:
        """Read page content as Markdown.

        Retrieves all content on the page, including text, links, and images. Images are returned as links only; to understand image content, combine `visual_query` with `scroll_to` to analyze the page progressively.

        `scope` parameter (default `viewport`):
        - `viewport`: Reads only the current viewport and returns full content directly.
        - `all`: Reads the entire page, returns summary information only, and saves the full content to the working directory under `webview_reports`. Use the saved file to view the full content; this operation is expensive.

        `purify` parameter (default `false`):
        - `true`: Apply the default purification to remove ads/navigation/footer and other non-body elements. Helpful for article extraction but may delete useful parts.
        - `false`: Keep the original page structure without purification.
        - `string`: Use the string as custom purification guidance, e.g. "Keep main article body, remove related recommendations".

        Avoid invoking this action repeatedly with identical parameters to prevent saving duplicate files.
        """
        # 1) get and validate page
        _, error_result = await self._get_validated_page(browser, params)
        if error_result: return error_result
        page_id = params.page_id or await browser.get_active_page_id()
        if not page_id:
            return ToolResult(error="Unable to determine page ID for read_as_markdown")

        # 2) call DelightfulBrowser.read_as_markdown
        try:
            result = await browser.read_as_markdown(page_id=page_id, scope=params.scope)

            # 3) process returned result
            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, MarkdownSuccess):
                # Extract data
                markdown_text = result.markdown
                url = result.url
                title = result.title
                scope = result.scope

                # Decide whether to purify content
                purification_applied = False
                purification_criteria = None
                if params.purify is not False: # any truthy or string triggers purification
                    if isinstance(params.purify, str):
                        purification_criteria = params.purify
                        logger.info(f"Requesting markdown purification with custom criteria: '{purification_criteria}'")
                    else: # params.purify is True
                        logger.info("Requesting markdown purification with default criteria...")

                    purified_markdown = await self._purify_content(markdown_text, criteria=purification_criteria)
                    if purified_markdown != markdown_text: # only mark when content changed
                        markdown_text = purified_markdown
                        purification_applied = True
                        logger.info("Markdown content purified.")
                    else:
                        logger.info("Markdown content unchanged after purification attempt; using original content.")
                else:
                    logger.info("Skipped markdown purification (purify=False).")

                # Only generate summary when scope == 'all'
                summary = None
                if scope == "all":
                    summary = await self._generate_summary(title, markdown_text)

                # --- filename generation logic ---
                records_dir = get_or_create_markdown_records_dir()
                safe_title = generate_safe_filename_with_timestamp(title)
                filename_suffix = scope # default "all" or "viewport"
                current_screen_for_metadata: Optional[int] = None # used for metadata

                # When reading viewport, try to capture current screen index
                if scope == "viewport":
                    try:
                        page_state_result = await browser.get_page_state(page_id=page_id)
                        if isinstance(page_state_result, PageStateSuccess) and page_state_result.state.position_info:
                            current_screen = int(page_state_result.state.position_info.current_screen)
                            filename_suffix = f"part{current_screen}" # use part{n}
                            current_screen_for_metadata = current_screen # store for metadata
                            logger.info(f"Captured current screen index: {current_screen}, suffix set to: {filename_suffix}")
                        else:
                            logger.warning(f"Unable to get screen index for page {page_id}; using suffix 'viewport'")
                            filename_suffix = "viewport"
                    except Exception as state_exc:
                        logger.warning(f"Error while fetching state for page {page_id} to determine screen index: {state_exc!s}; using suffix 'viewport'")
                        filename_suffix = "viewport"

                # Build filename with suffix
                filename = f"{safe_title}_{filename_suffix}.md"
                # --- filename generation end ---

                file_path = records_dir / filename
                markdown_text_with_metadata = _add_markdown_metadata(
                    content=markdown_text,
                    title=title,
                    url=url,
                    scope=scope,
                    current_screen=current_screen_for_metadata,
                    summary=summary
                )
                try:
                    with open(file_path, "w", encoding="utf-8") as f:
                        f.write(markdown_text_with_metadata)
                    relative_path = os.path.join(MARKDOWN_RECORDS_DIR_NAME, filename)
                except Exception as write_e:
                    logger.error(f"Failed to save markdown file: {file_path}, error: {write_e}", exc_info=True)
                    return ToolResult(error=f"Failed to save markdown file: {write_e}")

                # Format success result based on scope
                markdown_result_header = (
                    f"**action: read_as_markdown**\n"
                    f"Status: success\n"
                    f"Scope: {scope}{f' (screen {current_screen})' if scope == 'viewport' and filename_suffix.startswith('part') else ''}\n" # show screen index when available
                    f"Purified: {'yes' if purification_applied else 'no'}"
                )
                if purification_applied:
                    markdown_result_header += f" (criteria: {'default' if purification_criteria is None else f'custom - {purification_criteria}'})"
                markdown_result_header += f"{' Note: purification may remove valuable content' if purification_applied else ''}\n"
                markdown_result_header += (
                    f"title: {title}\n"
                    f"Saved at: `{relative_path}`\n"
                )

                if scope == "all":
                    # When reading full page, include summary and hint
                    markdown_result_content = f"\n**content summary**:\n{summary}" # summary exists here
                    markdown_result_tip = "\n\n**Note**: Full page content saved to file; read it to view detailed content."
                    markdown_result = markdown_result_header + markdown_result_content + markdown_result_tip
                else: # scope == 'viewport'
                    # When reading viewport, include full content and hint
                    markdown_result_content = f"\n**content detail**:\n{markdown_text}"
                    screen_info = f" (screen {current_screen})" if 'current_screen' in locals() and current_screen is not None else ""
                    markdown_result_tip = (
                        "\n\n**Note**: Viewport content saved."  \
                        " Scroll (e.g. `scroll_down`) and read again for other parts, or use `scope: all` to read the full page at once."
                    )
                    markdown_result = markdown_result_header + markdown_result_content + markdown_result_tip

                return ToolResult(content=markdown_result)
            else:
                logger.error(f"read_as_markdown returned unknown type: {type(result)}")
                return ToolResult(error="read_as_markdown returned an unexpected result type")

        except Exception as e:
            logger.error(f"read_as_markdown encountered an unexpected error: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error while reading markdown: {e!s}")

    @operation(
        example=[{
            "operation": "visual_query",
            "operation_params": {
                "query": "Describe the current page's style and color palette"
            },
            "operation": "visual_query",
            "operation_params": {
                "query": "Extract the content of article images on this page as structured Markdown"
            }
        }]
    )
    async def visual_query(self, browser: DelightfulBrowser, params: VisualQueryParams) -> ToolResult:
        """Use visual understanding to analyze the current viewport. Combine with `scroll_to` to inspect layout/style/elements across the page, or to interpret image-heavy pages. Visual analysis cannot extract link URLs; use `read_as_markdown` or `get_interactive_elements` if URLs are needed.
        """
        # 1) get and validate page
        page, error_result = await self._get_validated_page(browser, params)
        if error_result: return error_result
        page_id = params.page_id or await browser.get_active_page_id()
        if not page_id:
            return ToolResult(error="Unable to determine page ID for visual_query")

        logger.info(f"Starting visual_query: page={page_id}, query='{params.query}'")

        try:
            # 2) Screenshot current viewport via DelightfulBrowser temp file
            logger.info(f"Requesting screenshot for page {page_id} to run visual_query...")
            # Always use temp file for screenshot
            screenshot_result = await browser.take_screenshot(page_id=page_id, path=None, full_page=False)

            if isinstance(screenshot_result, DelightfulBrowserError):
                logger.error(f"visual_query screenshot failed: {screenshot_result.error}")
                return ToolResult(error=f"visual_query screenshot failed: {screenshot_result.error}")
            elif not isinstance(screenshot_result, ScreenshotSuccess):
                logger.error(f"take_screenshot returned unexpected type: {type(screenshot_result)}")
                return ToolResult(error="take_screenshot returned an unexpected result type")

            screenshot_path = screenshot_result.path
            # Temp file managed by DelightfulBrowser; no need to track is_temp here
            logger.info(f"visual_query screenshot saved at: {screenshot_path}")

            # 3) Call visual understanding tool
            visual_understanding = VisualUnderstanding()
            vision_params = VisualUnderstandingParams(images=[str(screenshot_path)], query=params.query)
            logger.info(f"Sending query to vision model: {params.query}")
            vision_result = await visual_understanding.execute_purely(params=vision_params)

            if not vision_result.ok:
                error_msg = vision_result.content or "visual model execution failed"
                logger.error(f"visual understanding failed: {error_msg}")
                return ToolResult(error=f"visual understanding failed: {error_msg}")

            # 4) Format success result
            analysis_content = vision_result.content
            markdown_content = (
                f"**action: visual_query**\n"
                f"Status: success\n"
                f"Page ID: {page_id}\n"
                f"Query: '{params.query}'\n\n"
                f"**analysis result**:\n{analysis_content}\n"
            )
            logger.info(f"visual_query success, page: {page_id}, query: '{params.query}'")
            # return screenshot path as attachment for front-end reference
            return ToolResult(content=markdown_content, attachments=[str(screenshot_path)])

        except Exception as e:
            logger.exception(f"visual_query encountered an unexpected error: {e!s}")
            return ToolResult(error=f"Unexpected internal error during visual_query: {e!s}")

    async def _purify_content(self, original_content: str, criteria: Optional[str] = None) -> str:
        """Attempt to purify markdown content using default or custom criteria.

        Args:
            original_content: Raw markdown text to purify.
            criteria: Optional custom rule; when None, use the default rule.

        Returns:
            str: Purified markdown; if purification fails or is empty, returns the original text.
        """
        purifier = Purify()
        try:
            log_msg = "Purifying markdown with default criteria"
            if criteria:
                log_msg = f"Purifying markdown with custom criteria: '{criteria}'"
            logger.info(log_msg)

            purified_markdown = await purifier._get_purified_content(
                original_content=original_content,
                criteria=criteria, # pass through criteria
            )

            if purified_markdown is not None:
                if not purified_markdown.strip():
                    logger.warning("Purified markdown is empty; original content may be irrelevant. Using original text.")
                    return original_content
                else:
                    logger.info("Markdown content purified successfully")
                    return purified_markdown
            else:
                logger.warning("Markdown purification failed or returned no result. Using original text.")
                return original_content

        except Exception as purify_exc:
            logger.warning(f"Error during markdown purification: {purify_exc!s}. Using original text.", exc_info=True)
            return original_content

    async def _generate_summary(self, title: str, content: str) -> str:
        """generatecontentsummary

        Args:
            title: Page title
            content: Page content

        Returns:
            str: Generated summary text
        """
        try:
            # Generate summary using summarizer
            summarizer = Summarize()
            # Ensure parameter format is correct
            summary_content = await summarizer.summarize_content(content=content, title=title, max_length=300)
            if summary_content:
                return summary_content
            else:
                logger.warning("Generate summaryfailed")
                return "(Summary generation failed)"
        except Exception as e:
            logger.error(f"Exception during summary generation: {e!s}", exc_info=True)
            return "(Summary generation error)"
