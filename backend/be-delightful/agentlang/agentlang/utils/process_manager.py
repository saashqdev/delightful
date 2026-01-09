"""Subprocess management module, provides independent subprocess management.

This module implements a reliable subprocess manager for managing long-running
background processes in FastAPI applications.
Main features include:
1. Create and manage independent subprocesses
2. Gracefully handle process termination and resource cleanup
3. Integrate with FastAPI lifecycle events
4. Provide async interface compatible with FastAPI's async model
"""

import asyncio
import logging
import multiprocessing as mp
import os
import signal
import subprocess
import time
from typing import Any, Callable, Dict, List, Optional

from agentlang.logger import get_logger

logger = get_logger(__name__)

class WorkerProcess:
    """Worker process wrapper class for managing a single subprocess."""

    def __init__(self, worker_function: Callable, *args: Any, **kwargs: Any):
        """Initialize worker process.

        Args:
            worker_function: Function to run in the subprocess
            args: Positional arguments for the worker function
            kwargs: Keyword arguments for the worker function
        """
        self.worker_function = worker_function
        self.args = args
        self.kwargs = kwargs
        self.process: Optional[mp.Process] = None
        self.start_time: Optional[float] = None

    def start(self) -> int:
        """Start the worker process.

        Returns:
            int: Process ID
        """
        self.process = mp.Process(
            target=self.worker_function,
            args=self.args,
            kwargs=self.kwargs,
            daemon=True  # Set as daemon process, auto-terminates when main process ends
        )
        self.process.start()
        self.start_time = time.time()
        logger.info(f"Worker process started: PID={self.process.pid}")
        return self.process.pid

    def terminate(self, timeout: int = 5) -> bool:
        """Terminate the worker process, try graceful termination first, force if failed.

        Args:
            timeout: Timeout in seconds to wait for process termination

        Returns:
            bool: Whether termination was successful
        """
        if not self.process or not self.process.is_alive():
            logger.warning("Attempting to terminate a non-existent or already terminated process")
            return True

        logger.info(f"Terminating worker process PID={self.process.pid}")

        # Attempt normal termination
        self.process.terminate()
        self.process.join(timeout=timeout)

        # If process is still running, force termination
        if self.process.is_alive():
            logger.warning(f"Worker process (PID={self.process.pid}) failed to terminate normally within {timeout} seconds, forcing termination")
            try:
                os.kill(self.process.pid, signal.SIGKILL)
                self.process.join(timeout=1)  # Wait a bit longer
            except Exception as e:
                logger.error(f"Error during forced termination of worker process: {e}")
                return False

        logger.info(f"Worker process (PID={self.process.pid}) terminated")
        return True

    def is_alive(self) -> bool:
        """Check if the process is still running.

        Returns:
            bool: Whether the process is alive
        """
        return self.process is not None and self.process.is_alive()

    def get_info(self) -> Dict[str, Any]:
        """Get process information.

        Returns:
            Dict: Dictionary containing process information
        """
        if not self.process:
            return {"status": "not_started"}

        return {
            "pid": self.process.pid,
            "status": "running" if self.process.is_alive() else "terminated",
            "exit_code": self.process.exitcode if not self.process.is_alive() else None,
            "uptime": time.time() - self.start_time if self.start_time else None
        }


