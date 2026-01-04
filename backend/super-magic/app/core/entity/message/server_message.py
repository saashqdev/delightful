import time
from enum import Enum
from typing import Any, Dict, List, Optional, Union

from pydantic import BaseModel, ConfigDict, Field

from agentlang.event.event import EventType
from agentlang.utils.snowflake import Snowflake
from app.core.entity.attachment import Attachment
from app.core.entity.message.message import MessageType
from app.core.entity.project_archive import ProjectArchiveInfo


class TaskStatus(str, Enum):
    """Task status enumeration"""

    WAITING = "waiting"
    RUNNING = "running"
    FINISHED = "finished"
    ERROR = "error"
    SUSPENDED = "suspended"

class ToolStatus(str, Enum):
    """Tool call status enumeration"""

    WAITING = "waiting"
    RUNNING = "running"
    FINISHED = "finished"
    ERROR = "error"


class DisplayType(str, Enum):
    """Display type enumeration"""

    TEXT = "text"
    MD = "md"
    HTML = "html"
    TERMINAL = "terminal"
    BROWSER = "browser"
    SEARCH = "search"
    SCRIPT = "script"
    CODE = "code"
    ASK_USER = "ask_user"  # Add ASK_USER enum value


class TaskStep(BaseModel):
    """Task step model"""

    id: str  # Step ID, using snowflake ID
    title: str  # Step title
    status: TaskStatus  # Step status


class FileContent(BaseModel):
    """Text, Markdown, HTML file content model"""

    file_name: str  # File name
    content: str  # File content


class TerminalContent(BaseModel):
    """Terminal content model"""

    command: str  # Terminal command
    output: str  # Terminal output
    exit_code: int  # Terminal exit code


class ScriptExecutionContent(BaseModel):
    """Script execution content model"""

    code: str  # Executed code content
    args: Optional[str] = None  # Command line arguments
    stdout: str  # Standard output
    stderr: str  # Standard error
    exit_code: int  # Exit code
    success: bool  # Whether execution succeeded


class BrowserContent(BaseModel):
    """Browser content model"""

    url: str  # Browser URL
    title: str  # Browser title
    file_key: Optional[str] = None  # Browser screenshot


class SearchResultItem(BaseModel):
    """Search result item model"""

    title: str  # Search result title
    url: str  # Search result URL
    snippet: str  # Search result description
    icon_url: Optional[str] = None  # Website icon URL field


class SearchGroupItem(BaseModel):
    """Search group model"""

    keyword: str  # Search keyword
    results: List[SearchResultItem]  # Search result list for this keyword


class SearchContent(BaseModel):
    """Search content model"""

    groups: List[SearchGroupItem]  # Multiple search result groups, each corresponding to a keyword


class DeepWriteContent(BaseModel):
    """Deep writing content model"""

    title: str  # Deep writing title
    reasoning_content: str  # Deep writing process content
    content: str  # Deep writing conclusion


class AskUserContent(BaseModel):
    """User question content model"""

    content: str  # Question content
    question_type: Optional[str] = None  # Question type, optional parameter

class ToolDetail(BaseModel):
    """Tool detail model"""

    type: DisplayType  # Display type
    data: Union[
        FileContent, TerminalContent, BrowserContent, SearchContent,
        ScriptExecutionContent, DeepWriteContent, AskUserContent, Dict[str, Any]
    ]  # Display content, dynamically displayed based on type

    model_config = ConfigDict(use_enum_values=True)


class Tool(BaseModel):
    """Tool model"""

    id: str  # Tool call id
    name: str  # Tool name
    action: Optional[str] = None  # Tool execution action
    status: ToolStatus  # Current tool call status
    remark: Optional[str] = None  # Remark note
    detail: Optional[ToolDetail] = None  # Tool detail
    attachments: Optional[List[Attachment]] = None  # Attachment list


class ServerMessagePayload(BaseModel):
    """Task message model"""

    message_id: str
    type: Union[MessageType, str]  # Message type
    task_id: str  # Current task id
    status: TaskStatus  # Task status
    content: str = Field(default="")  # Message content
    sandbox_id: Optional[str] = None  # Sandbox ID
    steps: Optional[List[TaskStep]] = None  # Task step list
    tool: Optional[Tool] = None  # Tool info
    attachments: Optional[List[Attachment]] = None  # Attachment list
    send_timestamp: int  # Send timestamp in seconds
    event: Optional[EventType] = None  # Event type, optional parameter
    project_archive: Optional[ProjectArchiveInfo] = None  # Project archive info
    show_in_ui: bool = True  # Whether to show message in UI

    model_config = ConfigDict(use_enum_values=True)  # Use enum values instead of enum objects

    @property
    def is_empty(self) -> bool:
        """
        Check if message is empty

        Returns:
            bool: Returns True if message content is empty and tool detail is empty, otherwise False
        """
        return not self.content and (not self.tool or (not self.tool.action and not self.tool.detail))

    @classmethod
    def create(
        cls,
        task_id: str,
        message_type: MessageType,
        status: TaskStatus,
        content: str,
        sandbox_id: Optional[str] = None,
        tool: Optional[Tool] = None,
        steps: Optional[List[TaskStep]] = None,
        attachments: Optional[List[Attachment]] = None,
        event: Optional[EventType] = None,
        project_archive: Optional[ProjectArchiveInfo] = None,
        show_in_ui: bool = True,  # New parameter, defaults to True
    ) -> "ServerMessagePayload":
        """
        Factory method to create task message

        Args:
            task_id: Task ID
            message_type: Message type
            status: Task status
            content: Message content
            sandbox_id: Sandbox ID, optional
            tool: Optional tool info
            steps: Optional task step list
            attachments: Optional attachment list
            event: Optional event type
            project_archive: Optional project archive info
            show_in_ui: Whether to show in UI, defaults to True

        Returns:
            TaskMessage: Created task message object
        """
        # Use snowflake algorithm to generate ID
        snowflake = Snowflake.create_default()

        return ServerMessagePayload(
            message_id=str(snowflake.get_id()),
            type=message_type,
            task_id=task_id,
            status=status,
            content=content,
            sandbox_id=sandbox_id,
            tool=tool,
            steps=steps,
            attachments=attachments,
            send_timestamp=int(time.time()),
            event=event,
            project_archive=project_archive,
            show_in_ui=show_in_ui,  # Pass display flag
        )

class ServerMessage(BaseModel):
    """Task message model"""

    metadata: Dict[str, Any]
    payload: ServerMessagePayload

    @classmethod
    def create(cls, metadata: Dict[str, Any], payload: ServerMessagePayload) -> "ServerMessage":
        return ServerMessage(
            metadata=metadata,
            payload=payload
        )
