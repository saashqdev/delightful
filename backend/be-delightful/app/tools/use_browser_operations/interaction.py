"""Browser interaction operations: click, input, scroll, and visual selection."""

import asyncio
import re
import tempfile
import uuid
from pathlib import Path
from typing import Annotated, Literal, Optional

from pydantic import Field

from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.tools.use_browser_operations.base import BaseOperationParams, OperationGroup, operation
from app.tools.visual_understanding import VisualUnderstanding, VisualUnderstandingParams
from delightful_use.delightful_browser import (
    ClickSuccess,
    InputSuccess,
    InteractiveElementsSuccess,
    JSEvalSuccess,
    DelightfulBrowser,
    DelightfulBrowserError,
    ScreenshotSuccess,
    ScrollToSuccess,
)

# Logger
logger = get_logger(__name__)

# --- Visual interaction setup ---
# System temp directory
TEMP_DIR = Path(tempfile.gettempdir())
# Unique subdir per run to avoid conflicts
SCREENSHOT_CACHE_DIR = TEMP_DIR / f"be_delightful_visual_{uuid.uuid4()}"
# Ensure cache directory exists
SCREENSHOT_CACHE_DIR.mkdir(parents=True, exist_ok=True)
logger.info(f"Visual interaction screenshots stored at temp dir: {SCREENSHOT_CACHE_DIR}")
# --- End visual interaction setup ---


class GetInteractiveElementsParams(BaseOperationParams):
    """Parameters for fetching interactive elements."""
    scope: Literal["viewport", "all"] = Field("viewport", description="Element scope ('viewport': visible area, 'all': entire page)")


class ClickParams(BaseOperationParams):
    """Parameters for clicking an element."""
    selector: str = Field(
        ...,
        description="CSS selector of the element to click (e.g., '#element-id', '.class-name', '[attribute=value]', '[delightful-touch-id=\"a1b2c\"]')"
    )


class InputTextParams(BaseOperationParams):
    """Parameters for typing text into an element."""
    selector: str = Field(
        ...,
        description="CSS selector of the input field (e.g., '#input-id', 'input[name=\"username\"]', '[delightful-touch-id=\"a1b2c\"]')"
    )
    text: str = Field(
        ...,
        description="Text content to input"
    )
    clear_first: bool = Field(
        True,
        description="Whether to clear the input field before typing"
    )
    press_enter: bool = Field(
        False,
        description="Whether to press Enter key after typing"
    )


class FindInteractiveElementVisuallyParams(BaseOperationParams):
    """Parameters to locate an element visually by description."""
    element_description: str = Field(
        ...,
        description='Natural-language description of the target element (button, link, input, etc.). Example: "search button at the top", "link with the word \"Login\"". Must refer to a single element, not a region.'
    )


class ScrollToParams(BaseOperationParams):
    """Parameters to scroll to a specific screen number."""
    screen_number: Annotated[int, Field(ge=1, description="Target screen index (starting from 1)")] = Field(
        ...,
    )


