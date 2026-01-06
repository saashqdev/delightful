import traceback
from typing import List

from agentlang.context.tool_context import ToolContext
from agentlang.event.data import (
    AfterInitEventData,
    AfterLlmResponseEventData,
    AfterMainAgentRunEventData,
    AfterToolCallEventData,
    AgentSuspendedEventData,
    BeforeInitEventData,
    BeforeLlmRequestEventData,
    BeforeToolCallEventData,
    ErrorEventData,
)
from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.event.event import (
    AfterClientChatEventData,
    AfterSafetyCheckEventData,
    BeforeSafetyCheckEventData,
)
from app.core.entity.event.event_context import EventContext
from app.core.entity.event.file_event import FileEventData
from app.core.entity.factory.task_message_factory import TaskMessageFactory
from app.core.entity.message.server_message import ServerMessage, TaskStatus, TaskStep
from app.core.stream.http_subscription_stream import HTTPSubscriptionStream
from app.core.stream.stdout_stream import StdoutStream
from app.service.agent_event.base_listener_service import BaseListenerService

logger = get_logger(__name__)

class StreamListenerService:
    """
    BeDelightful event listener service for handling and sending BeDelightful events
    """

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register standard event listeners for agent context

        Args:
            agent_context: Agent context object
        """
        # Create mapping from event types to handler functions
        event_listeners = {
            EventType.BEFORE_INIT: StreamListenerService._handle_before_init,
            EventType.AFTER_INIT: StreamListenerService._handle_after_init,
            EventType.BEFORE_SAFETY_CHECK: StreamListenerService._handle_before_safety_check,
            EventType.AFTER_SAFETY_CHECK: StreamListenerService._handle_after_safety_check,
            EventType.AFTER_CLIENT_CHAT: StreamListenerService._handle_after_client_chat,
            EventType.BEFORE_LLM_REQUEST: StreamListenerService._handle_before_llm_request,
            EventType.AFTER_LLM_REQUEST: StreamListenerService._handle_after_llm_response,
            EventType.BEFORE_TOOL_CALL: StreamListenerService._handle_before_tool_call,
            EventType.AFTER_TOOL_CALL: StreamListenerService._handle_after_tool_call,
            EventType.AGENT_SUSPENDED: StreamListenerService._handle_agent_suspended,
            EventType.BEFORE_MAIN_AGENT_RUN: StreamListenerService._handle_before_main_agent_run,
            EventType.AFTER_MAIN_AGENT_RUN: StreamListenerService._handle_after_main_agent_run,
            EventType.ERROR: StreamListenerService._handle_error,
            EventType.FILE_CREATED: StreamListenerService._handle_file_created,
        }

        # Use base class method to register listeners in batch
        BaseListenerService.register_listeners(agent_context, event_listeners)

        logger.info("Registered all standard event listeners for agent context")

    @staticmethod
    async def _handle_before_init(event: Event[BeforeInitEventData]) -> None:
        """
        Handle before initialization event

        Args:
            event: Before initialization event object containing BeforeInitEventData
        """
        # Use factory to create task message
        task_message = TaskMessageFactory.create_before_init_message(event)

        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)

    @staticmethod
    async def _handle_after_init(event: Event[AfterInitEventData]) -> None:
        """
        Handle after initialization event

        Args:
            event: After initialization event object containing AfterInitEventData
        """
        # Use factory to create task message
        task_message = TaskMessageFactory.create_after_init_message(event)

        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)

    @staticmethod
    async def _handle_after_client_chat(event: Event[AfterClientChatEventData]) -> None:
        """
        Handle after client chat event

        Args:
            event: After client chat event object containing AfterClientChatEventData
        """

        task_message = TaskMessageFactory.create_after_client_chat_message(event)
        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_before_llm_request(event: Event[BeforeLlmRequestEventData]) -> None:
        """
        Handle before LLM request event

        Args:
            event: Before LLM request event object containing BeforeLlmRequestEventData
        """
        # Use factory to create task message
        task_message = TaskMessageFactory.create_before_llm_request_message(event)

        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)
        logger.info(f"Starting LLM request: {event.data.model_name}")

    @staticmethod
    async def _handle_after_llm_response(event: Event[AfterLlmResponseEventData]) -> None:
        """
        Handle after LLM response event

        Args:
            event: After LLM response event object containing AfterLlmResponseEventData
        """
        # Use factory to create task message
        task_message = TaskMessageFactory.create_after_llm_response_message(event)

        if event.data.llm_response_message.content == "Continue":
            logger.info("LLM returned no content, message not sent")
            return

        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)
        logger.info(f"Completed LLM request: {event.data.model_name}, time elapsed: {event.data.request_time:.2f}s")

    @staticmethod
    async def _handle_before_tool_call(event: Event[BeforeToolCallEventData]) -> None:
        """
        Handle before tool call event

        Args:
            event: Before tool call event object containing BeforeToolCallEventData
        """
        # Use factory to create task message
        task_message = await TaskMessageFactory.create_before_tool_call_message(event)

        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)

    @staticmethod
    async def _handle_after_tool_call(event: Event[AfterToolCallEventData]) -> None:
        """
        Handle after tool call event

        Args:
            event: After tool call event object containing AfterToolCallEventData
        """

        task_message = await TaskMessageFactory.create_after_tool_call_message(event)
        await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)

    @staticmethod
    async def _handle_agent_suspended(event: Event[AgentSuspendedEventData]) -> None:
        """
        Handle agent suspension event

        Args:
            event: Agent suspension event object containing AgentSuspendedEventData
        """
        task_message = TaskMessageFactory.create_agent_suspended_message(event.data.agent_context)
        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_before_main_agent_run(event: Event[BeforeSafetyCheckEventData]) -> None:
        """
        Handle before main agent run event

        Args:
            event: Before main agent run event object containing BeforeSafetyCheckEventData
        """
        task_message = TaskMessageFactory.create_before_safety_check_message(event)

        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_after_main_agent_run(event: Event[AfterMainAgentRunEventData]) -> None:
        """
        Handle main agent completion event

        Args:
            event: Main agent completion event object containing AfterMainAgentRunEventData
        """
        task_message = TaskMessageFactory.create_after_main_agent_run_message(event)
        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_error(event: Event[ErrorEventData]) -> None:
        """
        Handle error event

        Args:
            event: Error event object containing ErrorEventData
        """
        task_message = TaskMessageFactory.create_error_message(event.data.agent_context, event.data.error_message)
        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_before_safety_check(event: Event[BeforeSafetyCheckEventData]) -> None:
        """
        Handle before safety check event

        Args:
            event: Before safety check event object containing BeforeSafetyCheckEventData
        """
        task_message = TaskMessageFactory.create_before_safety_check_message(event)

        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    async def _handle_after_safety_check(event: Event[AfterSafetyCheckEventData]) -> None:
        """
        Handle after safety check event

        Args:
            event: After safety check event object containing AfterSafetyCheckEventData
        """
        task_message = TaskMessageFactory.create_after_safety_check_message(event)

        tool_context = ToolContext(metadata=event.data.agent_context.get_metadata())
        tool_context.register_extension("agent_context", event.data.agent_context)
        tool_context.register_extension("event_context", EventContext())

        await StreamListenerService._send_task_message(tool_context, task_message, event)

    @staticmethod
    def _create_steps_from_todo_items(agent_context: AgentContext) -> List[TaskStep]:
        """
        Create step list from todo_items in agent_context

        Args:
            agent_context: Agent context containing todo_items

        Returns:
            List[TaskStep]: Step list
        """
        steps = []
        todo_items = agent_context.get_todo_items()

        if not todo_items:
            return steps

        for todo_text, todo_info in todo_items.items():
            # Set step status based on todo item's completed status
            step_status = TaskStatus.FINISHED if todo_info.get('completed', False) else TaskStatus.WAITING

            # Get snowflake ID
            snowflake_id = todo_info.get('id')

            # Create TaskStep object
            step = TaskStep(
                id=str(snowflake_id),  # Convert snowflake ID to string
                title=todo_text,
                status=step_status
            )
            steps.append(step)

        if steps:
            logger.info(f"Created {len(steps)} steps from todo_items")

        return steps

    @staticmethod
    async def _send_task_message(tool_context: ToolContext, task_message: ServerMessage, event: Event) -> None:
        """
        Send task message to client via WebSocket

        Args:
            tool_context: Tool context containing agent_context and event_context
            task_message: Task message to send
        """
        if not tool_context.get_extension_typed("agent_context", AgentContext).streams:
            logger.error("agent_context.streams is empty")
            return

        agent_context = tool_context.get_extension_typed("agent_context", AgentContext)

        try:
            # Get event context from tool context
            event_context = tool_context.get_extension_typed("event_context", EventContext)
            payload = task_message.payload

            # Check if message should be displayed in UI
            if hasattr(payload, "show_in_ui") and not payload.show_in_ui:
                logger.info(f"Skipped sending message to client because show_in_ui=False, message_id: {payload.message_id}")
                return

            # Check if message needs to be pushed to client
            if payload.is_empty:
                logger.info(f"Skipped sending message to client because ServerMessage content is empty, server_message: {task_message.model_dump_json()}")
                return

            # If event context exists and steps need to be updated, process step information
            if event_context:
                steps = StreamListenerService._create_steps_from_todo_items(agent_context)
                if steps:
                    payload.steps = steps
                    logger.info(f"Message included {len(steps)} steps when sent")

                event_context.steps_changed = False
                logger.debug("Reset steps_changed flag to False")
            else:
                logger.debug("Event context not found, skipping step processing")

            message_json = task_message.model_dump_json()

            # Send to all streams
            # Create a copy of the dictionary for iteration to avoid errors from modifying dictionary during iteration
            for stream_id, stream in list(agent_context.streams.items()):
                try:
                    if stream.should_ignore_event(event.event_type):
                        logger.info(f"Skipped writing message to stream, stream type: {type(stream)}, event type: {event.event_type}")
                        continue

                    logger.info(f"Starting to write message to stream, stream type: {type(stream)}")
                    await stream.write(message_json)
                    logger.info(f"Successfully wrote message to stream, stream type: {type(stream)}")
                except Exception as e:
                    logger.error(f"Stack trace: {traceback.format_exc()}")
                    logger.error(f"Failed to write message to stream: {e!s}, removing stream, stream type: {type(stream)}")
                    # Do not remove if StdoutStream or HTTPStream
                    if isinstance(stream, StdoutStream) or isinstance(stream, HTTPSubscriptionStream):
                        logger.info(f"Stream not removed, stream type: {type(stream)}")
                    else:
                        logger.info(f"Removing stream, stream type: {type(stream)}")
                        tool_context.get_extension_typed("agent_context", AgentContext).remove_stream(stream)
            logger.debug(f"Successfully sent task message: {payload.message_id}")
        except Exception as e:
            # Print stack trace
            logger.error(f"Stack trace: {traceback.format_exc()}")
            logger.error(f"Failed to send task message: {e!s}")

    @staticmethod
    async def _handle_file_created(event: Event[FileEventData]) -> None:
        """
        Handle file creation event

        Args:
            event: File creation event object containing FileEventData
        """
        logger.info(f"StreamListenerService: File creation event received: {event.data.filepath}")
        # Typically, file creation notifications are sent through AFTER_TOOL_CALL event messages,
        # which contain attachments created by FileStorageListenerService.
        # If StreamListenerService needs to send specific messages about file creation directly,
        # then TaskMessageFactory needs to provide corresponding creation methods and call them here.
        # Currently, this handler only logs to avoid duplicate notifications.

        # Example: if need to send specific message
        # task_message = TaskMessageFactory.create_file_created_message(event) # Assume this factory method exists
        # await StreamListenerService._send_task_message(event.data.tool_context, task_message, event)
        pass
