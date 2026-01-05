"""
Common classes for the event system.

Provides base event data models and type definitions to break circular dependencies.
"""

from pydantic import BaseModel, ConfigDict


class BaseEventData(BaseModel):
    """Base class for event data models; all event data should inherit this."""

    model_config = ConfigDict(arbitrary_types_allowed=True) 
