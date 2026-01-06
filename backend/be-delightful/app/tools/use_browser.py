"""Browser usage tool

Provides atomic browser operation capabilities based on a modular operation architecture.

# Browser Tool Architecture

## Design Philosophy

UseBrowser tool is a browser control tool based on a modular operation architecture, adopting the following design principles:

1. **Modular Operations**:
   - All browser operations are split into atomic operation units
   - Each operation is encapsulated as an independent function with clear input/output
   - Supports dynamic extension of new operation types

2. **Unified Interface**:
   - All operations are called through unified JSON format parameters
   - Operation results follow standardized response formats
   - Automatically generates operation documentation and examples

3. **Resource Management**:
   - Browser instance lifecycle is maintained in tool context
   - Supports multi-page and session state persistence
   - Automatic screenshots and state tracking

4. **User Experience Optimization**:
   - Automatically generates page location descriptions
   - Smart screenshot avoidance of duplicates
   - Friendly error handling and prompts

## Architecture Components

This tool depends on the following core components:
- **OperationsRegistry**: Operation registration and management center
- **Browser**: Underlying browser control interface
- **OperationGroup**: Functionally grouped operation collections
- **BaseOperationParams**: Operation parameters base class

## Extension Approach

To extend browser tool capabilities:
1. Add new operations in the appropriate OperationGroup
2. Operations are automatically registered to OperationsRegistry
3. UseBrowser tool automatically discovers and integrates new operations
4. Tool description automatically updates to include new operation documentation

No need to modify UseBrowser core code, adhering to the open-closed principle.
"""

import asyncio
import hashlib
import json
import math
import os
import traceback
from typing import Any, Dict, Optional

from pydantic import Field

from agentlang.config.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import generate_safe_filename_with_timestamp
from app.core.context.agent_context import AgentContext
from app.core.entity.event.event_context import EventContext
from app.core.entity.message.server_message import BrowserContent, DisplayType, ToolDetail
from app.core.entity.tool.browser_opration import BrowserOperationNames
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool
from app.tools.use_browser_operations.operations_registry import operations_registry
from app.tools.visual_understanding import VisualUnderstanding, VisualUnderstandingParams
from app.tools.workspace_guard_tool import WorkspaceGuardTool
from magic_use.magic_browser import (
    DelightfulBrowser,
    DelightfulBrowserConfig,
    DelightfulBrowserError,
    PageStateSuccess,
    ScreenshotSuccess,
)

logger = get_logger(__name__)


