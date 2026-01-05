"""
Attachment download service

Responsible for handling attachment downloads in chat messages
"""

import asyncio
import json
import os
from pathlib import Path
from typing import Any, Dict, List, Optional

import httpx

from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext

# Configure logging
logger = get_logger(__name__)


class AttachmentService:
    """Attachment download service class"""

    def __init__(self, agent_context: AgentContext):
        """
        Initialize attachment download service

        Args:
            agent_context: Agent context, contains storage credentials
        """
        self.agent_context = agent_context

        # Record initialization information
        logger.info("Initializing attachment download service")

        # Set attachment download directory
        self.attachments_dir = self._get_attachments_dir()
        # Ensure directory exists
        os.makedirs(self.attachments_dir, exist_ok=True)
        logger.info(f"Attachment download directory: {self.attachments_dir}")

    def _get_attachments_dir(self) -> Path:
        """
        Get attachment download directory

        Directly use agent workspace directory

        Returns:
            Path: Attachment download directory
        """
        # Directly use agent context workspace directory
        workspace_dir = self.agent_context.get_workspace_dir()
        attachments_dir = Path(workspace_dir)
        return attachments_dir

    async def download_attachment(self, attachment: Dict[str, Any]) -> Optional[str]:
        """
        Download single attachment

        Args:
            attachment: Attachment info, contains file_url, file_tag, filename and other fields

        Returns:
            Local file path after download, returns None if download fails
        """
        try:
            logger.info(f"Starting to process attachment: {json.dumps(attachment, ensure_ascii=False)}")

            # Get required fields
            file_url = attachment.get('file_url')
            filename = attachment.get('filename')
            file_tag = attachment.get('file_tag')
            file_key = attachment.get('file_key')
            file_size = attachment.get('file_size', 0)

            if not file_url or not filename:
                logger.error(f"Attachment info incomplete: {attachment}")
                return None

            # Ensure HTTP URL
            if not file_url.startswith(('http://', 'https://')):
                logger.error(f"Unsupported URL format: {file_url}, only HTTP or HTTPS supported")
                return None

            logger.info(f"Attachment tag: {file_tag}, URL: {file_url}, filename: {filename}, size: {file_size}, file key: {file_key}")

            # Process filename to ensure safety
            safe_name = self._get_safe_filename(filename)
            logger.debug(f"Processed safe filename: {safe_name}")

            # Determine file save path
            local_path = self.attachments_dir / safe_name
            logger.info(f"Attachment save path: {local_path}")

            # Handle HTTP(S) link
            logger.info(f"Starting to download HTTP file: {file_url}")
            async with httpx.AsyncClient() as client:
                logger.debug(f"Starting HTTP request: {file_url}")
                try:
                    response = await client.get(file_url)
                    if response.status_code == 200:
                        logger.info(f"HTTP request successful, status code: {response.status_code}, content length: {len(response.content)} bytes")
                        # Ensure parent directory exists
                        os.makedirs(os.path.dirname(local_path), exist_ok=True)
                        # Write file
                        with open(local_path, 'wb') as f:
                            f.write(response.content)
                        logger.info(f"Attachment downloaded successfully and saved to: {local_path}")
                        return str(local_path)
                    else:
                        logger.error(f"HTTP download failed: status code {response.status_code}, response: {response.text[:100]}...")
                        return None
                except Exception as e:
                    logger.error(f"HTTP request exception: {e}")
                    return None

        except Exception as e:
            import traceback
            logger.error(f"Error downloading attachment: {e}")
            logger.error(traceback.format_exc())
            return None

    async def download_attachments(self, attachments: List[Dict[str, Any]]) -> List[str]:
        """
        Download all attachments in message

        Args:
            attachments: Attachment list

        Returns:
            List of local paths of successfully downloaded attachments
        """
        if not attachments:
            logger.info("No attachments found, no download needed")
            return []

        logger.info(f"Starting to download {len(attachments)} attachments")

        # Download all attachments concurrently
        download_tasks = [self.download_attachment(attachment) for attachment in attachments]
        results = await asyncio.gather(*download_tasks)

        # Filter out failed downloads
        successful_downloads = [path for path in results if path]
        logger.info(f"Successfully downloaded {len(successful_downloads)}/{len(attachments)} attachments")

        # Log each successful download
        for i, path in enumerate(successful_downloads, 1):
            logger.info(f"Successfully downloaded attachment {i}: {path}")

        return successful_downloads

    def _get_safe_filename(self, filename: str) -> str:
        """
        Process filename to ensure it is safe and unique

        Args:
            filename: Original filename

        Returns:
            Safely processed filename
        """
        # Remove path separators and other unsafe characters
        safe_name = "".join(c for c in filename if c.isalnum() or c in "._- ")
        logger.debug(f"Sanitized filename: {filename} -> {safe_name}")

        return safe_name
