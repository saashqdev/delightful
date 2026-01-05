"""Retry utility functions, provides error message parsing and retry mechanism."""

import asyncio
import random
import re
from typing import Any, Callable, Optional, TypeVar

from agentlang.logger import get_logger

logger = get_logger(__name__)

T = TypeVar("T")


def extract_retry_delay_from_error(error_message: str) -> Optional[int]:
    """Extract retry delay time (seconds) from error message.
    
    Args:
        error_message: Error message string

    Returns:
        int | None: Number of seconds to wait, or None if not found
    """
    # Try to match common retry time patterns, e.g., "retry after 16 seconds" or "Please retry after 16 seconds"
    # Azure OpenAI error message format: "Please retry after 16 seconds."
    pattern = r"retry after (\d+) seconds"
    match = re.search(pattern, error_message, re.IGNORECASE)

    if match:
        return int(match.group(1))

    return None


async def retry_with_exponential_backoff(
    func: Callable[..., Any], 
    *args: Any, 
    max_retries: int = 5,
    initial_delay: float = 1.0,
    exponential_base: float = 2.0,
    jitter: bool = True,
    **kwargs: Any
) -> Any:
    """Retry function using exponential backoff strategy.
    
    Args:
        func: Function to retry
        args: Positional arguments for the function
        max_retries: Maximum number of retries, default is 5
        initial_delay: Initial delay time (seconds), default is 1.0
        exponential_base: Exponential base, default is 2.0
        jitter: Whether to add random jitter, default is True
        kwargs: Keyword arguments for the function
        
    Returns:
        Any: Return value of the function
        
    Raises:
        Exception: If all retries fail, raise the last exception
    """
    delay = initial_delay
    last_exception = None

    # Try to execute function, retry up to max_retries times
    for attempt in range(max_retries + 1):
        try:
            return await func(*args, **kwargs)
        except Exception as e:
            last_exception = e

            if attempt == max_retries:
                logger.error(f"Reached maximum retry count {max_retries}, giving up")
                raise

            # Extract retry delay time from error message
            error_message = str(e)
            retry_delay = extract_retry_delay_from_error(error_message)

            # If found explicit retry time, use it
            if retry_delay is not None:
                delay = retry_delay
                logger.info(f"Extracted retry delay from error message: {delay} seconds")
            else:
                # Otherwise use exponential backoff strategy
                delay = initial_delay * (exponential_base ** attempt)

            # Add random jitter to avoid multiple requests retrying simultaneously
            if jitter:
                delay = delay * (0.5 + random.random())

            logger.warning(f"Attempt {attempt+1} failed: {error_message}, will retry after {delay:.2f} seconds")

            # Wait for delay time
            await asyncio.sleep(delay)

    # This should not be reached, as the last attempt failure will raise in the loop
    if last_exception:
        raise last_exception

    raise RuntimeError("All retries failed but no exception was captured, this should not happen") 
