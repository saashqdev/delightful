"""
Browser Management Module

Manages the global unique Playwright browser instance and context,
provides browser initialization, context creation and lifecycle management functionality.
"""

import asyncio
import logging
import os
from typing import Dict, Optional

from playwright.async_api import Browser, BrowserContext, async_playwright

from magic_use.magic_browser_config import MagicBrowserConfig

# Set up logging
logger = logging.getLogger(__name__)


class BrowserManager:
    """Browser manager, responsible for global browser instance and context lifecycle management"""

    _instance = None
    _initialized = False

    def __new__(cls):
        """Singleton pattern implementation"""
        if cls._instance is None:
            cls._instance = super(BrowserManager, cls).__new__(cls)
        return cls._instance

    def __init__(self):
        """Initialize browser manager"""
        # Skip if already initialized
        if BrowserManager._initialized:
            return

        self._playwright = None
        self._browser = None
        self._initialized = False
        self._contexts: Dict[str, BrowserContext] = {}  # Context ID to object mapping
        self._context_counter = 0  # Context ID counter
        self._lock = asyncio.Lock()  # Lock for protecting concurrent initialization
        self._storage_save_tasks = {}  # Storage state periodic save tasks
        self._client_refs = 0  # Client reference count

        BrowserManager._initialized = True

    async def register_client(self) -> None:
        """Register browser client, increment reference count"""
        async with self._lock:
            self._client_refs += 1
            logger.debug(f"Browser client registered, current reference count: {self._client_refs}")

    async def unregister_client(self) -> None:
        """Unregister browser client, decrement reference count

        When reference count reaches zero, automatically close the browser instance
        """
        async with self._lock:
            if self._client_refs > 0:
                self._client_refs -= 1

            logger.debug(f"Browser client unregistered, current reference count: {self._client_refs}")

            # Auto close browser if no active clients
            if self._client_refs == 0 and self._initialized:
                await self.close()

    async def initialize(self, config: MagicBrowserConfig = None) -> None:
        """Initialize browser instance

        Args:
            config: Browser configuration, if None use default scraping configuration
        """
        # Use lock to prevent concurrent initialization
        async with self._lock:
            if self._initialized:
                return

            self.config = config or MagicBrowserConfig.create_for_scraping()

            try:
                # Start Playwright
                self._playwright = await async_playwright().start()

                # Get browser type
                browser_type_mapping = {
                    "chromium": self._playwright.chromium,
                    "firefox": self._playwright.firefox,
                    "webkit": self._playwright.webkit
                }

                browser_type = browser_type_mapping.get(self.config.browser_type.lower())
                if not browser_type:
                    logger.warning(f"Unknown browser type: {self.config.browser_type}, using chromium")
                    browser_type = self._playwright.chromium

                # Remove user_data_dir related config to avoid conflicts
                browser_args = self.config.browser_args.copy()
                user_data_dir_args = [arg for arg in browser_args if '--user-data-dir' in arg]
                for arg in user_data_dir_args:
                    browser_args.remove(arg)
                    logger.warning(f"Removed user_data_dir parameter: {arg}")

                # Launch browser using generated launch options from config
                launch_options = self.config.to_launch_options()
                launch_options["args"] = browser_args
                self._browser = await browser_type.launch(**launch_options)

                logger.info(f"Browser started: {self.config.browser_type}")
                self._initialized = True
            except Exception as e:
                logger.error(f"Failed to start browser: {e}")
                if self._playwright:
                    await self._playwright.stop()
                    self._playwright = None
                raise

    async def get_context(self, config: Optional[MagicBrowserConfig] = None) -> tuple[str, BrowserContext]:
        """Get or create browser context

        If browser is not initialized yet, initialize it first

        Args:
            config: Browser configuration, if None use manager's default configuration

        Returns:
            tuple: (context ID, context object)
        """
        if not self._initialized:
            await self.initialize(config)

        # Create a new context
        context_id = self._generate_context_id()
        context_config = config or self.config
        context_options = await context_config.to_context_options()

        # Create context
        context = await self._browser.new_context(**context_options)

        # Inject anti-fingerprinting script
        try:
            # Pull language setting from context_config
            language_setting = context_config.language or "zh-CN"
            # Extract primary language, e.g., "zh-CN" from "zh-CN,zh;q=0.9,en;q=0.8"
            primary_language = language_setting.split(',')[0]
            # Build language array with primary language first
            languages_array = f"['{primary_language}'"
            # Append other preferred languages in priority order
            secondary_languages = []
            for lang_pref in language_setting.split(',')[1:]:
                if ';' in lang_pref:
                    lang = lang_pref.split(';')[0].strip()
                    if lang and lang != primary_language:
                        secondary_languages.append(f"'{lang}'")
                elif lang_pref.strip() and lang_pref.strip() != primary_language:
                    secondary_languages.append(f"'{lang_pref.strip()}'")

            if secondary_languages:
                languages_array += ", " + ", ".join(secondary_languages)
            languages_array += "]"

            anti_fingerprint_script = f"""
            // Hide webdriver property
            Object.defineProperty(navigator, 'webdriver', {{
                get: () => undefined
            }});

            // Unify language settings per configuration
            Object.defineProperty(navigator, 'languages', {{
                get: () => {languages_array}
            }});

            // Simulate plugins
            Object.defineProperty(navigator, 'plugins', {{
                get: () => [1, 2, 3, 4, 5]
            }});

            // Chrome runtime environment
            window.chrome = {{ runtime: {{}} }};

            // Permissions query
            const originalQuery = window.navigator.permissions.query;
            window.navigator.permissions.query = (parameters) => (
                parameters.name === 'notifications' ?
                    Promise.resolve({{ state: Notification.permission }}) :
                    originalQuery(parameters)
            );

            // Shadow DOM handling
            (function () {{
                const originalAttachShadow = Element.prototype.attachShadow;
                Element.prototype.attachShadow = function attachShadow(options) {{
                    return originalAttachShadow.call(this, {{ ...options, mode: "open" }});
                }};
            }})();
            """
            await context.add_init_script(anti_fingerprint_script)
            logger.debug(f"Injected anti-fingerprint JS for context {context_id}, language settings: {languages_array}")
        except Exception as e:
            logger.error(f"Failed to inject anti-fingerprint JS: {e}")

        # Register context
        self._contexts[context_id] = context

        # Schedule periodic storage state saving
        if context_config.storage_state_file:
            save_task = asyncio.create_task(self._periodic_save_storage_state(context_id))
            self._storage_save_tasks[context_id] = save_task

        logger.info(f"Created browser context: {context_id}")
        return context_id, context

    def _generate_context_id(self) -> str:
        """Generate unique context ID"""
        self._context_counter += 1
        return f"ctx_{self._context_counter}"

    async def get_context_by_id(self, context_id: str) -> Optional[BrowserContext]:
        """Get browser context by ID

        Args:
            context_id: Context ID

        Returns:
            Optional[BrowserContext]: Context object, None if not found
        """
        return self._contexts.get(context_id)

    async def _save_context_storage_state(self, context_id: str) -> None:
        """Save context storage state

        Args:
            context_id: Context ID
        """
        context = self._contexts.get(context_id)
        if not context or not self.config.storage_state_file:
            if not context:
                logger.warning(f"Failed to save storage state: context {context_id} does not exist")
            elif not self.config.storage_state_file:
                logger.warning(f"Failed to save storage state: storage state file path not configured, context {context_id}")
            return

        try:
            await self.config.save_storage_state(context)
        except Exception as e:
            logger.error(f"Failed to save context storage state: {context_id}, {e}")

    async def _periodic_save_storage_state(self, context_id: str) -> None:
        """Periodically save context storage state task

        Args:
            context_id: Context ID
        """
        try:
            while context_id in self._contexts:
                # Save storage state every 15 minutes
                await asyncio.sleep(15 * 60)
                await self._save_context_storage_state(context_id)
        except asyncio.CancelledError:
            # When task is cancelled, no need to save state again, handled by close_context
            # await self._save_context_storage_state(context_id)
            pass
        except Exception as e:
            logger.error(f"Periodic storage state save task failed: {context_id}, {e}")

    async def close_context(self, context_id: str) -> None:
        """Close and remove a context

        Args:
            context_id: Context ID
        """
        # Retrieve context first instead of popping immediately
        context = self._contexts.get(context_id)

        if context:
            # Cancel periodic save task
            save_task = self._storage_save_tasks.pop(context_id, None)
            if save_task:
                save_task.cancel()
                try:
                    # Await completion and ignore CancelledError
                    await save_task
                except asyncio.CancelledError:
                    logger.debug(f"Save task for context {context_id} was cancelled.")
                except Exception as e:
                    logger.error(f"Error while waiting for save task of context {context_id}: {e}")

            # Save final storage state before removal
            if self.config and self.config.storage_state_file:
                try:
                    logger.debug(f"Attempting to save final storage state for context {context_id}...")
                    # Save using the context object directly
                    await self.config.save_storage_state(context)
                    logger.info(f"Successfully saved final storage state for context {context_id}.")
                except Exception as e:
                    logger.error(f"Failed to save final storage state for context {context_id}: {e}")
            else:
                logger.debug(f"No storage state path configured; skipping final state save for context {context_id}.")

            # Remove context ID from dictionary
            self._contexts.pop(context_id, None)

            # Close context
            try:
                await context.close()
                logger.info(f"Closed browser context: {context_id}")
            except Exception as e:
                logger.error(f"Error closing Playwright context {context_id}: {e}")
        else:
            logger.warning(f"Attempted to close a non-existent or already closed context: {context_id}")

    async def close(self) -> None:
        """Close the browser and Playwright

        Closes all contexts and the browser instance
        """
        if not self._initialized:
            return

        try:
            # Close all contexts
            context_ids = list(self._contexts.keys())
            for context_id in context_ids:
                await self.close_context(context_id)

            # Close the browser
            if self._browser:
                await self._browser.close()
                self._browser = None
                logger.info("Browser closed")

            # Stop Playwright
            if self._playwright:
                await self._playwright.stop()
                self._playwright = None
                logger.info("Playwright stopped")

            self._initialized = False
        except Exception as e:
            logger.error(f"Failed to close browser: {e}")
            raise
