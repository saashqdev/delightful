"""
Local file storage implementation.
"""

import asyncio
import io
import os
import time
from contextlib import asynccontextmanager
from typing import Any, AsyncGenerator, BinaryIO, Dict, Optional, Tuple

import aiohttp
from loguru import logger

from .base import AbstractStorage, BaseFileProcessor, with_refreshed_credentials
from .exceptions import (
    DownloadException,
    DownloadExceptionCode,
    InitException,
    InitExceptionCode,
    UploadException,
    UploadExceptionCode,
)
from .types import BaseStorageCredentials, FileContent, LocalCredentials, Options, PlatformType, StorageResponse


class LocalStorage(AbstractStorage, BaseFileProcessor):
    """Local storage implementation for file operations."""

    # Defaults from environment variables
    DEFAULT_HOST = os.environ.get('LOCAL_STORAGE_HOST', "")
    DEFAULT_DIR = os.environ.get('LOCAL_STORAGE_DIR', ".workspace")
    DEFAULT_READ_HOST = os.environ.get('LOCAL_STORAGE_READ_HOST', "")
    DEFAULT_EXPIRY_DURATION = int(os.environ.get('LOCAL_STORAGE_EXPIRY', "3600"))  # Default credential TTL (s)
    DEFAULT_TIMEOUT = int(os.environ.get('LOCAL_STORAGE_TIMEOUT', "10"))  # Default request timeout (s)
    MAX_FILE_SIZE = 5 * 1024 * 1024 * 1024  # 5GB cap

    def set_credentials(self, credentials: BaseStorageCredentials) -> None:
        """
        Set storage credentials.
        
        Args:
            credentials: Credential object or dict
            
        Raises:
            ValueError: If credential format/type is invalid
        """
        if not isinstance(credentials, LocalCredentials):
            if isinstance(credentials, dict):
                try:
                    credentials = LocalCredentials(**credentials)
                except Exception as e:
                    logger.error(f"Failed to parse local storage credentials: {e}")
                    raise ValueError(f"Invalid credential format: {e}")
            else:
                raise ValueError(f"Expected LocalCredentials, got {type(credentials)}")
        self.credentials = credentials

    def _should_refresh_credentials_impl(self) -> bool:
        """
        Determine whether credentials should be refreshed.
        
        Returns:
            bool: True if credentials need refresh, else False
        """
        if not self.credentials:
            return True

        credentials: LocalCredentials = self.credentials
        if credentials.expires is None:
            return False

        # Refresh 180s before expiry
        return time.time() > credentials.expires - 180

    def _create_default_credentials(self) -> LocalCredentials:
        """
        Create default local storage credentials.
        
        Returns:
            LocalCredentials: Default credential object
        """
        default_credential_id = f"local_credential:default_{int(time.time())}"
        default_data = {
            "platform": "local",
            "temporary_credential": {
                "host": self.DEFAULT_HOST,
                "dir": self.DEFAULT_DIR,
                "read_host": self.DEFAULT_READ_HOST,
                "credential": default_credential_id
            },
            "expires": int(time.time()) + self.DEFAULT_EXPIRY_DURATION
        }
        return LocalCredentials(**default_data)

    async def _refresh_credentials_impl(self) -> None:
        """
        Refresh credentials via configured STS endpoint.
        
        Raises:
            InitException: If credentials cannot be obtained
        """
        logger.info("Fetching local storage credentials")

        # Require STS refresh config
        if not self.sts_refresh_config:
            error_msg = "STS refresh not configured; cannot obtain local storage credentials"
            logger.error(error_msg)
            raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)

        # Prepare JSON payload
        json_data = {"metadata": self.metadata} if self.metadata else {}

        try:
            async with self._create_client_session() as session:
                try:
                    async with session.request(
                        method=self.sts_refresh_config.method,
                        url=self.sts_refresh_config.url,
                        headers=self.sts_refresh_config.headers,
                        json=json_data,
                        timeout=self.DEFAULT_TIMEOUT
                    ) as response:
                        response.raise_for_status()
                        response_body = await response.json()

                        # Validate response data
                        credential_data = response_body.get("data")
                        if not credential_data:
                            error_msg = "Local storage credential response missing data field"
                            logger.error(error_msg)
                            raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)

                        try:
                            self.credentials = LocalCredentials(**credential_data)

                            # Validate required fields
                            if not self.credentials.temporary_credential or not self.credentials.temporary_credential.dir:
                                error_msg = "Credential missing required dir field"
                                logger.error(error_msg)
                                raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)

                            logger.info("Local storage credentials fetched successfully")
                        except Exception as e:
                            error_msg = f"Failed to parse credential data: {e}"
                            logger.error(error_msg)
                            raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)
                except (aiohttp.ClientError, asyncio.TimeoutError) as e:
                    error_msg = f"Request to local storage credential API failed: {e}"
                    logger.error(error_msg)
                    raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)
        except InitException:
            # Propagate InitException
            raise
        except Exception as e:
            error_msg = f"Failed to obtain local storage credentials: {e}"
            logger.error(error_msg)
            raise InitException(InitExceptionCode.CREDENTIAL_ERROR, error_msg)

    @asynccontextmanager
    async def _create_client_session(self) -> AsyncGenerator[aiohttp.ClientSession, None]:
        """
        Context manager to create an HTTP client session.
        
        Yields:
            aiohttp.ClientSession: HTTP client session
        """
        session = aiohttp.ClientSession()
        try:
            yield session
        finally:
            await session.close()

    @with_refreshed_credentials
    async def upload(
        self,
        file: FileContent,
        key: str,
        options: Optional[Options] = None
    ) -> StorageResponse:
        """
        Upload a file via HTTP POST to the local storage endpoint.
        
        Args:
            file: File object, file path, or bytes
            key: File name/path
            options: Optional config including headers and progress callback
        
        Returns:
            StorageResponse: Standardized response
        
        Raises:
            InitException: When required params are missing or file too large
            UploadException: When upload fails (expired creds or network)
            ValueError: When file type unsupported, credential type wrong, or metadata unset
        """
        # Process file and ensure closure
        file_obj = None
        try:
            # Validate credentials
            credentials = self._validate_credentials()

            # Prepare file
            file_obj, file_size = self.process_file(file)
            self._check_file_size(file_obj, file_size, key, is_path=isinstance(file, str))

            # Get upload URL and credential
            upload_url, credential = self._get_upload_details(credentials)

            # Build request data
            data, headers = self._prepare_upload_request(file_obj, key, credential, options or {})

            # Send request and handle response
            return await self._send_upload_request(upload_url, data, headers, key, credentials)

        except Exception as e:
            self._handle_upload_exception(e)
        finally:
            # Ensure we close file if we opened it
            if file_obj and isinstance(file, str):
                try:
                    file_obj.close()
                except Exception as e:
                    logger.debug(f"Error closing file (safe to ignore): {e}")

    def _validate_credentials(self) -> LocalCredentials:
        """
        Validate credentials
        
        Returns:
            LocalCredentials: Validated credentials
            
        Raises:
            UploadException: If credentials are invalid
        """
        credentials: LocalCredentials = self.credentials
        if not credentials or not credentials.temporary_credential:
            error_msg = "Credentials invalid or temporary credential missing"
            logger.error(error_msg)
            raise UploadException(UploadExceptionCode.CREDENTIALS_EXPIRED, error_msg)
        return credentials

    def _check_file_size(self, file_obj: BinaryIO, file_size: int, key: str, is_path: bool) -> None:
        """
        Check whether file size exceeds limit.
        
        Args:
            file_obj: File object
            file_size: File size
            key: File key
            is_path: Whether the input is a file path
            
        Raises:
            InitException: If file is too large
        """
        if file_size > self.MAX_FILE_SIZE:
            if is_path:
                file_obj.close()
            raise InitException(
                InitExceptionCode.FILE_TOO_LARGE,
                "local",
                file_name=key
            )

    def _get_upload_details(self, credentials: LocalCredentials) -> Tuple[str, str]:
        """
        Get upload URL and credential.
        
        Args:
            credentials: Credential object
            
        Returns:
            Tuple[str, str]: Upload URL and credential ID
            
        Raises:
            UploadException: If upload URL is invalid
        """
        tc = credentials.temporary_credential
        upload_url = tc.host
        credential = tc.credential

        if not upload_url:
            error_msg = "Upload URL cannot be empty"
            logger.error(error_msg)
            raise UploadException(UploadExceptionCode.CREDENTIALS_EXPIRED, error_msg)

        return upload_url, credential

    def _prepare_upload_request(
        self, 
        file_obj: BinaryIO, 
        key: str, 
        credential: str, 
        options: Dict[str, Any]
    ) -> Tuple[aiohttp.FormData, Dict[str, str]]:
        """
    Prepare FormData and headers for upload.
        
        Args:
            file_obj: File object
            key: File key
            credential: Credential ID
            options: Optional config
            
        Returns:
            Tuple[aiohttp.FormData, Dict[str, str]]: Form data and headers
            
        Raises:
            ValueError: If file object type unsupported
        """
        # Prepare headers
        headers = {
            'Accept': '*/*',
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache',
        }

        # Add optional headers
        if custom_headers := options.get('headers'):
            headers.update(custom_headers)

        # Build form data
        data = aiohttp.FormData()

        # Add key param (file path/name)
        data.add_field('key', key)

        # Add credential param
        if credential:
            data.add_field('credential', credential)
        else:
            generated_credential = f"local_credential:default_{int(time.time())}"
            data.add_field('credential', generated_credential)
            logger.warning(f"Credential missing 'credential' field; generated default used: {generated_credential}")

        # Add file content
        if hasattr(file_obj, 'read'):
            # Save current position
            current_pos = file_obj.tell()
            # Seek to start
            file_obj.seek(0)
            # Read content
            file_content = file_obj.read()
            # Restore original position
            file_obj.seek(current_pos)

            # Add to form
            filename = os.path.basename(key)
            data.add_field('file', file_content, filename=filename)
        else:
            # Should not happen; process_file already returns a file object
            raise ValueError("Unsupported file object type")

        return data, headers

    async def _send_upload_request(
        self, 
        upload_url: str, 
        data: aiohttp.FormData, 
        headers: Dict[str, str],
        key: str, 
        credentials: LocalCredentials
    ) -> StorageResponse:
        """
        Send upload request and handle response.
        
        Args:
            upload_url: Upload URL
            data: Form data
            headers: Request headers
            key: File key
            credentials: Credential object
            
        Returns:
            StorageResponse: Upload response
            
        Raises:
            UploadException: If upload fails or response invalid
        """
        async with self._create_client_session() as session:
            async with session.post(
                upload_url, 
                data=data, 
                headers=headers, 
                timeout=self.DEFAULT_TIMEOUT
            ) as response:
                if response.status != 200:
                    error_text = await response.text()
                    raise UploadException(
                        UploadExceptionCode.NETWORK_ERROR, 
                        f"Upload failed; status={response.status}, error={error_text}"
                    )

                # Parse response
                response_data = await self._parse_response(response)

                # Validate response data
                self._validate_response(response_data)

                # Extract uploaded key and URL
                uploaded_key = response_data["data"]["key"]
                url = self._build_file_url(credentials, uploaded_key)

                # Return standardized response
                return StorageResponse(
                    key=uploaded_key,
                    platform=PlatformType.local,
                    headers=dict(response.headers) if response.headers else {},
                    url=url
                )

    async def _parse_response(self, response: aiohttp.ClientResponse) -> Dict[str, Any]:
        """
        Parse response JSON data.
        
        Args:
            response: Response object
            
        Returns:
            Dict[str, Any]: Parsed response data
            
        Raises:
            UploadException: If parsing fails
        """
        try:
            response_data = await response.json()
            logger.debug(f"Local storage upload response: {response_data}")
            return response_data
        except Exception as e:
            logger.error(f"Failed to parse response JSON: {e}")
            raise UploadException(
                UploadExceptionCode.INVALID_RESPONSE,
                f"Failed to parse response JSON: {e}"
            )

    def _validate_response(self, response_data: Dict[str, Any]) -> None:
        """
        Validate response data.
        
        Args:
            response_data: Response data
            
        Raises:
            UploadException: If response data invalid
        """
        if not response_data or "data" not in response_data:
            logger.error(f"Response missing data field: {response_data}")
            raise UploadException(
                UploadExceptionCode.INVALID_RESPONSE,
                f"Response missing data field: {response_data}"
            )

        if "key" not in response_data.get("data", {}):
            logger.error(f"Response data missing key field: {response_data}")
            raise UploadException(
                UploadExceptionCode.INVALID_RESPONSE,
                f"Response data missing key field: {response_data}"
            )

    def _build_file_url(self, credentials: LocalCredentials, key: str) -> Optional[str]:
        """
        Build file URL.
        
        Args:
            credentials: Credential object
            key: File key
            
        Returns:
            Optional[str]: File URL, or None if cannot build
        """
        base_url = credentials.get_public_access_base_url()
        return f"{base_url}/{key}" if base_url else None

    def _handle_upload_exception(self, exception: Exception) -> None:
        """
        Handle exceptions during upload.
        
        Args:
            exception: Exception instance
            
        Raises:
            UploadException: Converted upload exception
            Exception: Original exception if already specialized
        """
        if isinstance(exception, (InitException, UploadException)):
            raise exception
        logger.error(f"Unexpected error during upload: {exception}")
        raise UploadException(UploadExceptionCode.NETWORK_ERROR, str(exception))

    @with_refreshed_credentials
    async def download(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> BinaryIO:
        """
        Download a file asynchronously from local storage.

        Args:
            key: File name/path
            options: Optional config

        Returns:
            BinaryIO: File content stream

        Raises:
            DownloadException: If download fails
            ValueError: If credential type is wrong or metadata missing
        """
        options = options or {}
        credentials: LocalCredentials = self.credentials

        # Build download URL
        download_url = self._build_download_url(credentials, key)

        # Prepare headers
        headers = options.get('headers', {})

        try:
            async with self._create_client_session() as session:
                async with session.get(
                    download_url, 
                    headers=headers,
                    timeout=self.DEFAULT_TIMEOUT
                ) as response:
                    await self._handle_download_response(response, key)
                    # Read file content
                    content = await response.read()
                    return io.BytesIO(content)
        except aiohttp.ClientError as e:
            logger.error(f"Network error during download: {e}")
            raise DownloadException(DownloadExceptionCode.NETWORK_ERROR, str(e))
        except Exception as e:
            if not isinstance(e, DownloadException):
                logger.error(f"Unexpected error during download: {e}")
                raise DownloadException(DownloadExceptionCode.NETWORK_ERROR, str(e))
            raise

    def _build_download_url(self, credentials: LocalCredentials, key: str) -> str:
        """
        Build download URL.
        
        Args:
            credentials: Credential object
            key: File key
            
        Returns:
            str: Download URL
            
        Raises:
            DownloadException: If URL cannot be built
        """
        base_url = credentials.get_public_access_base_url()
        if not base_url:
            raise DownloadException(
                DownloadExceptionCode.INVALID_RESPONSE,
                "Cannot build download URL; missing read_host"
            )
        return f"{base_url}/{key}"

    async def _handle_download_response(
        self, 
        response: aiohttp.ClientResponse, 
        key: str
    ) -> None:
        """
        Handle download response
        
        Args:
            response: Response object
            key: File key
            
        Raises:
            DownloadException: If status is not 200
        """
        if response.status != 200:
            error_text = await response.text()
            if response.status == 404:
                raise DownloadException(
                    DownloadExceptionCode.FILE_NOT_FOUND,
                    f"File not found: {key}"
                )
            else:
                raise DownloadException(
                    DownloadExceptionCode.NETWORK_ERROR,
                    f"Download failed; status={response.status}, error={error_text}"
                )

    @with_refreshed_credentials
    async def exists(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> bool:
        """
        Check asynchronously whether a file exists in local storage.

        Args:
            key: File name/path
            options: Optional config

        Returns:
            bool: True if file exists, else False
        """
        options = options or {}
        credentials: LocalCredentials = self.credentials

        # Build URL
        base_url = credentials.get_public_access_base_url()
        if not base_url:
            logger.warning("Cannot build URL to check existence; missing read_host")
            return False

        url = f"{base_url}/{key}"
        headers = options.get('headers', {})

        try:
            async with self._create_client_session() as session:
                async with session.head(
                    url, 
                    headers=headers,
                    timeout=self.DEFAULT_TIMEOUT
                ) as response:
                    return response.status == 200
        except Exception as e:
            logger.error(f"Error while checking file existence: {e}")
            return False 
