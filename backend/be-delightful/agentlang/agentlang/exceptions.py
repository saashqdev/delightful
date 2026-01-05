"""
Exception definitions module.

Contains all exception classes used by the agentlang package, providing a unified exception interface and user-friendly messaging mechanism.
"""

from abc import ABC, abstractmethod
from typing import Any, Dict


class UserFriendlyException(BaseException, ABC):
    """Interface for user-friendly exceptions.

    Defines an interface for exceptions that can present friendly error messages to users.
    Subclasses should implement ``get_user_friendly_message`` to provide text suitable for end users.
    """

    def __init__(self, message: str = "", **kwargs: Any):
        """Initialize the exception.

        Args:
            message: Exception message
            **kwargs: Additional exception metadata
        """
        self.message = message
        self.extra_data: Dict[str, Any] = kwargs
        super().__init__(message)

    @abstractmethod
    def get_user_friendly_message(self) -> str:
        """Return a user-friendly error message.

        Should provide a formatted, easy-to-understand string suitable for display to end users without technical background.

        Returns:
            str: Formatted user-facing message
        """
        pass 
