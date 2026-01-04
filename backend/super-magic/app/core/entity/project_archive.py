import time

from pydantic import BaseModel, Field


class ProjectArchiveInfo(BaseModel):
    """Project archive information model"""
    file_key: str  # File storage key
    file_size: int  # File size (bytes)
    file_md5: str  # File MD5 hash
    upload_timestamp: int = Field(default_factory=lambda: int(time.time()))  # Upload timestamp 
    version: int = 0
