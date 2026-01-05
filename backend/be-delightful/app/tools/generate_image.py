"""
Image Generation Tool

This module provides the ability to call image generation services, generating AI images through HTTP requests.

Supported features:
- Generate AI images (generate_image)

Last updated: 2024-09-20
"""

import asyncio
import json
import os
from collections import defaultdict
from typing import Any, Dict, List, Optional

import aiohttp
from pydantic import Field

from agentlang.config.config import config
from agentlang.context.tool_context import ToolContext
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.context.agent_context import AgentContext
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.core.entity.tool.tool_result import ImageToolResult
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class GenerateImageParams(BaseToolParams):
    """Image generation parameters"""
    message: str = Field(
        ...,
        description="Image generation prompt describing the desired image content"
    )
    generated_file_name: str = Field(
        ...,
        description="Generated image file name (without extension) for saving locally"
    )
    output_path: str = Field(
        "generated_images",
        description="Image save directory, path relative to workspace root, defaults to generated_images"
    )
    override: bool = Field(
        False,
        description="Whether to override if file already exists"
    )


@tool()
class GenerateImage(WorkspaceGuardTool[GenerateImageParams]):
    """
    AI image generation tool that generates images based on text descriptions
    """

    # Record the number of images generated per conversation
    _generation_counts = defaultdict(int)

    # Maximum number of images to generate per conversation
    MAX_IMAGES_PER_CONVERSATION = 30

    def __init__(self, **data):
        super().__init__(**data)

    @property
    def api_url(self) -> str:
        """Get API URL"""
        api_url = config.get("image_generator.api_url")
        if not api_url:
            raise ValueError("Image generation service API URL not configured")
        return api_url

    @property
    def api_key(self) -> str:
        """Get API key"""
        api_key = config.get("image_generator.api_key")
        if not api_key:
            raise ValueError("Image generation service API key not configured")
        return api_key

    @property
    def headers(self) -> Dict[str, str]:
        """Get HTTP request headers"""
        return {
            "api-key": self.api_key,
            "Content-Type": "application/json"
        }

    async def _generate_image(self, message: str, conversation_id: str = "") -> List[str]:
        """
        Call API to generate images

        Args:
            message: Generation prompt
            conversation_id: Conversation ID

        Returns:
            List[str]: List of generated image URLs
        """
        payload = {
            "message": message,
            "conversation_id": conversation_id
        }

        logger.info(f"Requesting image generation service: message={message}, conversation_id={conversation_id}")

        try:
            async with aiohttp.ClientSession() as session:
                logger.debug(f"Starting image generation API call: {self.api_url}")
                async with session.post(
                    self.api_url,
                    headers=self.headers,
                    json=payload,
                    timeout=60  # Increase timeout as image generation may take longer
                ) as response:
                    if response.status != 200:
                        error_text = await response.text()
                        logger.error(f"Image generation request failed, status code: {response.status}, response: {error_text}")
                        raise Exception(f"Image generation request failed, status code: {response.status}, response: {error_text[:200]}")

                    result = await response.json()
                    logger.debug(f"Image generation API returned result: {result}")

                    if result.get("code") != 1000:
                        error_msg = result.get("message", "Unknown error")
                        logger.error(f"Image generation API call failed: {result}")
                        raise Exception(f"Image generation API call failed: {error_msg}")

                    # Parse return result
                    data = result.get("data", {})
                    messages = data.get("messages", [])

                    if not messages:
                        logger.warning("Image generation succeeded but no messages returned")
                        return []

                    # Extract image URLs from messages
                    last_message = messages[-1]
                    content = last_message.get("message", {}).get("content", "")
                    logger.debug(f"Received image content: {content}")

                    # Content may be a JSON string formatted list of URLs
                    try:
                        image_urls = json.loads(content)
                        if isinstance(image_urls, list):
                            # Unescape URLs
                            image_urls = [url.replace("\\", "") for url in image_urls]
                            logger.info(f"Successfully parsed image URLs: {image_urls}")
                            return image_urls
                    except json.JSONDecodeError as e:
                        logger.warning(f"Unable to parse image URL content as JSON: {content}, error: {e}")

                    # If content is a string but not valid JSON, try to return it as a single URL
                    if isinstance(content, str) and content.startswith(('http://', 'https://')):
                        logger.info(f"Using content as single URL: {content}")
                        return [content]

                    # If content is not URL format, try to find URLs in content
                    if isinstance(content, str):
                        # Try to find URLs using regex
                        import re
                        urls = re.findall(r'https?://[^\s"\']+', content)
                        if urls:
                            logger.info(f"Extracted URLs from content: {urls}")
                            return urls

                    logger.warning(f"Unable to extract image URLs from content: {content}")
                    return []

        except aiohttp.ClientError as e:
            logger.error(f"HTTP request error: {e}")
            raise Exception(f"Image generation request network error: {e}")
        except json.JSONDecodeError as e:
            logger.error(f"Failed to parse response JSON: {e}")
            raise Exception(f"Failed to parse image generation response: {e}")
        except Exception as e:
            logger.error(f"Image generation request exception: {e}")
            raise

    def _get_conversation_id_from_context(self, tool_context: ToolContext) -> str:
        """
        Get conversation ID from tool context

        Args:
            tool_context: Tool context

        Returns:
            str: Conversation ID
        """
        # Try to get chat_client_message from agent_context
        if hasattr(tool_context, 'agent_context') and tool_context.get_extension_typed("agent_context", AgentContext):
            agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
            if agent_context:
                chat_msg = agent_context.get_chat_client_message()
                if chat_msg and hasattr(chat_msg, 'message_id'):
                    return chat_msg.message_id

        # If unable to get, return empty string
        return ""

    async def _download_image(self, url: str, save_dir: str, custom_filename: str, should_override: bool = False) -> str:
        """
        Download image and save to specified directory

        Args:
            url: Image URL
            save_dir: Save directory (relative to workspace)
            custom_filename: Custom file name (without extension)
            should_override: Whether to override existing file

        Returns:
            str: Saved image path
        """
        # Check if URL is valid
        if not url or not url.startswith(('http://', 'https://')):
            raise ValueError(f"Invalid URL format: {url}")

        # URL may contain escape characters, process them first
        url = url.replace('\\', '')

        # Remove quotes
        url = url.strip('"\'')

        logger.debug(f"Processed URL: {url}")

        # Use get_safe_path to ensure path is within workspace
        save_path_str = os.path.join(save_dir, f"{custom_filename}.jpg")
        save_path, error = self.get_safe_path(save_path_str)

        if error:
            raise ValueError(error)

        # Ensure save directory exists
        save_path.parent.mkdir(parents=True, exist_ok=True)

        # Handle file name conflicts
        if save_path.exists() and not should_override:
            counter = 1
            while True:
                new_filename = f"{custom_filename}_{counter}.jpg"
                new_path_str = os.path.join(save_dir, new_filename)
                new_path, new_error = self.get_safe_path(new_path_str)

                if new_error:
                    raise ValueError(new_error)

                if not new_path.exists():
                    save_path = new_path
                    break
                counter += 1

        try:
            async with aiohttp.ClientSession() as session:
                # Add user agent header to avoid restrictions on some websites
                headers = {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                }

                # Add timeout and retry mechanism
                retry_count = 3
                current_try = 0
                last_error = None

                while current_try < retry_count:
                    try:
                        current_try += 1
                        logger.debug(f"Attempting to download image (attempt {current_try}): {url}")

                        async with session.get(url, headers=headers, allow_redirects=True, timeout=30) as response:
                            if response.status != 200:
                                logger.error(f"Image download failed, status code: {response.status}, URL: {url}")
                                raise Exception(f"Image download failed, status code: {response.status}")

                            # Check Content-Type to ensure it's an image
                            content_type = response.headers.get('Content-Type', '')
                            if not content_type.startswith('image/'):
                                logger.warning(f"Response is not an image type, Content-Type: {content_type}, URL: {url}")
                                # Continue trying, some servers may not set Content-Type correctly

                            image_data = await response.read()

                            if not image_data:
                                raise Exception("Downloaded image data is empty")

                            # Save image
                            with open(save_path, "wb") as f:
                                f.write(image_data)

                            logger.info(f"Image saved to: {save_path}")
                            return str(save_path)

                    except aiohttp.ClientError as e:
                        last_error = e
                        logger.warning(f"Download attempt {current_try}/{retry_count} failed: {e}, will retry...")
                        await asyncio.sleep(1)  # Wait one second before retrying

                # All retries failed
                raise Exception(f"Image download failed after {retry_count} attempts: {last_error}")

        except aiohttp.ClientError as e:
            logger.error(f"Image download failed (network error): {e}, URL: {url}")
            raise Exception(f"Image download failed (network error): {e}")
        except Exception as e:
            logger.error(f"Image download failed: {e}, URL: {url}")
            raise Exception(f"Image download failed: {e}")

    async def _dispatch_file_event(self, tool_context: ToolContext, file_path: str, event_type: EventType) -> None:
        """
        Dispatch file creation/update event

        Args:
            tool_context: Tool context
            file_path: File path
            event_type: Event type (FILE_CREATED or FILE_UPDATED)
        """
        if tool_context and tool_context.has_extension("agent_context"):
            agent_context = tool_context.get_extension_typed("agent_context", AgentContext)
            if agent_context:
                await agent_context.dispatch_event(event_type, {
                    "path": file_path,
                    "source": "generate_image"
                })
            logger.debug(f"Dispatched event {event_type} for file: {file_path}")

    async def execute(self, tool_context: ToolContext, params: GenerateImageParams) -> ImageToolResult:
        """
        Execute image generation

        Args:
            tool_context: Tool context
            params: Image generation parameters

        Returns:
            ImageToolResult: Result containing generated image information
        """
        # Get conversation ID from context
        conversation_id = self._get_conversation_id_from_context(tool_context)
        logger.info(f"Got conversation ID from context: {conversation_id}")

        # Check if single conversation image generation limit is exceeded
        current_count = self._generation_counts[conversation_id]
        logger.info(f"Current conversation images generated: {current_count}")

        if current_count >= self.MAX_IMAGES_PER_CONVERSATION:
            error_message = f"Maximum image generation limit per topic reached ({self.MAX_IMAGES_PER_CONVERSATION}), please create a new topic to continue generating images"
            logger.warning(error_message)
            return ImageToolResult(
                error=error_message
            )

        try:
            # Verify output path is within workspace
            output_dir_path, error = self.get_safe_path(params.output_path)
            if error:
                return ImageToolResult(
                    error=f"Invalid output path: {error}"
                )

            # Ensure output directory exists
            output_dir_path.mkdir(parents=True, exist_ok=True)

            # Call API to generate images
            image_urls = await self._generate_image(params.message, conversation_id)
            logger.info(f"Generated image URLs: {image_urls}")
            if not image_urls:
                return ImageToolResult(
                    error="Image generation failed, no image URLs returned",
                )

            # Download images
            saved_images = []
            failed_downloads = 0
            for idx, url in enumerate(image_urls):
                try:
                    logger.info(f"Starting download of image {idx+1}: {url}")

                    # Construct file name
                    # For multiple images, add index suffix
                    if len(image_urls) > 1:
                        custom_filename = f"{params.generated_file_name}_{idx+1}"
                    else:
                        custom_filename = params.generated_file_name

                    # Download image using safe path
                    saved_path = await self._download_image(
                        url,
                        params.output_path,
                        custom_filename,
                        params.override
                    )
                    saved_images.append(saved_path)
                    logger.info(f"Image {idx+1} downloaded successfully, save path: {saved_path}")

                    # Trigger file event
                    event_type = EventType.FILE_UPDATED if params.override else EventType.FILE_CREATED
                    await self._dispatch_file_event(tool_context, saved_path, event_type)

                except Exception as e:
                    failed_downloads += 1
                    logger.error(f"Image {idx+1} download and save failed: {e}")

            # If all images failed to download
            if failed_downloads == len(image_urls):
                return ImageToolResult(
                    error="All images failed to download, please check network or image URLs",
                )

            # Update generation count
            # Only count successfully downloaded images
            successful_downloads = len(saved_images)
            self._generation_counts[conversation_id] += successful_downloads
            current_count = self._generation_counts[conversation_id]
            logger.info(f"Updated conversation ({conversation_id}) images generated: {current_count}")

            # Check if approaching limit
            remaining = self.MAX_IMAGES_PER_CONVERSATION - current_count
            warning_message = ""
            if remaining <= 5:
                warning_message = f"\n⚠️ Warning: Current topic can generate {remaining} more images. After reaching the {self.MAX_IMAGES_PER_CONVERSATION} image limit, a new topic will be needed."

            # Build result
            # Build content including each image path
            content = f"Successfully generated {len(image_urls)} images and saved {len(saved_images)} to {params.output_path} directory"
            if saved_images:
                content += "\nImage paths:"
                for i, path in enumerate(saved_images):
                    content += f"\n{i+1}. {path}"

            # Add warning message
            if warning_message:
                content += warning_message

            result = ImageToolResult(
                images=image_urls,  # Use new images field to store all image URLs
                content=content
            )

            # If there are saved image paths, add to extra info
            if saved_images:
                result.extra_info = {
                    "saved_images": saved_images,
                    "conversation_id": conversation_id,
                    "remaining_generations": remaining
                }
            else:
                result.extra_info = {
                    "conversation_id": conversation_id,
                    "remaining_generations": remaining
                }

            return result

        except Exception as e:
            logger.error(f"Image generation failed: {e}")
            return ImageToolResult(
                error=f"Image generation failed: {e}",
            )

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get corresponding ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution parameter dictionary

        Returns:
            Optional[ToolDetail]: Tool detail object, may be None
        """
        if not result.ok or not isinstance(result, ImageToolResult):
            return None

        saved_images = result.extra_info.get("saved_images", []) if result.extra_info else []
        if not saved_images:
            return None

        # Only display the first image
        first_image_path = saved_images[0]
        file_name = os.path.basename(first_image_path)

        return ToolDetail(
            type=DisplayType.IMAGE,
            data=FileContent(
                file_name=file_name,
                content=""  # For image files, do not return content
            )
        )

    async def get_after_tool_call_friendly_content(
        self,
        tool_context: ToolContext,
        result: ToolResult,
        execution_time: float,
        arguments: Dict[str, Any] = None
    ) -> str:
        """Customize friendly output content after tool execution"""
        if result.error:
            return f"Image generation failed: {result.error}"

        if isinstance(result, ImageToolResult) and result.images:
            image_count = len(result.images)
            saved_images = result.extra_info.get("saved_images", []) if result.extra_info else []

            # Get remaining generation count
            remaining = result.extra_info.get("remaining_generations", 0) if result.extra_info else 0
            remaining_info = ""
            if remaining <= 5:
                remaining_info = f"(Current topic can generate {remaining} more images)"

            if saved_images:
                return f"Generated {image_count} images and saved to: {', '.join(saved_images)} {remaining_info}"
            return f"Generated {image_count} images {remaining_info}"

        return "Image generation completed"

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool invocation
        """
        if not arguments:
            return {"action": "Generate image", "remark": "Unknown description"}

        message = arguments.get("message", "")
        remark = message[:30] + "..." if len(message) > 30 else message

        return {
            "action": "Generate image",
            "remark": remark
        }
