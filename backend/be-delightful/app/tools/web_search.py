import asyncio
import json
import re
from typing import Any, Dict, List, Optional

import aiohttp
from pydantic import Field

from agentlang.config.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.factory.tool_detail_factory import ToolDetailFactory
from app.core.entity.message.server_message import ToolDetail
from app.core.entity.tool.tool_result import WebSearchToolResult
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)

# Maximum number of search results
MAX_RESULTS = 10


class WebSearchParams(BaseToolParams):
    """Web search parameters"""
    query: List[str] = Field(
        ...,
        description="List of search queries; pass multiple queries to search in parallel. For a single query, pass an array with one element."
    )
    num_results: int = Field(
        10,
        description="Number of results per query (default: 10, max: 20)"
    )
    language: str = Field(
        "zh-CN",
        description="Search language (default: zh-CN)"
    )
    region: str = Field(
        "CN",
        description="Search region (default: CN)"
    )
    safe_search: bool = Field(
        True,
        description="Whether to enable safe search (default: true)"
    )
    time_period: Optional[str] = Field(
        None,
        description="Search time period (optional): day, week, month, year"
    )


# Custom WebSearchAPI implementation replacing langchain_community's WebSearchAPIWrapper
class WebSearchAPI:
    """Custom Bing search API wrapper"""

    def __init__(self, k: int = 10, search_kwargs: dict = None):
        """
        Initialize Bing search API wrapper.

        Args:
            k: Number of results to return
            search_kwargs: Search parameters
        """
        self.k = k
        self.search_kwargs = search_kwargs or {}
        # Get API key and search URL from config instead of environment variables
        self.subscription_key = config.get("bing.search_api_key", "")
        self.search_url = config.get("bing.search_endpoint", "https://api.bing.microsoft.com/v7.0") + "/search"

    async def run(self, query: str) -> str:
        """
        Execute search and return text summary.

        Args:
            query: Search query

        Returns:
            str: Text summary of search results
        """
        search_results = await self._search(query)
        if not search_results:
            return "No results found"

        # Format as text
        result_str = ""
        for i, result in enumerate(search_results, 1):
            result_str += f"{i}. {result['title']}: {result['snippet']}\n"

        return result_str

    async def results(self, query: str, k: int = None) -> List[Dict[str, Any]]:
        """
        Execute search and return structured results.

        Args:
            query: Search query
            k: Number of results, overrides initialized value

        Returns:
            List[Dict[str, Any]]: List of search results
        """
        limit = k if k is not None else self.k
        search_results = await self._search(query, limit)
        return search_results

    async def _search(self, query: str, limit: int = None) -> List[Dict[str, Any]]:
        """
        Execute actual Bing search API call.

        Args:
            query: Search query
            limit: Result count limit

        Returns:
            List[Dict[str, Any]]: List of search results
        """
        if not self.subscription_key:
            raise ValueError("Bing Search API key is required")

        # Set request headers
        headers = {
            "Ocp-Apim-Subscription-Key": self.subscription_key,
            "Accept": "application/json"
        }

        # Set query parameters
        params = {
            "q": query,
            "count": limit or self.k,
            **self.search_kwargs
        }

        # Clean parameters
        for k, v in list(params.items()):
            if v is None:
                del params[k]

        try:
            # Send HTTP request
            async with aiohttp.ClientSession() as session:
                async with session.get(self.search_url, headers=headers, params=params) as response:
                    if response.status != 200:
                        error_detail = await response.text()
                        logger.error(f"Bing Search API requestfailed: {response.status} {error_detail}")
                        return []

                    data = await response.json()

                    # Parse response data
                    if "webPages" not in data or "value" not in data["webPages"]:
                        return []

                    results = []
                    for item in data["webPages"]["value"]:
                        results.append({
                            "title": item.get("name", ""),
                            "link": item.get("url", ""),
                            "snippet": item.get("snippet", "")
                        })

                    return results[:limit or self.k]
        except Exception as e:
            logger.error(f"Bing Search API request error: {e}")
            return []


