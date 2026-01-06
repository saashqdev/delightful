"""Browser operation foundation module

Defines base classes and decorators for browser operations.

# Browser Operation Architecture

## Design Goals

This module implements a modular, extensible browser operation architecture with goals to:
- Reduce boilerplate for declaring each operation
- Improve maintainability
- Enable flexible operation extension
- Preserve functionality and compatibility

## Core Components

1. **Parameter modeling**:
    - Use Pydantic models instead of handwritten JSON Schema
    - Leverage type hints to auto-generate validation
    - BaseOperationParams captures shared parameters

2. **Operation decorator (@operation)**:
    - Auto-extract description from docstrings
    - Auto-detect parameter types
    - Auto-generate operation examples
    - Simplify registration

3. **Operation grouping**:
    - OperationGroup organizes related operations
    - Operations are grouped by functionality
    - Plugin-style architecture supports dynamic loading

4. **Unified result format**:
    - Standardized result structure
    - Consistent error handling and responses

## Extending operations

To add new browser operations:
1. Define a params class in the appropriate operation group module, subclassing BaseOperationParams
2. Implement the operation method and decorate with @operation
3. No extra config needed; the operation will be auto-discovered and registered

## Related files

- base.py: base classes and decorator definitions
- operations_registry.py: operation registry and management
- [operation group modules].py: concrete grouped operation implementations
"""

import functools
import inspect
from abc import ABC
from typing import Any, Callable, ClassVar, Dict, List, Optional, Union

from pydantic import BaseModel, Field

from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult

# Logger
logger = get_logger(__name__)


class BaseOperationParams(BaseModel):
    """Base parameter model for operations.

    Shared parameters for all operation parameter models.
    """
    page_id: Optional[str] = Field(
        "",
        description="id of the page being operated, currently active page default"
    )


def operation(
    name: Optional[str] = None,
    example: Optional[Union[Dict[str, Any], List[Dict[str, Any]]]] = None
):
    """Operation registration decorator.

    Registers browser operations and extracts parameter type info.

    Args:
        name: Operation name; defaults to function name (without leading underscore)
        example: Operation examples; if not provided, attempts auto-generation
    """
    def decorator(func: Callable):
        # Get the first annotated parameter after self and browser
        sig = inspect.signature(func)
        params_class = None
        for param_name, param in list(sig.parameters.items())[2:]:  # Skip self and browser
            if param.annotation != inspect.Parameter.empty:
                params_class = param.annotation
                break

        @functools.wraps(func)
        async def wrapper(self, browser, params, *args, **kwargs):
            # Validate parameters
            if params_class and not isinstance(params, params_class):
                # Attempt conversion
                try:
                    params = params_class(**params)
                except Exception as e:
                    # Provide friendlier error info on validation failure
                    logger.debug(f"parametersvalidatefailed: {e!s}")

                    # Build friendly error message
                    error_msg = self._generate_friendly_validation_error(e, name or func.__name__.lstrip('_'), params_class)

                    # Return friendly error result
                    return ToolResult(error=error_msg)

            return await func(self, browser, params, *args, **kwargs)

        # Store operation metadata
        operation_name = name or func.__name__.lstrip('_')
        wrapper.operation_name = operation_name
        wrapper.params_class = params_class
        wrapper.description = func.__doc__ if func.__doc__ else ""
        wrapper.is_operation = True  # Mark as operation

        # Process examples, always store as list
        examples = []
        if example:
            if isinstance(example, list):
                examples.extend(example)
            elif isinstance(example, dict):
                examples.append(example)
            else:
                logger.warning(f"Invalid example format for operation '{operation_name}'; must be a dict or list of dicts")
        elif params_class:
            # Auto-generate example
            try:
                # Create example params only for fields with defaults
                example_params = {}
                required_fields = []

                # Collect field info
                for field_name, field in params_class.model_fields.items():
                    # Add optional fields with defaults
                    if field.default is not None and field.default is not ...:
                        example_params[field_name] = field.default
                    elif field.is_required():
                        required_fields.append(field_name)

                # Add placeholders for required fields
                if required_fields:
                    # Common example values
                    common_field_examples = {
                        "url": "https://{actual_domain}/article/12345",
                        "selector": "#abcdefg",
                        "text": "example text",
                        "query": "Search keywords"
                    }

                    # Insert example values for required fields
                    for field in required_fields:
                        if field in common_field_examples:
                            example_params[field] = f"<{common_field_examples[field]}>"

                # Build final example
                generated_example = {
                    "operation": operation_name,
                    "operation_params": example_params
                }

                # If required fields missing example values, add note
                if required_fields and not any(field in example_params for field in required_fields):
                    generated_example["required_fields"] = required_fields
                    generated_example["note"] = f"Please provide: {', '.join(required_fields)}"
                examples.append(generated_example)

            except Exception as e:
                logger.debug(f"Auto-generating operation example failed: {e!s}")
                pass
        else:
            pass

        wrapper.examples = examples

        return wrapper
    return decorator


