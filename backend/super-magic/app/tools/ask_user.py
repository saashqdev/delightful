"""
Tool for asking the user questions and awaiting replies.

Enables the assistant to request additional information or confirmations from the user.
"""


from typing import Any, Dict, Optional

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.message.server_message import AskUserContent, DisplayType, ToolDetail
from app.core.entity.tool.tool_result import AskUserToolResult
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)

_ASK_USER_DESCRIPTION = """Use this tool when you need extra information, confirmation, or further instruction from the user.
It lets you pose a question and wait for a reply before continuing. Use only when necessary; typically you should steer the flow yourself to solve the user's problem and goal."""


class AskUserParams(BaseToolParams):
    """Parameters for asking the user a question."""
    question: str = Field(
        ...,
        description="要向用户提出的问题或请求"
    )
    type: Optional[str] = Field(
        None,
        description="可选的内容类型，例如 'todo'"
    )
    content: Optional[str] = Field(
        None,
        description="与问题相关的内容，例如任务内容、代码片段等"
    )


@tool()
class AskUser(BaseTool[AskUserParams]):
    """
    Tool for asking the user questions.

    Use when you need extra information, confirmation, or further instruction. It allows asking and waiting for a reply before continuing. Use only when necessary; generally you should guide the flow yourself to achieve the user's goals.
    """

    async def execute(self, tool_context: ToolContext, params: AskUserParams) -> AskUserToolResult:
        """
        Ask the user a question and wait for a response.

        Args:
            tool_context: Tool context
            params: Parameter object containing the question and optional type/content

        Returns:
            AskUserToolResult: Tool result with the question details
        """
        logger.info(f"Ask user: {params.question}")
        if params.type:
            logger.info(f"Content type: {params.type}")
        if params.content:
            logger.info(f"Related content: {params.content}")

        # Create AskUserToolResult and set fields directly
        result = AskUserToolResult(
            question=params.question,
            content=params.question,  # Default ToolResult content to the question text
            system="ASK_USER",  # System directive marking this as a user inquiry
        )

        # 设置可选字段
        if params.type:
            result.set_type(params.type)
        if params.content:
            result.set_content(params.content)

        return result

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get user-friendly action and remark after tool call.

        Args:
            tool_name: Tool name
            tool_context: Tool context
            result: Tool execution result
            execution_time: Execution duration
            arguments: Execution arguments

        Returns:
            Dict: Dictionary containing action and remark
        """
        question = ""
        if arguments and "question" in arguments:
            question = arguments["question"]

        return {
            "action": "",  # Per template, leave empty
            "remark": ""  # Use the tool's question content as remark
        }

    async def get_after_tool_call_friendly_content(self, tool_context: ToolContext, result: AskUserToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> str:
        """
        Get user-friendly message after tool call.

        Args:
            tool_context: Tool context
            result: Tool execution result (AskUserToolResult)
            execution_time: Execution duration
            arguments: Execution arguments

        Returns:
            str: Friendly execution result message
        """
        # Use result.question directly without type checking
        ask_result = result  # type: AskUserToolResult
        return f"Waiting for user to answer: {ask_result.question}"

    async def get_tool_detail(self, tool_context: ToolContext, result: AskUserToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Build ToolDetail based on tool execution result.

        Args:
            tool_context: Tool context
            result: Tool execution result (AskUserToolResult)
            arguments: Execution arguments

        Returns:
            Optional[ToolDetail]: Tool detail object
        """
        if not result.ok:
            return None

        # Use AskUserToolResult properties directly
        ask_result = result  # type: AskUserToolResult

        # Prefer result.content, fallback to question
        content = ask_result.content or ask_result.question

        # Retrieve question type if present
        question_type = getattr(ask_result, 'type', None)

        # Build custom tool detail
        return ToolDetail(
            type=DisplayType.ASK_USER,
            data=AskUserContent(
                content=content,
                question_type=question_type
            )
        )
