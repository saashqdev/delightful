import asyncio
import json
import traceback

from fastapi import APIRouter, WebSocket, WebSocketDisconnect

from agentlang.event.data import AgentSuspendedEventData, ErrorEventData
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.utils.snowflake import Snowflake
from app.api.dto.base import WebSocketMessage
from app.core.entity.event.event import AfterClientChatEventData
from app.core.entity.message.client_message import ChatClientMessage, ContextType, InitClientMessage
from app.core.entity.message.message import MessageType
from app.core.stream.websocket_stream import WebSocketStream
from app.service.agent_dispatcher import AgentDispatcher

# Create router
router = APIRouter(prefix="/ws")

logger = get_logger(__name__)

class WebSocketManager:
    """WebSocket connection manager, handles WebSocket connections and message forwarding"""

    _instance = None
    _lock = asyncio.Lock()

    @classmethod
    async def get_instance(cls):
        """Get WebSocketManager singleton instance"""
        if cls._instance is None:
            async with cls._lock:
                if cls._instance is None:
                    cls._instance = WebSocketManager()
                    logger.info("WebSocketManager singleton initialized")
        return cls._instance

    def __init__(self):
        """Initialize WebSocketManager"""
        # Task management
        self.manager_task: asyncio.Task = None
        self.worker_task: asyncio.Task = None

        # Create AgentDispatcher instance
        self.agent_dispatcher = AgentDispatcher.get_instance()

    async def handle_chat(self, websocket: WebSocket, message: ChatClientMessage):
        """Handle chat message"""
        agent_context = self.agent_dispatcher.agent_context

        await agent_context.dispatch_event(EventType.AFTER_CLIENT_CHAT, AfterClientChatEventData(
            agent_context=agent_context,
            client_message=message
        ))

        await self.agent_dispatcher.dispatch_agent(message)

    async def handle_workspace_init(self, message: InitClientMessage):
        """Handle workspace initialization message"""
        logger.info(f"Received {MessageType.INIT} message")

        # Initialize workspace
        await self.agent_dispatcher.initialize_workspace(message)

        logger.info("Workspace initialization completed")

    async def manager_coroutine(self, websocket: WebSocket):
        """Main coroutine for managing WebSocket connection"""
        while True:
            try:
                # Receive message
                data = await websocket.receive_text()
                logger.info(f"Received raw message: {data}")

                # Parse JSON message
                message_data = json.loads(data)

                # Basic message structure validation
                ws_message = WebSocketMessage(**message_data)
                type = ws_message.type

                if type == MessageType.CHAT.value:
                    message = ChatClientMessage(**message_data)

                    # Handle different message types
                    if message.context_type == ContextType.NORMAL or message.context_type == ContextType.FOLLOW_UP:
                        # Cancel currently running worker_task (if exists)
                        await self.cancel_task()

                        # Create new worker_task, passing message directly
                        self.worker_task = asyncio.create_task(self.worker_coroutine(websocket, message))
                        logger.info(f"Created new worker task to handle {'normal' if message.context_type == ContextType.NORMAL else 'follow-up'} message")
                        try:
                            await self.worker_task
                        except asyncio.CancelledError:
                            logger.info("Cancelled running worker task")

                    elif message.context_type == ContextType.INTERRUPT:
                        logger.info("Received interrupt request")
                        # Only cancel task, don't create new task
                        await self.cancel_task()
                        # Send suspended message
                        await self.agent_dispatcher.agent_context.dispatch_event(EventType.AGENT_SUSPENDED, AgentSuspendedEventData(
                            agent_context=self.agent_dispatcher.agent_context,
                        ))

                    # Exit loop after handling chat message
                    break

                elif type == MessageType.INIT.value:
                    message = InitClientMessage(**message_data)
                    await self.handle_workspace_init(message)

                else:
                    valid_actions = ", ".join([MessageType.CHAT.value, MessageType.INIT.value])
                    raise ValueError(f"Unsupported message type: {type}, valid values: {valid_actions}")
            except WebSocketDisconnect:
                logger.error(traceback.format_exc())
                logger.info("WebSocket connection disconnected")
                break
            except Exception as e:
                # Print error stack trace
                logger.error(traceback.format_exc())
                await self.agent_dispatcher.agent_context.dispatch_event(EventType.ERROR, ErrorEventData(
                    agent_context=self.agent_dispatcher.agent_context,
                    error_message="Service exception"
                ))
                break

    async def worker_coroutine(self, websocket: WebSocket, message: ChatClientMessage):
        """Worker coroutine for handling specific messages"""
        snowflake = Snowflake.create_default()
        task_id = str(snowflake.get_id())
        self.agent_dispatcher.agent_context.set_task_id(task_id)

        await self.handle_chat(websocket, message)
        logger.info("Worker coroutine completed")

    async def cancel_task(self):
        """Cancel task"""
        if self.worker_task and not self.worker_task.done():
            self.worker_task.cancel()
            try:
                await self.worker_task
            except asyncio.CancelledError:
                logger.info("Cancelled running worker task")


# Create WebSocketManager instance, but actual initialization will occur on first get_instance call
# ws_manager = WebSocketManager()

@router.websocket("")
async def websocket_endpoint(websocket: WebSocket):
    """
    WebSocket endpoint, handles client messages and returns BeDelightful agent replies

    Supports sync/sync-ack synchronization mechanism to ensure task continues execution and pushes messages to client:
    - Client establishes connection by sending sync action
    - Server returns sync-ack response with task running status
    - If client disconnects, can reconnect by sending sync message again
    - Optional pull_history parameter controls whether to receive historical messages

    All exception handling is unified by websocket_route_handler decorator
    """
    await websocket.accept()

    logger.info("New WebSocket connection established")

    # Get WebSocketManager instance
    ws_manager = await WebSocketManager.get_instance()

    # Create WebSocket stream for new connection and add to Agent context
    stream = WebSocketStream(websocket=websocket)
    ws_manager.agent_dispatcher.agent_context.add_stream(stream)

    try:
        # Create management coroutine
        ws_manager.manager_task = asyncio.create_task(ws_manager.manager_coroutine(websocket))

        # Wait for management coroutine to complete
        await ws_manager.manager_task

        logger.info("Manager coroutine finished running")

    except Exception as e:
        logger.error(f"WebSocket connection exception: {e!s}")

        # Also cancel any running tasks
        if ws_manager.worker_task and not ws_manager.worker_task.done():
            ws_manager.worker_task.cancel()
            logger.info("Cancelled worker task")

        if ws_manager.manager_task and not ws_manager.manager_task.done():
            ws_manager.manager_task.cancel()
            logger.info("Cancelled manager task")
    finally:
        # Remove WebSocket stream from Agent context
        ws_manager.agent_dispatcher.agent_context.remove_stream(stream)
