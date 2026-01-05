"""
Base classes and utilities for storage SDK.
"""

import io
import os
import functools
import time
import json
import uuid
from pathlib import Path
from abc import ABC, abstractmethod
from typing import BinaryIO, Dict, Optional, Callable, TypeVar, Any

from loguru import logger

from app.core.config.communication_config import STSTokenRefreshConfig
from app.paths import PathManager
from .types import BaseStorageCredentials, FileContent, Options, StorageResponse, PlatformType


T = TypeVar('T')

def with_refreshed_credentials(method: Callable[..., T]) -> Callable[..., T]:
    """
    Decorator: Ensures storage operations are executed with latest credentials

    Automatically handles credential refresh
    """
    @functools.wraps(method)
    async def wrapper(self, *args, **kwargs):
        await self.refresh_credentials()

        return await method(self, *args, **kwargs)

    return wrapper


class AbstractStorage(ABC):
    """Abstract storage class, defines interface for storage operations."""

    def __init__(self):
        self.credentials: Optional[BaseStorageCredentials] = None
        self.sts_refresh_config: Optional[STSTokenRefreshConfig] = None
        self.metadata: Optional[Dict] = None

    def set_credentials(self, credentials: BaseStorageCredentials):
        """Set storage credentials"""
        self.credentials = credentials

    def set_sts_refresh_config(self, config: Optional[STSTokenRefreshConfig]):
        """Set STS Token refresh configuration"""
        self.sts_refresh_config = config

    def set_metadata(self, metadata: Optional[Dict]):
        """Set metadata for credential refresh"""
        self.metadata = metadata

    def get_platform_name(self) -> str:
        """
        Get current storage service platform name.
        If credentials have platform info, return that platform name, otherwise return 'unknown'.

        Returns:
            str: Storage platform name
        """
        if self.credentials and hasattr(self.credentials, 'platform'):
            if isinstance(self.credentials.platform, PlatformType):
                return self.credentials.platform.value
            return str(self.credentials.platform)
        return "unknown"

    def get_platform_type(self) -> Optional[PlatformType]:
        """
        Get current storage service platform type.
        If credentials have platform info and type is PlatformType, return that platform type, otherwise return None.

        Returns:
            Optional[PlatformType]: Storage platform type, returns None if cannot determine
        """
        if self.credentials and hasattr(self.credentials, 'platform'):
            if isinstance(self.credentials.platform, PlatformType):
                return self.credentials.platform
            try:
                return PlatformType(str(self.credentials.platform))
            except (ValueError, TypeError):
                pass
        return None

    async def refresh_credentials(self):
        """
        Refresh credentials if needed - template method
        """
        if self._should_refresh_credentials():
            logger.info("Starting credential refresh")

            # Call subclass implemented refresh method
            await self._refresh_credentials_impl()

            logger.info("Credential refresh completed")

            # Automatically save to file after refresh
            if self.credentials:
                await self._save_credentials_to_file()

    def _should_refresh_credentials(self) -> bool:
        """
        Determine if credentials need refresh - template method
        """
        # Basic check: if no refresh config or metadata, no need to refresh
        if not self.sts_refresh_config or self.metadata is None:
            return False

        # Subclass specific check logic
        return self._should_refresh_credentials_impl()

    def _should_refresh_credentials_impl(self) -> bool:
        """
        Subclass specific credential refresh check logic, implemented by subclass
        """
        raise NotImplementedError("Subclass must implement this method")

    @abstractmethod
    async def _refresh_credentials_impl(self):
        """
        Actually perform credential refresh operation, implemented by subclass
        """
        raise NotImplementedError("Subclass must implement this method")

    async def _save_credentials_to_file(self):
        """
        Save credentials to local file
        """
        project_dir = PathManager.get_project_root()
        credentials_dir = Path(project_dir) / ".credentials"
        os.makedirs(credentials_dir, exist_ok=True)
        file_path = credentials_dir / "upload_credentials.json"
        credentials_data = self.credentials.model_dump()

        # Create credential data with batch ID
        wrapped_data = {
            "upload_config": credentials_data,
            "batch_id": str(uuid.uuid4())  # Add random batch ID
        }

        with open(file_path, "w") as f:
            json.dump(wrapped_data, f, indent=2)

        logger.info(f"Successfully saved credentials to file: {file_path}, batch ID: {wrapped_data['batch_id']}")

    @abstractmethod
    async def upload(
        self,
        file: FileContent,
        key: str,
        options: Optional[Options] = None
    ) -> StorageResponse:
        """
        Asynchronously upload file to storage platform.

        Args:
            file: File content (can be file path, bytes data, or file object)
            key: File name/path
            options: Optional configuration (such as progress callback, HTTP headers, etc.)

        Returns:
            StorageResponse: Standardized upload response

        Raises:
            InitException: If initialization parameters are missing or file is too large
            UploadException: If upload fails (expired credentials or network issues)
            ValueError: If file type is not supported or credential type is wrong or metadata is not set
        """
        raise NotImplementedError("Subclass must implement this method")

    @abstractmethod
    async def download(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> BinaryIO:
        """
        Asynchronously download file from storage platform.

        Args:
            key: File name/path
            options: Optional configuration (such as progress callback, HTTP headers, etc.)

        Returns:
            BinaryIO: Binary stream of file content

        Raises:
            InitException: If initialization parameters are missing
            DownloadException: If download fails (expired credentials or network issues)
            ValueError: If credential type is wrong or metadata is not set
        """
        raise NotImplementedError("Subclass must implement this method")

    @abstractmethod
    async def exists(
        self,
        key: str,
        options: Optional[Options] = None
    ) -> bool:
        """
        Asynchronously check if specified file exists on storage platform.

        Args:
            key: File name/path
            options: Optional configuration

        Returns:
            bool: True if file exists, False otherwise

        Raises:
            InitException: If initialization parameters are missing
            ValueError: If credential type is wrong or metadata is not set
        """
        raise NotImplementedError("Subclass must implement this method")

class BaseFileProcessor:
    """Base class for file processing operations."""

    @staticmethod
    def process_file(file: FileContent) -> tuple[BinaryIO, int]:
        """
        Process file input and return file object and size.

        Args:
            file: File content in various formats (path, bytes, or file object)

        Returns:
            tuple: (file_object, file_size)

        Raises:
            ValueError: If file type is not supported
        """
        try:
            if isinstance(file, str) and os.path.isfile(file):
                # If file is a file path
                file_size = os.path.getsize(file)
                file_obj = open(file, 'rb')
            elif hasattr(file, 'read') and callable(file.read):
                # If file is a file object
                if hasattr(file, 'seek') and callable(file.seek) and hasattr(file, 'tell') and callable(file.tell):
                    current_pos = file.tell()
                    file.seek(0, os.SEEK_END)
                    file_size = file.tell()
                    file.seek(current_pos)
                else:
                    file_size = 0  # Cannot determine size
                file_obj = file
            elif isinstance(file, bytes):
                # If file is bytes data
                file_size = len(file)
                file_obj = io.BytesIO(file)
            else:
                raise ValueError("Unsupported file type. Must be a file path, file object, or bytes")

            return file_obj, file_size

        except Exception as e:
            logger.error(f"Error processing file: {e}")
            raise

    @staticmethod
    def combine_path(dir_path: str, file_path: str) -> str:
        """
        Combine directory path and file path ensuring only one separator between them.

        Args:
            dir_path: Directory path
            file_path: File path or key

        Returns:
            str: Combined path with a single separator
        """
        clean_dir = dir_path.rstrip('/')  # Remove trailing slashes from directory
        clean_file = file_path.lstrip('/')  # Remove leading slashes from file path
        return f"{clean_dir}/{clean_file}"

class ProgressTracker:
    """Base progress tracker for file upload."""
    def __init__(self, callback: callable):
        """
        Initialize progress tracker.

        Args:
            callback: Function to call with progress updates
        """
        self.callback = callback
        self.total_size = 0
        self.uploaded = 0

    def __call__(self, monitor):
        """
        Update progress based on monitor data.

        Args:
            monitor: Upload monitor object with bytes_read and len attributes
        """
        self.uploaded = monitor.bytes_read
        if monitor.len and monitor.len > 0:
            progress = (self.uploaded / monitor.len) * 100
            self.callback(min(progress, 100.0))
