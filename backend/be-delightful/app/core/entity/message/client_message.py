"""
Client Message Structure Definition

Defines the WebSocket message structure sent by clients to the server
"""
from enum import Enum
from typing import Any, Dict, List, Optional, Union

from pydantic import BaseModel, Field, validator

from agentlang.environment import Environment
from app.core.config.communication_config import MessageSubscriptionConfig, STSTokenRefreshConfig
from app.core.entity.message.message import MessageType


class ClientMessage(BaseModel):
    """Task message model"""
    message_id: str
    type: Union[MessageType, str]

    class Config:
        use_enum_values = True


class ContextType(str, Enum):
    """Message context type enumeration"""
    NORMAL = "normal"      # Normal message
    FOLLOW_UP = "follow_up"  # Follow-up message
    INTERRUPT = "interrupt"  # Interrupt message

class TaskMode(str, Enum):
    """Task mode type enumeration"""
    CHAT = "chat"      # Chat mode, uses delightful.agent
    PLAN = "plan"      # Planning mode, uses be-delightful.agent

class ChatClientMessage(ClientMessage):
    """
    Chat message type
    
    For handling user-sent chat messages
    """
    type: str = MessageType.CHAT.value
    prompt: str
    attachments: List[Dict[str, Any]] = []
    context_type: ContextType = ContextType.NORMAL  # Default to normal message
    task_mode: TaskMode = TaskMode.PLAN  # Task mode, defaults to planning mode

    @validator('attachments', each_item=True)
    def validate_attachment(cls, v):
        if not isinstance(v, dict):
            raise ValueError("Attachment must be an object")

        required_fields = ["file_tag", "filename", "file_key", "file_size", "file_url"]

        for field in required_fields:
            if field not in v:
                raise ValueError(f"Attachment must contain '{field}' field")

        # Validate string type fields
        string_fields = ["file_tag", "filename", "file_key", "file_url"]
        for field in string_fields:
            if not isinstance(v[field], str):
                raise ValueError(f"Attachment's '{field}' must be a string")

        # Validate file size must be a number
        if not isinstance(v["file_size"], (int, float)):
            raise ValueError("Attachment's 'file_size' must be a number type")

        return v


class InitClientMessage(ClientMessage):
    """
    Initialization message type
    
    For workspace initialization
    """
    type: str = MessageType.INIT.value
    message_subscription_config: Optional[MessageSubscriptionConfig] = None  # Message subscription config, optional field
    sts_token_refresh: Optional[STSTokenRefreshConfig] = None  # STS Token refresh config, optional field
    metadata: Optional[Dict[str, Any]] = Field(default_factory=dict)
    upload_config: Optional[Dict[str, Any]] = None  # Upload config, may include platform type and temporary credentials

    @validator('message_subscription_config')
    def validate_message_subscription_config(cls, v):
        if Environment.is_dev():
            return v
        if v is None:
            raise ValueError("Message subscription config 'message_subscription_config' cannot be empty")
        return v

    @validator('metadata')
    def validate_metadata(cls, v):
        if v is None:
            raise ValueError("Metadata 'metadata' cannot be empty")
        return v
