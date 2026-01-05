# -*- coding: utf-8 -*-
"""
This module defines data structures and models related to chat history.
Contains message types, compression configuration, Token usage information and other classes related to chat history.
"""

import json
import re
import uuid
from dataclasses import dataclass, field
from datetime import datetime, timedelta
from typing import Any, Dict, List, Literal, Optional, Union

from agentlang.config.config import config
from agentlang.llms.token_usage.models import TokenUsage  # Unified TokenUsage class
from agentlang.logger import get_logger

logger = get_logger(__name__)

# ==============================================================================
# Compression Configuration Data Class
# ==============================================================================
@dataclass
class CompressionConfig:
    """Configuration class for chat history compression functionality"""
    # Basic switch configuration
    enable_compression: bool = True  # Whether to enable compression functionality

    # Basic Agent information
    agent_name: str = ""
    agent_id: str = ""
    agent_model_id: str = ""
    # Trigger threshold configuration
    token_threshold: int = 0  # Token count threshold for triggering compression, defaults to 0, will be calculated dynamically based on model
    message_threshold: int = 100  # Message count threshold
    preserve_recent_turns: int = 20  # Number of recent conversation turns to preserve without compression
    # Message compression configuration
    target_compression_ratio: float = 0.6  # Overall target compression ratio

    # Advanced configuration
    compression_cooldown: int = 6  # Minimum message count between two compressions
    compression_batch_size: int = 10  # Maximum message count per compression batch
    llm_for_compression: str = "gpt-4.1-mini"  # LLM model used for compression
    def __post_init__(self):
        """Parameter validation and normalization"""
        # Validate compression ratio range
        if not 0 <= self.target_compression_ratio <= 1:
            raise ValueError("Overall target compression ratio must be between 0 and 1")

        # Validate threshold and preserve turn count
        if self.message_threshold < 0:
            raise ValueError("Message count threshold cannot be negative")
        if self.preserve_recent_turns < 0:
            raise ValueError("Preserved conversation turn count cannot be negative")

        # If token_threshold is 0, set default value based on current model's context length
        if self.token_threshold <= 0:
            self.token_threshold = self._calculate_model_based_threshold()
            logger.info(f"Set compression context token_threshold to {self.token_threshold} based on the context length of the {self.agent_model_id} model used by current Agent")

    def _calculate_model_based_threshold(self) -> int:
        """
        Calculate appropriate token threshold based on model's context length

        Returns:
            int: Calculated token threshold
        """
        try:
            # Get model information
            threshold = 40000  # Default threshold

            # Get all model configurations
            model_configs = config.get("models", {})

            if self.agent_model_id:
                # Get max_context_tokens from model configuration
                model_config = model_configs.get(self.agent_model_id, {})
                max_context_tokens = int(model_config.get("max_context_tokens", 0))
                # Set to 70% of context length as threshold
                threshold = int(max_context_tokens * 0.7)

            return threshold

        except Exception as e:
            logger.error(f"Error setting token threshold: {e}")
            return 160000  # Return default value on error

# ==============================================================================
# Compression Information Metadata
# ==============================================================================
@dataclass
class CompressionInfo:
    """Metadata related to chat message compression"""
    is_compressed: bool = False  # Whether it is a compressed message
    original_message_count: int = 0  # Original message count
    compression_ratio: float = 0.0  # Actual compression ratio
    compressed_at: str = ""  # Compression time
    message_spans: List[Dict[str, str]] = field(default_factory=list)  # Time spans of original messages

    @classmethod
    def create(cls, message_count: int, original_tokens: int, compressed_tokens: int) -> 'CompressionInfo':
        """
        Create compression information instance

        Args:
            message_count: Number of original messages to be compressed
            original_tokens: Token count before compression
            compressed_tokens: Token count after compression

        Returns:
            CompressionInfo: Compression information instance
        """
        compression_ratio = 1.0
        if original_tokens > 0:
            compression_ratio = 1.0 - (compressed_tokens / original_tokens)

        # Limit compression ratio between 0 and 1
        compression_ratio = max(0.0, min(1.0, compression_ratio))

        return cls(
            is_compressed=True,
            original_message_count=message_count,
            compression_ratio=compression_ratio,
            compressed_at=datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        )

    def to_dict(self) -> Dict[str, Any]:
        """Convert compression information to dictionary format"""
        result = {
            "is_compressed": self.is_compressed,
            "original_message_count": self.original_message_count,
            "compression_ratio": self.compression_ratio,
            "compressed_at": self.compressed_at,
        }

        if self.message_spans:
            result["message_spans"] = self.message_spans

        return result

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> 'CompressionInfo':
        """Create compression information object from dictionary"""
        compression_info = cls(
            is_compressed=data.get("is_compressed", False),
            original_message_count=data.get("original_message_count", 0),
            compression_ratio=data.get("compression_ratio", 0.0),
            compressed_at=data.get("compressed_at", ""),
        )

        spans = data.get("message_spans")
        if spans and isinstance(spans, list):
            compression_info.message_spans = spans

        return compression_info

