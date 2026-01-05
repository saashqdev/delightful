"""Tool executor module.

Executes tool calls and manages tool instances as a core component of the tool
system. Provides a unified execution interface to simplify lookup and calling.

Design notes:
1. Singleton: global `tool_executor` instance reused across code
2. Execution proxy: extends tool_factory to handle param conversion and errors
3. Parameter adaptation: converts incoming args to the format tools expect
"""

import time
import traceback
from typing import Any, Dict, List

from pydantic import ValidationError

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.tools.core.tool_factory import tool_factory

logger = get_logger(__name__)


class ToolExecutor:
    """Manage and execute tools.

    Responsibilities:
    1) Retrieve and manage tool instances
    2) Execute calls with parameter handling and error capture
    3) Provide a unified interface for tool schemas

    Usage:
    ```python
    from app.tools.core.tool_executor import tool_executor

    result = await tool_executor.execute_tool_call(tool_context, arguments)
    tool = tool_executor.get_tool("tool_name")
    schemas = tool_executor.get_tool_schemas()
    ```
    """

    def __init__(self, tools=None):
        """Initialize executor with optional tool list."""
        # Ensure factory is ready
        if not tool_factory._tools:
            tool_factory.initialize()
        self._tools = tools or []

    def set_tools(self, tools: List):
        """Set active tool list."""
        self._tools = tools or []

    async def execute_tool_call(self, tool_context: ToolContext, arguments: Dict[str, Any] = None) -> ToolResult:
        """Primary entry for executing a tool call.

        Handles instance lookup, parameter validation/conversion, error capture,
        and result formatting.
        """
        tool_name = tool_context.tool_name

        try:
            # Get tool instance
            tool_instance = self.get_tool(tool_name)
            if not tool_instance:
                friendly_error = f"Tool '{tool_name}' does not exist; verify the tool name"
                logger.error(friendly_error)
                raise ValueError(friendly_error)

            # Ensure arguments present
            if arguments is None:
                arguments = {}

            # Call __call__ to trigger param model conversion instead of execute
            start_time = time.time()
            result: ToolResult = await tool_instance(tool_context, **arguments)

            if not result.ok:
                logger.error(f"Tool {tool_name} execution failed: {result.content}")

            # Set execution time and metadata
            if result:
                result.execution_time = time.time() - start_time
                result.name = tool_name
                result.tool_call_id = tool_context.tool_call_id

            return result
        except ValidationError as ve:
            # Parameter validation errors
            logger.error(f"Tool {tool_name} parameter validation failed: {ve!s}")

            # Try to build a friendly error message
            error_msg = self._get_friendly_validation_error(tool_name, ve)

            # Return error result
            result = ToolResult(
                error=error_msg,
                name=tool_name
            )

            # Propagate tool_call_id
            if hasattr(tool_context, 'tool_call_id'):
                result.tool_call_id = tool_context.tool_call_id

            return result
        except Exception as e:
            # Log full stack
            error_stack = traceback.format_exc()
            logger.error(f"Error executing tool {tool_name}: {e}")
            logger.error(f"Stack trace:\n{error_stack}")
            # Build friendly error message
            error_type = type(e).__name__
            error_msg = self._get_friendly_error_message(tool_name, error_type, str(e))

            # Return error result
            result = ToolResult(
                error=error_msg,
                name=tool_name
            )

            # Propagate tool_call_id
            if hasattr(tool_context, 'tool_call_id'):
                result.tool_call_id = tool_context.tool_call_id

            return result

    def _get_friendly_validation_error(self, tool_name: str, validation_error: ValidationError) -> str:
        """Return a friendly message for Pydantic validation errors."""
        # Extract details when possible
        try:
            error_details = validation_error.errors()

            # Categorize errors
            missing_fields = []
            type_errors = []
            other_errors = []

            for err in error_details:
                err_type = err.get("type", "")
                field_path = ".".join(str(loc) for loc in err.get("loc", []))

                if err_type == "missing":
                    missing_fields.append(field_path)
                elif "type" in err_type:
                    type_errors.append(f"'{field_path}'")
                else:
                    other_errors.append(f"'{field_path}'")

            # Build friendly message
            if missing_fields:
                return f"Tool '{tool_name}' is missing required parameters: {', '.join(missing_fields)}"
            elif type_errors:
                return f"Tool '{tool_name}' has parameter type errors, check: {', '.join(type_errors)}"
            elif other_errors:
                return f"Tool '{tool_name}' parameter validation failed, check: {', '.join(other_errors)}"

        except Exception as e:
            # Fall back to generic messaging
            logger.error(f"Failed to parse validation details: {e}")

        # Default friendly message
        return f"Tool '{tool_name}' parameter validation failed; please verify input requirements"

    def _get_friendly_error_message(self, tool_name: str, error_type: str, error_message: str) -> str:
        """Return a user-friendly error message based on type/content."""
        # If already friendly, return as-is
        if "does not exist" in error_message.lower() or "check" in error_message.lower():
            return error_message

        # Map common error types to friendly hints
        if "NotFound" in error_type or "not found" in error_message.lower():
            return f"Tool '{tool_name}' failed: requested resource not found"

        if "Permission" in error_type or "Access" in error_type:
            return f"Tool '{tool_name}' failed: insufficient permissions"

        if "Timeout" in error_type or "timeout" in error_message.lower():
            return f"Tool '{tool_name}' failed: operation timed out, please retry"

        if "Connection" in error_type or "network" in error_message.lower():
            return f"Tool '{tool_name}' failed: network connection issue"

        if "Value" in error_type:
            return f"Tool '{tool_name}' failed: invalid parameter value, check input"

        # Default friendly message
        return f"Tool '{tool_name}' failed: {error_message}"

    def get_tool(self, tool_name: str):
        """Fetch a tool instance by name; return None if unavailable."""
        try:
            return tool_factory.get_tool_instance(tool_name)
        except Exception as e:
            logger.error(f"Failed to get tool {tool_name} instance: {e}")
            return None

    def get_all_tools(self):
        """Return configured tools or all registered instances."""
        if self._tools:
            return self._tools
        return tool_factory.get_all_tool_instances()

    def get_tool_schemas(self) -> List[Dict[str, Any]]:
        """Return OpenAI-compatible function-calling schemas for all tools."""
        tools = self.get_all_tools()
        return [tool.to_param() for tool in tools]

    async def run_tool(self, tool_context: ToolContext, tool_name: str = None, **args):
        """Run a tool; keeps backward compatibility with optional tool_name."""
        # Update tool_context when tool_name provided
        if tool_name:
            tool_context.tool_name = tool_name

        # Execute tool
        return await self.execute_tool_call(tool_context, args)


# Global singleton tool executor instance
tool_executor = ToolExecutor()
