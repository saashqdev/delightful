"""
Agent idle monitoring service

Monitors activity status of all Agents, automatically exits service when all Agents exceed specified time without executing any tasks
"""
import os
import signal
import threading
import time

from agentlang.environment import Environment
from agentlang.logger import get_logger

logger = get_logger(__name__)

class IdleMonitorService:
    """
    Idle monitoring service, responsible for monitoring Agent activity status and exiting program after long inactivity
    """
    _instance = None

    @classmethod
    def get_instance(cls):
        """Get IdleMonitorService singleton instance"""
        if cls._instance is None:
            cls._instance = IdleMonitorService()
        return cls._instance

    def __init__(self):
        """Initialize idle monitoring service"""
        if IdleMonitorService._instance is not None:
            return

        self._monitor_thread = None
        self._running = False

        self._check_interval = Environment.get_idle_monitor_interval()

    def start(self):
        """Start monitoring service"""
        if self._running:
            logger.warning("Monitoring service is already running")
            return

        self._running = True
        self._monitor_thread = threading.Thread(target=self._monitor_task, daemon=True)
        self._monitor_thread.start()

        idle_timeout_seconds = Environment.get_agent_idle_timeout()
        logger.info(f"Idle monitoring service started, timeout: {idle_timeout_seconds} seconds, check interval: {self._check_interval} seconds")

    def _monitor_task(self):
        """Monitor task main loop"""
        from app.service.agent_dispatcher import AgentDispatcher

        while self._running:
            try:
                logger.info("Performing Agent idle status check...")
                dispatcher = AgentDispatcher.get_instance()
                if dispatcher.agent_context.is_idle_timeout():
                    idle_timeout_seconds = Environment.get_agent_idle_timeout()
                    logger.info(f"Agent has not executed any task for more than {idle_timeout_seconds} seconds, sending exit signal")
                    os.kill(os.getpid(), signal.SIGTERM)

                time.sleep(self._check_interval)
            except Exception as e:
                logger.error(f"Monitor task exception: {e}")
                time.sleep(self._check_interval)

    def stop(self):
        """Stop monitoring service"""
        self._running = False
        if self._monitor_thread and self._monitor_thread.is_alive():
            logger.info("Stopping idle monitoring service")