# ==============================================================================
# Helper Functions: Duration Formatting and Parsing
# ==============================================================================

def format_duration_to_str(duration_ms: Optional[float]) -> Optional[str]:
    """
    Format milliseconds (float) to human-readable string (Scheme 2: HhMmS.fffS).

    Args:
        duration_ms (Optional[float]): Duration in milliseconds.

    Returns:
        Optional[str]: Formatted string, or None.
    """
    if duration_ms is None or duration_ms < 0:
        return None

    try:
        # Create timedelta object (note timedelta uses seconds)
        delta = timedelta(milliseconds=duration_ms)

        total_seconds = delta.total_seconds()
        hours, remainder = divmod(total_seconds, 3600)
        minutes, seconds = divmod(remainder, 60)

        hours = int(hours)
        minutes = int(minutes)
        # Preserve millisecond precision for seconds
        seconds_float = seconds

        parts = []
        if hours > 0:
            parts.append(f"{hours}h")
        if minutes > 0:
            parts.append(f"{minutes}m")

        # Seconds part is always displayed and formatted as xxx.fff
        # Using Decimal or precise calculation to avoid floating point errors, but simple handling should be sufficient here
        parts.append(f"{seconds_float:.3f}s")

        return "".join(parts)

    except Exception as e:
        logger.warning(f"Error formatting duration {duration_ms}ms: {e}")
        return None

def parse_duration_from_str(duration_str: Optional[str]) -> Optional[float]:
    """
    Parse human-readable string (Scheme 2: HhMmS.fffS) back to milliseconds (float).

    Args:
        duration_str (Optional[str]): Formatted duration string.

    Returns:
        Optional[float]: Duration in milliseconds, or None (if parsing fails).
    """
    if not duration_str or not isinstance(duration_str, str):
        return None

    total_milliseconds = 0.0
    pattern = re.compile(r"(?:(?P<hours>\d+)h)?(?:(?P<minutes>\d+)m)?(?:(?P<seconds>[\d.]+)s)?")
    match = pattern.fullmatch(duration_str)

    if not match:
        logger.warning(f"Unable to parse duration string format: {duration_str}")
        return None

    try:
        data = match.groupdict()
        if data["hours"]:
            total_milliseconds += float(data["hours"]) * 3600 * 1000
        if data["minutes"]:
            total_milliseconds += float(data["minutes"]) * 60 * 1000
        if data["seconds"]:
            total_milliseconds += float(data["seconds"]) * 1000

        return total_milliseconds
    except (ValueError, TypeError) as e:
        logger.warning(f"Value conversion error parsing duration string {duration_str}: {e}")
        return None
    except Exception as e:
        logger.error(f"Unknown error parsing duration string {duration_str}: {e}", exc_info=True)
        return None


# ==============================================================================
# Dataclass Definitions (referencing openai.types.chat)
# ==============================================================================

@dataclass
class FunctionCall:
    """
    Represents function call information requested by the model.
    Reference: openai.types.chat.ChatCompletionMessageToolCall.Function
    """
    name: str  # Name of the function to call
    arguments: str  # Function arguments, JSON-formatted string

    def to_dict(self) -> Dict[str, Any]:
        """Convert function call information to dictionary format"""
        return {
            "name": self.name,
            "arguments": self.arguments
        }


