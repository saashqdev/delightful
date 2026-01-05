import base64
import os
import re
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Optional

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.llms.factory import LLMFactory
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)


class VisualUnderstandingParams(BaseToolParams):
    """Visual understanding tool parameters"""
    images: List[str] = Field(
        ...,
        description="List of image sources: URLs or local file paths. Multiple images supported"
    )
    query: str = Field(
        ...,
        description="Question or request about the images"
    )


@tool()
class VisualUnderstanding(BaseTool[VisualUnderstandingParams]):
    """
    Visual understanding tool for analyzing and explaining image content.

    Suitable scenarios:
    - Image content recognition and description
    - Chart analysis and interpretation
    - Visual scene understanding and explanation
    - OCR: recognize and extract text from images
    - Multi-image comparison

    Requirements:
    - Provide image paths or URLs (multiple images supported)
    - Provide specific questions or requests about the images

    Example calls:
    ```
    {
        "images": ["https://example.com/image1.jpg", "https://example.com/image2.jpg"],
        "query": "What are the similarities and differences in these images? Please describe in detail."
    }
    ```

    or
    ```
    {
        "images": ["./webview_reports/image1.jpg", "./webview_reports/image2.jpg"],
        "query": "Recognize the text in these images and compare it."
    }
    ```
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: VisualUnderstandingParams
    ) -> ToolResult:
        """Execute visual understanding and return result.

        Args:
            tool_context: tool context
            params: Visual understanding parameters object

        Returns:
            ToolResult: Tool result containing visual understanding output
        """
        return await self.execute_purely(params)

    async def execute_purely(
        self,
        params: VisualUnderstandingParams
    ) -> ToolResult:
        """Execute visual understanding and return result.

        Args:
            params: Visual understanding parameters object

        Returns:
            ToolResult: Tool result containing visual understanding output
        """
        try:
            # getparameters
            images = params.images
            query = params.query
            # Fixed value, may be parameterized later
            model_id = "doubao-1.5-vision-pro-32k"

            # Log visual understanding request
            logger.info(f"Executing visual understanding: image_count={len(images)}, query={query}, model={model_id}")

            # Process all image sources
            image_data_list = []
            for image_source in images:
                image_data = await self._process_image_source(image_source)
                if not image_data:
                    return ToolResult(error=f"Failed to process image source: {image_source}")
                image_data_list.append(image_data)

            # Get and format current time context
            current_time_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S Weekday {} (Week #%W)".format(
                ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"][datetime.now().weekday()]))

            # Build message content
            content = [
                {
                    "type": "text",
                    "text": query
                }
            ]

            # Add all images
            for i, image_data in enumerate(image_data_list):
                if i > 0:
                    content.append({
                        "type": "text",
                        "text": f"Image {i+1}:"
                    })
                content.append({
                    "type": "image_url",
                    "image_url": image_data
                })

            # Build messages
            messages = [
                {
                    "role": "system",
                    "content": "You are a professional visual understanding assistant, skilled at analyzing and explaining image content. Current time: " + current_time_str
                },
                {
                    "role": "user",
                    "content": content
                }
            ]

            # Request model
            response = await LLMFactory.call_with_tool_support(
                model_id=model_id,
                messages=messages,
                tools=None,  # No tool support needed
                stop=None
            )

            # Process response
            if not response or not response.choices or len(response.choices) == 0:
                return ToolResult(error="No valid response from model")

            # Get content
            content = response.choices[0].message.content

            # Extract descriptive info of image sources for display
            image_source_names = [self._extract_image_source_name(image) for image in images]

            # Build result
            result = ToolResult(
                content=content,
                extra_info={
                    "images": images,
                    "image_source_names": image_source_names,
                    "image_count": len(images)
                }
            )

            return result

        except Exception as e:
            logger.exception(f"Visual understanding failed: {e!s}")
            return ToolResult(error=f"Visual understanding failed: {e!s}")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Generate tool details for frontend display

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
            image_count = result.extra_info.get("image_count", 0) if result.extra_info else 0

            title = "Image visual understanding"
            if image_count == 1:
                image_source_name = result.extra_info.get("image_source_names", ["image"])[0] if result.extra_info else "image"
                title = f"Image visual understanding: {image_source_name}"
            elif image_count > 1:
                title = f"Multi-image visual understanding ({image_count} images)"

            # Return Markdown result
            return ToolDetail(
                type=DisplayType.MD,
                data=FileContent(
                    file_name="visual_understanding_result.md",
                    content=f"## {title}\n\n{result.content}"
                )
            )
        except Exception as e:
            logger.error(f"Generate tool detail failed: {e!s}")
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Friendly action/remark after tool call"""
        if not arguments or "images" not in arguments:
            return {
                "action": "Visual understanding",
                "remark": "Image understanding completed"
            }

        images = arguments["images"]
        image_count = len(images) if isinstance(images, list) else 1

        if image_count == 1:
            image_source_name = self._extract_image_source_name(images[0])
            return {
                "action": "Visual understanding",
                "remark": f"Understood image: {image_source_name}"
            }
        else:
            return {
                "action": "Visual understanding",
                "remark": f"Understood {image_count} images"
            }

    async def _process_image_source(self, image_source: str) -> Optional[Dict[str, str]]:
        """Process a single image source and convert to model-accepted format.

        Args:
            image_source: Image URL or local file path

        Returns:
            Optional[Dict[str, str]]: Processed image data in format {"url": url_or_base64}
        """
        try:
            # Check if it is a URL
            if re.match(r'^https?://', image_source):
                # URL: return as-is
                return {"url": image_source}
            else:
                # Local file: convert to base64
                return {"url": self._local_file_to_base64(image_source)}
        except Exception as e:
            logger.error(f"Processing image source failed: {e!s}")
            return None

    def _local_file_to_base64(self, file_path: str) -> str:
        """Convert local file to base64-encoded data.

        Args:
            file_path: Local file path

        Returns:
            str: Base64-encoded image data with mime prefix
        """
        # Ensure absolute path
        if not os.path.isabs(file_path):
            file_path = os.path.abspath(file_path)

        # Get file extension
        file_ext = Path(file_path).suffix.lower().lstrip('.')
        # Map file extension to MIME type
        mime_types = {
            'png': 'image/png',
            'jpg': 'image/jpeg',
            'jpeg': 'image/jpeg',
            'gif': 'image/gif',
            'webp': 'image/webp',
            'bmp': 'image/bmp',
        }
        mime_type = mime_types.get(file_ext, 'image/jpeg')

        # Read file and encode base64
        with open(file_path, "rb") as image_file:
            base64_data = base64.b64encode(image_file.read()).decode('utf-8')

        # Return base64 data with mime prefix
        return f"data:{mime_type};base64,{base64_data}"

    def _extract_image_source_name(self, image_source: str) -> str:
        """Extract display name from image source.

        Args:
            image_source: Image URL or local file path

        Returns:
            str: Display name for the image
        """
        if re.match(r'^https?://', image_source):
            # URL: extract filename from URL
            file_name = image_source.split('/')[-1].split('?')[0]
            return file_name if file_name else "web_image"
        else:
            # Local file: extract filename
            return os.path.basename(image_source)
