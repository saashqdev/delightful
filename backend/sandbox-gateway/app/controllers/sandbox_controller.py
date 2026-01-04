"""
Sandbox API controller
"""
import logging
from typing import Dict, List, Optional

from fastapi import APIRouter, WebSocket, WebSocketDisconnect

from app.models.sandbox import (
    SandboxCreateResponse, SandboxInfo, SandboxData,
    SandboxListResponse, SandboxDetailResponse,
    SandboxDeleteResponse, DeleteResponse, SandboxCreateRequest
)
from app.services.sandbox_service import sandbox_service
from app.utils.exceptions import async_handle_exceptions

logger = logging.getLogger("sandbox_gateway")

# Create API router
router = APIRouter(prefix="/sandboxes", tags=["sandboxes"])


@router.post("", response_model=SandboxCreateResponse)
@async_handle_exceptions
async def create_sandbox(request: SandboxCreateRequest) -> SandboxCreateResponse:
    """
    Create a new sandbox container
    
    Args:
        request: Request body containing optional sandbox ID
    
    Returns:
        SandboxCreateResponse: Response containing sandbox ID and creation status
    """
    sandbox_id = await sandbox_service.create_sandbox(request.sandbox_id)
    return SandboxCreateResponse(
        data=SandboxData(
            sandbox_id=sandbox_id,
            status="created",
            message="Sandbox container created successfully"
        )
    )


@router.get("", response_model=SandboxListResponse)
async def list_sandboxes() -> SandboxListResponse:
    """
    List all sandbox containers
    
    Returns:
        SandboxListResponse: Sandbox container list response
    """
    sandboxes = sandbox_service.list_sandboxes()
    return SandboxListResponse(data=sandboxes)


@router.get("/{sandbox_id}", response_model=SandboxDetailResponse)
async def get_agent_container(sandbox_id: str) -> SandboxDetailResponse:
    """
    Get sandbox container information
    
    Args:
        sandbox_id: Sandbox ID
        
    Returns:
        SandboxDetailResponse: Sandbox container information response
    """
    sandbox = sandbox_service.get_agent_container(sandbox_id)
    if not sandbox:
        return SandboxDetailResponse(
            code=4004,
            message="Sandbox does not exist"
        )
    return SandboxDetailResponse(data=sandbox)


@router.delete("/{sandbox_id}", response_model=SandboxDeleteResponse)
async def delete_sandbox(sandbox_id: str) -> SandboxDeleteResponse:
    """
    Delete sandbox container
    
    Args:
        sandbox_id: Sandbox ID
        
    Returns:
        SandboxDeleteResponse: Delete operation response
    """
    sandbox_service.delete_sandbox(sandbox_id)
    return SandboxDeleteResponse(
        data=DeleteResponse(
            message=f"Sandbox {sandbox_id} deleted successfully"
        )
    )


@router.websocket("/ws/{sandbox_id}")
async def sandbox_websocket(websocket: WebSocket, sandbox_id: str) -> None:
    """
    Connect to the WebSocket of the specified sandbox container
    
    This endpoint will:
    1. Connect to the specified sandbox container
    2. Bidirectionally forward messages between client and container
    
    Args:
        websocket: WebSocket connection
        sandbox_id: Sandbox ID to connect to
    """
    try:
        # Let the sandbox service handle the WebSocket connection
        await sandbox_service.handle_websocket(websocket, sandbox_id)
    except WebSocketDisconnect:
        logger.info(f"WebSocket connection disconnected: {sandbox_id}")
    except Exception as e:
        logger.error(f"Error handling sandbox WebSocket: {e}") 