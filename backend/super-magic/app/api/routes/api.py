"""
API route centralized registration module

This module is responsible for centralizing the registration of all API routes, including RESTful API and WebSocket routes,
simplifying route management and maintenance.
"""
from fastapi import APIRouter

from app.api.routes.chat_history import router as chat_history_router

# Import all routers that need to be registered
from app.api.routes.websocket import router as websocket_router

# Create main router with unified prefix
api_router = APIRouter(prefix="/api")

# Register WebSocket route
api_router.include_router(websocket_router)
# Register chat history route
api_router.include_router(chat_history_router)

@api_router.get("/health", tags=["base"])
async def health_check():
    """Health check endpoint for monitoring service status"""
    return {"status": "healthy"}
