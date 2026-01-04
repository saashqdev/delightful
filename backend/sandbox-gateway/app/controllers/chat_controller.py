"""
Chat history API controller
"""
import logging
from typing import Optional
from fastapi import APIRouter, HTTPException, Request
from fastapi.responses import StreamingResponse
import httpx

from app.services.sandbox_service import sandbox_service
from app.utils.exceptions import async_handle_exceptions

logger = logging.getLogger("sandbox_gateway")

# Create API router
router = APIRouter(prefix="/sandboxes", tags=["chat"])


@router.get("/{sandbox_id}/chat-history/download")
@async_handle_exceptions
async def proxy_chat_history_download(request: Request, sandbox_id: str) -> StreamingResponse:
    """
    Proxy download of chat history
    
    Args:
        request: FastAPI request object
        sandbox_id: Sandbox ID
        
    Returns:
        StreamingResponse: Proxied chat history download response stream
    """
    try:
        # Get sandbox container
        container = sandbox_service._get_agent_container_by_sandbox_id(sandbox_id)
        if not container:
            logger.error(f"Cannot find sandbox container: {sandbox_id}")
            raise HTTPException(status_code=404, detail=f"Cannot find sandbox {sandbox_id}")
            
        # Get container information
        container_info = sandbox_service._get_container_info(container)
        
        # Build API request URL
        target_url = f"http://{container_info.ip}:{container_info.ws_port}/api/chat-history/download"
        
        # Proxy request using httpx
        async with httpx.AsyncClient() as client:
            response = await client.get(
                target_url,
                headers={k: v for k, v in request.headers.items() if k.lower() not in ["host", "content-length"]},
                follow_redirects=True
            )
            
            # Return streaming response
            return StreamingResponse(
                response.aiter_bytes(),
                status_code=response.status_code,
                headers=dict(response.headers),
                media_type=response.headers.get("content-type")
            )
    except httpx.RequestError as e:
        error_msg = f"Proxy request error: {str(e)}"
        logger.error(error_msg)
        raise HTTPException(status_code=500, detail=error_msg) 