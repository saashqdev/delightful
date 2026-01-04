import subprocess
from typing import Any, Dict, List, Optional

from pydantic import BaseModel, Field

from agentlang.tools.tool_result import ToolResult


class SearchResult(BaseModel):
    """Single search result item."""
    title: str
    url: str
    snippet: Optional[str] = None
    source: Optional[str] = None
    icon_url: Optional[str] = None  # Website favicon URL


class WebSearchToolResult(ToolResult):
    """Structured result for Bing search tool."""
    # Search results returned to the LLM
    output_results: Dict[str, List[SearchResult]] = Field(default_factory=dict)
    # Search results returned to the client
    search_results: Dict[str, List[SearchResult]] = Field(default_factory=dict)

    def set_output_results(self, query: str, results: List[Dict[str, Any]]) -> None:
        """Convert raw search results to structured ``output_results``.

        Args:
            query: Search query string
            results: Raw search result list
        """
        if query not in self.output_results:
            self.output_results[query] = []

        for result in results:
            search_result = SearchResult(
                title=result.get("title", ""),
                url=result.get("link", ""),
            )
            self.output_results[query].append(search_result)

    def set_search_results(self, query: str, results: List[Dict[str, Any]]) -> None:
        """Convert raw search results to structured ``search_results``.

        Args:
            query: Search query string
            results: Raw search result list
        """
        if query not in self.search_results:
            self.search_results[query] = []

        for result in results:
            search_result = SearchResult(
                title=result.get("title", ""),
                url=result.get("link", ""),
                snippet=result.get("snippet"),
                source=result.get("source"),
                icon_url=result.get("icon_url", "")  # Include icon URL for client display only
            )
            self.search_results[query].append(search_result)

    def add_query_results(self, query: str, results: List[SearchResult]) -> None:
        """Add query results to ``search_results``.

        Args:
            query: Search query string
            results: Structured search result list
        """
        self.search_results[query] = results

    def output_results_to_dict(self) -> Dict[str, List[Dict[str, Any]]]:
        """Convert ``output_results`` to dictionary format.

        Returns:
            Dict[str, List[Dict[str, Any]]]: Converted dictionary
        """
        result_dict = {}
        for k, v in self.output_results.items():
            result_dict[k] = []
            for r in v:
                # Convert to dict
                item_dict = r.model_dump()
                # Remove empty fields
                if item_dict.get("snippet") is None:
                    item_dict.pop("snippet", None)
                if item_dict.get("source") is None:
                    item_dict.pop("source", None)
                if item_dict.get("icon_url") is None:
                    item_dict.pop("icon_url", None)
                result_dict[k].append(item_dict)
        return result_dict

class TerminalToolResult(ToolResult):
    """Structured result for terminal command execution tool."""
    command: str = Field(default="", description="Executed terminal command")
    exit_code: int = Field(default=0, description="Exit code; 0 indicates success")

    def set_command(self, command: str) -> None:
        """Set the executed terminal command.

        Args:
            command: Terminal command
        """
        self.command = command

    def set_exit_code(self, exit_code: int) -> None:
        """Set the exit code.

        Args:
            exit_code: Exit code, usually 0 means success
        """
        self.exit_code = exit_code

    def _handle_terminal_result(self, process_result: subprocess.CompletedProcess, command: str) -> None:
        """Populate properties from subprocess execution result.

        Args:
            process_result: ``subprocess.CompletedProcess`` object
            command: Executed command
        """
        self.command = command
        self.exit_code = process_result.returncode

        # Handle output
        if process_result.stdout:
            self.content = process_result.stdout.strip()

        if process_result.stderr:
            self.content = process_result.stderr.strip()


class BrowserToolResult(ToolResult):
    """Structured result for browser tool."""
    url: Optional[str] = Field(default=None, description="Visited URL")
    operation: str = Field(default="", description="Performed browser operation")
    oss_key: Optional[str] = Field(default=None, description="Object storage key for screenshot")
    title: Optional[str] = Field(default=None, description="Page title")


class WebpageToolResult(ToolResult):
    """Structured result for webpage tools (e.g., Zhihu, Xiaohongshu)."""
    url: Optional[str] = Field(default=None, description="Original content URL")
    title: Optional[str] = Field(default=None, description="Content title")

    def set_url(self, url: str) -> None:
        """Set content URL.

        Args:
            url: Original content URL
        """
        self.url = url

    def set_title(self, title: str) -> None:
        """Set content title.

        Args:
            title: Content title
        """
        self.title = title


class DeepWriteToolResult(ToolResult):
    """Structured result for deep writing tool (deepseek-reasoner output)."""
    reasoning_content: Optional[str] = Field(default=None, description="Detailed reasoning process content")

    def set_reasoning_content(self, reasoning_content: str) -> None:
        """Set reasoning process content.

        Args:
            reasoning_content: Detailed reasoning process content
        """
        self.reasoning_content = reasoning_content


class YFinanceToolResult(ToolResult):
    """Structured result for financial data tool storing YFinance queries."""

    ticker: Optional[str] = Field(default=None, description="Stock ticker")
    query_type: Optional[str] = Field(default=None, description="Query type, e.g., history, info, news")
    time_period: Optional[str] = Field(default=None, description="Time range for the query")

    def set_ticker(self, ticker: str) -> None:
        """Set stock ticker.

        Args:
            ticker: Stock ticker
        """
        self.ticker = ticker

    def set_query_type(self, query_type: str) -> None:
        """Set query type.

        Args:
            query_type: Query type, e.g., history, info, news
        """
        self.query_type = query_type

    def set_time_period(self, time_period: str) -> None:
        """Set time range for the query.

        Args:
            time_period: Time window, e.g., 1d, 5d, 1mo, 3mo, 6mo, 1y, 2y, 5y, 10y, ytd, max
        """
        self.time_period = time_period

class AskUserToolResult(ToolResult):
    """Structured result for asking the user a question and awaiting reply."""

    question: str = Field(
        ...,  # Required field
        description="Question or request presented to the user"
    )

    type: Optional[str] = Field(
        default=None,
        description="Content type, e.g., 'todo'"
    )

    content: Optional[str] = Field(
        default=None,
        description="Content related to the question"
    )

    def set_question(self, question: str) -> None:
        """Set the question content.

        Args:
            question: Question presented to the user
        """
        self.question = question

    def set_type(self, type: str) -> None:
        """Set content type.

        Args:
            type: Content type, e.g., 'todo'
        """
        self.type = type

    def set_content(self, content: str) -> None:
        """Set related content.

        Args:
            content: Content related to the question
        """
        self.content = content

class ImageToolResult(ToolResult):
    """Structured result for image tool."""
    image_url: Optional[str] = Field(default=None, description="Image URL (deprecated; use images)")
    images: List[str] = Field(default_factory=list, description="List of image URLs")

    def set_image_url(self, image_url: str) -> None:
        """Set single image URL (deprecated; use set_images).

        Args:
            image_url: Image URL
        """
        self.image_url = image_url
        # Keep backward compatibility by also adding to images
        if image_url and image_url not in self.images:
            self.images.append(image_url)

    def set_images(self, images: List[str]) -> None:
        """Set multiple image URLs.

        Args:
            images: List of image URLs
        """
        self.images = images
        # Update image_url for backward compatibility
        if images:
            self.image_url = images[0]
