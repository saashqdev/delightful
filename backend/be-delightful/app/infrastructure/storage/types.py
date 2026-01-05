"""
Type definitions for storage SDK.
"""

from abc import abstractmethod
from enum import Enum
from typing import Any, BinaryIO, Callable, Dict, Literal, Optional, Protocol, Union

from pydantic import BaseModel, ConfigDict, Field, model_validator


class PlatformType(str, Enum):
    """Storage platform types."""

    tos = "tos"
    aliyun = "aliyun"  # Aliyun OSS
    local = "local"    # Local storage


class BaseStorageCredentials(BaseModel):
    """Base storage credentials model."""

    platform: PlatformType = Field(..., description="Storage platform type")

    model_config = ConfigDict(populate_by_name=True)

    @abstractmethod
    def get_dir(self) -> str:
        """Upload directory path"""
        pass

    @abstractmethod
    def get_public_access_base_url(self) -> Optional[str]:
        """Get base URL for public access (e.g., https://bucket.endpoint or https://host).
        Return None if the platform cannot provide a public URL.
        """
        pass


class TemporaryCredentialData(BaseModel):
    """STS temporary credential fields."""
    AccessKeyId: str = Field(..., description="Temporary access key ID")
    SecretAccessKey: str = Field(..., description="Temporary access key")
    SessionToken: str = Field(..., description="Security token")
    ExpiredTime: str = Field(..., description="Expiration time")
    CurrentTime: str = Field(..., description="Current time")

    model_config = ConfigDict(populate_by_name=True)


class TemporaryCredentials(BaseModel):
    """STS temporary credential payload."""
    host: str = Field(..., description="Storage service host URL")
    region: str = Field(..., description="TOS region")
    endpoint: str = Field(..., description="TOS endpoint URL")
    credentials: TemporaryCredentialData = Field(..., description="STS credential details")
    bucket: str = Field(..., description="TOS bucket name")
    dir: str = Field(..., description="Upload directory path")
    expires: int = Field(..., description="Expiration time (seconds)")
    callback: str = Field("", description="Callback URL")

    model_config = ConfigDict(populate_by_name=True)


class VolcEngineCredentials(BaseStorageCredentials):
    """VolcEngine TOS credentials model with STS support."""

    platform: Literal[PlatformType.tos] = Field(PlatformType.tos, description="Storage platform type")
    temporary_credential: TemporaryCredentials = Field(..., description="STS temporary credential")
    expire: Optional[int] = Field(None, description="Expiration timestamp")
    expires: Optional[int] = Field(None, description="Expiration timestamp alias")

    def get_dir(self) -> str:
        """Upload directory path"""
        return self.temporary_credential.dir

    def get_public_access_base_url(self) -> Optional[str]:
        """Get TOS public access base URL (from temporary_credential.host)."""
        host = self.temporary_credential.host
        if not host:
            return None
        # Ensure returned base URL includes protocol
        if not host.startswith(("http://", "https://")):
             # Default to https; adjust if needed
             host = f"https://{host}"
        # Trim trailing slash
        if host.endswith('/'):
            host = host[:-1]
        return host


# Aliyun OSS temporary credential data
class AliyunTemporaryCredentialData(BaseModel):
    """Aliyun STS temporary credential fields."""
    AccessKeyId: str = Field(..., description="Temporary access key ID")
    AccessKeySecret: str = Field(..., description="Temporary access key")
    SecurityToken: str = Field(..., description="Security token")
    Expiration: str = Field(..., description="Expiration time")

    model_config = ConfigDict(populate_by_name=True)


