"""Browser operation mapping module.

Defines friendly names (previously Chinese labels) for browser operations and related icons.

"""

from enum import Enum

from agentlang.logger import get_logger

logger = get_logger(__name__)


# Mapping dictionary for quick lookups
OPERATION_NAME_MAPPING = {
    "goto": "Open webpage",
    "read_as_markdown": "Read page content",
    "get_interactive_elements": "Find interactive elements",
    "find_interactive_element_visually": "Locate interactive element precisely",
    "visual_query": "Query page content",
    "click": "Click element",
    "input_text": "Enter text",
    "scroll_to": "Scroll page",
}

class BrowserOperationNames(Enum):
    """Friendly names for browser operations."""

    @classmethod
    def get_operation_info(cls, operation_name: str) -> str:
        """Get the friendly name (and icon when available) for an operation.

        Args:
            operation_name: Operation name, e.g., goto, scroll_page

        Returns:
            Friendly name string; returns the original name if not found.
        """
        if operation_name not in OPERATION_NAME_MAPPING:
            logger.warning(f"Unknown operation: {operation_name}, using original name")
            return operation_name
        else:
            return OPERATION_NAME_MAPPING[operation_name]