class ProcessManager:
    """Process manager for managing multiple worker processes."""

    _instance = None  # Singleton instance

    @staticmethod
    def run_command(cmd, cwd=None, **kwargs):
        """Generic command-line execution function.

        Args:
            cmd: Command-line argument list
            cwd: Working directory
            **kwargs: Additional arguments passed to subprocess.Popen
        """
        try:
            # Set up subprocess logging
            logger = logging.getLogger("cmd_runner")
            logger.info(f"Command-line subprocess started: {' '.join(cmd if isinstance(cmd, list) else [cmd])}")

            # Start process
            process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                text=True,
                bufsize=1,
                cwd=cwd,
                **kwargs
            )

            # Read and log output
            for line in iter(process.stdout.readline, ''):
                if line:
                    logger.info(f"Command output: {line.rstrip()}")

            process.stdout.close()
            return_code = process.wait()
            logger.info(f"Command-line process exited with return code: {return_code}")

        except Exception as e:
            logger.error(f"Command-line subprocess exception: {e}")
            import traceback
            logger.error(traceback.format_exc())

    @classmethod
    def get_instance(cls) -> 'ProcessManager':
        """Get the singleton instance of ProcessManager.

        Returns:
            ProcessManager: Singleton instance
        """
        if cls._instance is None:
            cls._instance = ProcessManager()
        return cls._instance

    def __init__(self):
        """Initialize the process manager."""
        self.workers: Dict[str, WorkerProcess] = {}
        self._shutdown_event = asyncio.Event()
        self._monitor_task = None
        logger.info("Process manager initialized")

    async def start_worker(self, name: str, worker_function: Callable, *args: Any, **kwargs: Any) -> int:
        """Start a new worker process asynchronously.

        Args:
            name: Unique name for the process
            worker_function: Function to run in the subprocess
            args: Positional arguments for the worker function
            kwargs: Keyword arguments for the worker function

        Returns:
            int: Process ID
        """
        # If a process with the same name exists, stop it first
        if name in self.workers:
            await self.stop_worker(name)

        # Create and start new process
        loop = asyncio.get_event_loop()
        worker = WorkerProcess(worker_function, *args, **kwargs)

        # Run start method in thread pool to avoid blocking event loop
        pid = await loop.run_in_executor(None, worker.start)

        # Record the process
        self.workers[name] = worker
        logger.info(f"Worker process '{name}' started, PID={pid}")

        # If monitoring task is not running, start it
        if self._monitor_task is None or self._monitor_task.done():
            self._monitor_task = asyncio.create_task(self._monitor_workers())

        return pid

    async def start_worker_with_cmd(self, name: str, cmd: List[str], cwd: str = None, **kwargs: Any) -> int:
        """Start a new command-line worker process asynchronously.

        Args:
            name: Unique name for the process
            cmd: Command-line list to execute
            cwd: Working directory
            kwargs: Additional arguments passed to subprocess.Popen

        Returns:
            int: Process ID
        """
        # If a process with the same name exists, stop it first
        if name in self.workers:
            await self.stop_worker(name)

        # Create and start new process
        loop = asyncio.get_event_loop()
        worker = WorkerProcess(self.run_command, cmd, cwd, **kwargs)

        # Run start method in thread pool to avoid blocking event loop
        pid = await loop.run_in_executor(None, worker.start)

        # Record the process
        self.workers[name] = worker
        logger.info(f"Command-line worker process '{name}' started, PID={pid}")

        # If monitoring task is not running, start it
        if self._monitor_task is None or self._monitor_task.done():
            self._monitor_task = asyncio.create_task(self._monitor_workers())

        return pid

    async def stop_worker(self, name: str, timeout: int = 5) -> bool:
        """Stop a worker process asynchronously.

        Args:
            name: Process name
            timeout: Timeout in seconds to wait for process termination

        Returns:
            bool: Whether the operation was successful
        """
        if name not in self.workers:
            logger.warning(f"Attempting to stop non-existent worker process: {name}")
            return False

        worker = self.workers[name]
        loop = asyncio.get_event_loop()

        # Run terminate method in thread pool
        success = await loop.run_in_executor(None, lambda: worker.terminate(timeout))

        if success:
            # Remove from dictionary
            del self.workers[name]
            logger.info(f"Worker process '{name}' stopped and removed from manager")

        return success

    async def stop_all(self, timeout: int = 5) -> Dict[str, bool]:
        """Stop all worker processes asynchronously.

        Args:
            timeout: Timeout in seconds to wait for each process termination

        Returns:
            Dict[str, bool]: Mapping of each process name to whether it stopped successfully
        """
        logger.info("Stopping all worker processes...")

        # Set shutdown event, stop monitoring task
        self._shutdown_event.set()
        if self._monitor_task and not self._monitor_task.done():
            try:
                self._monitor_task.cancel()
                await asyncio.sleep(0.1)  # Give task some time to cancel
            except asyncio.CancelledError:
                pass

        results = {}
        worker_names = list(self.workers.keys())

        # Stop all processes concurrently
        tasks = [self.stop_worker(name, timeout) for name in worker_names]
        if tasks:
            results_list = await asyncio.gather(*tasks, return_exceptions=True)
            results = {name: result for name, result in zip(worker_names, results_list) if not isinstance(result, Exception)}

            # Handle exceptions
            for name, result in zip(worker_names, results_list):
                if isinstance(result, Exception):
                    logger.error(f"Error stopping worker process '{name}': {result}")
                    results[name] = False

        logger.info(f"All worker processes stop operation completed, results: {results}")
        return results

    async def get_worker_info(self, name: str = None) -> Dict[str, Any]:
        """Get information for a specific worker process or all processes.

        Args:
            name: Process name, if None returns information for all processes

        Returns:
            Dict: Dictionary containing process information
        """
        if name:
            if name in self.workers:
                return {name: self.workers[name].get_info()}
            return {}

        return {name: worker.get_info() for name, worker in self.workers.items()}

    async def _monitor_workers(self) -> None:
        """Background task for monitoring worker process status.

        Automatically detects exited processes and removes them from the manager.
        """
        logger.info("Process monitoring task started")
        try:
            while not self._shutdown_event.is_set():
                for name in list(self.workers.keys()):
                    worker = self.workers.get(name)
                    if worker and not worker.is_alive():
                        info = worker.get_info()
                        logger.info(f"Detected worker process '{name}' has exited, exit code: {info.get('exit_code')}")
                        # Remove exited process from dictionary
                        del self.workers[name]

                # Check every second
                try:
                    await asyncio.wait_for(self._shutdown_event.wait(), timeout=1)
                except asyncio.TimeoutError:
                    pass  # Expected timeout, continue loop

        except asyncio.CancelledError:
            logger.info("Process monitoring task cancelled")
        except Exception as e:
            logger.error(f"Process monitoring task error: {e}", exc_info=True)
        finally:
            logger.info("Process monitoring task ended")
