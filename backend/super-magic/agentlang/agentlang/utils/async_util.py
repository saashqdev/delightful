"""
Async utility functions for async operation helpers
"""

import asyncio
import inspect
from functools import wraps
from typing import Any, Callable, Coroutine, TypeVar

T = TypeVar("T")


def run_async(func: Callable[..., Coroutine[Any, Any, T]]) -> Callable[..., T]:
    """
    Decorator to wrap an async function as a sync function

    Args:
        func: Async function to wrap

    Returns:
        Sync function wrapper
    """

    @wraps(func)
    def wrapper(*args: Any, **kwargs: Any) -> T:
        """Sync function wrapper"""
        # Get current event loop or create a new one
        loop = asyncio.get_event_loop()

        # If loop is closed, create a new one
        if loop.is_closed():
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)

        # Run async function in the loop
        return loop.run_until_complete(func(*args, **kwargs))

    return wrapper


async def run_in_executor(func: Callable[..., T], *args: Any, **kwargs: Any) -> T:
    """
    Run a blocking function in a thread pool

    Args:
        func: Blocking function to run
        args: Positional arguments
        kwargs: Keyword arguments

    Returns:
        Function result
    """
    loop = asyncio.get_event_loop()

    # Wrap function in a single call
    def wrapped_func():
        return func(*args, **kwargs)

    # Run blocking function in thread pool
    return await loop.run_in_executor(None, wrapped_func)


def is_async_function(func: Callable) -> bool:
    """
    Check if a function is asynchronous

    Args:
        func: Function to check

    Returns:
        Whether it's an async function
    """
    return inspect.iscoroutinefunction(func)


async def gather_with_concurrency(n: int, *tasks):
    """
    Gather function with concurrency limit

    Args:
        n: Max concurrent tasks
        tasks: Task list

    Returns:
        Task result list
    """
    semaphore = asyncio.Semaphore(n)

    async def sem_task(task):
        async with semaphore:
            return await task

    return await asyncio.gather(*(sem_task(task) for task in tasks)) 
