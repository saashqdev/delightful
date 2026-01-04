"""
Base context class

Defines the common interface and basic functionality for all context types
"""

import time
from typing import Any, Dict


class BaseContext:
    """
    Base context class providing context data management functionality
    """

    def __init__(self):
        """
        Initialize base context
        """
        self._metadata = {}
        self._created_at = int(time.time() * 1000)  # Creation timestamp (milliseconds)

    def to_dict(self) -> Dict[str, Any]:
        """
        Convert context to dictionary format

        Returns:
            Dict[str, Any]: Dictionary representation of the context
        """
        # Base class only returns metadata and creation time
        result = {"_created_at": self._created_at, **self._metadata}
        return result

    def set_metadata(self, key: str, value: Any) -> None:
        """
        Set metadata

        Args:
            key: Metadata key
            value: Metadata value
        """
        self._metadata[key] = value

    def get_metadata(self, key: str, default: Any = None) -> Any:
        """
        Get metadata

        Args:
            key: Metadata key
            default: Default value to return if key does not exist

        Returns:
            Any: Metadata value or default value
        """
        return self._metadata.get(key, default)

    def update_metadata(self, metadata: Dict[str, Any]) -> None:
        """
        Batch update metadata

        Args:
            metadata: Metadata dictionary to update
        """
        self._metadata.update(metadata)

    def get_creation_time(self) -> int:
        """
        Get context creation time

        Returns:
            int: Creation timestamp (milliseconds)
        """
        return self._created_at

    def merge(self, other: "BaseContext") -> None:
        """
        Merge data from another context

        Args:
            other: Context object to merge
        """
        self._metadata.update(other._metadata) 
