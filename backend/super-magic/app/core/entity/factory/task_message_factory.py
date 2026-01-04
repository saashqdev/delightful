"""
Task message factory module

Provides factory class for creating different types of TaskMessage
"""

from agentlang.event.data import (
    AfterInitEventData,
    AfterLlmResponseEventData,
    AfterMainAgentRunEventData,
    AfterToolCallEventData,
    BeforeInitEventData,
    BeforeLlmRequestEventData,
    BeforeToolCallEventData,
)
from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.attachment import AttachmentTag
from app.core.entity.event.event import (
    AfterClientChatEventData,
    AfterSafetyCheckEventData,
    BeforeSafetyCheckEventData,
)
from app.core.entity.event.event_context import EventContext
from app.core.entity.message.server_message import (
    MessageType,
    ServerMessage,
    ServerMessagePayload,
    TaskStatus,
    Tool,
    ToolStatus,
)

logger = get_logger(__name__)

class TaskMessageFactory:
    """Task message factory class for creating different types of TaskMessage objects"""

    @classmethod
    def create_error_message(cls, agent_context: AgentContext, error_message: str) -> ServerMessage:
        """
        Create error message
        """
        return ServerMessage(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id="",
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.CHAT,
                status=TaskStatus.ERROR,
                content=error_message,
                event=EventType.ERROR
            )
        )

    @classmethod
    def create_before_init_message(cls, event: Event[BeforeInitEventData]) -> ServerMessage:
        """
        Create pre-initialization task message

        Args:
            event: Before initialization event

        Returns:
            TaskMessage: Pre-initialization task message
        """
        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.INIT,
                status=TaskStatus.WAITING,
                content="Workspace is initializing",
                event=event.event_type
            )
        )

    @classmethod
    def create_after_init_message(cls, event: Event[AfterInitEventData]) -> ServerMessage:
        """
        Create post-initialization task message

        Args:
            event: After initialization event

        Returns:
            TaskMessage: Post-initialization task message
        """
        if event.data.success:
            status = TaskStatus.RUNNING
            content = "Virtual machine initialization complete"
        else:
            status = TaskStatus.ERROR
            content = "Virtual machine initialization failed"

        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.INIT,
                status=status,
                content="",
                event=event.event_type,
                tool=Tool(
                    id=agent_context.get_task_id(),
                    name="finish_task",
                    action=content,
                    status=ToolStatus.FINISHED,
                    remark="",
                    detail=None,
                    attachments=[]
                )
            )
        )

    @classmethod
    def create_after_client_chat_message(cls, event: Event[AfterClientChatEventData]) -> ServerMessage:
        """
        Create task message after client chat
        """
        return ServerMessage.create(
            metadata=event.data.agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=event.data.agent_context.get_task_id(),
                sandbox_id=event.data.agent_context.get_sandbox_id(),
                message_type=MessageType.CHAT,
                status=TaskStatus.RUNNING,
                content="ok",
                event=EventType.AFTER_CLIENT_CHAT
            )
        )
        # Create suspended message
    @classmethod
    def create_agent_suspended_message(cls, agent_context: AgentContext) -> ServerMessage:
        """
        Create suspended message
        """
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.CHAT,
                status=TaskStatus.SUSPENDED,
                content="Task terminated",
                event=EventType.AGENT_SUSPENDED
            )
        )

    @classmethod
    def create_after_main_agent_run_message(cls, event: Event[AfterMainAgentRunEventData]) -> ServerMessage:
        """
        Create message after main agent run

        Args:
            event: After main agent run event, contains AfterMainAgentRunEventData data

        Returns:
            ServerMessage: Main agent task completion message
        """
        agent_context: AgentContext = event.data.agent_context
        all_attachments = agent_context.get_attachments()
        # Filter out browser attachments first
        filtered_attachments = [attachment for attachment in all_attachments
                                if attachment.file_tag != AttachmentTag.BROWSER]
        # Sort the filtered attachments by timestamp in descending order
        attachments = sorted(filtered_attachments, key=lambda att: att.timestamp, reverse=True)

        logger.info(f"Created main agent completion message, filtered out {len(all_attachments) - len(attachments)} browser attachments and sorted by timestamp")

        # Get project archive information (if exists)
        project_archive = agent_context.get_project_archive_info()
        if project_archive:
            logger.info(f"Retrieved project archive information from SharedContext: key={project_archive.file_key}")

        if event.data.agent_state == TaskStatus.FINISHED.value:
            status = TaskStatus.FINISHED
            content = "Task completed"
        else:
            status = TaskStatus.ERROR
            content = "Task execution finished"

        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.CHAT,
                status=status,
                content=content,
                attachments=attachments,
                project_archive=project_archive,
                event=EventType.AFTER_MAIN_AGENT_RUN
            )
        )

    @classmethod
    def create_before_llm_request_message(cls, event: Event[BeforeLlmRequestEventData]) -> ServerMessage:
        """
        Create task message before LLM request

        Args:
            event: Before LLM request event

        Returns:
            TaskMessage: Task message before LLM request
        """
        content = "Thinking"

        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.THINKING,
                status=TaskStatus.RUNNING,
                content=content,
                event=event.event_type
            )
        )

    @classmethod
    def create_after_llm_response_message(cls, event: Event[AfterLlmResponseEventData]) -> ServerMessage:
        """
        Create task message after LLM response

        Args:
            event: After LLM response event

        Returns:
            TaskMessage: Task message after LLM response
        """
        content = ""
        llm_response_message = event.data.llm_response_message
        if llm_response_message and llm_response_message.content:
            content = llm_response_message.content

        # Get show_in_ui value from event data
        show_in_ui = getattr(event.data, "show_in_ui", True)

        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)

        # Ensure task_id is not None, use empty string if it is None
        task_id = agent_context.get_task_id() or ""

        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=task_id,
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.THINKING,
                status=TaskStatus.RUNNING,
                content=content,
                event=event.event_type,
                show_in_ui=show_in_ui  # Pass display flag
            )
        )

    @classmethod
    async def create_before_tool_call_message(cls, event: Event[BeforeToolCallEventData]) -> ServerMessage:
        """
        Create task message before tool call

        Args:
            event: Before tool call event

        Returns:
            TaskMessage: Task message before tool call
        """
        tool_instance = event.data.tool_instance
        # If LLM has already returned, don't send content anymore
        if event.data.llm_response_message.content and event.data.llm_response_message.content != "Continue":
            content = ""
        else:
            content = await tool_instance.get_before_tool_call_friendly_content(event.data.tool_context, event.data.arguments)

        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)

        # Ensure task_id is not None
        task_id = agent_context.get_task_id() or ""

        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=task_id,
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.TOOL_CALL,
                status=TaskStatus.RUNNING,
                content=content,
                event=event.event_type
            )
        )

    @classmethod
    async def create_after_tool_call_message(cls, event: Event[AfterToolCallEventData]) -> ServerMessage:
        """
        Create task message after tool call

        Args:
            event: After tool call event

        Returns:
            TaskMessage: Task message after tool call
        """
        tool_name = event.data.tool_name
        execution_time = event.data.execution_time
        result = event.data.result

        tool_instance = event.data.tool_instance

        # Get attachment list from event context
        event_context = event.data.tool_context.get_extension_typed("event_context", EventContext)
        attachments = []
        if event_context:
            attachments = event_context.attachments
        else:
            logger.debug("Event context not found, using empty attachment list")

        # 1. Default content
        content = ""

        # 2. Try get_after_tool_call_friendly_content
        friendly_content = await tool_instance.get_after_tool_call_friendly_content(
            event.data.tool_context, 
            result, 
            execution_time, 
            event.data.arguments
        )
        if friendly_content and friendly_content.strip():
            content = friendly_content

        # 3. If friendly_content is empty, try result.explanation
        elif result.explanation is not None and result.explanation.strip():
            content = result.explanation

        message_type = MessageType.TOOL_CALL
        status = TaskStatus.RUNNING

        tool_detail = await tool_instance.get_tool_detail(event.data.tool_context, result, event.data.arguments)

        friendly_action_and_remark = await tool_instance.get_after_tool_call_friendly_action_and_remark(tool_name, event.data.tool_context, result, execution_time, event.data.arguments)

        # Create tool object
        tool = Tool(
            id=event.data.tool_call.id,
            name=tool_name,
            action=friendly_action_and_remark["action"],
            status=ToolStatus.FINISHED,
            remark=friendly_action_and_remark["remark"],
            detail=tool_detail,
            attachments=attachments
        )

        agent_context = event.data.tool_context.get_extension_typed("agent_context", AgentContext)

        # Ensure task_id is not None, use empty string if it is None
        task_id = agent_context.get_task_id() or ""

        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=task_id,
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=message_type,
                status=status,
                content=content,
                tool=tool,
                event=event.event_type
            )
        )

    @classmethod
    def create_before_safety_check_message(cls, event: Event[BeforeSafetyCheckEventData]) -> ServerMessage:
        """
        Create task message before safety check

        Args:
            event: Before safety check event

        Returns:
            ServerMessage: Task message before safety check
        """
        content = "Performing security check"

        agent_context = event.data.agent_context
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.THINKING,
                status=TaskStatus.RUNNING,
                content=content,
                event=event.event_type,
                show_in_ui=False
            )
        )

    @classmethod
    def create_after_safety_check_message(cls, event: Event[AfterSafetyCheckEventData]) -> ServerMessage:
        """
        Create task message after safety check

        Args:
            event: After safety check event

        Returns:
            ServerMessage: Task message after safety check
        """
        if event.data.is_safe:
            content = "Security check passed"
            status = TaskStatus.RUNNING
        else:
            content = "Security check failed"
            status = TaskStatus.RUNNING

        agent_context = event.data.agent_context
        return ServerMessage.create(
            metadata=agent_context.get_init_client_message_metadata(),
            payload=ServerMessagePayload.create(
                task_id=agent_context.get_task_id(),
                sandbox_id=agent_context.get_sandbox_id(),
                message_type=MessageType.THINKING,
                status=status,
                content=content,
                event=event.event_type
            )
        )
