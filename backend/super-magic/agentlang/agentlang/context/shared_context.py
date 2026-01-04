"""
Shared context module

Provides global unified AgentSharedContext for sharing state among multiple agent instances
"""

import json
from datetime import datetime, timedelta
from typing import Any, Dict, Optional, Tuple, Type, TypeVar, Union

from agentlang.logger import get_logger

logger = get_logger(__name__)

T = TypeVar('T')

class AgentSharedContext:
    """Shared context class
    
    Provides global unified shared state, supports field extensions
    """

    def __init__(self):
        """Initialize shared data"""
        logger.debug("Initializing AgentSharedContext")
        # Initialize fields
        self._initialize_fields()

        # Field dictionary
        self._fields = {}

        # Field type dictionary
        self._field_types = {}

        # Initialization status flag
        self._initialized = False

    def _initialize_fields(self):
        """Initialize fields"""
        # Set activity time related fields
        self.last_activity_time = datetime.now()

        # Set default timeout
        default_timeout = 3600  # Default 1 hour
        timeout_seconds = default_timeout

        try:
            from agentlang.environment import Environment
            timeout_seconds = Environment.get_agent_idle_timeout()
        except (ImportError, AttributeError) as e:
            logger.warning(f"Failed to get timeout setting: {e!s}, using default value {default_timeout} seconds")

        self.idle_timeout = timedelta(seconds=timeout_seconds)

    def is_initialized(self) -> bool:
        """Check if initialization is complete
        
        Returns:
            bool: Whether initialized
        """
        return self._initialized

    def set_initialized(self, value: bool = True) -> None:
        """Set initialization status
        
        Args:
            value: Initialization status value, defaults to True
        """
        self._initialized = value
        logger.debug(f"Set initialization status to: {value}")

    def update_activity_time(self) -> None:
        """Update activity time"""
        self.last_activity_time = datetime.now()
        logger.debug(f"Updated activity time: {self.last_activity_time}")

    def is_idle_timeout(self) -> bool:
        """Check if timeout occurred"""
        current_time = datetime.now()
        is_timeout = (current_time - self.last_activity_time) > self.idle_timeout
        if is_timeout:
            logger.info(f"Agent timed out: last activity time {self.last_activity_time}, current time {current_time}")
        return is_timeout

    def register_field(self, field_name: str, field_value: Any, field_type: Optional[Type[T]] = None) -> None:
        """Register field
        
        Args:
            field_name: Field name
            field_value: Field value
            field_type: Field type, optional
        """
        if field_name in self._fields:
            logger.warning(f"Field '{field_name}' already exists, will be overwritten")

        self._fields[field_name] = field_value

        if field_type is not None:
            self._field_types[field_name] = field_type
            logger.debug(f"Registered field '{field_name}' with type {field_type.__name__}")
        elif field_value is not None:
            self._field_types[field_name] = type(field_value)
            logger.debug(f"Auto-inferred field '{field_name}' type as {type(field_value).__name__} from value")

        logger.info(f"Successfully registered field '{field_name}'")

    def register_fields(self, fields: Dict[str, Union[Any, Tuple[Any, Type]]]) -> None:
        """Register multiple fields in batch
        
        Args:
            fields: Field dictionary, keys are field names, values can be field values or (field_value, field_type) tuples
        
        Examples:
            >>> shared_context.register_fields({
            >>>     "streams": {},
            >>>     "task_id": (None, str),
            >>>     "attachments": ({}, Dict[str, Attachment])
            >>> })
        """
        for field_name, field_data in fields.items():
            if isinstance(field_data, tuple) and len(field_data) == 2:
                field_value, field_type = field_data
                self.register_field(field_name, field_value, field_type)
            else:
                self.register_field(field_name, field_data)

        logger.debug(f"Registered {len(fields)} fields in batch")

    def update_field(self, field_name: str, field_value: Any, field_type: Optional[Type[T]] = None) -> None:
        """更新字段值，如果字段不存在则自动注册
        
        Args:
            field_name: 字段名称
            field_value: 新的字段值
            field_type: 字段类型，可选，仅当字段不存在时使用
        """
        if not self.has_field(field_name):
            logger.info(f"Field '{field_name}' does not exist, auto-registering")
            self.register_field(field_name, field_value, field_type)
            return

        self._fields[field_name] = field_value
        logger.debug(f"Updated field '{field_name}' successfully")

    def get_field(self, field_name: str) -> Any:
        """获取字段
        
        Args:
            field_name: 字段名称
            
        Returns:
            字段值
            
        Raises:
            KeyError: 如果字段不存在
        """
        if field_name not in self._fields:
            logger.error(f"Field '{field_name}' does not exist")
            raise KeyError(f"字段 '{field_name}' 不存在")

        return self._fields[field_name]

    def has_field(self, field_name: str) -> bool:
        """检查字段是否存在
        
        Args:
            field_name: 字段名称
            
        Returns:
            字段是否存在
        """
        return field_name in self._fields

    def get_field_type(self, field_name: str) -> Optional[Type]:
        """获取字段类型
        
        Args:
            field_name: 字段名称
            
        Returns:
            字段类型，如果未指定则返回None
        """
        return self._field_types.get(field_name)

    def _serialize_value(self, value: Any) -> Any:
        """将值转换为可序列化的格式
        
        Args:
            value: 需要序列化的值
            
        Returns:
            Any: 转换后可序列化的值
        """
        if value is None:
            return None

        # 处理 pathlib.Path 对象
        if hasattr(value, "absolute") and callable(getattr(value, "absolute")):
            return str(value)

        # 处理具有 to_dict 方法的对象
        if hasattr(value, "to_dict") and callable(getattr(value, "to_dict")):
            return value.to_dict()

        # 处理日期时间对象
        if isinstance(value, datetime) or isinstance(value, timedelta):
            return str(value)

        # 处理字典
        if isinstance(value, dict):
            return {k: self._serialize_value(v) for k, v in value.items()}

        # 处理列表或元组
        if isinstance(value, (list, tuple)):
            return [self._serialize_value(item) for item in value]

        # 处理类型对象，如 Type[T]
        if isinstance(value, type):
            return value.__name__

        # 尝试直接转换为 str
        try:
            json.dumps(value)
            return value
        except (TypeError, OverflowError, ValueError):
            # 如果无法序列化，则返回类型和ID信息
            return f"<{type(value).__name__}:{id(value)}>"

    def to_dict(self) -> Dict[str, Any]:
        """将上下文转换为字典
        
        Returns:
            Dict[str, Any]: 包含上下文信息的字典
        """
        result = {
            "_initialized": self._initialized,
            "last_activity_time": str(self.last_activity_time),
            "idle_timeout": str(self.idle_timeout),
        }

        # 添加所有字段和字段类型
        fields_info = {}
        for field_name, field_value in self._fields.items():
            fields_info[field_name] = {
                "value": self._serialize_value(field_value),
                "type": self._serialize_value(self._field_types.get(field_name))
            }

        result["fields"] = fields_info

        return result

    def __str__(self) -> str:
        """自定义字符串表示
        
        Returns:
            str: 字典形式的字符串表示
        """
        try:
            return json.dumps(self.to_dict(), ensure_ascii=False, indent=2)
        except Exception as e:
            return f"<AgentSharedContext object at {hex(id(self))}: {e!s}>"

    def __repr__(self) -> str:
        """自定义对象表示
        
        Returns:
            str: 字典形式的对象表示
        """
        return self.__str__()

    @classmethod
    def reset(cls):
        """重置共享上下文"""
        global AgentSharedContext
        # 记录旧实例的id
        old_id = id(AgentSharedContext)

        # 直接创建一个新的实例
        AgentSharedContext = cls()

        # 确保不共享引用
        new_id = id(AgentSharedContext)
        if old_id == new_id:
            logger.error(f"Reset failed: new and old instances are the same object (id={old_id})")
        else:
            logger.info(f"Shared context reset successfully: old id={old_id}, new id={new_id}")
            # 新实例的初始化状态已经是 False，这里记录一下日志
            logger.debug("New shared context initialization state has been reset to False")


# 创建单例实例
AgentSharedContext = AgentSharedContext() 
