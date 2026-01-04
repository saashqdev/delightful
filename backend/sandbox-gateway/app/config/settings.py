"""
Configuration management module
"""
import os
from typing import Optional
from pydantic import Field
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """Application configuration"""
    # Application environment
    app_env: str = Field(..., env="APP_ENV")
    # Sandbox image name
    super_magic_image_name: str = Field(..., env="SUPER_MAGIC_IMAGE_NAME")
    
    # Running container timeout (seconds), containers will be stopped after this time
    running_container_expire_time: int = Field(3600 * 6, env="CONTAINER_EXPIRE_TIME")
    
    # Exited container expiration time (seconds), default 30 minutes
    exited_container_expire_time: int = Field(1800, env="EXITED_CONTAINER_EXPIRE_TIME")
    
    # Service port
    sandbox_gateway_port: int = Field(8003, env="SANDBOX_GATEWAY_PORT")
    
    # Container WebSocket port
    container_ws_port: int = Field(8002, env="CONTAINER_WS_PORT")
    
    # Log level
    log_level: str = Field("INFO", env="LOG_LEVEL")
    
    # Log file
    log_file: Optional[str] = Field(None, env="LOG_FILE")
    
    # Health check interval (seconds)
    health_check_interval: int = Field(300, env="HEALTH_CHECK_INTERVAL")
    
    # Container cleanup interval (seconds)
    cleanup_interval: int = Field(300, env="CLEANUP_INTERVAL")

    # WebSocket receive message timeout (seconds)
    ws_receive_timeout: float = Field(600.0, env="WS_RECEIVE_TIMEOUT")
    
    # Qdrant configuration
    qdrant_image_name: str = Field("qdrant/qdrant:latest", env="QDRANT_IMAGE_NAME")
    qdrant_port: int = Field(6333, env="QDRANT_PORT")
    qdrant_grpc_port: int = Field(6334, env="QDRANT_GRPC_PORT")
    qdrant_label: str = Field("qdrant", env="QDRANT_LABEL")
    
    # Agent environment file configuration - required
    agent_env_file_path: str = Field(..., env="AGENT_ENV_FILE_PATH")
    
    class Config:
        """Configuration metadata"""
        env_file = ".env"
        case_sensitive = False
        extra = "ignore"  # Allow extra fields


# Load configuration
def load_settings() -> Settings:
    """Load application configuration"""
    # Try to load configuration from environment variables or .env file
    try:
        settings = Settings()
        # Check if Agent environment file exists
        if not os.path.isfile(settings.agent_env_file_path):
            raise ValueError(f"Agent environment file does not exist: {settings.agent_env_file_path}")
        return settings
    except Exception as e:
        # If loading fails, output error and use default values
        print(f"Configuration loading error: {e}", file=os.sys.stderr)
        # If SUPER_MAGIC_IMAGE_NAME is not defined, it must be set manually
        super_magic_image_name = os.environ.get("SUPER_MAGIC_IMAGE_NAME")
        if not super_magic_image_name:
            raise ValueError("Environment variable SUPER_MAGIC_IMAGE_NAME must be set to specify sandbox image name") from e
        
        # For required environment variable files, throw exception directly to prevent startup
        if "Agent environment file does not exist" in str(e):
            raise
        
        # Use basic configuration
        return Settings(super_magic_image_name=super_magic_image_name)


# Global configuration instance
settings = load_settings() 