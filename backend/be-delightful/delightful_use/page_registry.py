"""
Page registry module

Provides global page management functionality,
responsible for page registration, unregistration and state tracking.
"""

import asyncio
import logging
import math
from typing import Dict, List, Optional, Union

from pydantic import BaseModel, Field
from playwright.async_api import Page

from magic_use.js_loader import JSLoader
from magic_use.userscript_manager import UserscriptManager

# Configure logging
logger = logging.getLogger(__name__)


# Page state related data models
class ScrollPosition(BaseModel):
    """Page scroll position and size information"""
    x: float = 0.0
    y: float = 0.0
    document_width: float = 0.0
    document_height: float = 0.0
    viewport_width: float = 0.0
    viewport_height: float = 0.0


class PositionInfo(BaseModel):
    """Page relative position calculation information"""
    total_screens: float = 0.0
    current_screen: float = 0.0
    read_percent: float = 0.0
    remaining_percent: float = 0.0


class PageState(BaseModel):
    """Structured state information for a single page"""
    page_id: str
    url: Optional[str] = None
    title: Optional[str] = None
    context_id: Optional[str] = None
    scroll_position: Optional[ScrollPosition] = Field(default=None, description="Detailed scroll and size data")
    position_info: Optional[PositionInfo] = Field(default=None, description="Calculated relative position data")
    error: Optional[str] = Field(default=None, description="Error message that occurred when getting state")


