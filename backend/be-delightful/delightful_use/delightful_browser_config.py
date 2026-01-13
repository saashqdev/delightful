"""
Browser configuration module

Provides browser configuration classes and related functionality for customizing browser behavior and features.
Includes bypassing anti-scraping detection, performance optimization, user agent spoofing, and session persistence.
"""

import logging
import os
import aiohttp
from dataclasses import dataclass, field
from typing import Any, Dict, List, Optional

from app.paths import PathManager
from agentlang.config.config import config

# Set up logging
logger = logging.getLogger(__name__)

# Default browser header configuration
DEFAULT_HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
    'Sec-Ch-Ua': '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
    'Sec-Ch-Ua-Mobile': '?0',
    'Sec-Ch-Ua-Platform': '"macOS"'
}

@dataclass
class DelightfulBrowserProxyConfig:
    """Proxy server configuration"""

    # Proxy server address (e.g., http://proxy.example.com:8080)
    server: Optional[str] = None

    # Username (if authentication is required)
    username: Optional[str] = None

    # Password (if authentication is required)
    password: Optional[str] = None

    # List of addresses to bypass proxy (e.g., ["localhost", "127.0.0.1"])
    bypass: Optional[List[str]] = None

    def to_dict(self) -> Optional[Dict[str, Any]]:
        """Convert to proxy configuration dictionary"""
        if not self.server:
            return None

        result = {"server": self.server}

        if self.username and self.password:
            result["username"] = self.username
            result["password"] = self.password

        if self.bypass:
            result["bypass"] = ",".join(self.bypass)

        return result


