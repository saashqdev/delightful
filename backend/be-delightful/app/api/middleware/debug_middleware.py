"""
Debug middleware

Logs detailed information for each incoming request and outgoing response for debugging
"""


from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware

from agentlang.logger import get_logger

logger = get_logger(__name__)


class DebugMiddleware(BaseHTTPMiddleware):
    """Middleware for logging detailed debugging information"""

    async def dispatch(self, request: Request, call_next):
        """
        Process request and log debug information

        Args:
            request: HTTP request object
            call_next: Next middleware or route handler function

        Returns:
            Response: HTTP response object
        """
        # Log request information
        logger.info(f"[DEBUG] Incoming request: {request.method} {request.url}")
        logger.info(f"[DEBUG] Request headers: {dict(request.headers)}")
        logger.info(f"[DEBUG] Client: {request.client}")

        # Process request
        response = await call_next(request)

        # Log response information
        logger.info(f"[DEBUG] Response status: {response.status_code}")
        logger.info(f"[DEBUG] Response headers: {dict(response.headers)}")

        return response
