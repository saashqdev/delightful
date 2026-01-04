"""
Sandbox related data models
"""
from typing import Optional, TypeVar, Generic, Any, List
from pydantic import BaseModel


class BaseResponse(BaseModel):
    """Base response model"""
    code: int = 1000
    message: str = "success"
    data: Optional[Any] = None


T = TypeVar('T')


class Response(BaseResponse, Generic[T]):
    """Generic response model"""
    data: Optional[T] = None


class SandboxCreateRequest(BaseModel):
    """Sandbox creation request model"""
    sandbox_id: Optional[str] = None


class SandboxData(BaseModel):
    """Sandbox creation data model"""
    sandbox_id: str
    status: str
    message: str


class SandboxCreateResponse(Response[SandboxData]):
    """Response model for sandbox creation"""
    pass


class SandboxInfo(BaseModel):
    """Sandbox information model"""
    sandbox_id: str
    status: str
    created_at: float
    started_at: Optional[float] = None
    ip_address: Optional[str] = None


class SandboxListResponse(Response[List[SandboxInfo]]):
    """Sandbox list response model"""
    pass


class SandboxDetailResponse(Response[SandboxInfo]):
    """Sandbox detail response model"""
    pass


class DeleteResponse(BaseModel):
    """Delete operation response data model"""
    message: str


class SandboxDeleteResponse(Response[DeleteResponse]):
    """Sandbox deletion response model"""
    pass


class ContainerInfo(BaseModel):
    """Container information model for internal processing"""
    id: str
    ip: Optional[str] = None
    ws_port: int = 8002
    created_at: float
    started_at: Optional[float] = None
    status: str
    exited_at: Optional[float] = None