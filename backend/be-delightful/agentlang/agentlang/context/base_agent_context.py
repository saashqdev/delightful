"""
Base agent context class

Provides basic agent context functionality implementation without business logic
"""

import asyncio
import os
from typing import Any, Callable, Dict, Optional

from agentlang.context.base_context import BaseContext
from agentlang.context.shared_context import AgentSharedContext
from agentlang.event.dispatcher import EventDispatcher
from agentlang.event.interface import EventDispatcherInterface
from agentlang.interface.context import AgentContextInterface
from agentlang.logger import get_logger

logger = get_logger(__name__)

class BaseAgentContext(BaseContext, AgentContextInterface):
    """Base agent context implementation
    
    Provides core interface basic implementation without business logic
    """

    _workspace_dir: str
    _resources: Dict[str, Any]
    _user_id: Optional[str]

    def __init__(self):
        """Initialize base agent context"""
        super().__init__()
        # Use existing singleton instance instead of trying to create a new one
        self.shared_context = AgentSharedContext

        self._workspace_dir = ""
        self._resources: Dict[str, Any] = {}
        self._user_id = None

        # Framework layer base attributes
        self.agent_name = "base_agent"  # Default agent name
        self.is_main_agent = False  # Whether this is the main agent
        self.stream_mode = False  # Stream mode switch
        self.llm = None  # Currently used LLM model
        self.use_dynamic_prompt = True  # Dynamic prompt switch
        self.chat_history_dir = ""  # Chat history directory

    def _init_shared_fields(self):
        """Initialize shared fields and register to shared_context"""
        # Check if already initialized
        if self.shared_context.has_field("event_dispatcher"):
            return

        self.shared_context.register_fields({
            "event_dispatcher": (EventDispatcher(), EventDispatcherInterface),
        })

    def get_workspace_dir(self) -> str:
        """Get workspace directory"""
        return self._workspace_dir

    def set_workspace_dir(self, workspace_dir: str) -> None:
        """Set workspace directory"""
        self._workspace_dir = workspace_dir
        logger.debug(f"Set workspace directory: {workspace_dir}")

    def ensure_workspace_dir(self) -> str:
        """Ensure workspace directory exists"""
        if not self._workspace_dir:
            raise ValueError("Workspace directory not set")

        os.makedirs(self._workspace_dir, exist_ok=True)
        return self._workspace_dir

    def set_agent_name(self, agent_name: str) -> None:
        """Set agent name
        
        Args:
            agent_name: Agent name
        """
        self.agent_name = agent_name
        logger.debug(f"Set agent name: {agent_name}")

    def get_agent_name(self) -> str:
        """Get agent name"""
        return self.agent_name

    def set_main_agent(self, is_main: bool) -> None:
        """Set whether this is the main agent
        
        Args:
            is_main: Whether this is the main agent
        """
        self.is_main_agent = is_main
        logger.debug(f"Set main agent: {is_main}")

    def is_main_agent(self) -> bool:
        """Get whether this is the main agent"""
        return self.is_main_agent

    def set_stream_mode(self, enabled: bool) -> None:
        """Set whether to use streaming output
        
        Args:
            enabled: Whether to enable
        """
        self.stream_mode = enabled
        logger.debug(f"Set streaming output mode: {enabled}")

    def is_stream_mode(self) -> bool:
        """Get streaming output mode"""
        return self.stream_mode

    def set_llm(self, model: str) -> None:
        """Set LLM model
        
        Args:
            model: Model name
        """
        self.llm = model
        logger.debug(f"Set LLM model: {model}")

    def get_llm(self) -> str:
        """Get LLM model"""
        return self.llm

    def set_use_dynamic_prompt(self, enabled: bool) -> None:
        """Set whether to use dynamic prompts
        
        Args:
            enabled: Whether to enable
        """
        self.use_dynamic_prompt = enabled
        logger.debug(f"Set dynamic prompt: {enabled}")

    def is_use_dynamic_prompt(self) -> bool:
        """Get whether to use dynamic prompts"""
        return self.use_dynamic_prompt

    def set_chat_history_dir(self, directory: str) -> None:
        """Set chat history directory
        
        Args:
            directory: Chat history directory path
        """
        self.chat_history_dir = directory
        os.makedirs(directory, exist_ok=True)
        logger.debug(f"Set chat history directory: {directory}")

    def get_chat_history_dir(self) -> str:
        """Get chat history directory"""
        return self.chat_history_dir

    def get_event_dispatcher(self) -> EventDispatcherInterface:
        """Get event dispatcher

        Returns:
            EventDispatcherInterface: Event dispatcher
        """
        return self.shared_context.get_field("event_dispatcher")


    async def dispatch_event(self, event_type: str, data: Any) -> Any:
        """Dispatch event"""
        from agentlang.event.event import Event
        event = Event(event_type, data)
        logger.debug(f"Dispatch event: {event_type}")
        return await self.get_event_dispatcher().dispatch(event)

    def add_event_listener(self, event_type: str, listener: Callable) -> None:
        """Add event listener"""
        self.get_event_dispatcher().add_listener(event_type, listener)
        logger.debug(f"Add event listener: {event_type}")

    async def get_resource(self, name: str, factory=None) -> Any:
        """Get resource, create if not exists"""
        # If resource doesn't exist and factory is provided, create it
        if name not in self._resources and factory is not None:
            try:
                # If factory is async function, await its completion
                if asyncio.iscoroutinefunction(factory):
                    self._resources[name] = await factory()
                else:
                    self._resources[name] = factory()
                logger.debug(f"Create resource: {name}")
            except Exception as e:
                logger.error(f"Error creating resource {name}: {e}")
                raise RuntimeError(f"Error creating resource {name}: {e}")

        # Return resource (may be None)
        return self._resources.get(name)

    def add_resource(self, name: str, resource: Any) -> None:
        """Add resource"""
        self._resources[name] = resource
        logger.debug(f"Add resource: {name}")

    async def close_resource(self, name: str) -> None:
        """Close and remove resource"""
        if name not in self._resources:
            return

        resource = self._resources[name]
        try:
            # Try to close resource (if it has a close method)
            if hasattr(resource, "close") and callable(getattr(resource, "close")):
                if asyncio.iscoroutinefunction(resource.close):
                    await resource.close()
                else:
                    resource.close()
                logger.debug(f"Close resource: {name}")

            # Remove resource
            del self._resources[name]
        except Exception as e:
            logger.error(f"Error closing resource {name}: {e}")
            # Even if error occurs, still remove from dictionary
            if name in self._resources:
                del self._resources[name]
            raise RuntimeError(f"Error closing resource {name}: {e}")

    async def close_all_resources(self) -> None:
        """Close and remove all resources"""
        # Copy key list because dictionary will be modified during iteration
        resource_names = list(self._resources.keys())
        for name in resource_names:
            await self.close_resource(name)
        logger.debug(f"Closed all resources ({len(resource_names)} items)")

    def set_user_id(self, user_id: str) -> None:
        """Set user ID"""
        self._user_id = user_id

    def get_user_id(self) -> Optional[str]:
        """Get user ID"""
        return self._user_id

    def get_metadata(self) -> Dict[str, Any]:
        """Get context metadata
        
        Inherits from BaseContext, returns all metadata
        """
        return {**self._metadata} 
