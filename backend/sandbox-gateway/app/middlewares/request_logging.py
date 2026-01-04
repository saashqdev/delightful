"""
Request logging middleware - Records HTTP request information for all APIs
"""
import json
import logging
from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware

logger = logging.getLogger("sandbox_gateway")

class RequestLoggingMiddleware(BaseHTTPMiddleware):
    """Request logging middleware that records detailed information for all HTTP requests"""
    
    async def dispatch(self, request: Request, call_next):
        # Remove restriction on "/sandboxes" path, record all API requests
        # Build basic request information
        request_info = {
            "method": request.method,  # Record all HTTP methods: GET, POST, PUT, DELETE, PATCH, OPTIONS, etc.
            "url": str(request.url),
            "path_params": request.path_params,
            "query_params": dict(request.query_params),
            "headers": {k: v for k, v in request.headers.items() if k.lower() != "authorization"}  # Exclude sensitive headers
        }
        
        # For POST/PUT/PATCH requests, additionally record request body
        if request.method in ["POST", "PUT", "PATCH"]:
            try:
                # Save request body content
                body = await request.body()
                
                # Try to parse JSON
                if body:
                    try:
                        body_str = body.decode("utf-8")
                        # Try to parse as JSON for better formatting
                        json_body = json.loads(body_str)
                        request_info["body"] = json_body
                    except (json.JSONDecodeError, UnicodeDecodeError):
                        # If not JSON or cannot decode, save string representation of raw bytes
                        request_info["body"] = f"<binary data: {len(body)} bytes>"
                
                # Important: Set _body so it can be read again later
                request._body = body
            except Exception as e:
                logger.warning(f"Error reading request body: {e}")
        
        # Record request information - requests from all HTTP methods will be logged
        logger.info(f"API request: {json.dumps(request_info, ensure_ascii=False, default=str)}")
        
        # Continue processing request
        response = await call_next(request)
        return response 