# Tavily search API wrapper
class TavilySearchAPI:
    """Custom Tavily search API wrapper"""

    def __init__(self, k: int = 10, search_kwargs: dict = None):
        """
        Initialize Tavily search API wrapper.

        Args:
            k: Number of results to return
            search_kwargs: Search parameters
        """
        self.k = k
        self.search_kwargs = search_kwargs or {}
        # Get API key and search URL from config
        self.api_key = config.get("tavily.api_key", "")
        self.api_endpoint = config.get("tavily.api_endpoint", "https://api.tavily.com")
        self.search_endpoint = config.get("tavily.search_endpoint", "/search")
        self.search_url = f"{self.api_endpoint}{self.search_endpoint}"

    async def run(self, query: str) -> str:
        """
        Execute search and return text summary.

        Args:
            query: Search query

        Returns:
            str: Text summary of search results
        """
        search_results = await self._search(query)
        if not search_results or not search_results.get("results"):
            return "No results found"

        # Format as text, including AI-generated answer and search results
        result_str = ""

        # Add AI-generated answer (if any)
        if search_results.get("answer"):
            result_str += f"AI generated answer: {search_results['answer']}\n\nSearch results:\n"

        # Add search results
        for i, result in enumerate(search_results["results"], 1):
            result_str += f"{i}. {result['title']}: {result['content']}\n"

        return result_str

    async def results(self, query: str, k: int = None) -> List[Dict[str, Any]]:
        """
        Execute search and return structured results.

        Args:
            query: Search query
            k: Number of results, overrides initialized value

        Returns:
            List[Dict[str, Any]]: List of search results
        """
        limit = k if k is not None else self.k
        search_results = await self._search(query, limit)

        # Check if results are valid
        if not search_results or not search_results.get("results"):
            return []

        # Convert results to match Bing format
        formatted_results = []
        for item in search_results["results"]:
            formatted_results.append({
                "title": item.get("title", ""),
                "link": item.get("url", ""),
                "snippet": item.get("content", ""),
                "domain": self._extract_domain(item.get("url", "")),
                "icon_url": self._get_favicon_url(self._extract_domain(item.get("url", "")))
            })

        return formatted_results

    async def _search(self, query: str, limit: int = None) -> Dict[str, Any]:
        """
        Execute actual Tavily search API call.

        Args:
            query: Search query
            limit: Result count limit

        Returns:
            Dict[str, Any]: Search result
        """
        if not self.api_key:
            raise ValueError("Tavily Search API key is required")

        # Set request headers
        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }

        # Set request payload
        data = {
            "query": query,
            "max_results": limit or self.k,
            "include_answer": True,
            "search_depth": "basic",
            **self.search_kwargs
        }

        try:
            # Send HTTP request
            async with aiohttp.ClientSession() as session:
                async with session.post(self.search_url, headers=headers, json=data) as response:
                    if response.status != 200:
                        error_detail = await response.text()
                        logger.error(f"Tavily Search API requestfailed: {response.status} {error_detail}")
                        return {}

                    return await response.json()
        except Exception as e:
            logger.error(f"Tavily Search API request error: {e}")
            return {}

    def _extract_domain(self, url: str) -> str:
        """Extract domain from URL"""
        try:
            domain = re.search(r"https?://([^/]+)", url)
            if domain:
                return domain.group(1)
            return url
        except Exception:
            return url

    def _get_favicon_url(self, domain: str) -> str:
        """Generate website favicon URL"""
        return f"https://{domain}/favicon.ico"