@dataclass
class DelightfulBrowserConfig:
    """Browser configuration class"""

    # Browser type: chromium, firefox, webkit
    browser_type: str = "chromium"

    # Whether to run in headless mode (without displaying browser window)
    headless: bool = True

    # Browser window dimensions
    viewport_width: int = 1280
    viewport_height: int = 1940

    # Whether to disable security features (same-origin policy, etc.)
    disable_security: bool = False

    # Whether to enable anti-scraping countermeasures (bypass website anti-scraping detection)
    bypass_anti_scraping: bool = True

    # Whether to enable browser fingerprint masking
    fingerprint_mask: bool = True

    # Whether to enable performance optimization
    performance_optimization: bool = True

    # Whether to disable popups and notifications
    disable_popups: bool = True

    # Whether to enable advanced anti-scraping bypass settings
    advanced_bypass_anti_scraping: bool = False

    # Proxy configuration
    proxy: Optional[DelightfulBrowserProxyConfig] = None

    # Implicit wait timeout (milliseconds)
    default_timeout: int = 30000

    # Save path for screenshots and downloaded files
    download_path: Optional[str] = None

    # Browser launch arguments
    browser_args: List[str] = field(default_factory=list)

    # User agent string and other HTTP headers
    user_agent: Optional[str] = DEFAULT_HEADERS['User-Agent']

    # Custom HTTP headers
    extra_headers: Dict[str, str] = field(default_factory=lambda: DEFAULT_HEADERS.copy())

    # Browser language setting
    language: str = "en-US,zh;q=0.9,en;q=0.8"

    # Whether to enable geolocation spoofing
    geolocation_spoofing: bool = False

    # Geolocation (latitude and longitude)
    geolocation: Optional[Dict[str, float]] = None

    # Timezone ID
    timezone_id: Optional[str] = None

    # Browser storage state save path
    storage_state_file: Optional[str] = None

    # Browser permissions list
    permissions: Optional[List[str]] = None

    def __post_init__(self):
        """Post-initialization processing"""
        # Set default download path
        if not self.download_path:
            self.download_path = str(PathManager.get_workspace_dir() / "webview_reports")

        # Ensure browser arguments is a list
        if not isinstance(self.browser_args, list):
            self.browser_args = []

        # If disabling security features, add corresponding arguments
        if self.disable_security:
            if self.browser_type == "chromium":
                security_args = [
                    "--disable-web-security",
                    "--allow-running-insecure-content",
                    "--disable-features=IsolateOrigins,site-per-process"
                ]
                for arg in security_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # If enabling anti-scraping bypass, add corresponding arguments
        if self.bypass_anti_scraping:
            if self.browser_type == "chromium":
                anti_scraping_args = [
                    '--disable-blink-features=AutomationControlled',  # Hide automation control features to prevent websites from detecting automated browsers
                    '--disable-features=IsolateOrigins,site-per-process',  # Disable site isolation, allow cross-origin operations, avoid some security restrictions
                    '--disable-site-isolation-trials',  # Disable site isolation trials, improve compatibility with complex websites
                    '--disable-web-security',  # Disable web security features like same-origin policy, allow cross-origin requests
                    '--disable-sync',  # Disable Google account sync, reduce feature exposure
                    '--disable-background-networking',  # Disable background network requests, avoid unnecessary connections
                    '--disable-background-timer-throttling',  # Disable background timer throttling, improve performance
                    '--disable-backgrounding-occluded-windows',  # Disable resource throttling for occluded windows
                    '--disable-breakpad',  # Disable crash reporting, avoid sending data to Google
                    '--disable-client-side-phishing-detection',  # Disable client-side phishing detection, reduce requests to Google
                    '--disable-component-update',  # Disable component updates, avoid version changes
                    '--disable-default-apps',  # Disable default apps, reduce browser fingerprint
                    '--disable-infobars',  # Disable top info bars, avoid "Chrome is being controlled by automated software" notification
                    '--disable-notifications',  # Disable notification popups, avoid interference
                    '--no-default-browser-check',  # Don't check if it's the default browser
                    '--no-first-run',  # Skip first-run experience, avoid welcome pages
                    '--password-store=basic',  # Use basic password storage instead of system keychain, reduce system integration
                    '--use-mock-keychain',  # Use mock keychain, avoid accessing system keychain
                    '--disable-cookie-encryption',  # Disable cookie encryption, easier to read and modify cookies
                    '--allow-pre-commit-input',  # Allow input before page rendering is complete, improve responsiveness
                ]
                for arg in anti_scraping_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # If enabling browser fingerprint masking, add corresponding arguments
        if self.fingerprint_mask:
            if self.browser_type == "chromium":
                fingerprint_args = [
                    "--js-flags=--random-seed=1157259159",  # Make all JS random numbers deterministic
                    "--force-device-scale-factor=2",  # Standardize device scale factor
                    "--hide-scrollbars",  # Hide scrollbars
                    "--force-color-profile=srgb",  # Use consistent color profile
                    "--font-render-hinting=none",  # Ignore OS font hinting
                    "--disable-2d-canvas-clip-aa",  # Disable 2D canvas clip anti-aliasing
                    "--disable-partial-raster"  # Disable partial rasterization
                ]
                for arg in fingerprint_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # If enabling performance optimization, add corresponding arguments
        if self.performance_optimization:
            if self.browser_type == "chromium":
                performance_args = [
                    "--disable-renderer-backgrounding",  # Don't throttle tab rendering based on focus
                    "--disable-background-networking",  # Don't throttle network requests based on focus
                    "--disable-background-timer-throttling",  # Don't throttle timers
                    "--disable-backgrounding-occluded-windows",  # Don't throttle occluded windows
                    "--disable-ipc-flooding-protection",  # Don't throttle IPC communication
                    "--disable-lazy-loading",  # Preload all content instead of loading on demand
                    "--disable-extensions-http-throttling",  # Don't throttle HTTP traffic
                    "--disable-back-forward-cache",  # Disable back-forward navigation cache
                    "--remote-debugging-address=0.0.0.0",  # Allow remote debugging
                    "--enable-experimental-extension-apis"  # Enable experimental extension APIs
                ]
                for arg in performance_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # If disabling popups and notifications, add corresponding arguments
        if self.disable_popups:
            if self.browser_type == "chromium":
                popup_args = [
                    "--no-first-run",  # Disable first-run experience
                    "--no-default-browser-check",  # Disable default browser check
                    "--disable-infobars",  # Disable info bars
                    "--disable-notifications",  # Disable notifications
                    "--disable-desktop-notifications",  # Disable desktop notifications
                    "--deny-permission-prompts",  # Deny permission prompts
                    "--disable-session-crashed-bubble",  # Disable session crashed bubble
                    "--disable-hang-monitor",  # Disable hang monitor
                    "--disable-popup-blocking",  # Disable popup blocking
                    "--disable-component-update",  # Disable component updates
                    "--suppress-message-center-popups",  # Suppress message center popups
                    "--disable-client-side-phishing-detection",  # Disable client-side phishing detection
                    "--disable-print-preview",  # Disable print preview
                    "--disable-speech-api",  # Disable speech API
                    "--disable-speech-synthesis-api",  # Disable speech synthesis API
                    "--no-pings",  # Disable ping requests
                    "--disable-default-apps",  # Disable default apps
                    "--ash-no-nudges",  # Disable nudges
                    "--disable-search-engine-choice-screen",  # Disable search engine choice screen
                    "--disable-datasaver-prompt",  # Disable data saver prompt
                    "--block-new-web-contents",  # Block new web contents
                    "--disable-breakpad",  # Disable crash reporting
                    "--disable-domain-reliability"  # Disable domain reliability
                ]
                for arg in popup_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # If enabling advanced anti-scraping bypass settings, add corresponding arguments
        if self.advanced_bypass_anti_scraping:
            if self.browser_type == "chromium":
                advanced_args = [
                    "--disable-features=GlobalMediaControls,MediaRouter,DialMediaRouteProvider",  # Disable media controls
                    "--simulate-outdated-no-au=\"Tue, 31 Dec 2099 23:59:59 GMT\"",  # Simulate outdated, disable browser auto-update
                    "--export-tagged-pdf",  # Export tagged PDF
                    "--generate-pdf-document-outline",  # Generate PDF document outline
                    "--metrics-recording-only",  # Metrics recording only
                    "--silent-debugger-extension-api",  # Silent debugger extension API
                    "--noerrdialogs",  # No error dialogs
                    "--disable-prompt-on-repost"  # Disable prompt on repost
                ]
                for arg in advanced_args:
                    if arg not in self.browser_args:
                        self.browser_args.append(arg)

        # Set language
        if self.language:
            lang_arg = f"--lang={self.language}"
            if lang_arg not in self.browser_args:
                self.browser_args.append(lang_arg)

    def to_launch_options(self) -> Dict[str, Any]:
        """Convert to Playwright browser launch options"""
        options = {
            "headless": self.headless,
            "args": self.browser_args,
            "timeout": self.default_timeout,
        }

        if self.download_path:
            # Ensure download directory exists
            os.makedirs(self.download_path, exist_ok=True)
            options["downloads_path"] = self.download_path

        # Add proxy configuration
        if self.proxy:
            proxy_dict = self.proxy.to_dict()
            if proxy_dict:
                options["proxy"] = proxy_dict

        return options

    async def to_context_options(self) -> Dict[str, Any]:
        """Convert to Playwright browser context options"""
        options = {
            "viewport": {
                "width": self.viewport_width,
                "height": self.viewport_height
            },
            "accept_downloads": True,
            "extra_http_headers": self.extra_headers,
            "bypass_csp": self.disable_security,  # Bypass content security policy
            "ignore_https_errors": self.disable_security  # Ignore HTTPS certificate errors
        }

        if self.user_agent:
            options["user_agent"] = self.user_agent

        # Add geolocation spoofing
        if self.geolocation_spoofing and self.geolocation:
            options["geolocation"] = self.geolocation

        # Add timezone setting (default to Shanghai, China)
        options["timezone_id"] = self.timezone_id or "America/Toronto"

        # Add language setting (default to Chinese)
        locale = self.language.split(',')[0] if self.language else "en-US"
        options["locale"] = locale

        # Load storage state (including cookies and localStorage)
        if self.storage_state_file:
            if os.path.exists(self.storage_state_file):
                try:
                    # Directly provide file path to storage_state parameter
                    options["storage_state"] = self.storage_state_file
                    logger.info(f"Will load storage state from {self.storage_state_file}")
                except Exception as e:
                    logger.warning(f"Failed to load storage state: {e}")
            else:
                # Try to download template from cloud
                try:
                    # Get template URL from configuration
                    template_url = config.get("browser.storage_state_template_url")

                    if not template_url:
                        logger.info("Browser storage state template URL not configured, skipping storage state loading")
                        return options

                    logger.info(f"Local storage state file does not exist, attempting to download template from cloud: {template_url}")

                    # Use async request to download template
                    async with aiohttp.ClientSession() as session:
                        async with session.get(template_url) as response:
                            if response.status == 200:
                                # Ensure directory exists
                                os.makedirs(os.path.dirname(self.storage_state_file), exist_ok=True)
                                # Save template to local file
                                content = await response.read()
                                with open(self.storage_state_file, 'wb') as f:
                                    f.write(content)
                                options["storage_state"] = self.storage_state_file
                                logger.info(f"Downloaded template from cloud and saved to {self.storage_state_file}")
                            else:
                                logger.warning(f"Failed to download template from cloud, status code: {response.status}")
                except Exception as e:
                    logger.warning(f"Failed to download template from cloud: {e}")

        return options

    async def save_storage_state(self, context) -> None:
        """
        Save complete browser storage state to file (including cookies and localStorage)

        Args:
            context: Playwright browser context
        """
        if not self.storage_state_file:
            return

        try:
            # Use context's storage_state method to save directly to file
            await context.storage_state(path=self.storage_state_file)
            logger.info(f"Saved storage state to {self.storage_state_file}")
        except Exception as e:
            logger.error(f"Failed to save storage state: {e}")

    @classmethod
    def create_for_scraping(cls) -> 'DelightfulBrowserConfig':
        """Create configuration optimized for web scraping"""
        return cls(
            headless=True,
            disable_security=True,  # Enable security restriction removal to solve CSP limitation issues
            bypass_anti_scraping=True,
            fingerprint_mask=True,
            performance_optimization=True,
            disable_popups=True,
            advanced_bypass_anti_scraping=True,
            # Shanghai, China coordinates
            geolocation={"latitude": 31.230416, "longitude": 121.473701},
            geolocation_spoofing=True,
            timezone_id="America/Toronto",
            # Cookie configuration
            storage_state_file=str(PathManager.get_browser_storage_state_file()),
            permissions=["geolocation", "notifications"]
        )
