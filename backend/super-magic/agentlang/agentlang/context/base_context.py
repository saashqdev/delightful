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
        设置元数据

        Args:
            key: 元数据键
            value: 元数据值
        """
        self._metadata[key] = value

    def get_metadata(self, key: str, default: Any = None) -> Any:
        """
        获取元数据

        Args:
            key: 元数据键
            default: 如果键不存在时返回的默认值

        Returns:
            Any: 元数据值或默认值
        """
        return self._metadata.get(key, default)

    def update_metadata(self, metadata: Dict[str, Any]) -> None:
        """
        批量更新元数据

        Args:
            metadata: 要更新的元数据字典
        """
        self._metadata.update(metadata)

    def get_creation_time(self) -> int:
        """
        获取上下文创建时间

        Returns:
            int: 创建时间戳（毫秒）
        """
        return self._created_at

    def merge(self, other: "BaseContext") -> None:
        """
        合并另一个上下文的数据

        Args:
            other: 要合并的上下文对象
        """
        self._metadata.update(other._metadata) 
