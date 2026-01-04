"""
Health check controller
"""
import logging
from typing import Dict

from fastapi import APIRouter

logger = logging.getLogger("sandbox_gateway")

# Create API router
router = APIRouter(tags=["health"])


@router.get("/")
async def root() -> Dict[str, str]:
    """
    Service root path
    
    Returns:
        Dict: Service information
    """
    return {
        "service": "Sandbox Container Gateway",
        "status": "running",
        "version": "0.1.0"
    }


@router.get("/health")
async def health_check() -> Dict[str, str]:
    """
    Health check endpoint
    
    Returns:
        Dict: Health status
    """
    return {
        "status": "healthy",
        "message": "Service is running normally"
    } 