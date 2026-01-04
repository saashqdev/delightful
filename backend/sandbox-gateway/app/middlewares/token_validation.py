"""
Token validation middleware - Checks if the token in API requests is valid
"""
import logging
import os
from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import JSONResponse

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

        # Get token from request header
        token = request.headers.get("token")

        # Verify token
        if token != self.token:
            logger.warning(f"Invalid token: {token}")
            # Return 401 response directly instead of raising an exception
            return JSONResponse(
                status_code=401,
                content={"detail": "Unauthorized"}
            )
        
        # Token is valid, continue processing request
        return await call_next(request) 