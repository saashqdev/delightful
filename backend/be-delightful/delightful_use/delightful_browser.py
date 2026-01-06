"""
Browser control core module

Provides browser instance creation, configuration and control functionality,
including page operations, element finding and content retrieval.
"""

import asyncio
import glob
import logging
import os
import re
import tempfile
import uuid
from pathlib import Path
from typing import Any, Dict, List, Optional, Union, Literal

from pydantic import BaseModel, Field
from playwright.async_api import Page, Error as PlaywrightError

from agentlang.utils.file import safe_delete
from delightful_use.browser_manager import BrowserManager
from delightful_use.delightful_browser_config import DelightfulBrowserConfig
from delightful_use.page_registry import PageRegistry, PageState

# Setup logging
logger = logging.getLogger(__name__)


# --- DTO Definitions ---

class DelightfulBrowserError(BaseModel):
    """Generic DelightfulBrowser operation failure result"""
    success: Literal[False] = False
    error: str = Field(..., description="Error message description")
    operation: Optional[str] = Field(None, description="Name of failed operation")
    details: Optional[Dict[str, Any]] = Field(None, description="Optional error context details")

class GotoSuccess(BaseModel):
    success: Literal[True] = True
    final_url: str
    title: str

class ClickSuccess(BaseModel):
    success: Literal[True] = True
    final_url: Optional[str] = None # Click doesn't necessarily cause navigation
    title_after: Optional[str] = None

class InputSuccess(BaseModel):
    success: Literal[True] = True
    final_url: Optional[str] = None # Pressing enter after input may cause navigation
    title_after: Optional[str] = None

class ScreenshotSuccess(BaseModel):
    success: Literal[True] = True
    path: Path
    is_temp: bool

class MarkdownSuccess(BaseModel):
    success: Literal[True] = True
    markdown: str
    url: str
    title: str
    scope: str

class InteractiveElementsSuccess(BaseModel):
    success: Literal[True] = True
    elements_by_category: Dict[str, List[Dict[str, Any]]] # JS return structure may still be Dict
    total_count: int

class JSEvalSuccess(BaseModel):
    success: Literal[True] = True
    result: Any # JS return result type unknown

class PageStateSuccess(BaseModel):
    success: Literal[True] = True
    state: PageState # Directly encapsulate PageState

class ScrollPositionData(BaseModel):
    """Scroll position data for ScrollSuccess"""
    x: float
    y: float

class ScrollSuccess(BaseModel):
    success: Literal[True] = True
    direction: str
    full_page: bool
    before: ScrollPositionData
    after: ScrollPositionData
    actual_distance: float

class ScrollToSuccess(BaseModel):
    success: Literal[True] = True
    screen_number: int
    target_y: float
    before: ScrollPositionData
    after: ScrollPositionData

# Define unified return type alias
DelightfulBrowserResult = Union[
    GotoSuccess, ClickSuccess, InputSuccess, ScreenshotSuccess, MarkdownSuccess,
    InteractiveElementsSuccess, JSEvalSuccess, PageStateSuccess, ScrollSuccess, ScrollToSuccess,
    DelightfulBrowserError
]

# --- End DTO Definitions ---


