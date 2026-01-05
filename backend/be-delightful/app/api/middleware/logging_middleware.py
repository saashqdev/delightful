"""
Request logging middleware.

Records detailed HTTP request and response information.
"""

import time

from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware

from agentlang.logger import get_logger

logger = get_logger(__name__)


class RequestLoggingMiddleware(BaseHTTPMiddleware):
    """Middleware that logs request and response details."""

    async def dispatch(self, request: Request, call_next):
        """
        Handle request and log details.

        Args:
            request: HTTP request object
            call_next: Next middleware or route handler

        Returns:
            Response: HTTP response object
        """
        start_time = time.time()

        # Log request info
        logger.info(f"Request started: {request.method} {request.url.path}")

        # Log detailed headers, especially connection-related
        headers = dict(request.headers)
        connection_type = headers.get("connection", "unspecified")
        user_agent = headers.get("user-agent", "unspecified")

        logger.info(f"Connection: {connection_type}, User-Agent: {user_agent}")
        logger.info(f"Request headers: {headers}")

        try:
            # Call the next middleware or route handler
            response = await call_next(request)

            # Compute processing time
            process_time = time.time() - start_time
            logger.info(
                f"Request finished: {request.method} {request.url.path} - status {response.status_code}, duration: {process_time:.4f}s"
            )

            # Log response headers
            logger.info(f"Response headers: {dict(response.headers)}")

            return response
        except Exception as e:
            # Log exception details
            process_time = time.time() - start_time
            logger.error(f"Request error: {request.method} {request.url.path} - error: {e!s}, duration: {process_time:.4f}s")
            logger.exception("Request handling exception details:")
            raise
