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
        """Update field value, auto-register if field does not exist
        
        Args:
            field_name: Field name
            field_value: New field value
            field_type: Field type, optional, only used when field does not exist
        """
        if not self.has_field(field_name):
            logger.info(f"Field '{field_name}' does not exist, auto-registering")
            self.register_field(field_name, field_value, field_type)
            return

        self._fields[field_name] = field_value
        logger.debug(f"Updated field '{field_name}' successfully")

    def get_field(self, field_name: str) -> Any:
        """Get field
        
        Args:
            field_name: Field name
            
        Returns:
            Field value
            
        Raises:
            KeyError: If field does not exist
        """
        if field_name not in self._fields:
            logger.error(f"Field '{field_name}' does not exist")
            raise KeyError(f"Field '{field_name}' does not exist")

        return self._fields[field_name]

    def has_field(self, field_name: str) -> bool:
        """Check if field exists
        
        Args:
            field_name: Field name
            
        Returns:
            Whether field exists
        """
        return field_name in self._fields

    def get_field_type(self, field_name: str) -> Optional[Type]:
        """Get field type
        
        Args:
            field_name: Field name
            
        Returns:
            Field type, returns None if not specified
        """
        return self._field_types.get(field_name)

    def _serialize_value(self, value: Any) -> Any:
        """Convert value to serializable format
        
        Args:
            value: Value to serialize
            
        Returns:
            Any: Converted serializable value
        """
        if value is None:
            return None

        # Handle pathlib.Path objects
        if hasattr(value, "absolute") and callable(getattr(value, "absolute")):
            return str(value)

        # Handle objects with to_dict method
        if hasattr(value, "to_dict") and callable(getattr(value, "to_dict")):
            return value.to_dict()

        # Handle datetime objects
        if isinstance(value, datetime) or isinstance(value, timedelta):
            return str(value)

        # Handle dictionaries
        if isinstance(value, dict):
            return {k: self._serialize_value(v) for k, v in value.items()}

        # Handle lists or tuples
        if isinstance(value, (list, tuple)):
            return [self._serialize_value(item) for item in value]

        # Handle type objects like Type[T]
        if isinstance(value, type):
            return value.__name__

        # Try direct string conversion
        try:
            json.dumps(value)
            return value
        except (TypeError, OverflowError, ValueError):
            # If cannot serialize, return type and ID info
            return f"<{type(value).__name__}:{id(value)}>"

    def to_dict(self) -> Dict[str, Any]:
        """Convert context to dictionary
        
        Returns:
            Dict[str, Any]: Dictionary containing context information
        """
        result = {
            "_initialized": self._initialized,
            "last_activity_time": str(self.last_activity_time),
            "idle_timeout": str(self.idle_timeout),
        }

        # Add all fields and field types
        fields_info = {}
        for field_name, field_value in self._fields.items():
            fields_info[field_name] = {
                "value": self._serialize_value(field_value),
                "type": self._serialize_value(self._field_types.get(field_name))
            }

        result["fields"] = fields_info

        return result

    def __str__(self) -> str:
        """Custom string representation
        
        Returns:
            str: Dictionary form string representation
        """
        try:
            return json.dumps(self.to_dict(), ensure_ascii=False, indent=2)
        except Exception as e:
            return f"<AgentSharedContext object at {hex(id(self))}: {e!s}>"

    def __repr__(self) -> str:
        """Custom object representation
        
        Returns:
            str: Dictionary form object representation
        """
        return self.__str__()

    @classmethod
    def reset(cls):
        """Reset shared context"""
        global AgentSharedContext
        # Record old instance id
        old_id = id(AgentSharedContext)

        # Create a new instance directly
        AgentSharedContext = cls()

        # Ensure no shared references
        new_id = id(AgentSharedContext)
        if old_id == new_id:
            logger.error(f"Reset failed: new and old instances are the same object (id={old_id})")
        else:
            logger.info(f"Shared context reset successfully: old id={old_id}, new id={new_id}")
            # New instance initialization state is already False, log here
            logger.debug("New shared context initialization state has been reset to False")


# Create singleton instance
AgentSharedContext = AgentSharedContext() 
