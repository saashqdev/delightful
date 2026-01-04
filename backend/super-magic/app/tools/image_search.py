import math
import re
from datetime import datetime
from typing import Any, Dict, Optional

import aiohttp
from pydantic import Field

from agentlang.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)


class ImageSearchParams(BaseToolParams):
    """Image search tool parameters"""
    query: str = Field(..., description="Search keywords")
    count: int = Field(default=5, description="Expected number of image results to return", ge=1, le=50)


def _format_bytes(size_bytes: int) -> str:
    """Format byte size to a human-readable string"""
    if size_bytes == 0:
        return "0 B"
    size_name = ("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB")
    i = int(math.floor(math.log(size_bytes, 1024)))
    p = math.pow(1024, i)
    s = round(size_bytes / p, 2)
    return f"{s} {size_name[i]}"


def _format_iso_date(date_str: Optional[str]) -> str:
    """Format ISO 8601 date string to YYYY-MM-DD"""
    if not date_str:
        return "Unknown"
    try:
        # Parse ISO 8601 string containing timezone information
        dt_object = datetime.fromisoformat(date_str.replace('Z', '+00:00'))
        return dt_object.strftime('%Y-%m-%d')
    except ValueError:
        # If parsing fails, try to parse only the date part
        try:
            return datetime.strptime(date_str.split('T')[0], '%Y-%m-%d').strftime('%Y-%m-%d')
        except ValueError:
            logger.warning(f"Unable to parse date string: {date_str}")
            return "Unknown format"


