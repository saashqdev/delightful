import asyncio
import os
import json
from typing import Optional
import importlib
import importlib.metadata
import inspect

from app.core.context.agent_context import AgentContext
from agentlang.event.data import ErrorEventData
from agentlang.event.event import EventType
from app.core.stream.http_subscription_stream import HTTPSubscriptionStream
from app.core.stream.stdout_stream import StdoutStream
from agentlang.config.config import config
from app.delightful.agent import Agent
from app.service.agent_service import AgentService
from app.service.agent_event.file_storage_listener_service import FileStorageListenerService
from app.service.agent_event.finish_task_listener_service import FinishTaskListenerService
from app.service.agent_event.rag_listener_service import RagListenerService
from app.service.agent_event.stream_listener_service import StreamListenerService
from app.service.agent_event.todo_listener_service import TodoListenerService
from app.service.agent_event.file_listener_service import FileListenerService
from app.paths import PathManager
from app.core.entity.message.client_message import InitClientMessage, TaskMode, ChatClientMessage
from agentlang.logger import get_logger

logger = get_logger(__name__)

class AgentDispatcher:
    """
    Agent Dispatcher, responsible for Agent creation, initialization and execution

    Main responsibilities:
    1. Create and initialize Agent and its context
    2. Register Agent event listeners
    3. Handle workspace initialization
    4. Run Agent to process tasks
    """

    # Singleton instance
    _instance = None

    @classmethod
    def get_instance(cls):
        """Get AgentDispatcher singleton instance"""
        if cls._instance is None:
            cls._instance = AgentDispatcher()
        return cls._instance

    def __init__(self):
        """Initialize Agent dispatcher"""
        if self.__class__._instance is not None:
            return

        self.agent_context: Optional[AgentContext] = None
        self.http_stream: Optional[HTTPSubscriptionStream] = None
        self.is_workspace_initialized: bool = False  # Workspace initialization status flag
        self.agent_service = AgentService()  # Create AgentService instance
        self.agents = {}  # Store different types of agents

        # Set as singleton instance
        self.__class__._instance = self

    async def setup(self):
        """Set up Agent context and register listeners"""
        self.agent_context = self.agent_service.create_agent_context(
            stream_mode=False,
            task_id="",
            streams=[StdoutStream()],
            is_main_agent=True,
            sandbox_id=str(config.get("sandbox.id"))
        )

        self.agent_context.update_activity_time()

        # Register various listeners
        FileStorageListenerService.register_standard_listeners(self.agent_context)
        TodoListenerService.register_standard_listeners(self.agent_context)
        FinishTaskListenerService.register_standard_listeners(self.agent_context)
        StreamListenerService.register_standard_listeners(self.agent_context)
        RagListenerService.register_standard_listeners(self.agent_context)
        FileListenerService.register_standard_listeners(self.agent_context)

        # Get registered listeners from entry points, group=superdelightful.listeners.register
        group = 'superdelightful.agent_dispatcher.listeners.register'
        listeners_entry_points = list(importlib.metadata.entry_points(group=group))
        for entry_point in listeners_entry_points:
            try:
                logger.info(f"Found agent_dispatcher listener: {entry_point.name}")
                module_name = entry_point.value.split(':')[0]
                method_name = entry_point.value.split(':')[1]
                module = importlib.import_module(module_name)
                
                found_method = False
                for name, obj in inspect.getmembers(module):
                    if inspect.isclass(obj) and hasattr(obj, method_name):
                        class_method = getattr(obj, method_name)
                        # Call class static method
                        class_method(self.agent_context)
                        found_method = True
                        logger.info(f"Registered agent_dispatcher listener: {entry_point.name}")
                        break
                
                if not found_method:
                    logger.warning(f"Static method {method_name} not found in module {module_name}, skipping")
            except Exception as e:
                logger.error(f"Error registering listener {entry_point.name}: {e!s}")
                # Continue processing other listeners, don't interrupt flow

        logger.info("AgentDispatcher initialization completed")
        return self

    async def load_init_client_message(self) -> bool:
        """
        Load initialization client message to agent_context

        Returns:
            bool: Whether successfully loaded and initialized
        """
        if self.agent_context.get_init_client_message() is not None:
            logger.info("agent_context already has client initialization message, skipping file load")
            return True

        try:
            init_client_message_file = PathManager.get_init_client_message_file()
            if os.path.exists(init_client_message_file):
                with open(init_client_message_file, 'r', encoding='utf-8') as f:
                    init_message_data = json.load(f)
                    init_message = InitClientMessage(**init_message_data)
                    await self.initialize_workspace(init_message)
                    logger.info(f"Loaded client initialization message from {init_client_message_file}")
                    return True
            else:
                logger.error(f"Client initialization message file {init_client_message_file} does not exist")
                return False
        except Exception as e:
            logger.error(f"Error loading client initialization message: {e}")
            return False

    async def initialize_workspace(self, init_message):
        """Initialize workspace"""
        if self.is_workspace_initialized:
            logger.info("Workspace already initialized, skipping initialization process")
            return

        self.agent_context.set_init_client_message(init_message)

        if init_message.message_subscription_config and not self.http_stream:
            self.http_stream = HTTPSubscriptionStream(init_message.message_subscription_config)
            self.agent_context.add_stream(self.http_stream)
            logger.info("Created and added HTTP subscription stream")

        # Extract and set key fields from init_message.metadata
        if init_message.metadata:
            # Set task_id
            if "be_delightful_task_id" in init_message.metadata:
                self.agent_context.set_task_id(init_message.metadata["be_delightful_task_id"])
                logger.info(f"Set task ID from init_message.metadata: {init_message.metadata['be_delightful_task_id']}")
            
            # Set sandbox_id
            if "sandbox_id" in init_message.metadata:
                self.agent_context.set_sandbox_id(init_message.metadata["sandbox_id"])
                logger.info(f"Set sandbox ID from init_message.metadata: {init_message.metadata['sandbox_id']}")

            # Set organization_code
            if "organization_code" in init_message.metadata:
                self.agent_context.set_organization_code(init_message.metadata["organization_code"])
                logger.info(f"Set organization code from init_message.metadata: {init_message.metadata['organization_code']}")

        await self.agent_service.init_workspace(agent_context=self.agent_context)

        self.agents["delightful"] = await self.agent_service.create_agent("delightful", self.agent_context)
        self.agents["be-delightful"] = await self.agent_service.create_agent("be-delightful", self.agent_context)

        self.is_workspace_initialized = True
        logger.info("Workspace initialization completed")

    async def switch_agent(self, task_mode: TaskMode):
        """
        Switch to the corresponding agent based on task_mode

        Args:
            task_mode: Task mode, can be TaskMode.CHAT or TaskMode.PLAN

        Returns:
            Agent: Selected Agent instance
        """
        if task_mode == TaskMode.CHAT:
            agent_type = "delightful"
        elif task_mode == TaskMode.PLAN:
            agent_type = "be-delightful"

        if agent_type not in self.agents:
            logger.error(f"Agent type not found: {agent_type}, using default be-delightful")
            agent_type = "be-delightful"

        logger.info(f"Selected agent type based on task_mode({task_mode}): {agent_type}")

        return self.agents[agent_type]

    async def run_agent(self, agent: Agent):
        """
        Run Agent to process task

        Args:
            agent: Agent instance

        Returns:
            bool: Whether successfully run
        """
        await self.agent_service.run_agent(agent=agent)

    async def dispatch_agent(self, message: ChatClientMessage):
        """
        Dispatch agent to execute task

        Args:
            client_message: Client message

        Returns:
            bool: Whether successfully dispatched
        """
        # Ensure workspace is initialized
        if not self.is_workspace_initialized:
            initialized = await self.load_init_client_message()
            if not initialized:
                logger.error("Agent not initialized, please initialize workspace first")
                await self.agent_context.dispatch_event(EventType.ERROR, ErrorEventData(
                    agent_context=self.agent_context,
                    error_message="Agent not initialized, please initialize workspace first"
                ))
                return

        self.agent_context.set_chat_client_message(message)

        agent = await self.switch_agent(message.task_mode)
        await self.run_agent(agent=agent)

        return True