class OperationGroup(ABC):
    """Base class for operation groups.

    Organizes related operations.
    """
    group_name: ClassVar[str] = "base"
    group_description: ClassVar[str] = "Base operation group"

    def __init__(self):
        """Initialize operation group and register operations"""
        self.operations: Dict[str, Dict[str, Any]] = {}
        logger.debug(f"Initializing operation group: {self.__class__.group_name}")
        self.register_operations()

    def register_operations(self):
        """Register all operations in this group"""
        for name, method in inspect.getmembers(self, inspect.ismethod):
            if hasattr(method, 'is_operation') and method.is_operation:
                op_name = method.operation_name
                self.operations[op_name] = {
                    "handler": method,
                    "params_class": getattr(method, 'params_class', None),
                    "description": method.description,
                    "examples": getattr(method, 'examples', [])
                }
                logger.debug(f"Registered operation: {op_name} (from {self.__class__.group_name})")

    def get_operations(self) -> Dict[str, Dict[str, Any]]:
            """Get all operations in the group"""
        return self.operations

    def _generate_friendly_validation_error(self, error, operation_name, params_class):
        """Generate friendly parameter validation error message."""
        if hasattr(error, 'errors') and callable(getattr(error, 'errors')):
            try:
                error_details = error.errors()

                # Check missing fields
                missing_fields = []
                type_errors = []
                other_errors = []

                for err in error_details:
                    err_type = err.get("type", "")
                    field_path = ".".join(str(loc) for loc in err.get("loc", []))

                    if err_type == "missing":
                        missing_fields.append(field_path)
                    elif "type" in err_type:
                        type_errors.append((field_path, err))
                    else:
                        other_errors.append((field_path, err))

                # Handle missing fields
                if missing_fields:
                    missing_fields_str = ", ".join(missing_fields)
                    error_msg = f"Operation '{operation_name}' is missing required parameters: {missing_fields_str}"

                    # Add descriptions for required fields
                    if params_class and hasattr(params_class, 'model_fields'):
                        error_msg += "\n\nRequired parameter descriptions:"
                        for field in missing_fields:
                            if field in params_class.model_fields:
                                field_obj = params_class.model_fields[field]
                                field_desc = field_obj.description or "No description"
                                error_msg += f"\n- {field}: {field_desc}"

                    return error_msg

                # Handle type errors
                if type_errors:
                    errors_desc = []
                    for field_path, err in type_errors:
                        expected_type = "correct format"
                        if "expected_type" in err.get("ctx", {}):
                            expected_type = err["ctx"]["expected_type"]

                        received_type = "wrong type"
                        if "input_type" in err.get("ctx", {}):
                            received_type = err["ctx"]["input_type"]

                        errors_desc.append(f"'{field_path}' should be {expected_type}, received {received_type}")

                    return f"Operation '{operation_name}' parameter type error: " + "; ".join(errors_desc)

                # Handle other validation errors
                if other_errors:
                    errors_desc = []
                    for field_path, err in other_errors:
                        msg = err.get("msg", "validation failed")
                        errors_desc.append(f"'{field_path}': {msg}")

                    return f"Operation '{operation_name}' parameter validation failed: " + "; ".join(errors_desc)

            except Exception as e:
                logger.debug(f"Parsing validation error details failed: {e!s}")

        # Fallback messages
        if "dict" in str(error).lower() and "list" in str(error).lower():
            return f"Operation '{operation_name}' parameters must be an object (dict), not an array or other type"

        if "missing" in str(error).lower():
            return f"Operation '{operation_name}' is missing required parameters; please check completeness"

        if "validation error" in str(error).lower():
            return f"Operation '{operation_name}' parameter validation failed; please verify types and format"

        return f"Operation '{operation_name}' parameter error: {error!s}"

    # --- Page validation helper ---
    async def _get_validated_page(self, browser: 'DelightfulBrowser', params: BaseOperationParams) -> tuple[Optional['Page'], Optional[ToolResult]]:
        """Get and validate a page object.

        Resolves page_id and checks that the page exists and is open.
        If invalid, returns a ToolResult with error info.
        """
        page_id = params.page_id
        error_reason = ""
        page: Optional['Page'] = None

        try:
            if not page_id:
                page_id = await browser.get_active_page_id()
                if not page_id:
                    error_reason = "No active page"
                else:
                    page = await browser.get_page_by_id(page_id)
                    if not page:
                        error_reason = f"Active page {page_id} is unavailable or closed"
            else:
                # Page id provided; fetch and validate
                page = await browser.get_page_by_id(page_id)
                if not page:
                    error_reason = f"Page {page_id} does not exist or is closed"

            # Return ToolResult on error
            if error_reason:
                error_msg = f"{error_reason}. Confirm page ID or use goto to open a page."
                logger.warning(f"Page validation failed ({params.__class__.__name__}): {error_msg}")
                return None, ToolResult(error=error_msg)

            # Page is valid
            return page, None

        except Exception as e:
            logger.error(f"Unexpected error while validating page: {e}", exc_info=True)
            return None, ToolResult(error=f"Internal error getting or validating page: {e}")

    # --- End helper ---

# Dynamic import of operation groups
