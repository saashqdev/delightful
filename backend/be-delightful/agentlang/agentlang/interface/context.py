"""
Agent context interface.

Defines abstract interfaces for obtaining user info and core utilities, decoupling the framework from concrete implementations.
"""

from abc import ABC, abstractmethod
from typing import Any, Callable, Dict, Optional, TypeVar

T = TypeVar('T')  # For generic types

class AgentContextInterface(ABC):
    """
    Base interface for agent context.

    Defines core framework capabilities such as user info, workspace, event system, and resource management.
    """

    @abstractmethod
    def get_user_id(self) -> Optional[str]:
        """Get user ID.

        Returns:
            Optional[str]: User ID, or None if absent
        """
        pass

    @abstractmethod
    def get_metadata(self) -> Dict[str, Any]:
        """Get metadata.

        Returns:
            Dict[str, Any]: Context metadata
        """
        pass

    @abstractmethod
    def get_workspace_dir(self) -> str:
        """Get workspace directory.

        Returns:
            str: Absolute path to workspace directory
        """
        pass

    @abstractmethod
    def ensure_workspace_dir(self) -> str:
        """Ensure workspace directory exists and return its path.

        Returns:
            str: Absolute path to workspace directory
        """
        pass

    @abstractmethod
    async def dispatch_event(self, event_type: str, data: Any) -> Any:
        """Dispatch an event.

        Args:
            event_type: Event type
            data: Event payload

        Returns:
            Any: Event handling result
        """
        pass

    @abstractmethod
    def add_event_listener(self, event_type: str, listener: Callable[[Any], None]) -> None:
        """Add an event listener.

        Args:
            event_type: Event type
            listener: Listener callable receiving an event payload
        """
        pass

    @abstractmethod
    async def get_resource(self, name: str, factory=None) -> Any:
        """Get a resource, creating via factory if missing.

        Args:
            name: Resource name
            factory: Factory callable to create resource when absent

        Returns:
            Any: Requested resource instance
        """
        pass

    @abstractmethod
    def add_resource(self, name: str, resource: Any) -> None:
        """Add a resource.

        Args:
            name: Resource name
            resource: Resource instance
        """
        pass

    @abstractmethod
    async def close_resource(self, name: str) -> None:
        """Close and remove a resource.

        Args:
            name: Resource name
        """
        pass 
