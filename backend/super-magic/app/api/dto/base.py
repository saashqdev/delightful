"""
Define base DTO classes and common structures
"""


from pydantic import BaseModel, root_validator

from app.core.entity.message.client_message import ClientMessage


class BaseDTO(BaseModel):
    """Base data transfer object"""
    class Config:
        arbitrary_types_allowed = True


class WebSocketMessage(ClientMessage):
    """WebSocket message model"""

    @root_validator(pre=True)
    def validate_id_and_type(cls, values):
        if "message_id" not in values:
            raise ValueError("Message must contain 'message_id' field")

        if "type" not in values:
            raise ValueError("Message must contain 'type' field")

        return values