# Aliyun OSS credentials
class AliyunCredentials(BaseStorageCredentials):
    """Aliyun OSS credential model with STS support."""

    platform: Literal[PlatformType.aliyun] = Field(PlatformType.aliyun, description="Storage platform type")
    endpoint: str = Field(default=..., description="OSS endpoint URL")
    region: str = Field(..., description="OSS region")
    bucket: str = Field(..., description="OSS bucket name")
    dir: str = Field(..., description="Upload directory path")
    credentials: AliyunTemporaryCredentialData = Field(..., description="STS temporary credential")
    expire: Optional[int] = Field(None, description="Expiration timestamp")

    @model_validator(mode='before')
    @classmethod
    def _normalize_input(cls, data: Any) -> Any:
        if not isinstance(data, dict):
            return data

        input_data = data.copy()
        temp_cred_dict = input_data.pop('temporary_credential', None)

        if temp_cred_dict and isinstance(temp_cred_dict, dict):
            default_endpoint = None
            if temp_cred_dict.get('region'):
                default_endpoint = f"oss-{temp_cred_dict.get('region')}.aliyuncs.com"
            elif input_data.get('region'):
                 default_endpoint = f"oss-{input_data.get('region')}.aliyuncs.com"
            else:
                default_endpoint = "oss-cn-hangzhou.aliyuncs.com"

            input_data.setdefault('endpoint', temp_cred_dict.get('endpoint', default_endpoint))
            input_data.setdefault('region', temp_cred_dict.get('region'))
            input_data.setdefault('bucket', temp_cred_dict.get('bucket'))
            input_data.setdefault('dir', temp_cred_dict.get('dir', ''))

            if 'credentials' not in input_data:
                input_data['credentials'] = {
                    'AccessKeyId': temp_cred_dict.get('access_key_id'),
                    'AccessKeySecret': temp_cred_dict.get('access_key_secret'),
                    'SecurityToken': temp_cred_dict.get('sts_token'),
                    'Expiration': input_data.get('expire_time', '2099-12-31T23:59:59Z')
                }

        return input_data

    def get_dir(self) -> str:
        """Upload directory path"""
        return self.dir

    def get_public_access_base_url(self) -> Optional[str]:
        """Get Aliyun OSS public base URL (format: https://bucket.endpoint)."""
        if not self.bucket or not self.endpoint:
            return None

        # Strip protocol if present
        endpoint = self.endpoint
        if endpoint.startswith("http://"):
            endpoint = endpoint[len("http://"):]
        if endpoint.startswith("https://"):
            endpoint = endpoint[len("https://"):]

        # Remove trailing slash if any
        if endpoint.endswith('/'):
            endpoint = endpoint[:-1]

        # Always use https
        return f"https://{self.bucket}.{endpoint}"


# Type aliases
FileContent = Union[str, bytes, BinaryIO]
ProgressCallback = Callable[[float], None]
Headers = Dict[str, str]
Options = Dict[str, Any]


class StorageResponse(BaseModel):
    """Standard storage operation response."""

    key: str = Field(..., description="Full path/key of the file")
    platform: PlatformType = Field(..., description="Storage platform identifier")
    headers: Headers = Field(..., description="Response headers from the server")
    url: Optional[str] = Field(None, description="Public URL of the file if available")


class StorageUploader(Protocol):
    """Protocol for storage upload operations."""

    def upload(
        self, file: FileContent, key: str, credentials: BaseStorageCredentials, options: Optional[Options] = None
    ) -> StorageResponse:
        """Upload file to storage platform."""
        ...


# Local storage credentials
class LocalTemporaryCredential(BaseModel):
    """Local storage temporary credential model."""
    host: str = Field(..., description="Upload endpoint URL")
    dir: str = Field(..., description="Upload directory path")
    read_host: str = Field(..., description="Base URL for file reads")
    credential: str = Field("", description="Credential identifier")

    model_config = ConfigDict(populate_by_name=True)


class LocalCredentials(BaseStorageCredentials):
    """Local storage credential model."""

    platform: Literal[PlatformType.local] = Field(PlatformType.local, description="Storage platform type")
    temporary_credential: LocalTemporaryCredential = Field(..., description="Local storage temporary credential")
    expires: Optional[int] = Field(None, description="Expiration timestamp")

    def get_dir(self) -> str:
        """Upload directory path"""
        return self.temporary_credential.dir

    def get_public_access_base_url(self) -> Optional[str]:
        """Get local storage public access base URL."""
        read_host = self.temporary_credential.read_host
        if not read_host:
            return None

        # Ensure protocol is present
        if not read_host.startswith(("http://", "https://")):
            read_host = f"http://{read_host}"

        # Trim trailing slash
        if read_host.endswith('/'):
            read_host = read_host[:-1]

        return read_host