class PageRegistry:
    """Page registry, responsible for global page management"""

    _instance = None
    _initialized = False
    _userscript_manager: Optional[UserscriptManager] = None
    _userscript_load_task: Optional[asyncio.Task] = None

    def __new__(cls):
        """Singleton pattern implementation"""
        if cls._instance is None:
            cls._instance = super(PageRegistry, cls).__new__(cls)
        return cls._instance

    def __init__(self):
        """Initialize page registry"""
        # If already initialized, skip
        if PageRegistry._initialized:
            return

        self._pages: Dict[str, Page] = {}  # Mapping from page ID to page object
        self._page_counter = 0  # Page ID counter
        self._js_loaders: Dict[str, JSLoader] = {}  # Mapping from page ID to JS loader
        self._page_contexts: Dict[str, str] = {}  # Mapping from page ID to context ID
        self._lock = asyncio.Lock()  # Used to protect registration and unregistration operations

        # Get UserscriptManager instance and load scripts in background
        # Use create_task to avoid blocking __init__
        if PageRegistry._userscript_manager is None and PageRegistry._userscript_load_task is None:
            async def _init_userscript_manager():
                try:
                    PageRegistry._userscript_manager = await UserscriptManager.get_instance()
                    await PageRegistry._userscript_manager.load_scripts()
                except Exception as e:
                    logger.error(f"Failed to initialize or load UserscriptManager: {e}", exc_info=True)
                finally:
                    PageRegistry._userscript_load_task = None # Clean up task reference

            PageRegistry._userscript_load_task = asyncio.create_task(_init_userscript_manager())
            logger.info("Started background Userscript loading task.")


        PageRegistry._initialized = True

    def _generate_page_id(self) -> str:
        """Generate unique page ID"""
        self._page_counter += 1
        return f"page_{self._page_counter}"

    async def register_page(self, page: Page, context_id: Optional[str] = None) -> str:
        """Register page

        Args:
            page: Playwright page object
            context_id: Context ID to which the page belongs, can be None

        Returns:
            str: Page ID
        """
        async with self._lock:
            page_id = self._generate_page_id()
            self._pages[page_id] = page

            # Create and associate JS loader
            self._js_loaders[page_id] = JSLoader(page)

            # Record the context to which the page belongs, if any
            if context_id:
                self._page_contexts[page_id] = context_id

            # Modify page load handler function
            async def handle_page_load_and_script_injection():
                # Check if page is still valid to avoid operations on closed pages
                if page.is_closed():
                    logger.warning(f"Page {page_id} was already closed before processing load event.")
                    return

                page_url = ""
                try:
                    page_url = page.url # Get URL first
                    # Only skip browser built-in pages
                    if page_url.startswith(('chrome://', 'chrome-error://', 'about:', 'edge://', 'firefox://')):
                        logger.debug(f"Page {page_id} URL: {page_url} is built-in page, skip script injection.")
                        return

                    # 1. Load core modules and initialize (marker, pure)
                    core_modules = ["marker", "pure"]
                    core_success = False
                    for module in core_modules:
                        # Ensure JS module loading function now handles page_id
                        load_result = await self.ensure_js_module_loaded(page_id, module)
                        if load_result.get(module, False):
                            core_success = True
                    if not core_success:
                        logger.warning(f"Page {page_id} URL: {page_url} core JS module loading incomplete, may affect subsequent operations.")
                        # Try to load userscript even if core module fails? Or return directly?
                        # return # Temporarily choose to return to avoid injection in incomplete state

                    # Initialize DelightfulMarker (if exists)
                    # Check if DelightfulMarker really exists to avoid unnecessary evaluate calls
                    if core_success and "marker" in core_modules: # Assume marker module provides DelightfulMarker
                        await page.evaluate("if(typeof window.DelightfulMarker !== 'undefined' && window.DelightfulMarker.mark) window.DelightfulMarker.mark()")

                    logger.info(f"Page {page_id} URL: {page_url} core JS module load completed.")


                    # 2. Inject matching userscripts
                    if self._userscript_manager:
                        # Ensure userscript manager has completed initialization
                        # Can add a simple wait, or rely on manager's internal initialized flag
                        if not self._userscript_manager._initialized:
                            # If still loading, can wait a bit
                            if PageRegistry._userscript_load_task:
                                logger.debug(f"Page {page_id} waiting for Userscript loading to complete...")
                                try:
                                     await asyncio.wait_for(PageRegistry._userscript_load_task, timeout=10.0) # Set timeout
                                except asyncio.TimeoutError:
                                     logger.warning(f"Userscript loading timeout for page {page_id}, userscripts may not be injected.")
                                except Exception as e:
                                     logger.error(f"Error waiting for Userscript loading: {e}", exc_info=True)

                        if self._userscript_manager._initialized:
                             # Get matching scripts (currently only handles document-end)
                             matching_scripts = self._userscript_manager.get_matching_scripts(url=page_url, run_at="document-end")
                             if matching_scripts:
                                 logger.info(f"Page {page_id} URL: {page_url} found {len(matching_scripts)} matching userscripts, preparing to inject...")
                                 for script in matching_scripts:
                                     try:
                                         # Check again if page is closed
                                         if page.is_closed():
                                             logger.warning(f"Page {page_id} closed before injecting script '{script.name}'.")
                                             break # Stop injecting subsequent scripts
                                         await page.add_script_tag(content=script.content)
                                         logger.debug(f"Successfully injected script '{script.name}' to page {page_id}")
                                     except Exception as e:
                                         # Similarly handle Playwright navigation/closing errors
                                         if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                                             logger.warning(f"Navigation or closing error occurred for page {page_id} when injecting userscript '{script.name}': {e!s}")
                                             break # Stop injecting subsequent scripts
                                         else:
                                             logger.error(f"Failed to inject userscript '{script.name}' to page {page_id}: {e!s}", exc_info=True)
                             else:
                                logger.debug(f"Page {page_id} URL: {page_url} no matching userscripts found (run_at=document-end).")
                        else:
                             logger.warning(f"Userscript manager failed to initialize successfully, cannot inject userscripts for page {page_id}.")
                    else:
                         logger.warning("Userscript manager instance does not exist, cannot inject userscripts.")

                except Exception as e:
                    # Capture any other exceptions during processing
                    # playwright._impl._api_types.Error: Navigation or evaluation failed
                    # Such errors may occur when page navigates or closes, need graceful handling
                    if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                         logger.warning(f"Page {page_id} (URL: {page_url}) encountered navigation or closing error during load/injection processing: {e!s}")
                    else:
                         logger.error(f"Unexpected error processing page {page_id} (URL: {page_url}) load and script injection: {e!s}", exc_info=True)

            # Listen for page events
            # Execute core JS loading and userscript injection after page fully loads (including all resources)
            # This triggers later than domcontentloaded event, page is more stable, reduces navigation conflicts
            # Use lambda: asyncio.create_task(...) to ensure async execution without blocking event loop
            page.on("load", lambda: asyncio.create_task(handle_page_load_and_script_injection()))

            # Handle page close event
            await self._set_page_close_listener(page, page_id)

            logger.info(f"Page registered: {page_id}")
            return page_id

    async def unregister_page(self, page_id: str) -> None:
        """Unregister page

        Args:
            page_id: Page ID
        """
        async with self._lock:
            self._pages.pop(page_id, None)
            self._js_loaders.pop(page_id, None)
            self._page_contexts.pop(page_id, None)
            logger.info(f"Page unregistered: {page_id}")

    async def _set_page_close_listener(self, page: Page, page_id: str) -> None:
        """Set page close event listener

        Automatically unregister page from registry when page closes

        Args:
            page: Playwright Page object
            page_id: Page ID
        """
        # Define close handling function
        async def handle_close():
            # Try to remove event listeners before unregistering to reduce potential memory leak risks
            # Note: Playwright's page.remove_listener may need the original lambda function reference, which is troublesome
            # Usually Playwright automatically cleans up listeners when page closes, so explicit removal can be omitted here
            # page.remove_listener("domcontentloaded", the_lambda_reference) # Hard to get lambda reference
            await self.unregister_page(page_id)

        # Listen for page close event
        page.on("close", lambda: asyncio.create_task(handle_close()))

    async def get_page_by_id(self, page_id: str) -> Optional[Page]:
        """Get page by ID

        Args:
            page_id: Page ID

        Returns:
            Optional[Page]: Page object, returns None if not exists
        """
        # Add check, if page object exists but is closed, also return None
        page = self._pages.get(page_id)
        if page and page.is_closed():
             logger.debug(f"Attempted to get closed page: {page_id}")
             # Remove closed page from registry to prevent subsequent access
             # Note: Close event should have already triggered unregister_page, this is double insurance
             # If unregister_page did not execute for some reason, this can compensate
             # But need to pay attention to concurrency issues, better to rely on close event
             # async with self._lock:
             #     self._pages.pop(page_id, None)
             #     self._js_loaders.pop(page_id, None)
             #     self._page_contexts.pop(page_id, None)
             return None
        return page

    async def get_context_id_for_page(self, page_id: str) -> Optional[str]:
        """Get context ID that page belongs to

        Args:
            page_id: Page ID

        Returns:
            Optional[str]: Context ID, returns None if not exists
        """
        # Can also add page existence check here, if page_id not in self._pages, return None directly
        if page_id not in self._pages:
             return None
        return self._page_contexts.get(page_id)

    async def ensure_js_module_loaded(self, page_id: str, module_names: Union[str, List[str]]) -> Dict[str, bool]:
        """Ensure specified page loads JS modules

        Args:
            page_id: Page ID
            module_names: Module name or list of names

        Returns:
            Dict[str, bool]: Load results, keys are module names, values are success status
        """
        js_loader = self._js_loaders.get(page_id)
        page = await self.get_page_by_id(page_id) # Use get_page_by_id to get page, includes close check

        # Add check: if page or loader not exists, or page is closed, don't load
        if not js_loader or not page: # page already checked if closed
            logger.warning(f"Page {page_id} invalid or closed, cannot load JS modules: {module_names}")
            if isinstance(module_names, str):
                return {module_names: False}
            else:
                return {name: False for name in module_names}

        # Unify as list
        if isinstance(module_names, str):
            module_names = [module_names]

        results = {}
        for module_name in module_names:
            try:
                # Check page status again, since loading may be async
                if page.is_closed():
                     logger.warning(f"Page {page_id} closed before attempting to load module '{module_name}'.")
                     results[module_name] = False
                     continue
                success = await js_loader.load_module(module_name)
                results[module_name] = success
            except Exception as e:
                # Similarly handle Playwright navigation/closing errors
                if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                     logger.warning(f"Navigation or closing error for page {page_id} when loading JS module '{module_name}': {e!s}")
                else:
                     logger.error(f"Failed to load JS module: {module_name} on page {page_id}, {e}", exc_info=True)
                results[module_name] = False

        return results

    async def _get_page_scroll_info(self, page: Page) -> Dict[str, Union[int, float]]:
        """Get page scroll information

        Args:
            page: Playwright Page object

        Returns:
            Dict: Scroll information, including position and document height
        """
        # Add page close check
        if page.is_closed():
            logger.warning(f"Attempted to get scroll information on closed page.")
            return {
                "x": 0, "y": 0, "document_width": 0, "document_height": 0,
                "viewport_width": 0, "viewport_height": 0
            }
        try:
            scroll_info = await page.evaluate("""
                () => {
                    return {
                        x: window.scrollX || window.pageXOffset,
                        y: window.scrollY || window.pageYOffset,
                        document_width: document.documentElement.scrollWidth,
                        document_height: document.documentElement.scrollHeight,
                        viewport_width: window.innerWidth,
                        viewport_height: window.innerHeight
                    };
                }
            """)
            return scroll_info
        except Exception as e:
            # Handle Playwright navigation/closing errors
            if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                 logger.warning(f"Navigation or closing error when getting page scroll info: {e!s}")
            else:
                 logger.error(f"Failed to get page scroll info: {e}", exc_info=True)
            return {
                "x": 0, "y": 0, "document_width": 0, "document_height": 0,
                "viewport_width": 0, "viewport_height": 0
            }

    async def get_page_state(self, page_id: str) -> PageState:
        """Get page state information

        Get complete page state based on page ID, including scroll position, content percentage, etc.
        Return structured PageState object for clients to understand and process page state.

        Args:
            page_id: Page ID

        Returns:
            PageState: Structured page state information
        """
        page = await self.get_page_by_id(page_id) # Use method that includes close check to get page
        if not page:
            return PageState(page_id=page_id, error=f"Page does not exist or is closed: {page_id}")

        try:
            # Get basic information
            url = page.url # If page just closed, this may error
            title = await page.title() # Same as above

            # Get scroll position information
            scroll_info = await self._get_page_scroll_info(page)

            # Create scroll position object
            scroll_position = ScrollPosition(
                x=scroll_info.get("x", 0),
                y=scroll_info.get("y", 0),
                document_width=scroll_info.get("document_width", 0),
                document_height=scroll_info.get("document_height", 0),
                viewport_width=scroll_info.get("viewport_width", 0),
                viewport_height=scroll_info.get("viewport_height", 0)
            )

            # Calculate relative position information
            position_info = None
            current_pos = scroll_info.get("y", 0)
            doc_height = scroll_info.get("document_height", 0)
            viewport_height = scroll_info.get("viewport_height", 0)

            # If document has height and viewport height, calculate relative position data
            if doc_height > 0 and viewport_height > 0:
                # Calculate remaining and read percentage
                remaining_height = max(0, doc_height - (current_pos + viewport_height))
                remaining_percent = round(remaining_height / doc_height * 100)
                read_percent = 100 - remaining_percent

                # Calculate total screens and current screen position
                total_screens = math.ceil(doc_height / viewport_height) if viewport_height > 0 else 1
                current_screen = math.ceil(current_pos / viewport_height + 1) if viewport_height > 0 else 1


                # Create position info object
                position_info = PositionInfo(
                    total_screens=total_screens,
                    current_screen=current_screen,
                    read_percent=read_percent,
                    remaining_percent=remaining_percent
                )

            # Return complete page state
            return PageState(
                page_id=page_id,
                url=url,
                title=title,
                scroll_position=scroll_position,
                position_info=position_info,
                context_id=self._page_contexts.get(page_id) # context_id does not depend on page object state
            )
        except Exception as e:
             # Handle Playwright navigation/closing errors
             if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                 logger.warning(f"Navigation or closing error when getting page state: {page_id}, {e!s}")
                 # Return partial information or mark error
                 return PageState(
                    page_id=page_id,
                    error=f"Page closed or navigation failed when getting page state: {str(e)}"
                 )
             else:
                 logger.error(f"Failed to get page state: {page_id}, {e}", exc_info=True)
                 return PageState(
                     page_id=page_id,
                     error=f"Failed to get page state: {str(e)}"
                 )

    async def get_all_pages(self) -> Dict[str, Page]:
        """Get all **currently open** pages

        Returns:
            Dict[str, Page]: Mapping from page ID to page objects (only includes non-closed pages)
        """
        # Return a copy and filter out closed pages
        active_pages = {}
        all_page_ids = list(self._pages.keys()) # Create a copy of keys for iteration
        for page_id in all_page_ids:
             page = await self.get_page_by_id(page_id) # Use method that includes check
             if page:
                 active_pages[page_id] = page
        return active_pages

    async def get_page_basic_info(self, page_id: str) -> Dict[str, any]:
        """Get page basic information

        Args:
            page_id: Page ID

        Returns:
            Dict: Page basic information including URL and title, returns error info if page doesn't exist or is closed
        """
        page = await self.get_page_by_id(page_id) # Use method that includes close check
        if not page:
            return {"page_id": page_id, "error": "Page does not exist or is closed"}

        try:
            url = page.url
            title = await page.title()
            return {
                "page_id": page_id,
                "url": url,
                "title": title,
                "context_id": self._page_contexts.get(page_id)
            }
        except Exception as e:
             # Handle Playwright navigation/closing errors
             if "Navigation or evaluation failed" in str(e) or "Target page, context or browser has been closed" in str(e):
                  logger.warning(f"Navigation or closing error when getting page basic info: {page_id}, {e!s}")
                  return {
                      "page_id": page_id,
                      "error": f"Page closed or navigation failed when getting page basic info: {str(e)}"
                  }
             else:
                  logger.error(f"Failed to get page basic info: {page_id}, {e}", exc_info=True)
                  return {
                      "page_id": page_id,
                      "error": f"Failed to get page basic info: {str(e)}"
                  }
