"""
FinishTask tool listener service

Monitor FinishTask tool call events and execute corresponding logic after FinishTask tool is successfully called
"""

from agentlang.event.data import AfterToolCallEventData
from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.event.event_context import EventContext
from app.service.agent_event.base_listener_service import BaseListenerService

logger = get_logger(__name__)


class FinishTaskListenerService:
    """
    FinishTask tool listener service

    Monitor FinishTask tool call events and execute corresponding logic after FinishTask tool is successfully called
    """

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register FinishTask tool event listener for agent context

        Args:
            agent_context: Agent context object
        """
        # Create mapping from event type to handler function
        event_listeners = {
            EventType.AFTER_TOOL_CALL: FinishTaskListenerService._handle_after_tool_call
        }

        # Use base class method to register listeners in batch
        BaseListenerService.register_listeners(agent_context, event_listeners)

        logger.info("Registered FinishTask tool event listener for agent context")

    @staticmethod
    async def _handle_after_tool_call(event: Event[AfterToolCallEventData]) -> None:
        """
        Handle after tool call event, focusing on FinishTask tool calls

        Args:
            event: After tool call event object containing AfterToolCallEventData
        """
        # Check if it is a FinishTask tool call
        if event.data.tool_name != "finish_task":
            return

        # Get output message of tool call
        message = event.data.result.content

        # Check if tool call succeeded (no error)
        if not event.data.result.ok:
            logger.warning(f"FinishTask tool call failed: {message}")
            return

        logger.info("Detected successful FinishTask tool call")

        # Set finish_task_called flag to True in event context
        event_context = event.data.tool_context.get_extension_typed("event_context", EventContext)
        if event_context:
            event_context.finish_task_called = True
            logger.info(f"Task completed, final message: {message}")
        else:
            logger.warning("Unable to set finish_task_called flag: EventContext not registered")
