"""Tool detail factory module"""

from typing import Dict, List

from app.core.entity.message.server_message import (
    BrowserContent,
    DeepWriteContent,
    DisplayType,
    SearchContent,
    SearchGroupItem,
    SearchResultItem,
    TerminalContent,
    ToolDetail,
)
from app.core.entity.tool.tool_result import SearchResult


class ToolDetailFactory:
    """Tool detail factory class, used to create different types of ToolDetail objects"""

    @staticmethod
    def create_search_detail_from_search_results(search_results: Dict[str, List[SearchResult]]) -> ToolDetail:
        """Create search type tool detail from SearchResult objects

        Args:
            search_results: Search results dictionary, key is query term, value is list of SearchResult objects

        Returns:
            ToolDetail: Search type tool detail
        """
        # Create multiple groups of search results
        search_groups = []

        for query, results in search_results.items():
            result_items = [
                SearchResultItem(
                    title=item.title,
                    url=item.url,
                    snippet=item.snippet or "",
                    icon_url=item.icon_url  # Add icon URL
                ) for item in results
            ]

            # Create one keyword and its corresponding results as a SearchGroupItem
            group = SearchGroupItem(keyword=query, results=result_items)

            search_groups.append(group)

        return ToolDetail(type=DisplayType.SEARCH, data=SearchContent(groups=search_groups))

    @staticmethod
    def create_terminal_detail(command: str, output: str, exit_code: int) -> ToolDetail:
        """Create terminal type tool detail

        Args:
            command: Terminal command executed
            output: Terminal output content
            exit_code: Command execution exit code

        Returns:
            ToolDetail: Terminal type tool detail
        """
        return ToolDetail(
            type=DisplayType.TERMINAL, data=TerminalContent(command=command, output=output, exit_code=exit_code)
        )

    @staticmethod
    def create_use_browser_detail(oss_key: str, title: str, url: str = "") -> ToolDetail:
        """Create browser screenshot type tool detail

        Args:
            oss_key: Image key in object storage
            title: Screenshot title
            url: Web URL (optional)

        Returns:
            ToolDetail: Browser type tool detail with screenshot
        """
        return ToolDetail(type=DisplayType.BROWSER, data=BrowserContent(url=url, title=title, file_key=oss_key))

    @staticmethod
    def create_deep_write_detail(title: str, reasoning_content: str, content: str) -> ToolDetail:
        """Create deep write type tool detail

        Args:
            title: Deep write title
            reasoning_content: Deep write process content
            content: Deep write conclusion

        Returns:
            ToolDetail: Deep write type tool detail
        """
        return ToolDetail(
            type=DisplayType.MD,  # Use Markdown format for display
            data=DeepWriteContent(
                title=title,
                reasoning_content=reasoning_content,
                content=content
            )
        )