@dataclass
class ToolCall:
    """
    Represents a tool call request generated by the model.
    Reference: openai.types.chat.ChatCompletionMessageToolCall
    """
    id: str  # Unique identifier for the tool call
    type: Literal["function"] = "function"  # Tool type, currently only supports 'function'
    function: FunctionCall = None # Function call details

    def to_dict(self) -> Dict[str, Any]:
        """Convert tool call information to dictionary format"""
        return {
            "id": self.id,
            "type": self.type,
            "function": self.function.to_dict() if self.function else None
        }


@dataclass
class SystemMessage:
    """System message"""
    content: str # System message content, cannot be empty
    role: Literal["system"] = "system"
    created_at: str = field(default_factory=lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    show_in_ui: bool = True # <--- Renamed and set default value

    def to_dict(self) -> Dict[str, Any]:
        return {
            "id": str(uuid.uuid4()), # Runtime ID
            "timestamp": self.created_at,
            "role": self.role,
            "content": self.content,
            "show_in_ui": self.show_in_ui,
        }

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "SystemMessage":
        """Create system message object from dictionary"""
        return cls(
            content=data.get("content", " "), # Ensure content exists
            role=data.get("role", "system"),
            show_in_ui=data.get("show_in_ui", True),
            created_at=data.get("timestamp", datetime.now().isoformat()),
        )


@dataclass
class UserMessage:
    """User message"""
    content: str # User message content, cannot be empty
    role: Literal["user"] = "user"
    created_at: str = field(default_factory=lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    show_in_ui: bool = True # <--- Renamed and set default value

    def to_dict(self) -> Dict[str, Any]:
        return {
            "id": str(uuid.uuid4()), # Runtime ID
            "timestamp": self.created_at,
            "role": self.role,
            "content": self.content,
            "show_in_ui": self.show_in_ui,
        }

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "UserMessage":
        """Create user message object from dictionary"""
        return cls(
            content=data.get("content", " "), # Ensure content exists
            role=data.get("role", "user"),
            show_in_ui=data.get("show_in_ui", True),
            created_at=data.get("timestamp", datetime.now().isoformat()),
        )


@dataclass
class AssistantMessage:
    """Assistant message (model's response)"""
    content: Optional[str] = None # Assistant message content. Can be None or empty if and only if tool_calls exists.
    role: Literal["assistant"] = "assistant"
    tool_calls: Optional[List[ToolCall]] = None # List of tool calls requested by the model
    created_at: str = field(default_factory=lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    show_in_ui: bool = True # <--- Renamed and set default value (finish_task will be set to False on append)
    duration_ms: Optional[float] = None # Internally stored as milliseconds float
    # --- Use unified TokenUsage type ---
    token_usage: Optional[TokenUsage] = None
    # --- New compression-related fields ---
    compression_info: Optional[CompressionInfo] = None

    def to_dict(self) -> Dict[str, Any]:
        result = {
            "id": str(uuid.uuid4()),  # Runtime ID
            "timestamp": self.created_at,
            "role": self.role,
            "content": self.content,
            "show_in_ui": self.show_in_ui,
            "duration_ms": self.duration_ms,  # Note: removed on save; converted to duration string
        }

        # Handle token_usage
        if self.token_usage:
            result["token_usage"] = self.token_usage.to_dict()

        # Only include compression_info when present
        if self.compression_info:
            result["compression_info"] = self.compression_info.to_dict()

        if self.tool_calls:
            result["tool_calls"] = [tc.to_dict() for tc in self.tool_calls]

        # Drop top-level keys that are None (except content/tool_calls since assistant can have either)
        result = {k: v for k, v in result.items() if v is not None or k in ['content', 'tool_calls']}

        return result

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "AssistantMessage":
        msg = cls(
            content=data.get("content"), # Allow None
            role=data.get("role", "assistant"),
            show_in_ui=data.get("show_in_ui", True),
            duration_ms=data.get("duration_ms"),
            created_at=data.get("timestamp", datetime.now().isoformat()),
        )

        # --- Parse token_usage ---
        token_usage_data = data.get("token_usage")
        if token_usage_data and isinstance(token_usage_data, dict):
            try:
                # Directly use from_response method to handle, it will automatically adapt to various formats
                token_usage_obj = TokenUsage.from_response(token_usage_data)
                msg.token_usage = token_usage_obj
            except Exception as e:
                logger.warning(f"Failed to parse token_usage when loading history: {token_usage_data}, error: {e}")

        # --- Parse compression_info ---
        compression_info_data = data.get("compression_info")
        if compression_info_data and isinstance(compression_info_data, dict):
            try:
                compression_info_obj = CompressionInfo.from_dict(compression_info_data)
                # Only keep those with is_compressed as True
                if compression_info_obj and compression_info_obj.is_compressed:
                    msg.compression_info = compression_info_obj
                else:
                     logger.debug(f"Skipping empty or uncompressed compression_info when loading: {compression_info_data}")
            except Exception as e:
                logger.warning(f"Failed to parse compression_info when loading history: {compression_info_data}, error: {e}")

        # --- Parse tool_calls ---
        tool_calls_data = data.get("tool_calls")
        if tool_calls_data and isinstance(tool_calls_data, list):
            msg.tool_calls = []
            for tc_data in tool_calls_data:
                if isinstance(tc_data, dict):
                    try:
                        function_data = tc_data.get("function", {})
                        # Ensure arguments is a string
                        arguments_raw = function_data.get("arguments")
                        arguments_str = arguments_raw if isinstance(arguments_raw, str) else json.dumps(arguments_raw or {})

                        function_call = FunctionCall(
                            name=function_data.get("name", ""),
                            arguments=arguments_str
                        )
                        tool_call = ToolCall(
                            id=tc_data.get("id", str(uuid.uuid4())),
                            type=tc_data.get("type", "function"),
                            function=function_call
                        )
                        # Basic validation
                        if tool_call.id and tool_call.function and tool_call.function.name:
                            msg.tool_calls.append(tool_call)
                        else:
                            logger.warning(f"Skipping invalid tool_call structure when loading (missing id or function.name): {tc_data}")
                    except Exception as e:
                         logger.warning(f"Failed to parse tool_call when loading: {tc_data}, error: {e}")

        return msg


@dataclass
class ToolMessage:
    """Tool execution result message"""
    content: str # Tool execution result content, cannot be empty
    tool_call_id: str # Corresponding tool call ID
    role: Literal["tool"] = "tool"
    system: Optional[str] = None # Internal system flag, for example marking interruptions
    created_at: str = field(default_factory=lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    show_in_ui: bool = True # <--- Renamed and set default value (interrupt prompts will be set to False on append)
    duration_ms: Optional[float] = None # Internally stored as milliseconds float

    def to_dict(self) -> Dict[str, Any]:
        result = {
            "id": str(uuid.uuid4()), # Runtime ID
            "timestamp": self.created_at,
            "role": self.role,
            "content": self.content,
            "tool_call_id": self.tool_call_id,
            "system": self.system,
            "show_in_ui": self.show_in_ui,
            "duration_ms": self.duration_ms, # Note: this field will be removed on save
        }
        # Clean top-level keys with None values (system, duration_ms may be None)
        return {k: v for k, v in result.items() if v is not None}

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "ToolMessage":
        """Create tool message object from dictionary"""
        return cls(
            content=data.get("content", " "), # Ensure content exists
            tool_call_id=data.get("tool_call_id", ""), # ID cannot be empty, subsequent validate will check
            role=data.get("role", "tool"),
            system=data.get("system"), # Can be None
            show_in_ui=data.get("show_in_ui", True),
            duration_ms=data.get("duration_ms"), # Can be None
            created_at=data.get("timestamp", datetime.now().isoformat()),
        )


# Union type for all possible message types
ChatMessage = Union[SystemMessage, UserMessage, AssistantMessage, ToolMessage]
