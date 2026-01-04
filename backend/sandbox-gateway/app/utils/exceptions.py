"""
Exception handling utilities
"""
import functools
import logging
from typing import Any, Callable, TypeVar, cast

from fastapi import HTTPException

logger = logging.getLogger("sandbox_gateway")

T = TypeVar("T")


class SandboxException(Exception):
    """Base exception class for sandbox service"""
    def __init__(self, message: str, status_code: int = 500):
        self.message = message
        self.status_code = status_code
        super().__init__(self.message)


class SandboxNotFoundError(SandboxException):
    """Sandbox not found exception"""
    def __init__(self, sandbox_id: str):
        message = f"Sandbox {sandbox_id} does not exist or has expired"
        super().__init__(message, status_code=404)


class ContainerOperationError(SandboxException):
    """Container operation exception"""
    def __init__(self, message: str):
        super().__init__(f"Container operation failed: {message}", status_code=500)


def handle_exceptions(func: Callable[..., T]) -> Callable[..., T]:
    """
    Exception handling decorator that converts internal exceptions to HTTP exceptions
    
    Args:
        func: Function that needs exception handling
        
    Returns:
        Decorated function
    """
    @functools.wraps(func)
    def wrapper(*args: Any, **kwargs: Any) -> T:
        try:
            return func(*args, **kwargs)
        except SandboxException as e:
            logger.error(f"{func.__name__} failed: {e.message}")
            raise HTTPException(status_code=e.status_code, detail=e.message)
        except Exception as e:
            error_message = f"{func.__name__} encountered unknown error: {str(e)}"
            logger.exception(error_message)
            raise HTTPException(status_code=500, detail="Internal service error")
    return cast(T, wrapper)


def async_handle_exceptions(func: Callable[..., T]) -> Callable[..., T]:
    """
    Exception handling decorator for async functions
    
    Args:
        func: Async function that needs exception handling
        
    Returns:
        Decorated async function
    """
    @functools.wraps(func)
    async def wrapper(*args: Any, **kwargs: Any) -> T:
        try:
            return await func(*args, **kwargs)
        except SandboxException as e:
            logger.error(f"{func.__name__} failed: {e.message}")
            raise HTTPException(status_code=e.status_code, detail=e.message)
        except Exception as e:
            error_message = f"{func.__name__} encountered unknown error: {str(e)}"
            logger.exception(error_message)
            raise HTTPException(status_code=500, detail="Internal service error")
    return cast(T, wrapper) 