class UseBrowserParams(BaseToolParams):
    """Browser operation parameters"""
    operation: str = Field(
        ...,
        description="Browser operation to execute"
    )
    operation_params: Dict[str, Any] = Field(
        default_factory=dict,
        description="Specific parameters required for the operation, varies by operation type"
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error information

        Provides friendlier error messages for specific fields and error types

        Args:
            field_name: Parameter field name
            error_type: Error type

        Returns:
            Optional[str]: Custom error information, None means use default error information
        """
        # Special handling for operation_params type error
        if field_name == "operation_params" and "type" in error_type:
            return "operation_params is optional, but if provided, it must be an object (like: {\"url\": \"https://{actual_domain}/foo/bar\"}), not an array or other types. If you want to call the tool without providing any parameters, you can just call use_browser(operation=\"read_as_markdown\")"

        # Special handling for missing operation
        if field_name == "operation" and error_type == "missing":
            return "missing required parameter: operation (like: \"goto\", \"read_as_markdown\" and so on)"

        return None


@tool()
class UseBrowser(WorkspaceGuardTool[UseBrowserParams], AbstractFileTool):
    """Browser usage tool

    Provides atomic browser operation capabilities based on a modular operation architecture.

    Browser Tool that provides atomic browser operations.
    Typically, you should first call 'goto' to open a webpage, then perform other operations.
    When calling this tool, specify the 'operation' parameter as one of the operations listed below, and provide the required parameters in 'operation_params' object.
    examples:
    use_browser(operation="goto", operation_params={"url": "https://{actual_domain}/foo/bar"})
    use_browser(operation="read_as_markdown")
    """

    def __init__(self, **data):
        super().__init__(**data)
        # No longer dynamically generate description here
        # Record last screenshot page hash and path
        self._last_screenshot_hash: Optional[str] = None
        self._last_screenshot_path: Optional[str] = None

    def get_prompt_hint(self) -> str:
        """Generate detailed prompt information for all browser operations (XML optimized)."""
        operations_xml_parts = []
        indent_operation = "  "  # indentation for operation level
        indent_param = "    "    # indentation for parameter level
        indent_example = "    "  # indentation for example level

        # Ensure registry is initialized
        operations_registry.initialize()

        # Get all operations
        all_operations = operations_registry.get_all_operations()
        logger.debug(f"Fetched {len(all_operations)} operations for tool prompt generation")

        # Sort by operation name
        sorted_operations = sorted(all_operations.items())

        for name, op_info in sorted_operations:
            # --- Operation description (single line) ---
            desc_text = op_info.get("description", "") or ""
            # Remove line breaks, collapse spaces, trim
            single_line_desc = ' '.join(desc_text.split()).strip()
            desc_xml = f"{indent_param}<description>{single_line_desc}</description>"

            # --- Parameter formatting (one per line) ---
            params_xml_parts = []
            params_class = op_info.get("params_class")

            if params_class:
                for field_name, field in params_class.model_fields.items():
                    # Get field type name
                    field_type = "unknown"
                    annotation_str = str(field.annotation)
                    if annotation_str.startswith("typing.Optional"):
                        field_type = annotation_str.split("[")[1].split("]")[0]
                    elif hasattr(field.annotation, "__name__"):
                        field_type = field.annotation.__name__
                    else:
                        field_type = annotation_str

                    # Required or optional flag
                    req_opt_attr = 'required="true"' if field.is_required() else 'optional="true"'

                    # Default value
                    default_attr = ""
                    if not field.is_required() and field.default is not ... and field.default is not None and field.default != '':
                        default_repr = repr(field.default) # repr() automatically adds quotes
                        # Quotes in XML attribute values need escaping
                        escaped_default = default_repr.replace('"', '&quot;').replace("'", "&apos;")
                        default_attr = f' default="{escaped_default}"'

                    # Parameter description (as tag content)
                    param_desc = field.description or ""

                    # Compose parameter XML line (with indentation)
                    param_xml = f'{indent_param}  <param name="{field_name}" type="{field_type}" {req_opt_attr}{default_attr}>{param_desc}</param>'
                    params_xml_parts.append(param_xml)

            if params_xml_parts:
                params_xml = f"{indent_param}<params>\n"
                params_xml += "\n".join(params_xml_parts)
                params_xml += f"\n{indent_param}</params>"
            else:
                params_xml = f"{indent_param}<params />"


            # --- Example formatting (complete call, indented) ---
            examples_xml_parts = []
            # Traverse example list
            for example in op_info.get("examples", []):
                try:
                    # Extract operation_params and ensure dictionary
                    example_params = example.get("operation_params", {})
                    if not isinstance(example_params, dict):
                         example_params = {} # reset to empty if invalid

                    # Build call string for this example
                    if example_params:
                        # Generate compact single-line JSON for operation_params
                        params_json = json.dumps(example_params, ensure_ascii=False, separators=(',', ':'))
                        example_call = f'use_browser(operation="{name}", operation_params={params_json})'
                    else:
                        # If no params, omit operation_params
                        example_call = f'use_browser(operation="{name}")'

                    # Wrap in <example> tag with indentation
                    example_xml = f"{indent_param}<example>\n{indent_example}  {example_call}\n{indent_param}</example>"
                    examples_xml_parts.append(example_xml)

                except Exception as e:
                    # Log warning but skip bad example
                    logger.warning(f"Error formatting examples for action '{name}'; skipping example: {e!s}")
                    continue

            # Combine all example XML; show empty tag if none
            examples_xml = "\n".join(examples_xml_parts) if examples_xml_parts else f"{indent_param}<examples />"


            # --- Compose operation XML (indented) ---
            operation_xml = f"""{indent_operation}<operation name="{name}">
{desc_xml}
{params_xml}
{examples_xml}
{indent_operation}</operation>"""
            operations_xml_parts.append(operation_xml)

        # --- Build full hint (with indentation) ---
        operations_xml_str = "\n".join(operations_xml_parts)
        hint = f"""<tool name="use_browser">
    <operations>
{operations_xml_str}
    </operations>
    <instructions>
        Please select one of the 'operation' listed above and provide the corresponding 'operation_params' object to call the use_browser tool.
    </instructions>
</tool>"""
        # Note: newlines removed in logs to avoid excessive length
        logger.debug("Generated use_browser tool prompt (optimized XML)")
        logger.debug(hint)
        return hint

    async def _create_browser(self):
        """Factory for creating a browser instance.

        Creates a fresh browser per call (multi-instance mode).

        Returns:
            Browser: Newly created browser instance
        """
        # Start with a scraping-friendly base config
        browser_config = DelightfulBrowserConfig.create_for_scraping()

        # Override with non-empty user config values
        if config.get("browser.headless") is not None:
            browser_config.headless = config.get("browser.headless")
        if config.get("browser.default_timeout") is not None:
            browser_config.default_timeout = config.get("browser.default_timeout")
        if config.get("browser.viewport_width") is not None:
            browser_config.viewport_width = config.get("browser.viewport_width")
        if config.get("browser.viewport_height") is not None:
            browser_config.viewport_height = config.get("browser.viewport_height")
        if config.get("browser.ignore_https_errors") is not None:
            browser_config.disable_security = config.get("browser.ignore_https_errors")
        if config.get("browser.user_agent") is not None:
            browser_config.user_agent = config.get("browser.user_agent")
        if config.get("browser.browser_type") is not None:
            browser_config.browser_type = config.get("browser.browser_type")

        # Create browser instance
        browser = DelightfulBrowser(config=browser_config)
        await browser.initialize()
        logger.info("Created new browser instance (multi-instance mode)")

        return browser

    async def _take_screenshot_for_show(self, browser: DelightfulBrowser) -> Optional[str]:
        """Capture a screenshot of the current active browser page."""
        try:
            # Get active page id
            page_id = await browser.get_active_page_id()
            if not page_id:
                logger.warning("No active browser page; cannot capture screenshot")
                return None

            # Get page object
            page = await browser.get_page_by_id(page_id)
            if not page:
                logger.warning("Selected page is unavailable; provide page_id or use goto to open a page. Cannot capture screenshot.")
                return None

            # Get page title and URL
            title = await page.title()

            # Skip screenshot if title missing
            if not title:
                logger.info("Page title empty; skip screenshot")
                return None

            # Get current scroll position
            scroll_position = await page.evaluate("""
                () => {
                    return {
                        x: window.scrollX || window.pageXOffset,
                        y: window.scrollY || window.pageYOffset
                    };
                }
            """)

            # Get page content hash as fingerprint
            # Get page content
            content = await page.text_content('body')

            # Compute hash including scroll position
            hash_content = content + f"|scrollX:{scroll_position['x']}|scrollY:{scroll_position['y']}|"
            page_hash = hashlib.md5(hash_content.encode()).hexdigest()

            # Reuse previous screenshot if content unchanged
            if page_hash == self._last_screenshot_hash and self._last_screenshot_path:
                # Ensure last screenshot file still exists
                if os.path.exists(self._last_screenshot_path):
                    logger.info(f"Page content unchanged; reusing previous screenshot: {self._last_screenshot_path}")
                    return self._last_screenshot_path

            # Build safe filename with timestamp
            safe_title = generate_safe_filename_with_timestamp(title)

            # Create screenshot path
            screenshot_filename = f"{safe_title}_screenshot.png"
            screenshots_dir = self.base_dir / ".browser_screenshots"
            screenshot_path = screenshots_dir / screenshot_filename

            # Ensure directory exists
            os.makedirs(screenshots_dir, exist_ok=True)

            # Capture screenshot
            await page.screenshot(path=str(screenshot_path))
            logger.info(f"Saved browser screenshot to: {screenshot_path}")

            # Update last screenshot fingerprint
            self._last_screenshot_hash = page_hash
            self._last_screenshot_path = str(screenshot_path)

            return str(screenshot_path)
        except Exception as e:
            # Print stacktrace
            logger.error(traceback.format_exc())
            logger.error(f"Failed to capture browser screenshot: {e!s}")
            return None

    async def _find_and_validate_operation(self, operation: str) -> Dict[str, Any]:
        """Locate and validate an operation handler."""
        operation_info = operations_registry.get_operation(operation)
        if not operation_info:
            logger.warning(f"Operation handler not found: {operation}")
            all_ops = list(operations_registry.get_all_operations().keys())
            error_msg = f"Unknown operation: {operation}. Available operations: {', '.join(all_ops)}"
            return {"error": error_msg}
        return operation_info

    async def _validate_and_create_op_params(self, browser: DelightfulBrowser, operation: str,
                                            params_class: Any, operation_params_dict: Dict[str, Any]) -> Any:
        """Validate and construct operation parameter object."""
        # Default to raw dict
        if not params_class:
            return operation_params_dict

        try:
            # Auto-fill page_id when missing
            if 'page_id' not in operation_params_dict:
                active_page_id = await browser.get_active_page_id()
                if active_page_id:
                    operation_params_dict['page_id'] = active_page_id
                # If no active page and page_id required
                elif 'page_id' in params_class.model_fields and params_class.model_fields['page_id'].is_required():
                    logger.warning(f"Operation {operation} requires page_id, but no active page and none provided")
                    return {"error": f"Operation '{operation}' requires an open page; please open a page via 'goto' before calling it."}

            return params_class(**operation_params_dict)
        except Exception as validation_error:
            logger.warning(f"Operation '{operation}' parameter validation failed: {validation_error!s}")
            # Build friendlier error message
            error_msg = f"Parameters for '{operation}' are invalid."
            if "missing" in str(validation_error).lower():
                import re
                missing_fields = re.findall(r'Field required \[type=missing, input_value=.*', str(validation_error))
                if missing_fields:
                    error_msg += " Missing required fields; check 'operation_params' for this operation."
                else:
                    error_msg += f" Details: {validation_error!s}"
            elif "extra fields not permitted" in str(validation_error).lower():
                import re
                extra_fields = re.findall(r"Extra inputs are not permitted \(extra='(.*)'\)", str(validation_error))
                if extra_fields:
                    error_msg += f" Unsupported parameter: {extra_fields[0]}. Refer to tool docs for required 'operation_params'."
                else:
                    error_msg += f" Details: {validation_error!s}"
            else:
                error_msg += f" Please check parameter format. Details: {validation_error!s}"

            return {"error": error_msg}

    async def _generate_browser_status_summary(self, browser: DelightfulBrowser) -> str:
        """Generate a status summary for all browser pages."""
        try:
            # Get active page state
            active_page_id = await browser.get_active_page_id()
            active_page_state_result = {}
            if active_page_id:
                active_page_state_result = await browser.get_page_state(active_page_id)

            # Get all pages info
            all_pages = await browser.get_all_pages()
            inactive_page_infos = []

            # Collect inactive page info
            if all_pages:
                tasks = []
                for pid, page in all_pages.items():
                    if pid != active_page_id:
                        async def get_info(p, p_id):
                            try:
                                title = await p.title()
                                url = p.url
                                return {"id": p_id, "title": title, "url": url}
                            except Exception as e:
                                logger.warning(f"Failed to get inactive page {p_id} info: {e}")
                                return {"id": p_id, "title": "[gettitlefailed]", "url": p.url}
                        tasks.append(get_info(page, pid))

                if tasks:
                    inactive_page_infos = await asyncio.gather(*tasks)

            # Build status summary lines
            status_lines = ["\n\n---", "Browser status:"]

            # Active page info
            status_lines.append("Active page:")
            if isinstance(active_page_state_result, PageStateSuccess):
                page_state = active_page_state_result.state
                status_lines.append(f"- title: {page_state.title or '[no title]'}")
                status_lines.append(f"- URL: {page_state.url or '[no URL]'}")

                # Human-readable status description
                status_desc = "Status information unavailable"
                if page_state.position_info and page_state.scroll_position:
                    pos_info = page_state.position_info
                    scroll = page_state.scroll_position

                    if scroll.document_height > 0 and scroll.viewport_height > 0:
                        # Scroll-related data
                        current_y = scroll.y
                        doc_height = scroll.document_height
                        viewport_height = scroll.viewport_height
                        remaining_height = max(0, doc_height - (current_y + viewport_height))

                        # Horizontal scroll data
                        current_x = scroll.x
                        doc_width = scroll.document_width
                        viewport_width = scroll.viewport_width

                        # Position info
                        read_percent = pos_info.read_percent
                        remaining_percent = pos_info.remaining_percent
                        current_screen = pos_info.current_screen
                        total_screens = pos_info.total_screens

                        # Vertical position description
                        vertical_position_desc = f"Vertical: screen {current_screen:.0f}/{total_screens:.0f}"

                        # Horizontal position description (only when horizontal scroll matters)
                        horizontal_position_desc = ""
                        if doc_width > viewport_width:
                            # Compute horizontal screens
                            total_horizontal_screens = math.ceil(doc_width / viewport_width) if viewport_width > 0 else 1
                            current_horizontal_screen = math.ceil(current_x / viewport_width + 1) if viewport_width > 0 else 1
                            # Bound current screen to total (avoid float precision issues)
                            current_horizontal_screen = min(current_horizontal_screen, total_horizontal_screens)

                            horizontal_position_desc = f", Horizontal: screen {current_horizontal_screen:.0f}/{total_horizontal_screens:.0f}"

                        # Combine full position description
                        position_desc = f"{vertical_position_desc}{horizontal_position_desc}"

                        # Describe vertical position
                        if current_y < viewport_height * 0.5:  # near top
                            status_desc = f"{position_desc}, near the start of the page; about {remaining_percent:.0f}% remains below."
                        elif remaining_height < viewport_height * 0.5:  # near bottom
                            status_desc = f"{position_desc}, near the bottom; around {read_percent:.0f}% has been viewed."
                        else:  # middle
                            status_desc = f"{position_desc}, around {read_percent:.0f}% down the page; about {remaining_percent:.0f}% remains."

                        # Add hint when lots of content remains
                        remaining_screens = math.ceil(remaining_height / viewport_height)
                        if current_screen > 5 and remaining_screens >= 2:
                            status_desc += " (Page is long; consider scrolling further or visiting related pages.)"

                status_lines.append(f"- status: {status_desc}")
            elif active_page_id:  # active id but failed state
                error_msg = "unknown error"
                if hasattr(active_page_state_result, 'error'):
                    error_msg = active_page_state_result.error
                elif isinstance(active_page_state_result, dict):
                    error_msg = active_page_state_result.get('error', 'unknown error')
                status_lines.append(f"- error: {error_msg}")
            else:  # no active page
                status_lines.append("- no active page")

            # Inactive pages
            status_lines.append("\nOther open pages:")
            if inactive_page_infos:
                for info in inactive_page_infos:
                    status_lines.append(f"- {info['title'] or '[no title]'} ({info['url']})")
            else:
                status_lines.append("- none")

            status_lines.append("---")

            # Return status summary
            return "\n".join(status_lines)
        except Exception as e:
            logger.error(f"Error generating browser status summary: {e}", exc_info=True)
            return "\n\n---\nBrowser status: failed to retrieve\n---"

    async def _generate_visual_focus_summary(self, browser: DelightfulBrowser) -> str:
        """Generate a visual focus summary for the current viewport.

        Returns empty string on failure or when no active page.
        """
        # Note: running vision on every call adds latency/cost; could be optimized via change detection later.
        try:
            page_id = await browser.get_active_page_id()
            if not page_id:
                logger.info("No active page; skip visual focus analysis.")
                return ""

            logger.info(f"Generating visual focus analysis for page {page_id}...")
            # 1) Capture current viewport (temp file)
            screenshot_result = await browser.take_screenshot(page_id=page_id, path=None, full_page=False)

            if isinstance(screenshot_result, ScreenshotSuccess):
                screenshot_path = str(screenshot_result.path)
                logger.info(f"Visual analysis screenshot saved: {screenshot_path}")

                # 2) Call vision model
                visual_understanding = VisualUnderstanding()
                query = (
                    "Briefly describe the main content visible on the current screen and list a few key points to guide the next action. "
                    "If the page contains many images or non-text elements, summarize what the images show and remind the user to call tools to extract image content. "
                    "(Interactive elements are already highlighted by overlays.)"
                )
                vision_params = VisualUnderstandingParams(images=[screenshot_path], query=query)
                vision_result = await visual_understanding.execute_purely(params=vision_params)

                if vision_result.ok and vision_result.content:
                    # 3) Format visual summary
                    analysis_content = vision_result.content.strip()
                    logger.info(f"Visual focus analysis completed for page {page_id}.")
                    return f"\n\n---\n**Visual focus:**\n{analysis_content}\n---"
                else:
                    error_msg = vision_result.content or "Visual model did not return valid content"
                    logger.warning(f"Visual understanding failed or returned no result: {error_msg}")

            elif isinstance(screenshot_result, DelightfulBrowserError):
                logger.warning(f"Screenshot for visual analysis failed: {screenshot_result.error}")
            else:
                 logger.warning(f"take_screenshot returned unexpected type: {type(screenshot_result)}")

        except Exception as ve:
            logger.error(f"Unexpected error during visual focus analysis: {ve!s}", exc_info=True)

        return ""  # Empty string indicates skipped/failed

    async def _process_operation_result(self, browser: DelightfulBrowser, handler_result: ToolResult) -> ToolResult:
        """Post-process an operation result by appending status/vision summaries."""
        if not isinstance(handler_result, ToolResult):
            # Fatal error; code bug
            raise ValueError(f"Operation handler returned unexpected type: {type(handler_result)}")

        # 1) Generate browser status summary
        browser_status_summary = ""
        try:
            browser_status_summary = await self._generate_browser_status_summary(browser)
        except Exception as e:
            logger.error(f"Error generating browser status summary: {e}", exc_info=True)

        # 2) Visual focus analysis (currently disabled)
        visual_focus_summary = ""
        # if handler_result.ok:
        #     try:
        #         visual_focus_summary = await self._generate_visual_focus_summary(browser)
        #     except Exception as e:
        #         logger.error(f"Unexpected error during visual focus analysis: {e!s}", exc_info=True)

        # 3) Combine final result
        final_content = handler_result.content or ""
        # Append browser status summary
        if browser_status_summary:
            if final_content:
                final_content += browser_status_summary
            else:
                final_content = browser_status_summary.lstrip()

        # Append visual focus summary
        if visual_focus_summary:
            if final_content:
                final_content += visual_focus_summary
            else:
                final_content = visual_focus_summary.lstrip()

        handler_result.content = final_content
        return handler_result

    async def execute(
        self,
        tool_context: ToolContext,
        params: UseBrowserParams
    ) -> ToolResult:
        """Execute a browser operation."""
        operation = params.operation
        operation_params_dict = params.operation_params or {}
        logger.info(f"Preparing to execute browser operation: {operation}, params: {operation_params_dict}")

        try:
            # Get browser instance via resource manager
            agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
            browser: DelightfulBrowser = await agent_context.get_resource("browser", self._create_browser)

            # --- Locate handler ---
            operation_info = await self._find_and_validate_operation(operation)
            if "error" in operation_info:
                return ToolResult(error=operation_info["error"])

            handler = operation_info["handler"]
            params_class = operation_info["params_class"]

            # --- Validate and build params object ---
            op_params_result = await self._validate_and_create_op_params(
                browser, operation, params_class, operation_params_dict
            )
            if isinstance(op_params_result, dict) and "error" in op_params_result:
                return ToolResult(error=op_params_result["error"])

            op_params_obj = op_params_result

            # --- Execute handler ---
            logger.debug(f"Calling operation handler '{operation}'...")
            handler_result = await handler(browser, op_params_obj)
            logger.debug(f"Operation handler '{operation}' returned result")

            # --- Page state ---
            page_id = await browser.get_active_page_id() # re-fetch in case action changed active page

            # --- Screenshot ---
            screenshot_path = None
            if page_id:
                screenshot_path = await self._take_screenshot_for_show(browser)
                if screenshot_path:
                    await self._dispatch_file_event(tool_context, filepath=screenshot_path, event_type=EventType.FILE_CREATED, is_screenshot=True)

            # --- Post-process result ---
            final_result = await self._process_operation_result(browser, handler_result)
            return final_result

        except Exception as e:
            logger.error(f"Unexpected error executing browser operation '{operation}': {e!s}", exc_info=True)
            return ToolResult(error=f"Unexpected internal error executing '{operation}': {e!s}")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        # Retrieve event context from tool context
        event_context = tool_context.get_extension_typed("event_context", EventContext)
        # If screenshot exists and EventContext available, return it
        if event_context and event_context.attachments:
            try:
                # Get current page URL/title
                agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
                browser: DelightfulBrowser = await agent_context.get_resource("browser", self._create_browser)
                page_id = await browser.get_active_page_id()
                page = await browser.get_page_by_id(page_id)
                url = page.url
                title = await page.title()

                return ToolDetail(
                    type=DisplayType.BROWSER,
                    data=BrowserContent(url=url, title=title, file_key=event_context.attachments[0].file_key)
                )
            except Exception as e:
                logger.error(f"Failed to create tool detail: {e!s}")
                return None
        return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Friendly action/remark after tool call
        """
        operation = arguments.get("operation", "")
        try:
            agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
            browser: DelightfulBrowser = await agent_context.get_resource("browser", self._create_browser)
            page_id = await browser.get_active_page_id()
            page = await browser.get_page_by_id(page_id)
            url = page.url
            title = await page.title()
            return {
                "action": BrowserOperationNames.get_operation_info(operation),
                "remark": title if title else url
            }
        except Exception as e:
            logger.error(f"Failed to build friendly action/remark: {e!s}")
            return {
                "action": BrowserOperationNames.get_operation_info(operation),
                "remark": f"Executed operation {operation}"
            }
