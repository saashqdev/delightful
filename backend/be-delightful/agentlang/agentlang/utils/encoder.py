"""
JSON Encoder

Provides custom JSON encoder for serialization of special object types
"""

import json
from typing import Any

from openai.types.chat import ChatCompletionMessageToolCall


class CustomJSONEncoder(json.JSONEncoder):
    """Custom JSON encoder for special type serialization"""

    def default(self, obj: Any) -> Any:
        """
        Handle serialization of special types

        Args:
            obj: Object to serialize

        Returns:
            Object that can be processed by standard JSON encoder
        """
        # Handle ChatCompletionMessageToolCall objects
        if isinstance(obj, ChatCompletionMessageToolCall):
            return {
                "id": obj.id,
                "type": obj.type,
                "function": {
                    "name": obj.function.name,
                    "arguments": obj.function.arguments
                }
            }

        # Try model_dump method (Pydantic models)
        if hasattr(obj, "model_dump"):
            return obj.model_dump()

        # Try __dict__ attribute
        if hasattr(obj, "__dict__"):
            return obj.__dict__

        # Default handling
        return super().default(obj)


def json_dumps(obj: Any, **kwargs) -> str:
    """
    JSON serialization function using custom encoder

    Args:
        obj: Object to serialize
        **kwargs: Additional arguments to pass to json.dumps

    Returns:
        Serialized JSON string
    """
    # Set defaults
    kwargs.setdefault("ensure_ascii", False)
    kwargs.setdefault("cls", CustomJSONEncoder)

    return json.dumps(obj, **kwargs) 
