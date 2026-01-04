#!/usr/bin/env python3
"""
Sandbox gateway service
Integrates Docker container management, WebSocket communication, and FastAPI service
"""

import asyncio
import logging
import os
import signal
import sys
from typing import Any

import uvicorn
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

from fastapi import FastAPI
from uvicorn.config import Config

from app.config import settings
from app.controllers import health_router, sandbox_router, chat_history_router
from app.middlewares import TokenValidationMiddleware, RequestLoggingMiddleware
from app.services.sandbox_service import sandbox_service
from app.utils.logging import setup_logging

# Configure logging
logger = setup_logging(
    log_level=settings.log_level,
    log_file=settings.log_file
)


# Override uvicorn.Server class for proper signal handling
class CustomServer(uvicorn.Server):
    """Custom uvicorn Server class for proper signal handling"""
    
    def install_signal_handlers(self) -> None:
        """Do not install signal handlers, use our own approach"""
        pass
    
    async def shutdown(self, sockets=None):
        """Attempt to gracefully shut down the server"""
        logger.info("Shutting down uvicorn server...")
        await super().shutdown(sockets=sockets)


# Create FastAPI application
app = FastAPI(
    title="Sandbox Container Gateway",
    description="Sandbox container gateway service providing Docker container management and WebSocket communication",
    version="0.1.0",
    docs_url="/docs" if os.environ.get("ENABLE_DOCS", "True").lower() in ("true", "1", "yes") else None,
    redoc_url="/redoc" if os.environ.get("ENABLE_DOCS", "True").lower() in ("true", "1", "yes") else None,
)

# Register middleware
app.add_middleware(TokenValidationMiddleware)
app.add_middleware(RequestLoggingMiddleware)  # Add request logging middleware

# Register routes
app.include_router(health_router)
app.include_router(sandbox_router)
app.include_router(chat_history_router)


# Global server instance
server = None


# Signal handling
def handle_exit(sig: Any, frame: Any) -> None:
    """
    Handle exit signal
    
    Args:
        sig: Signal
        frame: Frame
    """
    global server
    logger.info(f"Received signal {sig}, shutting down service...")
    if server:
        server.should_exit = True
    else:
        sys.exit(0)


# Register signal handlers
signal.signal(signal.SIGINT, handle_exit)
signal.signal(signal.SIGTERM, handle_exit)


@app.on_event("startup")
async def startup_event() -> None:
    """Execute on application startup"""
    # Check if Docker image exists
    try:
        image_name = sandbox_service.image_name
        sandbox_service.docker_client.images.get(image_name)
        logger.info(f"Sandbox container image '{image_name}' is ready")
    except Exception as e:
        logger.warning(f"Warning: Sandbox container image check failed: {str(e)}")
        logger.warning("Please ensure the image is built, otherwise sandbox functionality will not work properly")

    # Start sandbox container cleanup task
    asyncio.create_task(sandbox_service.cleanup_idle_containers())
    logger.info("Sandbox gateway started, beginning periodic cleanup of idle sandbox containers")


async def start_async() -> None:
    """Asynchronously start sandbox gateway service"""
    global server
    port = settings.sandbox_gateway_port

    # Create uvicorn configuration
    uvicorn_config = Config(
        app,
        host="0.0.0.0",
        port=port,
        log_level=settings.log_level.lower(),
        ws_ping_interval=None,  # Disable WebSocket ping
    )

    # Start FastAPI application
    logger.info(f"Starting sandbox gateway service, listening on 0.0.0.0:{port}")
    logger.info(f"Using sandbox image: {sandbox_service.image_name}")
    logger.info(f"Application environment: {settings.app_env}")
    
    # Use custom server to start
    server = CustomServer(uvicorn_config)
    
    # Create shutdown event
    shutdown_event = asyncio.Event()
    
    # Modify signal handler to set event
    def handle_signal(sig, frame):
        """Handle signal"""
        # Use signal.Signals enum to get signal name
        signal_name = signal.Signals(sig).name
        
        logger.info(f"Received signal {signal_name}, preparing to shut down service...")
        server.should_exit = True
        shutdown_event.set()
    
    # Set up signal handlers
    original_sigint_handler = signal.getsignal(signal.SIGINT)
    original_sigterm_handler = signal.getsignal(signal.SIGTERM)
    signal.signal(signal.SIGINT, handle_signal)
    signal.signal(signal.SIGTERM, handle_signal)
    
    # Create server task
    server_task = asyncio.create_task(server.serve())
    
    try:
        # Wait for shutdown event or server task completion
        await asyncio.wait(
            [asyncio.create_task(shutdown_event.wait()), server_task],
            return_when=asyncio.FIRST_COMPLETED
        )
    except Exception as e:
        logger.error(f"Error occurred during service operation: {e}")
    finally:
        # Ensure server is marked for exit
        server.should_exit = True
        
        # Wait a short time for lifespan to close properly
        await asyncio.sleep(0.5)
        
        # Cancel server task
        if not server_task.done():
            server_task.cancel()
            try:
                await asyncio.wait_for(server_task, timeout=5.0)
            except (asyncio.CancelledError, asyncio.TimeoutError):
                pass
        
        # Restore original signal handlers
        signal.signal(signal.SIGINT, original_sigint_handler)
        signal.signal(signal.SIGTERM, original_sigterm_handler)
        
        logger.info("Service has fully shut down")


def start() -> None:
    """Synchronous entry point for starting the sandbox gateway service"""
    try:
        asyncio.run(start_async())
    except KeyboardInterrupt:
        logger.info("User terminated the program")
    except Exception as e:
        logger.error(f"Error starting service: {e}")
        sys.exit(1)


if __name__ == "__main__":
    try:
        # Start service
        start()
    except KeyboardInterrupt:
        logger.info("User terminated the program")
    except Exception as e:
        logger.error(f"Error starting service: {e}")
        sys.exit(1) 
