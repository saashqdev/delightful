from typing import Any, Dict, List

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.tools.tool_result import ToolResult
from app.tools.core import BaseTool, BaseToolParams, tool


class FinishTaskParams(BaseToolParams):
    """Parameters for finishing a task."""
    explanation: str = Field(
        "",  # Default exists but treated as required in practice
        description="""
        In first person (“I”), summarize the work you finished, focusing on:
        1. Which major tasks you completed
        2. What deliverables you produced (files, reports, etc.)
        3. Which user problem or need those deliverables address
        Do not mention the “finish task” tool itself—only describe the outcomes and value.
        """
    )
    message: str = Field(
        ...,
        description="Final user-facing message before finishing; build on, but do not repeat, explanation."
    )
    files: List[str] = Field(
        ...,
        description="List of file paths related to the final message, e.g., ['final_report.md', 'final_report.html', '.webview_report/xxx.md']."
    )


@tool()
class FinishTask(BaseTool[FinishTaskParams]):
    """
    Call this tool when all required work is complete and you are ready to give the final reply. After invoking it, the current conversation turn ends, so ensure every necessary action is done first.
    """

    async def execute(self, tool_context: ToolContext, params: FinishTaskParams) -> ToolResult:
        """Complete the current task and produce the final message."""
        # Format file list and append to message
        files_str = ""
        if params.files and len(params.files) > 0:
            files_str = "\n\nRelated files:\n" + "\n".join([f"- {file}" for file in params.files])

        # Combine explanation, message, and files list
        final_content = params.explanation + "\n\n" + params.message + files_str

        return ToolResult(
            content=final_content,
            system="FINISH_TASK",  # System flag indicating completion
        )

    async def get_after_tool_call_friendly_content(
        self,
        tool_context: ToolContext,
        result: ToolResult, # ToolResult returned by execute
        execution_time: float,
        arguments: Dict[str, Any] # Arguments used for the call (FinishTaskParams)
    ) -> str:
        """Return the final user-friendly message for finish_task.

        The design simply returns the content prepared by execute.
        """
        if result and result.get_content() is not None:
            return result.get_content() # No .strip()
        return "" # Fallback to empty string
