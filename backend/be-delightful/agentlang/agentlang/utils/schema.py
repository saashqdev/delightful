from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field


class BaseFileSystemInfo(BaseModel):
    """Basic information for filesystem items."""

    name: str = Field(description="File or directory name")
    path: str = Field(description="Path relative to workspace")
    is_dir: bool = Field(description="Whether it is a directory")
    last_modified: float = Field(description="Last modification timestamp")

    def format_time(self) -> str:
        """Format last modification time."""
        dt = datetime.fromtimestamp(self.last_modified)
        return dt.strftime("%b %d, %I:%M %p")


class FileInfo(BaseFileSystemInfo):
    """File information."""

    size: int = Field(description="File size (bytes)")
    line_count: Optional[int] = Field(None, description="Number of lines in file (text files only)")


class DirectoryInfo(BaseFileSystemInfo):
    """Directory information."""

    item_count: str = Field(description="Number of items in directory") 
