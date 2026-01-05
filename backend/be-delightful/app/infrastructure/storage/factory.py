"""
Storage factory module.
"""

import os
from typing import Dict, Optional, Type

from app.core.entity.message.client_message import STSTokenRefreshConfig

from .aliyun import AliyunOSSUploader
from .base import AbstractStorage
from .local import LocalStorage
from .types import PlatformType
from .volcengine import VolcEngineUploader


class StorageFactory:
    """Storage factory for creating per-platform storage instances."""

    _instances: Dict[PlatformType, AbstractStorage] = {}
    _implementations: Dict[PlatformType, Type[AbstractStorage]] = {
        PlatformType.tos: VolcEngineUploader,
        PlatformType.aliyun: AliyunOSSUploader,
        PlatformType.local: LocalStorage,
        # Add other platform implementations here
    }

    @classmethod
    async def get_storage(
        cls, 
        sts_token_refresh: Optional[STSTokenRefreshConfig] = None,
        metadata: Optional[Dict] = None
    ) -> AbstractStorage:
        """
        Get a storage instance for the configured platform.
        Platform is chosen via env STORAGE_PLATFORM (default 'tos').
        Uses a singleton per platform.
        
        Args:
            sts_token_refresh: STS token refresh config (optional)
            metadata: Metadata for credential refresh (optional)

        Returns:
            AbstractStorage: Storage implementation instance

        Raises:
            ValueError: If env platform type is unsupported/invalid
        """
        platform_str = os.environ.get('STORAGE_PLATFORM', 'tos').lower()

        try:
            platform = PlatformType(platform_str)
        except ValueError:
            raise ValueError(f"Invalid or unsupported platform '{platform_str}' in STORAGE_PLATFORM; check configuration.")

        if platform not in cls._instances:
            if platform not in cls._implementations:
                raise ValueError(f"Unsupported storage platform: {platform.value} (from STORAGE_PLATFORM='{platform_str}')")

            implementation = cls._implementations[platform]
            cls._instances[platform] = implementation()

        storage_service = cls._instances[platform]

        storage_service.set_sts_refresh_config(sts_token_refresh)
        storage_service.set_metadata(metadata)

        # refresh_credentials() keeps credentials fresh when obtaining the service (esp. STS)
        # StorageUploaderTool later calls set_credentials with file-loaded credentials
        await storage_service.refresh_credentials()

        return storage_service

    @classmethod
    def register_implementation(
        cls,
        platform: PlatformType,
        implementation: Type[AbstractStorage]
    ) -> None:
        """
        Register a new storage platform implementation.

        Args:
            platform: Storage platform type
            implementation: Implementation class

        Raises:
            ValueError: If implementation is not a subclass of AbstractStorage
        """
        if not issubclass(implementation, AbstractStorage):
            raise ValueError(
                f"Implementation must be a subclass of AbstractStorage, got {implementation}"
            )

        cls._implementations[platform] = implementation
        # Clear any existing instance to use the new implementation
        if platform in cls._instances:
            del cls._instances[platform] 