class DelightfulBrowser:
    """Browser control class

    Manages browser page collection, provides page operations and content retrieval functionality.
    Implements functionality through composition of BrowserManager and PageRegistry.
    Uniformly handles underlying Playwright errors and returns structured result objects.
    """

    def __init__(self, config: Optional[DelightfulBrowserConfig] = None):
        """Initialize browser control class

        Args:
            config: Browser configuration, if None use default scraping configuration
        """
        self.config = config or DelightfulBrowserConfig.create_for_scraping()
        self._browser_manager = BrowserManager()
        self._page_registry = PageRegistry()

        self._active_context_id: Optional[str] = None
        self._active_page_id: Optional[str] = None
        self._managed_page_ids: set[str] = set()
        self._initialized: bool = False
        self._temp_files: List[Path] = [] # For managing temporary screenshot files

        # --- Temporary screenshot directory ---
        self._TEMP_SCREENSHOT_DIR: Optional[Path] = None
        try:
            # Create unique screenshot subdirectory for this browser instance in system temp directory
            temp_dir = Path(tempfile.gettempdir())
            unique_dir_name = f"be_delightful_browser_screenshots_{uuid.uuid4()}"
            self._TEMP_SCREENSHOT_DIR = temp_dir / unique_dir_name
            self._TEMP_SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)
            logger.info(f"Browser temporary screenshot directory created: {self._TEMP_SCREENSHOT_DIR}")
        except Exception as e:
            logger.error(f"Failed to create temporary screenshot directory: {self._TEMP_SCREENSHOT_DIR}, error: {e}", exc_info=True)
            self._TEMP_SCREENSHOT_DIR = None # Mark as unavailable
        # --- End temporary screenshot directory ---

    async def initialize(self) -> None:
        """Initialize browser instance

        Initialize underlying browser manager and create first context
        """
        if self._initialized:
            return

        try:
            # Initialize browser manager
            await self._browser_manager.initialize(self.config)
            # Register as browser client
            await self._browser_manager.register_client()

            # Create default context
            context_id, _ = await self._browser_manager.get_context(self.config)
            self._active_context_id = context_id

            self._initialized = True
            logger.info("DelightfulBrowser initialization completed")
        except Exception as e:
            logger.error(f"Failed to initialize browser: {e}", exc_info=True)
            raise

    async def _ensure_initialized(self):
        """Ensure browser is initialized"""
        if not self._initialized:
            await self.initialize()

    async def new_context(self) -> str:
        """Create new browser context and set as active"""
        await self._ensure_initialized()
        try:
            context_id, _ = await self._browser_manager.get_context(self.config)
            self._active_context_id = context_id
            logger.info(f"Created new context and set as active: {context_id}")
            return context_id
        except Exception as e:
            logger.error(f"Failed to create browser context: {e}", exc_info=True)
            raise # Context creation failure also considered critical error

    async def new_page(self, context_id: Optional[str] = None) -> str:
        """Create new page and set as active"""
        await self._ensure_initialized()
        try:
            use_context_id = context_id or self._active_context_id
            if not use_context_id:
                logger.warning("No context ID specified and no active context, creating new context")
                use_context_id = await self.new_context()

            context = await self._browser_manager.get_context_by_id(use_context_id)
            if not context:
                raise ValueError(f"Context does not exist: {use_context_id}")

            page = await context.new_page()
            page_id = await self._page_registry.register_page(page, use_context_id)
            self._managed_page_ids.add(page_id)
            self._active_page_id = page_id
            self._active_context_id = use_context_id # Update active context ID

            # PageRegistry's internal handle_page_load_and_script_injection will handle JS loading

            logger.info(f"Created new page and set as active: {page_id} (context: {use_context_id})")
            return page_id
        except Exception as e:
            logger.error(f"Failed to create page: {e}", exc_info=True)
            raise # Page creation failure is considered a critical error

    async def ensure_js_module_loaded(self, page_id: str, module_names: Union[str, List[str]]) -> Dict[str, bool]:
        """Ensure specified page has loaded JS modules

        Args:
            page_id: Page ID
            module_names: Module name or list of module names

        Returns:
            Dict[str, bool]: Loading result
        """
        return await self._page_registry.ensure_js_module_loaded(page_id, module_names)

    async def get_active_context_id(self) -> Optional[str]:
        """Get current active context ID"""
        return self._active_context_id

    async def has_active_context(self) -> bool:
        """Check if there is an active context"""
        await self._ensure_initialized() # Ensure manager is initialized
        if not self._active_context_id:
            return False
        context = await self._browser_manager.get_context_by_id(self._active_context_id)
        return context is not None

    async def get_active_page_id(self) -> Optional[str]:
        """Get current active page ID (and verify page is still valid)"""
        if self._active_page_id:
            page = await self._page_registry.get_page_by_id(self._active_page_id)
            if not page: # get_page_by_id internally checks is_closed
                logger.warning(f"Active page {self._active_page_id} no longer exists or is closed, clearing active page ID.")
                self._managed_page_ids.discard(self._active_page_id)
                self._active_page_id = None
        return self._active_page_id

    async def get_page_by_id(self, page_id: str) -> Optional[Page]:
        """Get valid page by ID (returns None if doesn't exist or is closed)"""
        return await self._page_registry.get_page_by_id(page_id)

    async def get_active_context(self) -> Optional[Any]: # Return type should be Context, but avoid circular import
        """Get current active context object"""
        await self._ensure_initialized()
        if not self._active_context_id:
            return None
        return await self._browser_manager.get_context_by_id(self._active_context_id)

    async def get_active_page(self) -> Optional[Page]:
        """Get current active page object"""
        page_id = await self.get_active_page_id()
        if not page_id:
            return None
        return await self.get_page_by_id(page_id)

    async def get_all_pages(self) -> Dict[str, Page]:
        """Get all currently open pages"""
        # PageRegistry's get_all_pages is optimized to return only open pages
        return await self._page_registry.get_all_pages()

    async def goto(self, page_id: Optional[str], url: str, wait_until: str = "domcontentloaded") -> DelightfulBrowserResult:
        """Navigate to specified URL, if page_id is None, automatically create new page."""
        operation_name = "goto"
        page: Optional[Page] = None
        actual_page_id: Optional[str] = page_id

        try:
            await self._ensure_initialized()

            # If page_id not provided, create new page
            if not actual_page_id:
                logger.info(f"{operation_name}: page_id not provided, creating new page to visit {url}")
                actual_page_id = await self.new_page() # new_page will set as active page
                page = await self.get_page_by_id(actual_page_id)
                if not page: # new_page should guarantee page exists, but as insurance
                    raise RuntimeError(f"Failed to create or get new page for {operation_name}")
            else:
                page = await self.get_page_by_id(actual_page_id)
                if not page:
                    return DelightfulBrowserError(error=f"Page does not exist or is closed: {actual_page_id}", operation=operation_name)

            # Set active page (even for existing pages, update to active)
            self._active_page_id = actual_page_id
            context_id = await self._page_registry.get_context_id_for_page(actual_page_id)
            if context_id: self._active_context_id = context_id

            logger.info(f"{operation_name}: Page {actual_page_id} navigating to {url}")
            await page.goto(url, wait_until=wait_until, timeout=60000) # Increase timeout
            await self._wait_for_stable_network(page)

            final_url = page.url
            title = "Failed to get title"
            try:
                title = await page.title() or "No title"
            except PlaywrightError as title_e: # More precisely catch Playwright errors
                logger.warning(f"Failed to get title for page {actual_page_id}: {title_e}")
            except Exception as title_e_general: # Catch other possible exceptions
                logger.warning(f"Unexpected error when getting title for page {actual_page_id}: {title_e_general}")


            logger.info(f"{operation_name}: Page {actual_page_id} navigation successful, URL: {final_url}, Title: {title}")
            return GotoSuccess(final_url=final_url, title=title)

        except PlaywrightError as e: # Catch specific Playwright errors
            error_msg = f"Failed to navigate to {url} (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False) # PlaywrightError typically has enough info
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"url": url, "page_id": actual_page_id})
        except Exception as e: # Catch other unexpected errors
            error_msg = f"Failed to navigate to {url} (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"url": url, "page_id": actual_page_id})

    async def click(self, page_id: str, selector: str) -> DelightfulBrowserResult:
        """Click specified element"""
        operation_name = "click"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        try:
            self._active_page_id = page_id # Click operation also sets active page
            logger.info(f"{operation_name}: Page {page_id} clicking selector '{selector}'")
            # Use click method, Playwright automatically waits for element to be visible and clickable
            await page.click(selector, timeout=15000) # Increase timeout
            await self._wait_for_stable_network(page)

            # Get URL and title after click (may change)
            final_url = page.url
            title_after = "Failed to get title"
            try:
                # Check if page closed after click
                if page.is_closed():
                    logger.warning(f"{operation_name}: Page {page_id} closed after click, cannot get subsequent title.")
                    title_after = "Page closed"
                else:
                    title_after = await page.title() or "No title"
            except PlaywrightError as title_e:
                logger.warning(f"Failed to get page {page_id} title after click: {title_e}")
            except Exception as title_e_general:
                logger.warning(f"Unexpected error occurred while getting page {page_id} title after click: {title_e_general}")

            logger.info(f"{operation_name}: Page {page_id} clicked '{selector}' successfully.")
            return ClickSuccess(final_url=final_url, title_after=title_after)

        except PlaywrightError as e:
            error_msg = f"Failed to click element '{selector}' (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            # Check if it's element not found error
            if "selector resolved to no elements" in str(e):
                error_msg = f"Click failed: Element '{selector}' not found or element is not visible/not interactable."
            elif "timeout" in str(e).lower():
                error_msg = f"Click on element '{selector}' timed out, element may not have appeared or become clickable within expected time."
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"selector": selector, "page_id": page_id})
        except Exception as e:
            error_msg = f"Failed to click element '{selector}' (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"selector": selector, "page_id": page_id})

    async def input_text(self, page_id: str, selector: str, text: str, clear_first: bool = True, press_enter: bool = False) -> DelightfulBrowserResult:
        """Input text into input field"""
        operation_name = "input_text"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        details = {"selector": selector, "text_preview": text[:50] + '...', "clear_first": clear_first, "press_enter": press_enter, "page_id": page_id}

        try:
            self._active_page_id = page_id
            logger.info(f"{operation_name}: Page {page_id} inputting text to '{selector}' (Clear: {clear_first}, Enter: {press_enter})")

            # Use fill method to input, it automatically waits for element and clears
            if clear_first:
                await page.fill(selector, text, timeout=15000)
            else:
                # If not clearing, use type method to append
                await page.type(selector, text, timeout=15000)

            final_url = page.url # Record URL before pressing Enter
            title_after = None

            if press_enter:
                logger.info(f"{operation_name}: Page {page_id} pressing Enter after '{selector}'")
                await page.press(selector, "Enter")
                await self._wait_for_stable_network(page)
                # Get URL and title after pressing Enter
                final_url = page.url
                try:
                    if page.is_closed():
                        logger.warning(f"{operation_name}: Page {page_id} closed after input and pressing Enter.")
                        title_after = "Page closed"
                    else:
                        title_after = await page.title() or "No title"
                except PlaywrightError as title_e:
                    logger.warning(f"Failed to get page {page_id} title after input: {title_e}")
                    title_after = "Failed to get title"
                except Exception as title_e_general:
                    logger.warning(f"Unexpected error getting page {page_id} title after input: {title_e_general}")
                    title_after = "Failed to get title"


            logger.info(f"{operation_name}: Page {page_id} successfully input text to '{selector}'.")
            return InputSuccess(final_url=final_url, title_after=title_after)

        except PlaywrightError as e:
            error_msg = f"Failed to input text to '{selector}' (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            if "selector resolved to no elements" in str(e):
                error_msg = f"Input failed: cannot find element '{selector}' or element is not visible/interactive."
            elif "element is not an input element" in str(e):
                error_msg = f"Input failed: element corresponding to selector '{selector}' is not an input element (e.g., input, textarea)."
            elif "timeout" in str(e).lower():
                error_msg = f"Input text to '{selector}' timed out."
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Failed to input text to '{selector}' (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    async def scroll_page(self, page_id: str, direction: str, full_page: bool = False) -> DelightfulBrowserResult:
        """Scroll page"""
        operation_name = "scroll_page"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        details = {"direction": direction, "full_page": full_page, "page_id": page_id}

        try:
            self._active_page_id = page_id
            logger.info(f"{operation_name}: Page {page_id} scroll direction '{direction}', full page: {full_page}")

            # Get page scroll information
            # _get_page_scroll_info should also handle Playwright errors internally
            scroll_info = await self._page_registry._get_page_scroll_info(page)
            viewport_height = scroll_info.get("viewport_height", 0)
            document_height = scroll_info.get("document_height", 0)
            current_y = scroll_info.get("y", 0)
            current_x = scroll_info.get("x", 0)

            scroll_script = ""
            target_y = current_y
            target_x = current_x

            # Calculate scroll target and script
            if direction == "up":
                scroll_amount = viewport_height * 0.8 if not full_page else document_height # Scroll 80% of viewport
                target_y = max(0, current_y - scroll_amount)
            elif direction == "down":
                scroll_amount = viewport_height * 0.8 if not full_page else document_height
                target_y = min(document_height - viewport_height, current_y + scroll_amount) # Ensure bottom is visible
                # If target position didn't move down much, try to go directly to bottom
                if abs(target_y - current_y) < 10 and direction == "down":
                    target_y = document_height - viewport_height
            elif direction == "left":
                viewport_width = scroll_info.get("viewport_width", 0)
                scroll_amount = viewport_width * 0.8 if not full_page else scroll_info.get("document_width", 0)
                target_x = max(0, current_x - scroll_amount)
            elif direction == "right":
                viewport_width = scroll_info.get("viewport_width", 0)
                document_width = scroll_info.get("document_width", 0)
                scroll_amount = viewport_width * 0.8 if not full_page else document_width
                target_x = min(document_width - viewport_width, current_x + scroll_amount)
            elif direction == "top":
                target_x, target_y = 0, 0
            elif direction == "bottom":
                target_x = current_x # Usually x doesn't change during vertical scroll
                target_y = max(0, document_height - viewport_height) # Scroll to bottom to make bottom visible
            else:
                # Should not happen due to Literal type checking, but as a safeguard
                return DelightfulBrowserError(error=f"Invalid scroll direction: {direction}", operation=operation_name, details=details)

            # Ensure target coordinates are numbers
            target_x = float(target_x)
            target_y = float(target_y)

            scroll_script = f"window.scrollTo({target_x}, {target_y});"
            await page.evaluate(scroll_script)
            await asyncio.sleep(0.5) # Wait for scroll animation (if exists)

            # Get new scroll position
            new_scroll_info = await self._page_registry._get_page_scroll_info(page)
            new_x = new_scroll_info.get("x", 0)
            new_y = new_scroll_info.get("y", 0)
            actual_distance = new_y - current_y if direction in ["up", "down", "top", "bottom"] else new_x - current_x

            logger.info(f"{operation_name}: Page {page_id} scrolled successfully, new position (x={new_x}, y={new_y})")
            return ScrollSuccess(
                direction=direction,
                full_page=full_page,
                before=ScrollPositionData(x=current_x, y=current_y),
                after=ScrollPositionData(x=new_x, y=new_y),
                actual_distance=actual_distance
            )

        except PlaywrightError as e:
            error_msg = f"Scroll page failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Scroll page failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    async def scroll_to(self, page_id: str, screen_number: int) -> DelightfulBrowserResult:
        """Scroll page to the position of the specified screen number (>=1)."""
        operation_name = "scroll_to"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        if screen_number < 1:
            return DelightfulBrowserError(error="Screen number must be greater than or equal to 1", operation=operation_name, details={"screen_number": screen_number})

        details = {"screen_number": screen_number, "page_id": page_id}

        try:
            self._active_page_id = page_id
            logger.info(f"{operation_name}: Page {page_id} scroll to screen {screen_number}")

            js_get_info = """
            () => ({ x: window.scrollX, y: window.scrollY, viewportHeight: window.innerHeight })
            """
            scroll_info_before = await page.evaluate(js_get_info)
            viewport_height = scroll_info_before.get("viewportHeight", 0) or 600 # Provide default value
            current_x = scroll_info_before.get("x", 0)
            current_y = scroll_info_before.get("y", 0)

            target_y = (screen_number - 1) * viewport_height
            target_y = float(target_y) # Ensure is float

            scroll_script = f"window.scrollTo({float(current_x)}, {target_y});"
            await page.evaluate(scroll_script)
            await asyncio.sleep(0.5)

            scroll_info_after = await page.evaluate(js_get_info)
            new_x = scroll_info_after.get("x", 0)
            new_y = scroll_info_after.get("y", 0)

            logger.info(f"{operation_name}: Page {page_id} scroll to screen {screen_number} successfully, new position (x={new_x}, y={new_y})")
            return ScrollToSuccess(
                screen_number=screen_number,
                target_y=target_y,
                before=ScrollPositionData(x=current_x, y=current_y),
                after=ScrollPositionData(x=new_x, y=new_y)
            )
        except PlaywrightError as e:
            error_msg = f"Scroll to screen {screen_number} failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Scroll to screen {screen_number} failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    async def get_page_state(self, page_id: str) -> DelightfulBrowserResult:
        """Get page state information"""
        operation_name = "get_page_state"
        # PageRegistry.get_page_state internally handles page not found case and returns PageState(error=...)
        try:
            page_state: PageState = await self._page_registry.get_page_state(page_id)
            if page_state.error:
                # Convert PageState error to DelightfulBrowserError
                return DelightfulBrowserError(error=page_state.error, operation=operation_name, details={"page_id": page_id})
            else:
                return PageStateSuccess(state=page_state)
        except Exception as e: # Catch other exceptions that PageRegistry might throw
            error_msg = f"Unexpected error getting page state for {page_id}: {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"page_id": page_id})

    async def read_as_markdown(self, page_id: str, scope: str = "viewport") -> DelightfulBrowserResult:
        """Get Markdown representation of page content"""
        operation_name = "read_as_markdown"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        details = {"scope": scope, "page_id": page_id}

        try:
            self._active_page_id = page_id
            logger.info(f"{operation_name}: Page {page_id} read scope '{scope}'")

            # Internal JS module loading
            load_result = await self._page_registry.ensure_js_module_loaded(page_id, ["lens"])
            if not load_result.get("lens"):
                return DelightfulBrowserError(error="Failed to load JS module 'lens'", operation=operation_name, details=details)

            if scope not in ["viewport", "all"]:
                return DelightfulBrowserError(error=f"Invalid scope: {scope}, supports 'viewport' or 'all'", operation=operation_name, details=details)

            script = f"""
            async () => {{
                try {{
                    return await window.DelightfulLens.readAsMarkdown('{scope}');
                }} catch (e) {{
                    return {{ error: e.toString(), stack: e.stack }};
                }}
            }}
            """
            result = await page.evaluate(script)

            if isinstance(result, dict) and "error" in result:
                js_error = f"JS execution to get Markdown failed ({scope}): {result['error']}"
                logger.error(js_error)
                return DelightfulBrowserError(error=js_error, operation=operation_name, details=details)

            url = page.url
            title = await page.title() or "No title" # Also need to handle title retrieval error

            logger.info(f"{operation_name}: Page {page_id} read successfully.")
            return MarkdownSuccess(markdown=result, url=url, title=title, scope=scope)

        except PlaywrightError as e:
            error_msg = f"Read Markdown failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Read Markdown failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    async def get_interactive_elements(self, page_id: str, scope: str = "viewport") -> DelightfulBrowserResult:
        """Get interactive elements in page"""
        operation_name = "get_interactive_elements"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        details = {"scope": scope, "page_id": page_id}
        if scope not in ["viewport", "all"]:
            return DelightfulBrowserError(error=f"Invalid scope: {scope}", operation=operation_name, details=details)

        try:
            self._active_page_id = page_id
            logger.info(f"{operation_name}: Page {page_id} get elements, scope '{scope}'")

            # Internal JS module loading
            load_result = await self._page_registry.ensure_js_module_loaded(page_id, ["touch"])
            if not load_result.get("touch"):
                return DelightfulBrowserError(error="Failed to load JS module 'touch'", operation=operation_name, details=details)

            scope_value = "viewport" if scope == "viewport" else "all"
            script = f"""
            async () => {{
                try {{
                    return await window.DelightfulTouch.getInteractiveElements('{scope_value}');
                }} catch (e) {{
                    return {{ error: e.toString(), stack: e.stack }};
                }}
            }}
            """
            result = await page.evaluate(script)

            if isinstance(result, dict) and "error" in result:
                js_error = f"JS execution to get interactive elements failed: {result['error']}"
                logger.error(js_error)
                return DelightfulBrowserError(error=js_error, operation=operation_name, details=details)

            # result should be a categorized elements dictionary
            elements_by_category = result if isinstance(result, dict) else {}
            total_count = sum(len(v) for v in elements_by_category.values() if isinstance(v, list))

            logger.info(f"{operation_name}: Page {page_id} got {total_count} elements.")
            return InteractiveElementsSuccess(elements_by_category=elements_by_category, total_count=total_count)

        except PlaywrightError as e:
            error_msg = f"Get interactive elements failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Get interactive elements failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    async def take_screenshot(self, page_id: str, path: Optional[str] = None, full_page: bool = False) -> DelightfulBrowserResult:
        """Take screenshot of specified page"""
        operation_name = "take_screenshot"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        target_path_obj: Optional[Path] = None
        is_temp: bool = False
        final_path_str: str = ""

        try:
            if path:
                target_path_obj = Path(path)
                is_temp = False
                final_path_str = path
                try:
                    target_path_obj.parent.mkdir(parents=True, exist_ok=True)
                except Exception as mkdir_e:
                    return DelightfulBrowserError(error=f"Unable to create screenshot directory: {mkdir_e}", operation=operation_name, details={"path": path})
            else:
                if not self._TEMP_SCREENSHOT_DIR:
                    return DelightfulBrowserError(error="Temporary screenshot directory not available", operation=operation_name)
                temp_filename = f"screenshot_{uuid.uuid4()}.png"
                target_path_obj = self._TEMP_SCREENSHOT_DIR / temp_filename
                is_temp = True
                final_path_str = str(target_path_obj)

            logger.info(f"{operation_name}: Page {page_id} screenshot to '{final_path_str}' (Full: {full_page}, Temp: {is_temp})")
            await page.screenshot(path=final_path_str, full_page=full_page, timeout=30000) # Increased timeout

            # If temp file, add to management list
            if is_temp and target_path_obj:
                self._temp_files.append(target_path_obj)
                logger.debug(f"Temp screenshot file {target_path_obj} added to cleanup list.")

            logger.info(f"{operation_name}: Page {page_id} screenshot successful.")
            # Ensure return Path object
            return ScreenshotSuccess(path=target_path_obj if target_path_obj else Path(final_path_str), is_temp=is_temp)

        except PlaywrightError as e:
            error_msg = f"Screenshot failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"path": final_path_str, "full_page": full_page, "page_id": page_id})
        except Exception as e:
            error_msg = f"Screenshot failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details={"path": final_path_str, "full_page": full_page, "page_id": page_id})

    async def evaluate_js(self, page_id: str, js_code: str) -> DelightfulBrowserResult:
        """Execute JavaScript code on specified page"""
        operation_name = "evaluate_js"
        page = await self.get_page_by_id(page_id)
        if not page:
            return DelightfulBrowserError(error=f"Page does not exist or is closed: {page_id}", operation=operation_name)

        details = {"js_code_preview": js_code[:100] + '...', "page_id": page_id}

        try:
            logger.debug(f"{operation_name}: Page {page_id} execute JS: '{details['js_code_preview']}'")
            result = await page.evaluate(js_code)
            logger.debug(f"{operation_name}: Page {page_id} executed JS successfully, return type: {type(result)}")
            return JSEvalSuccess(result=result)

        except PlaywrightError as e:
            error_msg = f"Execute JS failed (Playwright Error): {e}"
            logger.error(error_msg, exc_info=False)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)
        except Exception as e:
            error_msg = f"Execute JS failed (Unexpected Error): {e}"
            logger.error(error_msg, exc_info=True)
            return DelightfulBrowserError(error=error_msg, operation=operation_name, details=details)

    # --- Cleanup ---
    async def cleanup_temp_files(self):
        """Clean up all temporary files created by this browser instance"""
        if not self._temp_files:
            return
        logger.info(f"Start cleaning up {len(self._temp_files)} browser temp files...")
        # Use safe_delete for concurrent cleanup
        tasks = [safe_delete(f) for f in self._temp_files]
        await asyncio.gather(*tasks)
        cleared_count = len(self._temp_files) # Record count of cleaned files
        self._temp_files.clear()
        logger.info(f"Browser temp file cleanup completed ({cleared_count} files).")

    async def close(self) -> None:
        """Close browser, close all managed pages, unregister client and clean up temp files"""
        if not self._initialized:
            return
        logger.info("Start closing DelightfulBrowser...")
        try:
            # Close all managed pages
            page_close_tasks = []
            managed_ids = list(self._managed_page_ids) # Iterate over copy
            for page_id in managed_ids:
                page = await self.get_page_by_id(page_id) # Use method with built-in checks
                if page:
                    logger.debug(f"Request to close page: {page_id}")
                    page_close_tasks.append(page.close()) # Add close task
                # Actively unregister from registry even if page.close() fails or page already closed
                await self._page_registry.unregister_page(page_id)

            if page_close_tasks:
                # Concurrently close pages, ignore individual errors
                results = await asyncio.gather(*page_close_tasks, return_exceptions=True)
                for i, result in enumerate(results):
                    if isinstance(result, Exception):
                        logger.warning(f"Error closing page {managed_ids[i]}: {result}")

            self._managed_page_ids.clear()
            self._active_page_id = None
            logger.info("All managed pages requested to close and unregistered.")

            # Unregister browser client reference
            await self._browser_manager.unregister_client()
            self._active_context_id = None


            # Clean up temp files
            await self.cleanup_temp_files()

            self._initialized = False
            logger.info("DelightfulBrowser close completed.")

        except Exception as e:
            logger.error(f"Exception occurred closing DelightfulBrowser: {e}", exc_info=True)
        finally:
            # Ensure state is reset
            self._initialized = False
            self._active_page_id = None
            self._active_context_id = None
            self._managed_page_ids.clear()
            self._temp_files.clear() # Clear again just to be safe

    async def _wait_for_stable_network(self, page: Page, wait_time: float = 0.5, max_wait_time: float = 5.0):
        """Wait for network activity to stabilize (internal helper method)"""
        # Track pending requests and last activity time
        pending_requests = set()
        last_activity = asyncio.get_event_loop().time()

        # Define relevant resource types
        RELEVANT_RESOURCE_TYPES = {
            'document', 'xhr', 'fetch', 'script',
            'stylesheet', 'image', 'font'
        }

        # URL patterns to ignore
        IGNORED_URL_PATTERNS = {
            'analytics', 'tracking', 'beacon', 'telemetry',
            'adserver', 'advertising', 'facebook.com/plugins',
            'platform.twitter', 'heartbeat', 'ping', 'wss://'
        }

        # Network request handler function
        async def on_request(request):
            # Filter resource types
            if request.resource_type not in RELEVANT_RESOURCE_TYPES:
                return

            # Filter ignored URL patterns
            url = request.url.lower()
            if any(pattern in url for pattern in IGNORED_URL_PATTERNS):
                return

            # Filter data URLs and blob object URLs
            if url.startswith(('data:', 'blob:')):
                return

            nonlocal last_activity
            pending_requests.add(request)
            last_activity = asyncio.get_event_loop().time()

        # Network response handler function
        async def on_response(response):
            request = response.request
            if request not in pending_requests:
                return

            # Filter content types that don't need to be waited
            content_type = response.headers.get('content-type', '').lower()
            if any(t in content_type for t in ['streaming', 'video', 'audio', 'event-stream']):
                pending_requests.remove(request)
                return

            nonlocal last_activity
            pending_requests.remove(request)
            last_activity = asyncio.get_event_loop().time()

        # Set request and response listeners
        page.on("request", on_request)
        page.on("response", on_response)

        try:
            # Wait for network to stabilize
            start_time = asyncio.get_event_loop().time()
            while asyncio.get_event_loop().time() - start_time < max_wait_time:
                await asyncio.sleep(0.1)
                now = asyncio.get_event_loop().time()

                # If no pending requests and has been stable for wait_time, network is stable
                if len(pending_requests) == 0 and (now - last_activity) >= wait_time:
                    break

            # If timed out but still have requests
            if len(pending_requests) > 0:
                logger.debug(f"Wait for stable network timed out (>{max_wait_time}s), still have {len(pending_requests)} active requests")

        finally:
            # Remove listeners
            try:
                page.remove_listener("request", on_request)
                page.remove_listener("response", on_response)
            except Exception as remove_e:
                # Removing listeners may fail when page is closed, ignore error
                logger.debug(f"Error removing network listeners (page may be closed): {remove_e}")

# JSLoader moved to js_loader.py
# Other dependent classes (BrowserManager, DelightfulBrowserConfig, PageRegistry) imported from respective files
