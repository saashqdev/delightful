"""Base tool module.

Defines the base class for all tools and provides shared behavior and interfaces.
"""

import inspect
import os
import re
import time
from abc import ABC, abstractmethod
from typing import Any, ClassVar, Dict, Generic, Optional, Type, TypeVar, Union, get_args, get_origin

from pydantic import ConfigDict, ValidationError

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.snowflake import Snowflake
from app.core.entity.message.server_message import ToolDetail
from app.tools.core.base_tool_params import BaseToolParams

# Parameter type variable
T = TypeVar('T', bound=BaseToolParams)


class BaseTool(Generic[T], ABC):
    """Base class for tools defining shared interfaces and behaviors."""
    # Tool metadata (class level)
    name: ClassVar[str] = ""
    description: ClassVar[str] = ""
    params_class: ClassVar[Type[T]] = None

    # Config options
    model_config = ConfigDict(arbitrary_types_allowed=True)

    def is_available(self) -> bool:
        """Check whether the tool is available.

        Subclasses can override this for custom readiness checks (env vars,
        API keys, external deps, etc.).

        Returns:
            bool: True if the tool is available, otherwise False.
        """
        return True

    def __init_subclass__(cls, **kwargs):
        """Handle metadata when subclasses are defined."""
        super().__init_subclass__(**kwargs)
        logger = get_logger(__name__)

        # Ensure subclass is marked as unregistered
        cls._registered = False

        # ---------- Determine tool name ----------
        if cls.__dict__.get('name'):  # Priority 1: subclass-defined name
            # Keep as provided by subclass
            pass
        elif hasattr(cls, '_initial_name') and cls._initial_name:  # Priority 2: decorator-provided name
            cls.name = cls._initial_name
        else:  # Priority 3: infer from filename
            try:
                # Get subclass module file path
                module = inspect.getmodule(cls)
                if module:
                    # Extract filename without extension from module path
                    file_path = module.__file__
                    file_name = os.path.basename(file_path)
                    name_without_ext = os.path.splitext(file_name)[0]
                    # Lowercase it
                    generated_name = name_without_ext.lower()
                    logger.debug(f"Generated tool name from filename {file_name}: {generated_name}")
                    cls.name = generated_name
                else:
                    # Fallback: generate from class name (camel to snake)
                    fallback_name = re.sub(r'(?<!^)(?=[A-Z])', '_', cls.__name__).lower()
                    logger.debug(f"Module filename unavailable, generated tool name from class: {fallback_name}")
                    cls.name = fallback_name
            except Exception as e:
                logger.warning(f"Failed to generate tool name from filename: {e}")
                # Fallback to class name
                cls.name = cls.__name__.lower()

        # ---------- Determine tool description ----------
        if cls.__dict__.get('description'):  # Priority 1: subclass-defined description
            # Keep as provided by subclass
            pass
        elif hasattr(cls, '_initial_description') and cls._initial_description:  # Priority 2: decorator-provided description
            cls.description = cls._initial_description
        elif cls.__doc__:  # Priority 3: derive from class docstring
            # Use inspect.cleandoc to process docstring
            cls.description = inspect.cleandoc(cls.__doc__)
        else:
            # Default description when none provided
            cls.description = f"Tool for {cls.name}"
            logger.warning(f"Tool {cls.name} has no description")

        # ---------- Determine params_class ----------
        if cls.__dict__.get('params_class'):  # Priority 1: subclass-defined params_class
            # Keep as provided by subclass
            pass
        elif hasattr(cls, '__orig_bases__'):  # Priority 2: extract from generic base (Generic[T])
            for base in cls.__orig_bases__:
                if hasattr(base, '__origin__') and base.__origin__ is Generic:
                    # Skip Generic base itself
                    continue

                # Handle specific generic tools like WorkspaceGuardTool[ParamType]
                if hasattr(base, '__origin__') and hasattr(base, '__args__') and len(base.__args__) > 0:
                    origin = get_origin(base)
                    if origin is not None and issubclass(origin, BaseTool):
                        args = get_args(base)
                        if args and len(args) > 0 and isinstance(args[0], type):
                            cls.params_class = args[0]
                            logger.debug(f"Derived params_class {cls.params_class} from generic base {base}")
                            break

        if not cls.params_class and hasattr(cls, 'execute'):  # Priority 3: extract from execute signature
            try:
                sig = inspect.signature(cls.execute)
                # Look for the 3rd parameter (skip self and tool_context)
                params = list(sig.parameters.values())
                if len(params) >= 3:
                    param = params[2]
                    # Check for type annotation
                    if param.annotation != inspect.Parameter.empty:
                        cls.params_class = param.annotation
                        logger.debug(f"Derived params_class from execute signature: {cls.params_class}")
            except Exception as e:
                logger.warning(f"Failed to derive params_class from execute signature: {e}")

        # Update internal keys for factory registration
        cls._tool_name = cls.name
        cls._tool_description = cls.description
        cls._params_class = cls.params_class

        # Ensure tool marker is set
        if not hasattr(cls, '_is_tool'):
            cls._is_tool = True

        logger.debug(f"Tool metadata resolved: name={cls.name}, params_class={cls.params_class}")

    def __init__(self, **data):
        """Initialize tool instance."""
        # Store instance-level overrides
        self._custom_name = data.get('name', None)
        self._custom_description = data.get('description', None)

        # Set other instance attributes (skip name and description)
        for key, value in data.items():
            if key not in ['name', 'description']:
                setattr(self, key, value)

    @abstractmethod
    async def execute(self, tool_context: ToolContext, params: T) -> ToolResult:
        """Execute the tool."""
        pass

    def get_effective_name(self) -> str:
        """Get the effective tool name (instance override > class value)."""
        return self._custom_name if self._custom_name is not None else self.__class__.name

    def get_effective_description(self) -> str:
        """Get the effective tool description (instance override > class value)."""
        return self._custom_description if self._custom_description is not None else self.__class__.description

    async def __call__(self, tool_context: ToolContext, **kwargs) -> ToolResult:
        """Invoke the tool via keyword args.

        Workflow:
        1) Validate/convert kwargs into the Pydantic params model
        2) Measure execution time
        3) Normalize the result payload
        4) Provide friendly validation errors when possible
        """
        start_time = time.time()

        logger = get_logger(__name__)

        # Tools without param model types are invalid
        if not self.params_class:
            error_msg = f"Tool {self.get_effective_name()} has no parameter model defined"
            logger.error(error_msg)
            return ToolResult(
                error=error_msg,
                name=str(self.get_effective_name())
            )

        # Try to build the param model instance
        try:
            params = self.params_class(**kwargs)
        except ValidationError as e:
            # Handle validation failures
            error_details = e.errors()
            logger.debug(f"Validation errors: {error_details}")

            # Check for custom error messages supplied by the params class
            for err in error_details:
                if err.get("loc"):
                    field_name = err.get("loc")[0]
                    error_type = err.get("type")

                    # Ask params class for a custom error message
                    custom_error = self.params_class.get_custom_error_message(field_name, error_type)
                    if custom_error:
                        logger.info(f"Using custom error message: field={field_name}, type={error_type}")
                        return ToolResult(
                            error=custom_error,
                            name=str(self.get_effective_name())
                        )

            # Otherwise, build a friendly validation message
            pretty_error_msg = self._generate_friendly_validation_error(error_details, str(self.get_effective_name()))
            return ToolResult(
                error=pretty_error_msg,
                name=str(self.get_effective_name())
            )
        except Exception as e:
            # Other exceptions
            logger.error(f"Parameter validation failed: {e!s}")
            pretty_error = f"Tool '{self.get_effective_name()}' parameter validation failed, please check input format"
            result = ToolResult(
                error=pretty_error,
                name=str(self.get_effective_name())
            )
            return result

        # Execute the tool
        try:
            result = await self.execute(tool_context, params)
        except Exception as e:
            logger.error(f"Tool {self.get_effective_name()} execution failed: {e}", exc_info=True)
            # Capture execution errors and return them
            result = ToolResult(
                error=f"Tool execution failed: {e!s}",
                name=str(self.get_effective_name())
            )

        # Set execution time and name
        execution_time = time.time() - start_time
        result.execution_time = execution_time
        result.name = str(self.get_effective_name())

        # Add explanation when present
        explanation = params.explanation if hasattr(params, 'explanation') else None
        if explanation:
            result.explanation = explanation

        return result

    def _generate_friendly_validation_error(self, error_details, tool_name: str) -> str:
        """Generate a user-friendly validation error message."""
        logger = get_logger(__name__)

        # Split errors by category
        missing_fields = []
        type_errors = []
        other_errors = []

        for err in error_details:
            err_type = err.get("type", "")
            field_path = ".".join(str(loc) for loc in err.get("loc", []))

            if err_type == "missing":
                missing_fields.append(field_path)
            elif "type" in err_type:  # Type errors, e.g. type_error
                # Expected type
                expected_type = "valid value"
                if "expected_type" in err.get("ctx", {}):
                    expected_type = err["ctx"]["expected_type"]
                elif "expected" in err.get("ctx", {}):
                    expected_type = err["ctx"]["expected"]

                # Received type
                received_type = "invalid type"
                if "input_type" in err.get("ctx", {}):
                    received_type = err["ctx"]["input_type"]
                elif "received" in err.get("ctx", {}):
                    received_type = str(type(err["ctx"]["received"]).__name__)

                error_msg = f"Parameter '{field_path}' should be of type {expected_type}, not {received_type}"
                type_errors.append(error_msg)
            else:
                # Other errors
                msg = err.get("msg", "unknown error")
                other_errors.append(f"Parameter '{field_path}': {msg}")

        # Build friendly message
        pretty_msg_parts = []

        if missing_fields:
            fields_str = ", ".join(missing_fields)
            pretty_msg_parts.append(f"Missing required parameters: {fields_str}")

        if type_errors:
            pretty_msg_parts.append("Type errors: " + "; ".join(type_errors))

        if other_errors:
            pretty_msg_parts.append("Validation errors: " + "; ".join(other_errors))

        if not pretty_msg_parts:
            # Provide generic message when no specific details
            return f"Tool '{tool_name}' parameter validation failed, please check input format"

        return "Tool invocation failed: " + "; ".join(pretty_msg_parts) + ", ensure parameters are correct, formatted as valid JSON, and not exceeding length limits."

    def to_param(self) -> Dict:
        """Convert tool into function-call schema for function calling."""
        logger = get_logger(__name__)

        # Note: "additionalProperties": False is intentionally removed here
        parameters = {
            "type": "object",
            "properties": {},
            "required": [],
        }

        if self.params_class:
            try:
                # Build schema via params_class cleaning method
                schema = self.params_class.model_json_schema_clean()

                # We only need properties and required
                if 'properties' in schema:
                    parameters['properties'] = schema['properties']

                if 'required' in schema:
                    parameters['required'] = schema['required']
                else:
                    # Default non-Optional fields to required when missing in schema
                    if 'properties' in parameters:
                         parameters['required'] = list(parameters['properties'].keys())

                # Ensure explanation is required when present and non-Optional
                if 'explanation' in parameters.get('properties', {}) and 'explanation' not in parameters['required']:
                     is_optional = False
                     explanation_field = self.params_class.model_fields.get('explanation')
                     if explanation_field and getattr(explanation_field, 'annotation', None):
                         from typing import get_args, get_origin
                         origin = get_origin(explanation_field.annotation)
                         if origin is Union:
                             args = get_args(explanation_field.annotation)
                             if type(None) in args:
                                 is_optional = True
                         # Handle Optional[T] syntax introduced in Python 3.10
                         elif origin is Optional:
                            is_optional = True

                     if not is_optional:
                        # Only add when explanation exists in properties
                        if 'explanation' in parameters.get('properties', {}):
                           parameters['required'].append('explanation')

            except Exception as e:
                logger.error(f"Failed generating tool parameter schema: {e!s}", exc_info=True)

        # Drop properties/required when empty
        if not parameters['properties']:
            parameters.pop('properties')
            parameters.pop('required', None)

        # Resolve effective name/description
        effective_name = self.get_effective_name()
        effective_description = self.get_effective_description()

        # Ensure values are strings
        if not isinstance(effective_name, str):
            effective_name = str(effective_name)
        if not isinstance(effective_description, str):
            effective_description = str(effective_description)

        return {
            "type": "function",
            "function": {
                "name": effective_name,
                "description": effective_description,
                "parameters": parameters,
            },
        }

    def generate_message_id(self) -> str:
        """Generate a message ID using the default Snowflake generator."""
        # Use Snowflake to generate the ID
        snowflake = Snowflake.create_default()
        return str(snowflake.get_id())

    def get_prompt_hint(self) -> str:
        """Return tool-specific prompt hints to append to the base prompt."""
        return ""

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """Build a ToolDetail for a result; override to provide custom detail."""
        # Default: no detail
        return None

    async def get_before_tool_call_friendly_content(self, tool_context: ToolContext, arguments: Dict[str, Any] = None) -> str:
        """Get pre-call friendly content."""
        return arguments["explanation"]

    async def get_after_tool_call_friendly_content(self, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> str:
        """Get post-call friendly content."""
        return ""

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Get post-call friendly action and remark."""
        return {
            "action": "",
            "remark": ""
        }
