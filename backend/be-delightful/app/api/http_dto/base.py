from typing import Any, Optional

from pydantic import BaseModel


class MessageResponse(BaseModel):
    """Message response model."""

    success: bool
    message: str
    data: Optional[Any] = None
