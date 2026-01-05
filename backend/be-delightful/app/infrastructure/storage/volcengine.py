"""
VolcEngine TOS upload implementation.
"""

import asyncio
import io
import time
from typing import BinaryIO, Optional

import aiohttp
from loguru import logger
from tos import TosClientV2

from .base import AbstractStorage, BaseFileProcessor, with_refreshed_credentials
from .exceptions import (
    DownloadException,
    DownloadExceptionCode,
    InitException,
    InitExceptionCode,
    UploadException,
    UploadExceptionCode,
)
from .types import BaseStorageCredentials, FileContent, Options, PlatformType, StorageResponse, VolcEngineCredentials


class VolcEngineUploader(AbstractStorage, BaseFileProcessor):
    """VolcEngine TOS uploader implementation."""

    def set_credentials(self, credentials: BaseStorageCredentials):
        """Set storage credentials."""
        if not isinstance(credentials, VolcEngineCredentials):
            if isinstance(credentials, dict):
                try:
                    credentials = VolcEngineCredentials(**credentials)
                except Exception as e:
                    raise ValueError(f"Invalid credential format: {e}")
            else:
                raise ValueError(f"Expected VolcEngineCredentials, got {type(credentials)}")
        self.credentials = credentials

    def _should_refresh_credentials_impl(self) -> bool:
        """Determine whether credentials should be refreshed."""
        if not self.credentials:
            return True

        credentials: VolcEngineCredentials = self.credentials
        if credentials.expire is None:
            return False

        # Refresh 180 seconds before expiry
        return time.time() > credentials.expire - 180

    async def _refresh_credentials_impl(self):
        """Refresh credentials via STS endpoint."""
        logger.info("Fetching Volcengine STS token")

        # Preconditions are checked in _should_refresh_credentials
        json_data = {}
        if self.metadata:
            json_data["metadata"] = self.metadata

        async with aiohttp.ClientSession() as session:
            async with session.request(
                method=self.sts_refresh_config.method,
                url=self.sts_refresh_config.url,
                headers=self.sts_refresh_config.headers,
                json=json_data
            ) as response:
                response.raise_for_status()
                responseBody = await response.json()
                self.credentials = VolcEngineCredentials(**responseBody["data"])
                logger.info("Volcengine STS token acquired")

    @with_refreshed_credentials
    async def upload(
        self,
        file: FileContent,
        key: str,
        options: Optional[Options] = None
    ) -> StorageResponse:
        """
        Upload file to Volcengine TOS asynchronously via SDK.
        
        Args:
            file: File object, path, or bytes
            key: File name/path
            options: Optional config including headers and progress callback
        
        Returns:
            StorageResponse: Standardized response
        
        Raises:
            InitException: If required params missing or file too large
            UploadException: If upload fails (expired creds or network)
            ValueError: If file type unsupported, credential wrong, or metadata missing
        """
        # Credentials already refreshed by decorator; use self.credentials directly
        if options is None:
            options = {}

        # Handle file
        try:
            file_obj, file_size = self.process_file(file)

            # Enforce 5GB size cap
            if file_size > 5 * 1024 * 1024 * 1024:  # 5GB
                if isinstance(file, str):
                    file_obj.close()
                raise InitException(
                    InitExceptionCode.FILE_TOO_LARGE,
                    "volcEngine",
                    file_name=key
                )

            # Get credential info
            credentials: VolcEngineCredentials = self.credentials
            tc = credentials.temporary_credential

            # Create TOS client
            tos_client = TosClientV2(
                endpoint=tc.endpoint,
                region=tc.region,
                ak=tc.credentials.AccessKeyId,
                sk=tc.credentials.SecretAccessKey,
                security_token=tc.credentials.SessionToken
            )

            try:
                # Run upload in executor
                loop = asyncio.get_event_loop()
                result = await loop.run_in_executor(
                    None,
                    lambda: tos_client.put_object(
                        bucket=tc.bucket,
                        key=key,
                        content=file_obj
                    )
                )

                # Close file we opened
                if isinstance(file, str):
                    file_obj.close()

                # Get response headers
                headers = {}
                if hasattr(result, 'headers'):
                    headers = dict(result.headers)
                elif hasattr(result, 'request_info') and hasattr(result.request_info, 'headers'):
                    headers = dict(result.request_info.headers)

                # Return standardized response
                return StorageResponse(
                    key=key,
                    platform=PlatformType.tos,
                    headers=headers
                )

            except Exception as e:
                logger.error(f"TOS error during async upload: {e}")
                if isinstance(file, str):
                    file_obj.close()
                raise UploadException(UploadExceptionCode.NETWORK_ERROR, str(e))

        except Exception as e:
            # Ensure file is closed
            if 'file_obj' in locals() and isinstance(file, str):
                try:
                    file_obj.close()
                except:
                    pass

            if not isinstance(e, (InitException, UploadException)):
                logger.error(f"Unexpected error during async upload: {e}")
                raise UploadException(UploadExceptionCode.NETWORK_ERROR, str(e))
            raise

    @with_refreshed_credentials
    async def download(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> BinaryIO:
        """
        Download file asynchronously from Volcengine TOS.

        Args:
            key: File name/path
            options: Optional config

        Returns:
            BinaryIO: File content stream

        Raises:
            DownloadException: If download fails
            ValueError: If credential type is wrong or metadata missing
        """
        # Credentials already refreshed by decorator

        if options is None:
            options = {}

        try:
            # Get credential info
            # Get credential info
            credentials: VolcEngineCredentials = self.credentials
            tc = credentials.temporary_credential

            # Create TOS client
            tos_client = TosClientV2(
                endpoint=tc.endpoint,
                region=tc.region,
                ak=tc.credentials.AccessKeyId,
                sk=tc.credentials.SecretAccessKey,
                security_token=tc.credentials.SessionToken
            )

            try:
                # Fetch object via executor
                loop = asyncio.get_event_loop()
                result = await loop.run_in_executor(
                    None,
                    lambda: tos_client.get_object(
                        bucket=tc.bucket,
                        key=key
                    )
                )

                # Read content into memory
                content = result.read()
                # Wrap into memory stream
                file_stream = io.BytesIO(content)
                return file_stream

            except Exception as e:
                logger.error(f"Error during async download: {e}")
                raise DownloadException(DownloadExceptionCode.NETWORK_ERROR, str(e))

        except Exception as e:
            if not isinstance(e, DownloadException):
                logger.error(f"Unexpected error during async download: {e}")
                raise DownloadException(DownloadExceptionCode.NETWORK_ERROR, str(e))
            raise

    @with_refreshed_credentials
    async def exists(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> bool:
        """
        Check asynchronously whether a file exists on the storage platform.

        Args:
            key: File name/path.
            options: Optional configuration.

        Returns:
            True if the file exists, otherwise False.

        Raises:
            InitException: If initialization parameters are missing.
            ValueError: If credentials are not set.
        """
        credentials: VolcEngineCredentials = self.credentials
        if credentials is None:
            raise ValueError("Credentials not set")

        if options is None:
            options = {}

        try:
            tc = credentials.temporary_credential

            tos_client = TosClientV2(
                endpoint=tc.endpoint,
                region=tc.region,
                ak=tc.credentials.AccessKeyId,
                sk=tc.credentials.SecretAccessKey,
                security_token=tc.credentials.SessionToken
            )

            loop = asyncio.get_event_loop()
            await loop.run_in_executor(
                None,
                lambda: tos_client.head_object(
                    bucket=tc.bucket,
                    key=key
                )
            )
            return True
        except Exception as e:
            logger.error(f"Error checking file existence: {e}")
            return False
