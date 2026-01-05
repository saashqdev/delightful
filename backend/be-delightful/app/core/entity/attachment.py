from enum import Enum
from typing import Optional

from pydantic import BaseModel, Field


class AttachmentTag(str, Enum):
    """Attachment type enumeration"""

    USER_UPLOAD = "user_upload"  # User upload
    PROCESS = "process"  # Intermediate artifact
    BROWSER = "browser"  # Browser screenshot
    FINAL = "final"  # Final artifact


class Attachment(BaseModel):
    """Attachment model
    
    Represents various attachments passed through the system, including files, images and other resources.
    This model can be shared by event system, message system and other modules.
    """

    file_key: str  # Object storage object_key
    file_tag: AttachmentTag  # Attachment tag
    file_extension: str  # File extension
    filename: str  # File name
    display_filename: str  # Display file name
    file_size: int = Field(description="File size in bytes")
    file_url: Optional[str] = None  # File access URL, optional
    timestamp: int = Field(description="Timestamp")
