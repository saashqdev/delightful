"""
Middleware module - Provides request interception features such as token validation
"""
import logging
import os
from fastapi import Request, HTTPException
from starlette.middleware.base import BaseHTTPMiddleware

logger = logging.getLogger("sandbox_gateway")

class TokenValidationMiddleware(BaseHTTPMiddleware):
    """Token validation middleware, checks if the token in the request header is valid"""
    
    def __init__(self, app):
        super().__init__(app)
        self.token = os.environ.get("API_TOKEN")  # Get token from environment variable
        if not self.token:
            logger.warning("API_TOKEN environment variable not set, API security validation disabled")
    
    async def dispatch(self, request: Request, call_next):
        # If token is not set, skip validation
        if not self.token:
            return await call_next(request)
            
        # If it's an OPTIONS request or WebSocket request or health check, skip validation
        path = request.url.path
        if request.method == "OPTIONS" or path.startswith("/ws") or path == "/health" or path == "/":
            return await call_next(request)
        
        # Get token from request header
        token = request.headers.get("token")
        
        # Verify token
        if token != self.token:
            logger.warning(f"Invalid token: {token}")
            raise HTTPException(status_code=401, detail="Invalid token")
        
        # Token is valid, continue processing request
        return await call_next(request) 