"""
Tool context class

Provides context information required for tool execution environment
"""

import uuid
from typing import Any, Dict, Optional, Type, TypeVar, cast

from agentlang.context.base_context import BaseContext

# Define generic type variable for extension types
T = TypeVar('T')

class ToolContext(BaseContext):
    """
    Tool context class, provides context information required for tool execution
    """

    def __init__(
        self, tool_call_id: str = "", tool_name: str = "", arguments: Dict[str, Any] = None, metadata: Dict[str, Any] = None
    ):
        """
        Initialize tool context

        Args:
            tool_call_id: Tool call ID
            tool_name: Tool name
            arguments: Tool parameters
            metadata: Initial metadata, usually inherited from AgentContext's metadata
        """
        super().__init__()

        self.id = str(uuid.uuid4())

        # Tool-specific attributes
        self.tool_call_id = tool_call_id
        self.tool_name = tool_name
        self.arguments = arguments or {}

        # Initialize metadata
        self._metadata = metadata.copy() if metadata else {}

        # Extension context dictionary for storing various extensions
        self._extensions: Dict[str, Any] = {}

    def to_dict(self) -> Dict[str, Any]:
        """
        Convert tool context to dictionary format

        Returns:
            Dict[str, Any]: Dictionary representation of the context
        """
        result = super().to_dict()

        # Add basic tool information
        result.update({
            "tool_call_id": self.tool_call_id,
            "tool_name": self.tool_name,
        })

        # Add task ID and working directory (if exists in metadata)
        if "task_id" in self._metadata:
            result["task_id"] = self._metadata["task_id"]
        if "workspace_dir" in self._metadata:
            result["workspace_dir"] = self._metadata["workspace_dir"]

        # Add extension information
        extensions_dict = {}
        for ext_name, ext_obj in self._extensions.items():
            if hasattr(ext_obj, 'to_dict') and callable(ext_obj.to_dict):
                extensions_dict[ext_name] = ext_obj.to_dict()
            else:
                extensions_dict[ext_name] = str(ext_obj)

        if extensions_dict:
            result["extensions"] = extensions_dict

        return result

    def get_argument(self, name: str, default: Any = None) -> Any:
        """
        Get tool parameter

        Args:
            name: Parameter name
            default: Default value

        Returns:
            Any: Parameter value or default value
        """
        return self.arguments.get(name, default)

    def has_argument(self, name: str) -> bool:
        """
        Check if specified parameter exists

        Args:
            name: Parameter name

        Returns:
            bool: Whether parameter exists
        """
        return name in self.arguments

    @property
    def task_id(self) -> str:
        """Get task ID"""
        return self._metadata.get("task_id", "")

    @property
    def base_dir(self) -> str:
        """Get base directory"""
        return self._metadata.get("workspace_dir", "")

    # Extension context related methods

    def register_extension(self, name: str, extension: Any) -> None:
        """
        Register an extension context
        
        Args:
            name: Extension name
            extension: Extension context object
        """
        self._extensions[name] = extension

    def get_extension(self, name: str) -> Optional[Any]:
        """
        Get extension context by specified name
        
        Args:
            name: Extension name
            
        Returns:
            Optional[Any]: Extension context object, returns None if not exists
        """
        return self._extensions.get(name)

    def get_extension_typed(self, name: str, extension_type: Type[T]) -> Optional[T]:
        """
        Get extension context by specified name and type
        
        Generic version of get_extension method, can automatically infer return type,
        provides better code completion support in IDE
        
        Args:
            name: Extension name
            extension_type: Extension type, e.g.: EventContext
            
        Returns:
            Optional[T]: Extension context object matching type, returns None if not exists or type mismatch
            
        Examples:
            ```python
            # IDE will correctly recognize event_context's type as EventContext
            event_context = tool_context.get_extension_typed("event_context", EventContext)
            if event_context:
                # Auto-completion for all EventContext methods available here
                event_context.add_attachment(attachment)
            ```
        """
        extension = self._extensions.get(name)
        if extension is not None and isinstance(extension, extension_type):
            # Use cast to help IDE recognize return type
            return cast(T, extension)
        return None

    def has_extension(self, name: str) -> bool:
        """
        Check if extension with specified name exists

        Args:
            name: Extension name

        Returns:
            bool: Whether extension exists