@tool()
class WebSearch(BaseTool[WebSearchParams]):
    """
    Internet search tool for web queries.
    Supports multiple queries in parallel; parallel searches improve efficiency.
    Per information-gathering rules, search summaries are not authoritative; use the browser to open original pages for complete information.
    Results include title, URL, summary, and source site.

    Use cases:
    - Find latest information and news
    - Search references on specific topics
    - Query facts and data
    - Look up solutions and tutorials
    - Search multiple related topics in parallel to gather diverse information

    Notes:
    - Search results are only leads; visit original pages via browser tool for full information
    - Gather info from multiple results for cross-validation
    - For complex queries, break into simpler ones and leverage parallel searches
    """

    def __init__(self, **data):
        super().__init__(**data)
        # Load API keys and endpoints
        # Bing search config
        self.bing_api_key = config.get("bing.search_api_key", default="")
        self.bing_endpoint = config.get("bing.search_endpoint", default="https://api.bing.microsoft.com/v7.0")

        # Tavily search config
        self.tavily_api_key = config.get("tavily.api_key", default="")
        self.tavily_endpoint = config.get("tavily.api_endpoint", default="https://api.tavily.com")

        # Default search engine
        self.default_engine = config.get("search.default_engine", default="bing").lower()

        # Decide which engine to use based on config and availability
        self.use_tavily = False

        # 1. If default is tavily and tavily config is valid, use tavily
        if self.default_engine == "tavily" and self.tavily_api_key:
            self.use_tavily = True
        # 2. If default is bing and bing config is valid, use bing
        elif self.default_engine == "bing" and self.bing_api_key:
            self.use_tavily = False
        # 3. If bing unavailable but tavily available, use tavily
        elif not self.bing_api_key and self.tavily_api_key:
            self.use_tavily = True

        logger.info(f"Search tool initialized, engine: {'Tavily' if self.use_tavily else 'Bing'}")

    def is_available(self) -> bool:
        """
        Check whether search tool is available.

        Validates API keys and endpoints for the configured search API.

        Returns:
            bool: True if available, else False
        """
        # Check availability based on configured search engine
        if self.use_tavily:
            # Check Tavily availability
            if self.tavily_api_key and self.tavily_endpoint:
                return True

            # Tavily unavailable but configured; attempt fallback to Bing
            if self.default_engine == "tavily":
                logger.warning("Configured Tavily search unavailable; attempting Bing as fallback")
                if self.bing_api_key and self.bing_endpoint:
                    self.use_tavily = False
                    return True
        else:
            # Check Bing availability
            if self.bing_api_key and self.bing_endpoint:
                return True

            # Bing unavailable but configured; attempt fallback to Tavily
            if self.default_engine == "bing":
                logger.warning("Configured Bing search unavailable; attempting Tavily as fallback")
                if self.tavily_api_key and self.tavily_endpoint:
                    self.use_tavily = True
                    return True

        # Log errors
        if self.use_tavily:
            if not self.tavily_api_key:
                logger.warning("Tavily search API key not configured")
            elif not self.tavily_endpoint:
                logger.warning("Tavily search API endpoint not configured")
        else:
            if not self.bing_api_key:
                logger.warning("Bing search API key not configured")
            elif not self.bing_endpoint:
                logger.warning("Bing search API endpoint not configured")

        # Check for any available search engine as fallback
        if not self.use_tavily and not self.bing_api_key:
            if self.tavily_api_key and self.tavily_endpoint:
                logger.info("Using Tavily search as fallback")
                self.use_tavily = True
                return True
        elif self.use_tavily and not self.tavily_api_key:
            if self.bing_api_key and self.bing_endpoint:
                logger.info("Using Bing search as fallback")
                self.use_tavily = False
                return True

        return False

    async def execute(
        self,
        tool_context: ToolContext,
        params: WebSearchParams
    ) -> ToolResult:
        """
        Execute search and return formatted result.

        Args:
            tool_context: tool context
            params: Search parameters object

        Returns:
            WebSearchToolResult: Tool result containing search results
        """
        try:
            # getparameters
            query = params.query
            num_results = params.num_results
            language = params.language
            region = params.region
            safe_search = params.safe_search
            time_period = params.time_period

            # Validate parameters
            if not query:
                return WebSearchToolResult(content="Search query cannot be empty")

            if num_results > MAX_RESULTS:
                num_results = MAX_RESULTS

            # Log search request
            api_type = "Tavily" if self.use_tavily else "Bing"
            logger.info(f"Executing {api_type} web search: query_count={len(query)}, results_per_query={num_results}")

            # Execute all queries in parallel
            tasks = [
                self._perform_search(
                    query=q,
                    num_results=num_results,
                    language=language,
                    region=region,
                    safe_search=safe_search,
                    time_period=time_period,
                )
                for q in query
            ]
            all_results = await asyncio.gather(*tasks)

            # Build structured result
            result = self._handle_queries_results(query, all_results)

            if len(query) > 1:
                message = f"Searched individually for: {', '.join(query)}"
            else:
                message = f"Searched: {query[0]}"
            # Set output text
            output_dict = {
                "message": message,
                "results": result.output_results_to_dict()
            }
            result.content = json.dumps(output_dict, ensure_ascii=False)

            return result

        except Exception as e:
            logger.exception(f"Search action failed: {e!s}")
            return WebSearchToolResult(error=f"Search action failed: {e!s}")

    def _handle_queries_results(self, queries: List[str], all_results: List[List[Dict[str, Any]]]) -> WebSearchToolResult:
        """
        Format search results for multiple queries

        Args:
            queries: List of query strings
            all_results: Search results list per query

        Returns:
            WebSearchToolResult: Tool result containing formatted search results
        """
        result = WebSearchToolResult(content="")

        # Format all results
        for q, search_results in zip(queries, all_results):
            result.set_output_results(q, search_results)
            result.set_search_results(q, search_results)

        return result

    async def _perform_search(
        self, query: str, num_results: int, language: str, region: str, safe_search: bool, time_period: Optional[str]
    ) -> List[Dict[str, Any]]:
        """Perform actual search request using Bing or Tavily based on configuration."""

        # Decide engine
        if self.use_tavily:
            return await self._perform_tavily_search(
                query=query,
                num_results=num_results,
                language=language,
                region=region,
                safe_search=safe_search,
                time_period=time_period
            )
        else:
            return await self._perform_bing_search(
                query=query,
                num_results=num_results,
                language=language,
                region=region,
                safe_search=safe_search,
                time_period=time_period
            )

    async def _perform_bing_search(
        self, query: str, num_results: int, language: str, region: str, safe_search: bool, time_period: Optional[str]
    ) -> List[Dict[str, Any]]:
        """Execute Bing search request"""
        # Set search parameters
        search_params = {
            "count": num_results,
            "setLang": language,
            "mkt": f"{language}-{region}",
        }

        # Set safe search
        if safe_search:
            search_params["safeSearch"] = "Strict"
        else:
            search_params["safeSearch"] = "Off"

        # Set time period
        if time_period:
            if time_period == "day":
                search_params["freshness"] = "Day"
            elif time_period == "week":
                search_params["freshness"] = "Week"
            elif time_period == "month":
                search_params["freshness"] = "Month"

        try:
            # Create WebSearchAPI instance
            search = WebSearchAPI(
                k=num_results,  # Number of results to return
                search_kwargs={
                    "mkt": f"{language}-{region}",  # Set region
                    "setLang": language,  # Set language
                },
            )

            # Execute search request
            # Get structured results
            search_results = await search.results(query, num_results)

            # Enrich results with source domain and favicon
            for item in search_results:
                # Extract domain (source site)
                domain = self._extract_domain(item["link"])
                item["domain"] = domain
                item["icon_url"] = self._get_favicon_url(domain)

            return search_results

        except Exception as e:
            logger.error(f"Bing search API request failed: {e!s}")
            return []  # Return empty results

    async def _perform_tavily_search(
        self, query: str, num_results: int, language: str, region: str, safe_search: bool, time_period: Optional[str]
    ) -> List[Dict[str, Any]]:
        """Execute Tavily search request"""
        # Set search parameters
        search_kwargs = {}

        # Set time period
        if time_period:
            if time_period == "day":
                search_kwargs["days"] = 1
            elif time_period == "week":
                search_kwargs["days"] = 7
            elif time_period == "month":
                search_kwargs["days"] = 30

        try:
            # Create TavilySearchAPI instance
            search = TavilySearchAPI(
                k=num_results,  # Number of results to return
                search_kwargs=search_kwargs
            )

            # Execute search request
            search_results = await search.results(query, num_results)
            return search_results

        except Exception as e:
            logger.error(f"Tavily search API request failed: {e!s}")
            return []  # Return empty results

    def _extract_domain(self, url: str) -> str:
        """Extract domain from URL"""
        try:
            domain = re.search(r"https?://([^/]+)", url)
            if domain:
                return domain.group(1)
            return url
        except Exception:
            return url

    def _get_favicon_url(self, domain: str) -> str:
        """Generate website favicon URL"""
        return f"https://{domain}/favicon.ico"

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Generate tool detail for frontend display

        Args:
            tool_context: tool context
            result: Tool result
            arguments: Tool parameters

        Returns:
            Optional[ToolDetail]: Tool detail
        """
        if not result.content:
            return None

        try:
            if not isinstance(result, WebSearchToolResult):
                return None

            # Use factory to create display detail
            return ToolDetailFactory.create_search_detail_from_search_results(
                search_results=result.search_results,
            )
        except Exception as e:
            logger.error(f"Generate tool detail failed: {e!s}")
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Friendly action/remark after tool call"""
        if not arguments or "query" not in arguments:
            return {
                "action": "Web search",
                "remark": "Search completed"
            }

        query = arguments["query"]
        if len(query) > 1:
            return {
                "action": "Web search",
                "remark": f"Searched: {', '.join(query)}"
            }
        return {
            "action": "Web search",
            "remark": f"Searched: {query[0]}"
        }
