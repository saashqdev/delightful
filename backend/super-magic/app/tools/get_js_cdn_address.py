import json
import time

import aiohttp
from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.tools.tool_result import ToolResult
from app.tools.core import BaseTool, BaseToolParams, tool


class GetJsCdnAddressParams(BaseToolParams):
    """Get JS CDN address parameters"""
    library_name: str = Field(
        ...,
        description="JavaScript library name to query, such as react, vue, echarts, etc."
    )


@tool()
class GetJsCdnAddress(BaseTool[GetJsCdnAddressParams]):
    """
    Get the CDN address of the specified JavaScript library from CDNJS
    """

    async def execute(self, tool_context: ToolContext, params: GetJsCdnAddressParams) -> ToolResult:
        """Execute JS CDN tool

        Args:
            tool_context: Tool context
            params: Tool parameters containing library_name

        Returns:
            ToolResult: Tool execution result
        """
        start_time = time.time()

        library_name = params.library_name
        if not library_name:
            return ToolResult(
                error="JavaScript library name not provided",
                execution_time=time.time() - start_time,
            )

        try:
            result = await self._fetch_cdn_info(library_name)
            return ToolResult(
                content=result,
                execution_time=time.time() - start_time,
            )
        except Exception as e:
            return ToolResult(
                error=f"Failed to get CDN address for {library_name} library: {e!s}",
                execution_time=time.time() - start_time,
            )

    async def _fetch_cdn_info(self, library_name: str) -> str:
        """Get JavaScript library CDN information from CDNJS

        Args:
            library_name: JavaScript library name

        Returns:
            str: Information text containing CDN address
        """
        # Fetch data from API
        api_url = f"https://api.cdnjs.com/libraries?search={library_name}&limit=3"

        async with aiohttp.ClientSession() as session:
            async with session.get(api_url) as response:
                if response.status != 200:
                    raise Exception(f"API request failed, status code: {response.status}")

                data = await response.text()
                api_data = json.loads(data)

        # Process search results
        results = api_data.get("results", [])

        # Attempt exact library name match
        exact_matches = [lib for lib in results if lib.get("name") == library_name]
        if exact_matches:
            lib_info = exact_matches[0]
            lib_name = lib_info.get("name", "")
            cdn_url = lib_info.get("latest", "")

            return (
                f"Found CDN for '{library_name}' library: {cdn_url}"
            )

        # If no exact match, show partial matches
        if results:
            result_text = f"No exact match found for '{library_name}', but found the following related JavaScript libraries:\n\n"

            for lib in results:
                lib_name = lib.get("name", "")
                cdn_url = lib.get("latest", "")

                result_text += f"Name: {lib_name}\nCDN Address: {cdn_url}\n\n"

            return result_text

        return f"No CDN information found related to '{library_name}'"
