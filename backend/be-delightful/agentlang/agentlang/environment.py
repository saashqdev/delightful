"""
Environment Configuration Class

Manages environment-related parameter settings
"""
import os
from typing import Any, Optional, Type, TypeVar, Union, cast

T = TypeVar('T')

class Environment:
    """Environment Configuration Class"""

    DEFAULT_AGENT_IDLE_TIMEOUT = 3600  # Default timeout: 1 hour
    DEFAULT_IDLE_MONITOR_INTERVAL = 60  # Default monitoring interval: 60 seconds

    @staticmethod
    def get_env(key: str, default: Optional[Any] = None, value_type: Optional[Type[T]] = None) -> Union[Optional[str], T]:
        """Get environment variable
        
        Args:
            key: Environment variable name
            default: Default value
            value_type: Return value type, such as int, bool, etc.
            
        Returns:
            Environment variable value, converted based on value_type
        """
        value = os.environ.get(key)
        if value is None:
            return default

        # If type is not specified, return string
        if value_type is None:
            return value

        # Convert based on specified type
        if value_type is bool:
            return cast(T, value.lower() in ('true', '1', 'yes', 'y', 'on'))
        elif value_type is int:
            try:
                return cast(T, int(value))
            except ValueError:
                return default
        else:
            # Try to use type constructor for conversion
            try:
                return cast(T, value_type(value))
            except (ValueError, TypeError):
                return default

    @staticmethod
    def get_int_env(key: str, default: int) -> int:
        """Get integer type environment variable
        
        Args:
            key: Environment variable name
            default: Default value
            
        Returns:
            Environment variable value
        """
        return Environment.get_env(key, default, int)

    @staticmethod
    def get_bool_env(key: str, default: bool) -> bool:
        """Get boolean type environment variable
        
        Args:
            key: Environment variable name
            default: Default value
            
        Returns:
            Environment variable value
        """
        return Environment.get_env(key, default, bool)

    @staticmethod
    def get_agent_idle_timeout() -> int:
        """Get agent idle timeout time (seconds)
        
        Returns:
            int: Agent idle timeout time
        """
        # First get from environment variable, if not found get from config file, finally use default value
        env_value = Environment.get_int_env("AGENT_IDLE_TIMEOUT", -1)
        if env_value > 0:
            return env_value

        # Get from config file
        try:
            from agentlang.config.config import config
            config_value = config.get("sandbox.agent_idle_timeout", -1)
            if config_value > 0:
                return config_value
        except (ImportError, AttributeError):
            pass

        return Environment.DEFAULT_AGENT_IDLE_TIMEOUT

    @staticmethod
    def get_idle_monitor_interval() -> int:
        """Get idle monitoring interval time (seconds)
        
        Returns:
            int: Idle monitoring interval time
        """
        # First get from environment variable, if not found get from config file, finally use default value
        env_value = Environment.get_int_env("IDLE_MONITOR_INTERVAL", -1)
        if env_value > 0:
            return env_value

        # Get from config file
        try:
            from agentlang.config.config import config
            config_value = config.get("sandbox.idle_monitor_interval", -1)
            if config_value > 0:
                return config_value
        except (ImportError, AttributeError):
            pass

        return Environment.DEFAULT_IDLE_MONITOR_INTERVAL

    @staticmethod
    def is_dev() -> bool:
        """Check if it is a development environment
        
        Returns:
            bool: Whether it is a development environment
        """
        # First get from environment variable, if not found get from config file
        env_value = Environment.get_env("APP_ENV", "").lower()
        if env_value in ("dev", "development"):
            return True

        # Get from config file
        try:
            from agentlang.config.config import config
            config_value = config.get("sandbox.app_env", "prod").lower()
            return config_value in ("dev", "development")
        except (ImportError, AttributeError):
            return False 
