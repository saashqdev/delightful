"""
Tool class for parallel execution of async tasks, providing a Golang-like concurrency model

Usage example:
    # Create Parallel instance
    parallel = Parallel()
    
    # Add async tasks
    parallel.add(async_function1, arg1, arg2, kwarg1=value1)
    parallel.add(async_function2)
    
    # Run all tasks and wait for results
    results = await parallel.run()
    
    # Or execute and wait separately
    parallel.start()
    # Do other things...
    results = await parallel.wait()
"""

import asyncio
from typing import Any, Awaitable, Callable, Dict, List, Optional, Tuple, TypeVar, Union

T = TypeVar('T')
TaskFunc = Callable[..., Awaitable[T]]
TaskItem = Tuple[TaskFunc, Tuple[Any, ...], Dict[str, Any]]
TaskResult = Tuple[int, Union[T, Exception]]

class Parallel:
    """
    Tool class for parallel execution of async tasks, providing a Golang-like concurrency model
    
    Supports adding multiple async tasks, executing them concurrently, then waiting for all tasks to complete and getting results
    """

    def __init__(self, timeout: Optional[float] = None):
        """
        Initialize parallel executor
        
        Args:
            timeout: Global task timeout in seconds, None means no timeout
        """
        self._tasks: List[TaskItem] = []
        self._running_tasks: List[asyncio.Task] = []
        self._timeout = timeout
        self._started = False

    def add(self, func: TaskFunc, *args: Any, **kwargs: Any) -> 'Parallel':
        """
        Add an async task
        
        Args:
            func: Async function
            args: Positional arguments
            kwargs: Keyword arguments
            
        Returns:
            self, supports method chaining
        
        Raises:
            RuntimeError: If trying to add tasks after starting
        """
        if self._started:
            raise RuntimeError("Cannot add tasks after starting")

        self._tasks.append((func, args, kwargs))
        return self

    async def _execute_task(self, index: int, func: TaskFunc, args: Tuple[Any, ...], 
                            kwargs: Dict[str, Any]) -> TaskResult:
        """
        Execute single task and capture exceptions
        
        Args:
            index: Task index
            func: Async function
            args: Positional arguments
            kwargs: Keyword arguments
            
        Returns:
            Tuple of (task index, result or exception)
        """
        try:
            result = await func(*args, **kwargs)
            return index, result
        except Exception as e:
            return index, e

    def start(self) -> None:
        """
        Start execution of all tasks without waiting for results
        
        Raises:
            RuntimeError: If tasks already started
        """
        if self._started:
            raise RuntimeError("Tasks already started")

        self._started = True

        for i, (func, args, kwargs) in enumerate(self._tasks):
            task = asyncio.create_task(self._execute_task(i, func, args, kwargs))
            self._running_tasks.append(task)

    async def wait(self) -> List[Union[Any, Exception]]:
        """
        Wait for all tasks to complete and return results
        
        If tasks haven't started, will automatically start them
        
        Returns:
            List of results in same order as tasks were added, exception objects at position if task failed
            
        Raises:
            asyncio.TimeoutError: If timeout is set and task execution exceeds it
        """
        if not self._started:
            self.start()

        results_with_index: List[TaskResult] = []

        try:
            # Wait for all tasks to complete or timeout
            done, pending = await asyncio.wait(
                self._running_tasks, 
                timeout=self._timeout,
                return_when=asyncio.ALL_COMPLETED
            )

            # If there are uncompleted tasks, timeout occurred
            if pending:
                # Cancel all uncompleted tasks
                for task in pending:
                    task.cancel()

                # Wait for cancellation to complete
                await asyncio.gather(*pending, return_exceptions=True)
                raise asyncio.TimeoutError(f"{len(pending)} tasks did not complete within the timeout")

            # Collect results
            for task in done:
                results_with_index.append(task.result())

        finally:
            # Reset state to allow running again
            self._started = False
            self._running_tasks = []

        # Sort results by original order
        results_with_index.sort(key=lambda x: x[0])
        return [result for _, result in results_with_index]

    async def run(self) -> List[Union[Any, Exception]]:
        """
        Start all tasks and wait for them to complete, returning results
        
        This is a convenience method combining start() and wait()
        
        Returns:
            List of results in same order as tasks were added, exception objects at position if task failed
            
        Raises:
            asyncio.TimeoutError: If timeout is set and task execution exceeds it
        """
        self.start()
        return await self.wait()

    @staticmethod
    async def execute(funcs: List[TaskFunc], *args_list: List[Any], 
                     timeout: Optional[float] = None, **kwargs_list: Dict[str, Any]) -> List[Any]:
        """
        Static convenience method to execute multiple async functions with same parameters
        
        Args:
            funcs: List of async functions
            args_list: List of positional argument lists for each function
            timeout: Timeout in seconds
            kwargs_list: List of keyword argument dictionaries for each function
            
        Returns:
            List of results
            
        Raises:
            asyncio.TimeoutError: If timeout is set and task execution exceeds it
        """
        parallel = Parallel(timeout=timeout)

        # If multiple argument sets are provided
        if args_list and isinstance(args_list[0], list):
            # Check if number of arguments matches
            if len(args_list) != len(funcs):
                raise ValueError("Number of argument lists must match number of functions")

            for i, func in enumerate(funcs):
                parallel.add(func, *args_list[i])
        else:
            # Use same arguments for all functions
            common_args = args_list
            for func in funcs:
                parallel.add(func, *common_args)

        return await parallel.run() 