@tool()
class ImageSearch(BaseTool[ImageSearchParams]):
    """
    Search for images on the internet based on keywords and return a list of results containing thumbnails, original image links, sources, and metadata.
    """

    def __init__(self, **data):
        super().__init__(**data)
        # Get API key and base endpoint from unified configuration
        self.api_key = config.get("bing.search_api_key") # Use unified Key configuration
        self.endpoint = config.get("bing.search_endpoint") # Use unified Endpoint configuration

    async def execute(
        self,
        tool_context: ToolContext,
        params: ImageSearchParams
    ) -> ToolResult:
        """Execute image search and return results"""
        return await self.execute_purely(params)

    async def execute_purely(
        self,
        params: ImageSearchParams
    ) -> ToolResult:
        """Core logic for executing image search"""
        # Use self.api_key and self.endpoint
        subscription_key = self.api_key
        endpoint = self.endpoint

        if not subscription_key:
            logger.error("Image search tool configuration error: configuration item 'bing.search_api_key' is not set or is empty")
            return ToolResult(error="Image search tool configuration error")
        if not endpoint:
            logger.error("Image search tool configuration error: configuration item 'bing.search_endpoint' is not set or is empty")
            return ToolResult(error="Image search tool configuration error")

        # Remove trailing slash to ensure correct base URL format
        base_endpoint = endpoint.rstrip('/')
        image_search_url = f"{base_endpoint}/images/search"

        headers = {"Ocp-Apim-Subscription-Key": subscription_key}
        api_params = {"q": params.query, "count": params.count}

        logger.info(f"Starting image search: query='{params.query}', count={params.count}, endpoint='{image_search_url}'")

        try:
            async with aiohttp.ClientSession() as session:
                async with session.get(image_search_url, headers=headers, params=api_params) as response:
                    response.raise_for_status()  # Raise exception for status codes >=400
                    search_results = await response.json()

            logger.info(f"Image search successful: query='{params.query}'")

            estimated_matches = search_results.get("totalEstimatedMatches", 0)
            image_values = search_results.get("value", [])

            markdown_parts = [
                f"## Image Search Results: {params.query}\n",
                f"**Estimated Matches:** {estimated_matches}\n",
                "---",
            ]

            if not image_values:
                 markdown_parts.append("\nNo related images found.")
            else:
                for i, image_data in enumerate(image_values):
                    name = image_data.get("name", "Untitled")
                    thumbnail_url = image_data.get("thumbnailUrl")
                    host_page_url = image_data.get("hostPageUrl")
                    content_url = image_data.get("contentUrl")
                    width = image_data.get("width", "N/A")
                    height = image_data.get("height", "N/A")
                    content_size_bytes = int(image_data.get("contentSize", "0 B").split(' ')[0]) # API returns '12345 B' format
                    encoding_format = image_data.get("encodingFormat", "Unknown")
                    date_published = image_data.get("datePublished") # May be None

                    formatted_size = _format_bytes(content_size_bytes) if isinstance(content_size_bytes, int) and content_size_bytes > 0 else "Unknown"
                    formatted_date = _format_iso_date(date_published)

                    item_md = [
                        f"\n{i+1}. **{name}**"
                    ]
                    if thumbnail_url and host_page_url:
                        item_md.append(f"[![{name}]({thumbnail_url})]({host_page_url})")
                    elif thumbnail_url:
                         item_md.append(f"![{name}]({thumbnail_url})") # Fallback handling, only display image

                    if host_page_url:
                       item_md.append(f"*   **Source Page:** [{host_page_url}]({host_page_url})")
                    if content_url:
                        item_md.append(f"*   **Original Link:** [Click to view]({content_url})")

                    item_md.append(f"*   **Dimensions:** {width}x{height}")
                    item_md.append(f"*   **Size:** {formatted_size}")
                    item_md.append(f"*   **Format:** {encoding_format}")
                    item_md.append(f"*   **Published Date:** {formatted_date}")
                    item_md.append("\n---") # Separator

                    markdown_parts.extend(item_md)

            final_markdown = "\n".join(markdown_parts)

            return ToolResult(
                content=final_markdown,
                extra_info={
                    "query": params.query,
                    "estimated_matches": estimated_matches,
                    "result_count": len(image_values)
                }
            )

        except aiohttp.ClientResponseError as e:
            # Keep detailed logs for developer debugging
            error_details = f"API request failed (status code {e.status}): {e.message}"
            try:
                error_content = await response.text() # Get raw text
                error_json = await response.json(content_type=None) # Try to parse JSON
                if error_json and 'errors' in error_json and error_json['errors']:
                     error_details += f". Error message: {error_json['errors'][0].get('message', 'No details')}"
                elif error_json:
                     error_details += f". Response content: {error_json}"
                else:
                     error_details += f". Response content: {error_content}"
            except Exception as json_e:
                logger.warning(f"Failed to parse Bing API error response: {json_e}")
                error_details += ". Unable to parse detailed error response."
            logger.error(f"Image search API request failed: status={e.status}, message='{e.message}', details={error_details}, query='{params.query}'")
            # Return simplified error message to AI
            return ToolResult(error=f"Image search API request failed (status code {e.status})")
        except aiohttp.ClientError as e:
            logger.error(f"Image search connection error: {e}, query='{params.query}'")
            # Return simplified error message to AI
            return ToolResult(error="Network connection error occurred during image search")
        except Exception as e:
            logger.exception(f"Image search unknown error: {e!s}, query='{params.query}'")
            # Return simplified error message to AI
            return ToolResult(error="Internal error occurred during image search")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """Generate tool details for frontend display"""
        if not result.content or result.error:
            return None

        query = arguments.get("query", "Unknown") if arguments else result.extra_info.get("query", "Unknown")

        try:
            # Clean query to make it suitable for filename
            safe_query = re.sub(r'[\\/*?:"<>|]', '_', query) # Replace illegal filename characters
            file_name = f"image_search_results_{safe_query[:30]}.md" # Limit filename length

            return ToolDetail(
                type=DisplayType.MD,
                data=FileContent(
                    file_name=file_name,
                    content=result.content # Directly use generated Markdown
                )
            )
        except Exception as e:
            logger.error(f"Failed to generate image search tool details: {e!s}")
            return None # Don't display details in exceptional cases

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Get friendly action and remark after tool call"""
        query = arguments.get("query", "") if arguments else result.extra_info.get("query", "")
        if result.error:
            remark = f"Error occurred while searching for images '{query}'"
        elif result.extra_info:
             result_count = result.extra_info.get("result_count", 0)
             remark = f"Completed image search '{query}', found {result_count} related images"
        else:
             remark = f"Completed image search '{query}'" # Fallback handling

        return {
            "action": "Image Search",
            "remark": remark
        }
