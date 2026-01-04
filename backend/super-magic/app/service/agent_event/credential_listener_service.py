"""
File listener service, starts file upload process
"""

import asyncio
import multiprocessing
import os
import subprocess
import sys
from pathlib import Path

from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger, setup_logger
from app.core.context.agent_context import AgentContext

logger = get_logger(__name__)

def run_tos_uploader(cmd, project_root, log_level="INFO"):
    """TOS uploader subprocess running function
    
    Args:
        cmd: Command line argument list
        project_root: Project root directory
        log_level: Log level
    """
    try:
        # Set up subprocess logging
        setup_logger(log_name="app", console_level=log_level)
        logger = get_logger("tos_uploader_process")

        logger.info(f"TOS uploader subprocess started: {' '.join(cmd)}")

        # Start uploader process
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1,
            cwd=str(project_root)
        )

        # Read and log output
        for line in iter(process.stdout.readline, ''):
            if line:
                logger.info(f"TOS uploader: {line.rstrip()}")

        process.stdout.close()
        return_code = process.wait()
        logger.info(f"TOS upload process exited, return code: {return_code}")

    except Exception as e:
        logger.error(f"TOS uploader subprocess exception: {e}")
        import traceback
        logger.error(traceback.format_exc())

class FileListenerService:
    """File listener service, starts file upload process"""

    # Use class variable to track upload process
    _uploader_process = None

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register standard file-related event listeners
        
        Args:
            agent_context: Agent context object
        """
        # Listen to initialization completion event
        agent_context.add_event_listener(
            EventType.AFTER_INIT, 
            FileListenerService._handle_after_init
        )

        # Listen to client chat event
        # agent_context.add_event_listener(
        #     EventType.AFTER_CLIENT_CHAT,
        #     FileListenerService._handle_after_client_chat
        # )

        logger.info("Registered file listener")

    @staticmethod
    async def _start_tos_uploader(agent_context: AgentContext) -> None:
        """
        Start TOS upload program (using multiprocessing to create independent subprocess)
        
        Args:
            agent_context: Agent context object
        """
        try:
            # Get project root directory
            project_root = Path(__file__).resolve().parent.parent.parent.parent
            uploader_path = project_root / "bin" / "tos_uploader.py"

            if not uploader_path.exists():
                logger.error(f"TOS upload program does not exist: {uploader_path}")
                return

            # Get sandbox ID
            sandbox_id = agent_context.get_sandbox_id()
            if not sandbox_id:
                logger.warning("Sandbox ID not set in AgentContext, TOS upload program may not work properly")

            # Get organization code
            organization_code = agent_context.get_organization_code()

            # Working directory
            workspace_dir = agent_context.get_workspace_dir()

            # Use the same log level as current
            log_level = os.getenv("LOG_LEVEL", "INFO")

            # Build command
            cmd = [
                sys.executable,  # Use current Python interpreter
                str(uploader_path),
                "watch",
                "--dir", workspace_dir,
                "--use-context"  # Use config/upload_credentials.json
            ]

            # Add sandbox ID (if available)
            if sandbox_id:
                cmd.extend(["--sandbox", sandbox_id])

            # Add organization code if available
            if organization_code:
                cmd.extend(["--organization-code", organization_code])

            # Log startup command
            logger.info(f"Preparing to start TOS upload program (subprocess mode): {' '.join(cmd)}")

            sandbox_info = f"Sandbox ID: {sandbox_id}" if sandbox_id else "Sandbox ID not set"
            logger.info(f"Using {sandbox_info}")

            # Terminate existing process (if any)
            await FileListenerService._terminate_existing_uploader()

            # Create new subprocess to run uploader
            loop = asyncio.get_event_loop()

            def start_uploader_process():
                process = multiprocessing.Process(
                    target=run_tos_uploader,
                    args=(cmd, project_root, log_level),
                    name="TOSUploader"
                )
                process.daemon = True  # Set as daemon process, automatically ends when main process ends
                process.start()
                return process

            # Use run_in_executor to start subprocess
            process = await loop.run_in_executor(None, start_uploader_process)

            # Save process reference
            FileListenerService._uploader_process = process

            logger.info(f"TOS uploader subprocess started, PID: {process.pid}")

        except Exception as e:
            logger.error(f"Failed to start TOS upload program: {e}")
            import traceback
            logger.error(traceback.format_exc())

    @staticmethod
    async def _terminate_existing_uploader() -> None:
        """Terminate existing upload process"""
        if FileListenerService._uploader_process is not None:
            try:
                process = FileListenerService._uploader_process

                # Check if process is still running
                if process.is_alive():
                    # Use loop.run_in_executor to execute terminate operation
                    loop = asyncio.get_event_loop()

                    def terminate_process():
                        process.terminate()
                        process.join(timeout=3)  # Wait up to 3 seconds
                        if process.is_alive():
                            logger.warning("TOS upload process did not terminate normally within timeout, forcing termination")
                            process.kill()
                        return True

                    await loop.run_in_executor(None, terminate_process)
                    logger.info("Terminated previous TOS upload subprocess")
                else:
                    logger.info("Previous TOS upload subprocess is no longer running")

            except Exception as e:
                logger.error(f"Failed to terminate previous upload process: {e}")

            # Reset process reference
            FileListenerService._uploader_process = None

    @staticmethod
    async def _handle_after_init(event: Event) -> None:
        """
        Handle initialization event, start upload program
        
        Args:
            event: Event object
        """
        try:
            # Get agent_context from event data
            agent_context = event.data.agent_context

            if agent_context:
                # Ensure credentials directory exists
                if not os.path.exists(".credentials"):
                    os.makedirs(".credentials", exist_ok=True)

                # Create async task to start TOS upload program, without blocking current flow
                asyncio.create_task(FileListenerService._start_tos_uploader(agent_context))
                logger.info("TOS upload program startup task created (subprocess mode)")

        except Exception as e:
            logger.error(f"Error handling initialization event to start upload program: {e}")

    # @staticmethod
    # async def _handle_after_init(event: Event) -> None:
    #     """
    #     处理初始化后事件，记录相关信息

    #     Args:
    #         event: 事件对象
    #     """
    #     try:
    #         # 从事件数据中安全地获取agent_context
    #         agent_context = None
    #         if hasattr(event.data, "agent_context") and event.data.agent_context:
    #             agent_context = event.data.agent_context
    #             logger.info("已捕获初始化完成事件，agent_context已设置")
    #         else:
    #             logger.info("初始化事件数据中未找到agent_context属性")

    #     except Exception as e:
    #         logger.error(f"处理初始化完成事件出错: {e}") 
