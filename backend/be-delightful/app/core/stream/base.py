"""
Stream interface base class

Define unified interface for stream operations for reading and writing data
"""

from abc import ABC, abstractmethod
from typing import List, Optional

from agentlang.event.event import EventType


class Stream(ABC):
    """Stream interface for reading and writing data.
    
    This abstract base class defines the interface for stream operations,
    providing a consistent API for both reading and writing operations.
    
    Methods:
        read: Read data from the stream
        write: Write data to the stream
    """

    def __init__(self):
        """Initialize Stream base class
        
        Initialize event filter list
        """
        # Process all events by default
        self._ignored_events: List[EventType] = []

    def ignore_events(self, event_types: List[EventType]) -> None:
        """Configure event types that this stream should ignore
        
        Args:
            event_types: List of event types to ignore
        """
        self._ignored_events.extend(event_types)

    def should_ignore_event(self, event_type: EventType) -> bool:
        """Check if this stream should handle a specific event type
        
        Args:
            event_type: Event type to check
            
        Returns:
            bool: Returns True if should handle, False otherwise
        """
        return event_type in self._ignored_events

    @abstractmethod
    def read(self, size: Optional[int] = None) -> str:
        """Read data from the stream.
        
        Args:
            size: Optional number of bytes/items to read. If None, reads all available data.
            
        Returns:
            The string data read from the stream.
            
        Raises:
            EOFError: When end of stream is reached.
            IOError: When stream read operation fails.
        """
        pass

    @abstractmethod
    def write(self, data: str, data_type: str = "json") -> int:
        """Write data to the stream.
        
        Args:
            data: The string data to write to the stream.
            data_type: The type of data to write to the stream.
        Returns:
            The number of bytes/items written.
            
        Raises:
            IOError: When stream write operation fails.
        """
        pass 
