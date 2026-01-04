"""
WebSocket server command module
"""
import asyncio
import importlib
import importlib.metadata
import os
import signal
import socket
from contextlib import asynccontextmanager

import uvicorn
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from uvicorn.config import Config

from agentlang.logger import get_logger
from agentlang.utils.process_manager import ProcessManager
from app.api.middleware import RequestLoggingMiddleware
from app.api.routes import api_router
from app.api.routes.websocket import router as websocket_router
from app.service.agent_dispatcher import AgentDispatcher
from app.service.idle_monitor_service import IdleMonitorService

# Get logger
logger = get_logger(__name__)

# Store server instance and global variables
ws_server = None
_app = None  # Internal variable to store FastAPI application instance


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Service lifecycle management"""
    # On startup
    logger.info("Service is starting...")

    # Print Git commit ID
    git_commit_id = os.getenv("GIT_COMMIT_ID", "Unknown")
    logger.info(f"Current version Git commit ID: {git_commit_id}")

    logger.info("WebSocket service will listen on port: 8002")
    yield
    # On shutdown
    logger.info("Service is shutting down...")


def create_app() -> FastAPI:
    """Create and configure FastAPI application instance"""
    # Create FastAPI application
    app = FastAPI(
        title="Super Magic API",
        description="Super Magic API and WebSocket service",
        version="0.1.0",
        lifespan=lifespan,
    )

    # Add request logging middleware
    app.add_middleware(RequestLoggingMiddleware)

    # Add CORS middleware - Modified config to solve browser cross-origin issues
    app.add_middleware(
        CORSMiddleware,
        allow_origins=["*"],  # Allow all origins, can also specify specific domains like ["http://localhost:3000"]
        allow_credentials=True,
        allow_methods=["GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH"],  # Explicitly specify allowed methods
        allow_headers=["*"],  # Allow all headers
        expose_headers=["*"],  # Expose all headers
        max_age=600,  # Preflight request result cache time in seconds
    )

    # Register routes
    app.include_router(api_router)
    app.include_router(websocket_router)

    return app


def get_app() -> FastAPI:
    """Get FastAPI application instance, avoid circular imports

    Returns:
        FastAPI: Application instance
    """
    global _app
    if _app is None:
        _app = create_app()
    return _app


class CustomServer(uvicorn.Server):
    """Custom uvicorn Server class for proper signal handling"""

    def install_signal_handlers(self) -> None:
        """Do not install signal handlers, use our own handling method"""
        pass

    async def shutdown(self, sockets=None):
        """Attempt to gracefully shutdown server"""
        logger.info("Shutting down uvicorn server...")
        await super().shutdown(sockets=sockets)


def start_ws_server():
    """Start WebSocket server"""
    # Get log level from environment variable
    log_level = os.getenv("LOG_LEVEL", "INFO")

    # Get FastAPI application instance
    app = get_app()

    # Create async function to start WS server only
    async def run_ws_only():
        process_manager = ProcessManager.get_instance()
        # Get and process entry_points
        try:
            # Use pkg_resources to process entry_points
            process_entry_points = list(importlib.metadata.entry_points(group='command.ws_server.process'))

            logger.info(f"Found {len(process_entry_points)} ws_server process entry_points")

            # Load all found entry_points
            for entry_point in process_entry_points:
                logger.info(f"Loading process: {entry_point.name}")
                value = entry_point.value
                value = value.split(":")
                module_name = value[0]
                function_name = value[1]
                logger.info(f"Loading module: {module_name}, function: {function_name}")
                loader = importlib.import_module(module_name)
                await loader.load(process_manager, log_level)
        except Exception as e:
            logger.error(f"Error loading entry_points: {e}")

        dispatcher = AgentDispatcher.get_instance()
        await dispatcher.setup()

        IdleMonitorService.get_instance().start()

        # Use code similar to original main() function, but only start WebSocket service
        # Create and configure WebSocket socket
        ws_port = 8002
        ws_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        ws_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        ws_socket.bind(("0.0.0.0", ws_port))

        logger.info(f"WebSocket service will listen on port: {ws_port}")

        # 创建uvicorn配置
        uvicorn_config = Config(
            app,
            host="0.0.0.0",
            port=0,
            log_level=log_level.lower(),
            ws_ping_interval=None,
        )

        # 启动服务器
        global ws_server
        ws_server = CustomServer(uvicorn_config)

        # 同样需要处理信号
        shutdown_event = asyncio.Event()

        # 设置信号处理器
        def handle_signal(sig, frame):
            logger.info(f"收到信号 {sig}，准备关闭服务...")
            shutdown_event.set()

        # 注册信号处理器
        original_sigint_handler = signal.getsignal(signal.SIGINT)
        original_sigterm_handler = signal.getsignal(signal.SIGTERM)
        signal.signal(signal.SIGINT, handle_signal)
        signal.signal(signal.SIGTERM, handle_signal)

        try:
            # 启动WS服务
            ws_task = asyncio.create_task(ws_server.serve(sockets=[ws_socket]))

            # 等待关闭事件
            await shutdown_event.wait()
            logger.info("正在停止WebSocket服务...")
        except Exception as e:
            logger.error(f"WebSocket服务运行过程中出现错误: {e}")
        finally:
            # 优雅关闭服务器
            if ws_server:
                ws_server.should_exit = True

            # 等待一小段时间让lifespan正常关闭
            await asyncio.sleep(0.5)

            # 取消服务任务
            ws_task.cancel()

            await process_manager.stop_all()

            IdleMonitorService.get_instance().stop()

            try:
                # 等待任务完成
                await asyncio.gather(ws_task, return_exceptions=True)
            except Exception as e:
                logger.error(f"关闭WebSocket服务时出现错误: {e}")

            # 恢复原始信号处理器
            signal.signal(signal.SIGINT, original_sigint_handler)
            signal.signal(signal.SIGTERM, original_sigterm_handler)

            # 关闭socket
            ws_socket.close()
            logger.info("WebSocket服务已完全关闭")

    # 运行异步函数
    asyncio.run(run_ws_only())
