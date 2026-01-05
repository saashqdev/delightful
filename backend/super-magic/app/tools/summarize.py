from datetime import datetime
from typing import Any, Dict, Optional

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.llms.factory import LLMFactory
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.token_estimator import truncate_text_by_token
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseTool, BaseToolParams, tool
from app.tools.read_file import ReadFile, ReadFileParams

logger = get_logger(__name__)

# Default maximum token count
DEFAULT_MAX_TOKENS = 10000


class SummarizeParams(BaseToolParams):
    """Summary tool parameters"""
    file_path: str = Field(
        ...,
        description="File path for which to generate summary"
    )
    max_length: int = Field(
        default=300,
        description="Maximum length of summary (number of characters)"
    )


@tool()
class Summarize(BaseTool[SummarizeParams]):
    """
    Summary tool for generating concise summaries from text file content.

    Applicable to the following scenarios:
    - Content summarization of long articles
    - Summary generation for research papers
    - Key points extraction from meeting minutes
    - Core information extraction from news articles

    Requirements:
    - Summary should be based on file content, do not add information not in the original
    - Summary should preserve key information and main viewpoints of the original

    Usage example:
    ```
    {
        "file_path": "./webview_report/article.md",
        "max_length": 300
    }
    ```
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: SummarizeParams
    ) -> ToolResult:
        return await self.execute_purely(params)

    async def execute_purely(
        self,
        params: SummarizeParams
    ) -> ToolResult:
        """Execute summary and return result.

        Args:
            tool_context: Tool context
            params: Summary parameters object

        Returns:
            ToolResult: Tool result containing summary result
        """
        try:
            # Get parameters
            file_path = params.file_path
            max_length = params.max_length
            model_id = "deepseek-chat"

            # Log summary request
            logger.info(f"execute/executionsummary: file path={file_path}, maximumlength={max_length}, model={model_id}")

            # Read file content
            file_content = await self._read_file(file_path)
            if not file_content:
                return ToolResult(error=f"Unable to read file: {file_path}")

            # Extract file name for display
            file_name = file_path.split('/')[-1]

            # Call internal method to process summary
            summary_content = await self.summarize_content(
                content=file_content,
                title=file_name,
                max_length=max_length,
                model_id=model_id
            )

            if not summary_content:
                return ToolResult(error="Generate summary failed")

            # Create result
            result = ToolResult(
                content=f"## File summary: {file_name}\n\n{summary_content}",
                extra_info={"file_path": file_path, "file_name": file_name}
            )

            return result

        except Exception as e:
            logger.exception(f"Summary operation failed: {e!s}")
            return ToolResult(error=f"Summary operation failed: {e!s}")

    async def summarize_content(
        self,
        content: str,
        title: str = "Document",
        max_length: int = 300,
        model_id: str = "deepseek-chat"
    ) -> Optional[str]:
        """Directly generate summary for text content without file reading

        Args:
            tool_context: Tool context
            content: Text content to summarize
            title: Content title
            max_length: Summary maximum length
            temperature: Model temperature
            model_id: Model ID to use

        Returns:
            Optional[str]: Summary content, returns None if failed
        """
        try:
            # Truncate here
            truncated_content, is_truncated = truncate_text_by_token(content, DEFAULT_MAX_TOKENS)
            if is_truncated:
                logger.warning(f"Summary content was truncated: title='{title}', original_length={len(content)}, truncated_length={len(truncated_content)}")

            # Get and format current time context
            current_time_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S Weekday {} (Week #%W)".format(
                ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"][datetime.now().weekday()]))

            # Build prompt
            prompt = f"""Please generate a concise and clear summary for the following text content, keeping it within {max_length} characters.
The summary should include the main points, key information, and important conclusions of the document.
Please ensure the summary is a faithful overview of the original content, without adding information not present in the original text.

Current time: {current_time_str}

Document title: {title}

Text content:
```
{truncated_content}
```

Please provide the summary:"""

            # Build messages
            messages = [
                {
                    "role": "system",
                    "content": "You are a professional text summarization assistant, skilled at extracting core content from text and generating concise and clear summaries."
                },
                {
                    "role": "user",
                    "content": prompt
                }
            ]

            # Request model
            response = await LLMFactory.call_with_tool_support(
                model_id=model_id,
                messages=messages,
                tools=None,  # No tool support needed
                stop=None,
            )

            # Process response
            if not response or not response.choices or len(response.choices) == 0:
                logger.error("No valid response received from model")
                return None

            # Get summary content
            summary_content = response.choices[0].message.content

            # If content was truncated, add notice
            if is_truncated:
                summary_content += "\n\n(Note: This summary was generated based on truncated content)"

            return summary_content if summary_content else None

        except Exception as e:
            logger.exception(f"Content summary processing failed: {e!s}")
            return None

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Generate tool details for frontend display

        Args:
            tool_context: Tool context
            result: Tool result
            arguments: Tool parameters

        Returns:
            Optional[ToolDetail]: Tool details
        """
        if not result.content:
            return None

        try:
            file_name = result.extra_info.get("file_name", "file") if result.extra_info else "file"

            # Return Markdown formatted summary content
            return ToolDetail(
                type=DisplayType.MD,
                data=FileContent(
                    file_name=f"{file_name}_summary.md",
                    content=result.content
                )
            )
        except Exception as e:
            logger.error(f"Generate tool details failed: {e!s}")
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Get friendly action and remark after tool call"""
        if not arguments or "file_path" not in arguments:
            return {
                "action": "Generate summary",
                "remark": "Completed summary generation"
            }

        file_path = arguments["file_path"]
        # Extract file name
        file_name = file_path.split('/')[-1]
        return {
            "action": "Generate summary",
            "remark": f"Generated summary for {file_name}"
        }

    async def _read_file(self, file_path: str) -> Optional[str]:
        """Read file content

        Args:
            tool_context: Tool context
            file_path: File path

        Returns:
            Optional[str]: File content, returns None if read failed
        """
        try:
            read_file_tool = ReadFile()
            read_file_params = ReadFileParams(
                file_path=file_path,
                should_read_entire_file=True
            )

            result = await read_file_tool.execute_purely(read_file_params)

            if result.ok:
                return result.content
            else:
                logger.error(f"Read file {file_path} failed: {result.content}")
                return None
        except Exception as e:
            logger.error(f"Exception occurred while reading file {file_path}: {e}")
            return None
