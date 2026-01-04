from typing import Any, Dict, Optional

from pydantic import BaseModel, Field, model_validator

from agentlang.utils.json import json_dumps


class ToolResult(BaseModel):
    """Represents the result of a tool execution.

    Correct usage:
    1. Success result:
       ```python
       # Return a successful result
       return ToolResult(
           content="Result content of successful operation",  # Required, result content
           system="Optional system information, not displayed to users",  # Optional
           name="Tool name"  # Optional
       )
       ```

    2. Error result:
       ```python
       # Use the error parameter
       return ToolResult(
           error="An error occurred: xxx"  # Validator automatically sets content and ok to False
       )
       ```

    Note:
    - Cannot set both error and content parameters
    - The error parameter is automatically converted to content, and ok is set to False
    - In exception handling, it is recommended to use the error parameter to mark errors
    """

    content: str = Field(description="Tool execution result content, returned as output to AI LLM")
    ok: bool = Field(default=True, description="Whether tool execution was successful")
    extra_info: Optional[Dict[str, Any]] = Field(default=None, description="Tool execution additional information, not displayed to users, not passed to AI LLM")
    system: Optional[str] = Field(default=None)
    tool_call_id: Optional[str] = Field(default=None)
    name: Optional[str] = Field(default=None)
    execution_time: float = Field(default=0.0, description="Tool execution time (seconds)")
    explanation: Optional[str] = Field(default=None, description="LLM's intention explanation for executing this tool")

    # Solution: Add a model validator to ToolResult; when an error param is provided, auto-set content field and set ok to false.
    @model_validator(mode='before')
    @classmethod
    def handle_error_parameter(cls, data):
        if not isinstance(data, dict):
            return data

        if 'error' in data and data['error'] is not None:
            if data.get('content') and data['content'] != "":
                raise ValueError("Cannot set both 'error' and 'content' parameters")

            # Move error value to content
            data['content'] = data.pop('error')
            # Set ok to False
            data['ok'] = False

        return data

    class Config:
        arbitrary_types_allowed = True

    def __bool__(self):
        return any(getattr(self, field) for field in self.model_fields)

    def __add__(self, other: "ToolResult"):
        def combine_fields(field: Optional[str], other_field: Optional[str], concatenate: bool = True):
            if field and other_field:
                if concatenate:
                    return field + other_field
                raise ValueError("Cannot combine tool results")
            return field or other_field or ""

        return ToolResult(
            content=combine_fields(self.content, other.content),
            system=combine_fields(self.system, other.system),
            tool_call_id=self.tool_call_id or other.tool_call_id,
            name=self.name or other.name,
            execution_time=self.execution_time + other.execution_time,  # Sum execution times
            explanation=self.explanation or other.explanation,  # Keep the first non-empty explanation
            ok=self.ok and other.ok,  # Only successful if both are successful
        )

    def __str__(self):
        return f"Error: {self.content}" if not self.ok else self.content

    def get_content(self) -> str:
        """Get the result content of tool execution
        
        Returns:
            str: Result content
        """
        return self.content

    def is_ok(self) -> bool:
        """Check if tool execution was successful
        
        Returns:
            bool: True for success, False for failure
        """
        return self.ok

    def get_extra_info(self) -> Optional[Dict[str, Any]]:
        """Get additional information of tool execution
        
        Returns:
            Optional[Dict[str, Any]]: Extra information dictionary, None if not available
        """
        return self.extra_info

    def model_dump_json(self, **kwargs) -> str:
        """Convert ToolResult object to JSON string

        Args:
            **kwargs: Parameters passed to json.dumps

        Returns:
            str: JSON string
        """
        return json_dumps(self.model_dump(), **kwargs)
