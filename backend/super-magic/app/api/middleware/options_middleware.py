"""
OPTIONS request handling middleware

Specifically handles cross-origin preflight requests (OPTIONS)
"""


from fastapi import Request, Response
from starlette.middleware.base import BaseHTTPMiddleware

from agentlang.logger import get_logger

logger = get_logger(__name__)


class OptionsMiddleware(BaseHTTPMiddleware):
    """Middleware for handling OPTIONS preflight requests"""

    async def dispatch(self, request: Request, call_next):
        """
        Handle OPTIONS requests

        Args:
            request: HTTP request object
            call_next: Next middleware or route handler function

        Returns:
            Response: HTTP response object
        """
        if request.method == "OPTIONS":
            logger.info("Received preflight request: OPTIONS")
            # Create empty response with status code 200
            response = Response(status_code=200)
            # Add allowed headers
            response.headers["Access-Control-Allow-Origin"] = "*"
            response.headers["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS, PATCH"
            response.headers["Access-Control-Allow-Headers"] = "*"
            response.headers["Access-Control-Allow-Credentials"] = "true"
            response.headers["Access-Control-Max-Age"] = "3600"
            return response

        # Non-OPTIONS request, continue processing
        return await call_next(request)