class InteractionOperations(OperationGroup):
    """Interaction operations such as clicking and typing."""
    group_name = "interaction"
    group_description = "Page interaction related operations"

    @operation(
        example={
            "operation": "get_interactive_elements",
            "operation_params": {
                "scope": "viewport",
            }
        }
    )
    async def get_interactive_elements(self, browser: DelightfulBrowser, params: GetInteractiveElementsParams) -> ToolResult:
        """Retrieve interactive elements on the page (buttons, links, inputs, etc.).

        Scope options:
        - `viewport`: only elements in the visible area
        - `all`: all elements on the page
        """
        # Get active page id
        page_id = params.page_id
        if not page_id:
            page_id = await browser.get_active_page_id()
            if not page_id:
                return ToolResult(error="No available page. Provide page_id or open a page via goto.")

        scope = params.scope

        try:
            # Call browser API
            result = await browser.get_interactive_elements(page_id, scope)

            # Process result
            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, InteractiveElementsSuccess):
                # Extract element data
                elements_by_category = result.elements_by_category
                total_count = result.total_count

                # Build structured markdown content
                markdown_content = (
                    f"**action: get_interactive_elements**\n"
                    f"Status: success\n"
                    f"Scope: {scope}\n"
                    f"Total elements: {total_count}\n"
                )

                # Append grouped summary
                if total_count > 0:
                    markdown_content += "\n**Interactive elements by category**:\n"

                    # Display order and limit
                    display_count = 0
                    MAX_DISPLAY = 100

                    # Show categories in preferred order
                    categories_to_display = ['button', 'link', 'input_and_select', 'other']

                    for category_name in categories_to_display:
                        elements_in_category = elements_by_category.get(category_name, [])
                        if elements_in_category and display_count < MAX_DISPLAY:
                            # Friendly category label
                            display_category = category_name.replace('_', ' & ').title()
                            markdown_content += f"\n* **{display_category}**:\n"

                            for elem in elements_in_category:
                                if display_count >= MAX_DISPLAY:
                                    break

                                elem_type = elem.get("type", "unknown")
                                elem_selector = elem.get("selector", "unavailable")
                                elem_name = elem.get("name") or elem.get("name_en") or "unnamed"
                                elem_text = elem.get("text", "")[:30]
                                elem_value = elem.get("value")
                                elem_href = elem.get("href")

                                # Build single-line description
                                description = f"`{elem_selector}` ({elem_type})"
                                if elem_text:
                                    description += f" | text: '{elem_text}'"
                                elif elem_name != "unnamed":
                                    description += f" | name: '{elem_name}'"

                                if elem_value is not None:
                                    description += f" | value: '{elem_value!s}'"
                                if elem_href:
                                    description += f" | href: '{elem_href}'"

                                markdown_content += f"  - {description}\n"
                                display_count += 1

                    if total_count > display_count:
                        markdown_content += f"\n... {total_count - display_count} more elements not shown\n"

                    markdown_content += (
                        "\n**Note**: Use `click` or `input_text` with these elements via their `selector` value (e.g., `[delightful-touch-id=\"a1b2c3\"]`).\n"
                    )
                else:
                    markdown_content += "\n**Note**: No interactive elements found. Try scope 'all' or check if the page has interactive content.\n"

                return ToolResult(content=markdown_content)
            else:
                logger.error(f"get_interactive_elements returned unknown type: {type(result)}")
                return ToolResult(error="get_interactive_elements returned an unexpected result type")

        except Exception as e:
            logger.error(f"get_interactive_elements failed: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error while getting interactive elements: {e!s}")

    @operation(
        example={
            "operation": "find_interactive_element_visually",
            "operation_params": {
                "element_description": "Find a clickable link title containing 'Number' in the search results"
            }
        }
    )
    async def find_interactive_element_visually(self, browser: DelightfulBrowser, params: FindInteractiveElementVisuallyParams) -> ToolResult:
        """Find interactive elements visually using natural language.

        Works with vague descriptions (e.g., "search box") but must target a single element, not a region. Recommended to inspect page structure with `visual_query` first. Returns a list of CSS selectors built from visual markers.
        """
        page_id = params.page_id
        if not page_id:
            page_id = await browser.get_active_page_id()
            if not page_id:
                return ToolResult(error="No available page. Provide page_id or open a page via goto.")

        logger.info(f"Starting visual locate: page={page_id}, description='{params.element_description}'")

        screenshot_path_obj: Optional[Path] = None

        try:
            # Load marker JS module
            load_result = await browser.ensure_js_module_loaded(page_id, ["marker"])
            if not load_result.get("marker"):
                return ToolResult(error="Failed to load marker JS module")
            await asyncio.sleep(0.5) # allow markers to settle

            logger.info(f"Capturing marked screenshot for page {page_id}...")
            # Use temporary file for screenshot
            screenshot_result = await browser.take_screenshot(page_id=page_id, path=None, full_page=False)

            if isinstance(screenshot_result, DelightfulBrowserError):
                logger.error(f"Visual locate screenshot failed: {screenshot_result.error}")
                return ToolResult(error=f"Screenshot for visual locate failed: {screenshot_result.error}")
            elif not isinstance(screenshot_result, ScreenshotSuccess):
                logger.error(f"take_screenshot returned unexpected type: {type(screenshot_result)}")
                return ToolResult(error="take_screenshot returned an unexpected result type")

            screenshot_path_obj = screenshot_result.path
            logger.info(f"Visual locate screenshot saved at: {screenshot_path_obj}")

            # Call vision model to find marker IDs
            visual_understanding = VisualUnderstanding()
            vision_query = (
                "Find up to three elements that may match the description "
                f"\"{params.element_description}\". Return the letter+number marker text from the colored tag on each candidate (e.g., B3, A12, C1), separated by commas. "
                "If none are found, return 'not found'."
            )
            vision_params = VisualUnderstandingParams(images=[str(screenshot_path_obj)], query=vision_query)
            logger.info(f"Sending marker query to vision model: {vision_query}")
            vision_result = await visual_understanding.execute_purely(params=vision_params)

            if not vision_result.ok:
                error_msg = vision_result.content or "visual model execution failed"
                logger.error(f"Visual marker search failed: {error_msg}")
                return ToolResult(error=f"Visual marker search failed: {error_msg}")

            # Parse returned marker IDs
            found_marker_ids_str = vision_result.content.strip()
            logger.info(f"Vision model returned marker id string: '{found_marker_ids_str}'")

            marker_ids = re.findall(r'\b[A-Za-z]\d+\b', found_marker_ids_str)
            if not marker_ids or "not found" in found_marker_ids_str.lower():
                logger.warning(f"Vision model could not find markers matching \"{params.element_description}\".")
                return ToolResult(error=(
                    f"Could not locate any element for description \"{params.element_description}\" in the current viewport. "
                    "Use visual_query to inspect layout and refine the description, or fallback to get_interactive_elements."
                ))

            logger.info(f"Parsed {len(marker_ids)} candidate marker IDs: {marker_ids}")

            # Loop through markers and fetch element details
            found_elements = []
            for marker_id in marker_ids:
                # JS to find element by marker id and extract details
                js_code = f"""
                (() => {{
                    const markerIdStr = "{marker_id}"; // current marker id
                    // Use marker.js global find to get touchId
                    const touchId = window.DelightfulMarker.find(markerIdStr);
                    if (!touchId) {{
                        return null; // touchId not found
                    }}

                    // Query element by touchId
                    const selector = `[delightful-touch-id="${{touchId}}"]`;
                    const element = document.querySelector(selector);
                    if (!element) {{
                        return {{ markerId: markerIdStr, touchId: touchId, error: "Element not found with touchId" }};
                    }}

                    // Extract element info
                    const elementType = element.tagName.toLowerCase();
                    let elementText = (element.textContent || element.innerText || "").trim();
                    if (elementText.length > 256) {{
                        elementText = elementText.substring(0, 256) + "...";
                    }}
                    const elementHref = elementType === 'a' ? element.getAttribute('href') : null;
                    const elementName = element.getAttribute('name') || element.getAttribute('aria-label') || element.getAttribute('title') || '';

                    return {{
                        markerId: markerIdStr,
                        touchId: touchId,
                        type: elementType,
                        text: elementText,
                        name: elementName,
                        href: elementHref,
                        selector: selector
                    }};
                }})()
                """
                logger.info(f"Running JS on page {page_id} to fetch element details (marker: '{marker_id}')...")
                element_details_result = await browser.evaluate_js(page_id=page_id, js_code=js_code)

                if isinstance(element_details_result, DelightfulBrowserError):
                    logger.error(f"Failed to fetch element details for marker {marker_id}: {element_details_result.error}")
                    continue
                elif not isinstance(element_details_result, JSEvalSuccess):
                    logger.error(f"evaluate_js (marker {marker_id}) returned unexpected type: {type(element_details_result)}")
                    continue

                element_details = element_details_result.result
                logger.info(f"Element details for marker {marker_id}: {element_details}")

                # Collect valid element info
                if element_details and element_details.get("touchId") and not element_details.get("error"):
                    found_elements.append(element_details)
                elif element_details and element_details.get("error"):
                    logger.warning(f"Found marker {marker_id} with touchId {element_details.get('touchId')} but element not located.")
                else:
                    logger.warning(f"Found marker {marker_id} but no interactive element selector (touchId) located.")

            # Build result for found elements
            if found_elements:
                markdown_content = (
                    f"**action: find_interactive_element_visually**\n"
                    f"Status: success\n"
                    f"Description: '{params.element_description}'\n"
                    f"Located {len(found_elements)} candidate elements:\n\n"
                )

                for i, elem in enumerate(found_elements, 1):
                    selector = elem["selector"]
                    elem_type = elem.get("type", "unknown")
                    elem_text = elem.get("text")
                    elem_name = elem.get("name")
                    elem_href = elem.get("href")
                    marker_id = elem.get("markerId", "N/A")

                    markdown_content += f"**Option {i} (marker {marker_id}):**\n"
                    markdown_content += f"- Selector: `{selector}`\n"
                    markdown_content += f"- Type: `{elem_type}`\n"
                    if elem_text:
                        markdown_content += f"- Text: '{elem_text}'\n"
                    if elem_name:
                        markdown_content += f"- Name/label: '{elem_name}'\n"
                    if elem_href:
                        markdown_content += f"- Href: `{elem_href}`\n"
                    markdown_content += "\n"


                markdown_content += (
                    "**Note**: Choose the selector that best matches your intent, then use `click` or `input_text` to interact. "
                    "If the element is a link and you need navigation, prefer `goto` instead of `click`. "
                    "If nothing fits, refine the description or use `get_interactive_elements` to inspect all elements."
                )

                logger.info(f"Visual locate succeeded with {len(found_elements)} candidates for '{params.element_description}'")
                return ToolResult(content=markdown_content)
            else:
                logger.warning(f"Markers found ({marker_ids}) for description '{params.element_description}' but no interactive elements located.")
                return ToolResult(error=(
                    f"Markers were detected for description \"{params.element_description}\" but no interactive elements could be located. "
                    "They may have disappeared, be hidden, or be non-interactive. Try get_interactive_elements."
                ))

        except Exception as e:
            logger.exception(f"Unexpected error during visual locate: {e!s}")
            return ToolResult(error=f"Unexpected internal error during visual locate: {e!s}")

    @operation(
        example={
            "operation": "click",
            "operation_params": {
                "selector": "[delightful-touch-id=\"a1b2\"]"
            }
        }
    )
    async def click(self, browser: DelightfulBrowser, params: ClickParams) -> ToolResult:
        """Click an element on the page. Prefer goto for navigation when possible.

        Accepts a CSS selector. Use get_interactive_elements or find_interactive_element_visually to obtain one.
        """
        # Get active page id
        page_id = params.page_id
        if not page_id:
            page_id = await browser.get_active_page_id()
            if not page_id:
                return ToolResult(error="No available page. Provide page_id or open a page via goto.")

        selector = params.selector

        try:
            # Validate selector
            if not selector:
                return ToolResult(error="Invalid selector; it cannot be empty.")

            # Page info (not currently used)
            page = await browser.get_page_by_id(page_id)
            url_before = page.url if page else "unknown"

            # Perform click
            logger.info(f"Attempting click: {selector}")
            result = await browser.click(page_id, selector)

            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, ClickSuccess):
                # Format success
                markdown_content = (
                    f"**action: click**\n"
                    f"Status: success\n"
                    f"Selector: `{selector}`"
                )
                if result.final_url:
                    markdown_content += f"\nNavigated to: `{result.final_url}`"
                if result.title_after:
                    markdown_content += f"\nPage title after click: {result.title_after}"

                return ToolResult(content=markdown_content)
            else:
                logger.error(f"click returned unknown type: {type(result)}")
                return ToolResult(error="click returned an unexpected result type")

        except Exception as e:
            logger.error(f"click failed: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error clicking '{selector}': {e!s}")

    @operation(
        example={
            "operation": "input_text",
            "operation_params": {
                "selector": "input[name='search']",
                "text": "content to input",
                "clear_first": True,
                "press_enter": True
            }
        }
    )
    @operation(name="input") # Expose the action name as 'input'
    async def input_text(self, browser: DelightfulBrowser, params: InputTextParams) -> ToolResult:
        """Type text into an input element.

        Accepts a CSS selector. Use get_interactive_elements or find_interactive_element_visually to obtain one. Provide context-appropriate input.
        """
        # Get active page id
        page_id = params.page_id
        if not page_id:
            page_id = await browser.get_active_page_id()
            if not page_id:
                return ToolResult(error="No available page. Provide page_id or open a page via goto.")

        selector = params.selector
        text = params.text
        clear_first = params.clear_first
        press_enter = params.press_enter

        try:
            # Validate selector
            if not selector:
                return ToolResult(error="Invalid selector; it cannot be empty.")

            # Page info (not currently used)
            page = await browser.get_page_by_id(page_id)
            url_before = page.url if page else "unknown"

            logger.info(f"Attempting to input text into {selector}: '{text[:20]}...'")
            result = await browser.input_text(page_id, selector, text, clear_first, press_enter)

            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, InputSuccess):
                # Format success
                action_desc = "typed" if not press_enter else "typed and pressed Enter"
                markdown_content = (
                    f"**action: input_text**\n"
                    f"Status: success\n"
                    f"Selector: `{selector}`\n"
                    f"Action: {action_desc}"
                )
                if result.final_url:
                    markdown_content += f"\nNavigated to: `{result.final_url}`"
                if result.title_after:
                    markdown_content += f"\nPage title: {result.title_after}"

                return ToolResult(content=markdown_content)
            else:
                logger.error(f"input_text returned unknown type: {type(result)}")
                return ToolResult(error="input_text returned an unexpected result type")

        except Exception as e:
            logger.error(f"input_text failed: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error inputting text into '{selector}': {e!s}")

    @operation(
        example={
            "operation": "scroll_to",
            "operation_params": {
                "screen_number": 3
            }
        }
    )
    async def scroll_to(self, browser: DelightfulBrowser, params: ScrollToParams) -> ToolResult:
        """Scroll the page to a specific screen index (1-based)."""
        page_id = params.page_id
        if not page_id:
            page_id = await browser.get_active_page_id()
            if not page_id:
                return ToolResult(error="No available page. Provide page_id or open a page via goto.")

        screen_number = params.screen_number

        try:
            logger.info(f"Scrolling page {page_id} to screen: {screen_number}")
            result = await browser.scroll_to(page_id, screen_number)

            if isinstance(result, DelightfulBrowserError):
                return ToolResult(error=result.error)
            elif isinstance(result, ScrollToSuccess):
                # Build result description
                screen_number = result.screen_number

                markdown_content = (
                    f"**action: scroll_to**\n"
                    f"Status: success\n"
                    f"Target screen: {screen_number}\n"
                    f"Note: This action does not fetch content automatically; use other tools to read the page."
                )

                return ToolResult(content=markdown_content)
            else:
                logger.error(f"scroll_to returned unknown type: {type(result)}")
                return ToolResult(error="scroll_to returned an unexpected result type")

        except Exception as e:
            logger.error(f"scroll_to failed: {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected error scrolling to screen {screen_number}: {e!s}")
