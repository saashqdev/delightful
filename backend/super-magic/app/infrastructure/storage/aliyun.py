"""
Aliyun OSS upload implementation.
"""

import asyncio
import io
import time
from typing import BinaryIO, Optional

import aiohttp
import oss2
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
from .types import AliyunCredentials, BaseStorageCredentials, FileContent, Options, PlatformType, StorageResponse


class AliyunOSSUploader(AbstractStorage, BaseFileProcessor):
    """Aliyun OSS uploader implementation."""

    def set_credentials(self, credentials: BaseStorageCredentials):
        """Set storage credentials"""
        if not isinstance(credentials, AliyunCredentials):
            if isinstance(credentials, dict):
                try:
                    # Now can directly instantiate, Pydantic model internal validators will handle structure conversion
                    parsed_credentials = AliyunCredentials(**credentials)
                except Exception as e:
                    # Catch Pydantic validation errors or any other initialization errors
                    logger.error(f"Error parsing Aliyun credentials: {e}\nInput data: {credentials}") # Log input data for debugging
                    raise ValueError(f"Invalid credential format or conversion failed: {e}")
                self.credentials = parsed_credentials # Use successfully parsed and converted credentials
            else:
                raise ValueError(f"Expected AliyunCredentials or dict type, got {type(credentials)}")
        else:
            self.credentials = credentials # It's already an AliyunCredentials instance

    def _should_refresh_credentials_impl(self) -> bool:
        """Check specific logic for whether to refresh credentials"""
        if not self.credentials:
            return True

        credentials: AliyunCredentials = self.credentials
        if credentials.expire is None:
            return False

        # Refresh 180 seconds in advance
        return time.time() > credentials.expire - 180

    async def _refresh_credentials_impl(self):
        """Execute credential refresh operation"""
        logger.info("Starting to retrieve Aliyun OSS STS Token")

        # Precondition check already completed in _should_refresh_credentials
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

                actual_credential_data_wrapper = responseBody.get("data", {})
                if not actual_credential_data_wrapper:
                    logger.error("STS refresh response missing 'data' field or 'data' field is empty.")
                    # Consider raising an exception here if this is a critical failure
                    return 

                try:
                    # Use actual_credential_data_wrapper directly, Pydantic model's internal validator will handle it
                    self.credentials = AliyunCredentials(**actual_credential_data_wrapper)
                    logger.info("Aliyun OSS STS Token retrieved successfully (via Pydantic model conversion)")
                except Exception as e:
                    logger.error(f"Failed to parse refreshed STS credentials: {e}\nInput data from STS: {actual_credential_data_wrapper}") # Log input for debugging
                    # Handle errors as needed, e.g., raise e
                    return

    @with_refreshed_credentials
    async def upload(
        self,
        file: FileContent,
        key: str,
        options: Optional[Options] = None
    ) -> StorageResponse:
        """
        Asynchronously upload file to Aliyun Object Storage using OSS SDK.
        
        Args:
            file: File object, file path or file content
            key: File name/path
            options: Optional configuration including headers and progress callback
        
        Returns:
            StorageResponse: Standardized response object
        
        Raises:
            InitException: If required parameters are missing or file is too large
            UploadException: If upload fails (credential expired or network issues)
            ValueError: If file type is not supported or credential type is incorrect or metadata is not set
        """
        # At this point credentials have been refreshed by the decorator, use self.credentials directly
        if options is None:
            options = {}

        # 处理文件
        try:
            file_obj, file_size = self.process_file(file)

            # 文件大小限制检查
            if file_size > 5 * 1024 * 1024 * 1024:  # 5GB
                if isinstance(file, str):
                    file_obj.close()
                raise InitException(
                    InitExceptionCode.FILE_TOO_LARGE,
                    "aliyun",
                    file_name=key
                )

            # 获取凭证信息
            credentials: AliyunCredentials = self.credentials
            oss_creds = credentials.credentials

            # 创建OSS身份验证对象
            auth = oss2.StsAuth(
                oss_creds.AccessKeyId,
                oss_creds.AccessKeySecret,
                oss_creds.SecurityToken
            )

            # 创建OSS Bucket对象
            bucket = oss2.Bucket(auth, credentials.endpoint, credentials.bucket)

            try:
                # 使用异步方式执行上传操作
                loop = asyncio.get_event_loop()
                result = await loop.run_in_executor(
                    None,
                    lambda: bucket.put_object(key, file_obj)
                )

                # 关闭文件（如果是我们打开的）
                if isinstance(file, str):
                    file_obj.close()

                # 获取响应头
                headers = {}
                if hasattr(result, 'headers'):
                    headers = dict(result.headers)
                elif hasattr(result, 'request_info') and hasattr(result.request_info, 'headers'):
                    headers = dict(result.request_info.headers)

                # 返回标准响应
                return StorageResponse(
                    key=key,
                    platform=PlatformType.aliyun,
                    headers=headers
                )

            except Exception as e:
                logger.error(f"OSS error during async upload: {e}")
                if isinstance(file, str):
                    file_obj.close()
                raise UploadException(UploadExceptionCode.NETWORK_ERROR, str(e))

        except Exception as e:
            # 确保文件被关闭
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
        异步从阿里云对象存储下载文件。

        Args:
            key: 文件名/路径
            options: 可选配置

        Returns:
            BinaryIO: 文件内容的二进制流

        Raises:
            DownloadException: 如果下载失败
            ValueError: 如果凭证类型不正确或未设置元数据
        """
        # 此时凭证已经由装饰器刷新过，直接使用self.credentials

        if options is None:
            options = {}

        try:
            # 获取凭证信息
            credentials: AliyunCredentials = self.credentials
            oss_creds = credentials.credentials

            # 创建OSS身份验证对象
            auth = oss2.StsAuth(
                oss_creds.AccessKeyId,
                oss_creds.AccessKeySecret,
                oss_creds.SecurityToken
            )

            # 创建OSS Bucket对象
            bucket = oss2.Bucket(auth, credentials.endpoint, credentials.bucket)

            try:
                # 异步获取对象
                loop = asyncio.get_event_loop()
                result = await loop.run_in_executor(
                    None,
                    lambda: bucket.get_object(key)
                )

                # 读取内容到内存
                content = result.read()
                # 创建内存流
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
        异步检查存储平台上是否存在指定的文件。

        Args:
            key: 文件名/路径
            options: 可选配置

        Returns:
            bool: 如果文件存在则为True，否则为False

        Raises:
            InitException: 如果初始化参数缺失
            ValueError: 如果凭证类型错误或未设置元数据
        """
        credentials: AliyunCredentials = self.credentials
        if credentials is None:
            raise ValueError("未设置凭证")

        if options is None:
            options = {}

        try:
            # 获取凭证信息
            oss_creds = credentials.credentials

            # 创建OSS身份验证对象
            auth = oss2.StsAuth(
                oss_creds.AccessKeyId,
                oss_creds.AccessKeySecret,
                oss_creds.SecurityToken
            )

            # 创建OSS Bucket对象
            bucket = oss2.Bucket(auth, credentials.endpoint, credentials.bucket)

            try:
                # 异步检查对象是否存在
                loop = asyncio.get_event_loop()
                await loop.run_in_executor(
                    None,
                    lambda: bucket.head_object(key)
                )
                return True
            except Exception as e:
                # 对象不存在，或者发生了其他错误
                logger.error(f"Error during head_object for key '{key}': {type(e).__name__} - {e}")
                return False

        except Exception as e:
            logger.error(f"Error checking file existence: {e}")
            return False 
