"""
File listener service, starts file upload process
"""

import asyncio
import os
import subprocess
from pathlib import Path

from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger, setup_logger
from agentlang.utils.process_manager import ProcessManager
from app.core.context.agent_context import AgentContext
from app.utils.executable_utils import get_executable_command

logger = get_logger(__name__)

def run_storage_uploader_process(cmd, project_root, log_level="INFO"):
    """Storage uploader subprocess running function"""
    try:
        setup_logger(log_name="storage_uploader_subprocess", console_level=log_level)
        logger_proc = get_logger("storage_uploader_subprocess_logger")
        logger_proc.info(f"Storage uploader subprocess started: {' '.join(cmd)}")
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1,
            universal_newlines=True,
            cwd=str(project_root)
        )
        for line in iter(process.stdout.readline, ''):
            if line:
                logger_proc.info(f"Storage uploader: {line.rstrip()}")
        process.stdout.close()
        return_code = process.wait()
        logger_proc.info(f"Storage upload process exited, return code: {return_code}")
    except Exception as e:
        logger.error(f"Storage uploader subprocess exception: {e}")
        import traceback
        logger.error(traceback.format_exc())

class FileListenerService:
    """File listener service, starts file upload process"""

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register standard file-related event listeners

        Args:
            agent_context: Agent context object
        """
        agent_context.add_event_listener(
            EventType.AFTER_INIT,
            FileListenerService._handle_after_init
        )
        logger.info("Registered file listener service for starting storage uploader after initialization.")

    @staticmethod
    async def _start_uploader_process(agent_context: AgentContext) -> None:
        """
        Start storage upload program (using ProcessManager to create and manage subprocess)
        """
        try:
            project_root = Path(__file__).resolve().parent.parent.parent.parent

            sandbox_id = agent_context.get_sandbox_id()
            organization_code = agent_context.get_organization_code()
            workspace_dir = agent_context.get_workspace_dir()
            log_level_to_pass = os.getenv("LOG_LEVEL", "INFO").upper()

            base_cmd = get_executable_command()
            cmd = base_cmd + [
                "storage-uploader",
                "watch",
                "--dir", str(workspace_dir),
                "--log-level", log_level_to_pass
            ]

            if sandbox_id:
                cmd.extend(["--sandbox", sandbox_id])
            else:
                logger.warning("Sandbox ID not found in AgentContext, uploader may not be able to register files properly.")

            if organization_code:
                cmd.extend(["--organization-code", organization_code])

            logger.info(f"Preparing to start storage upload program (ProcessManager mode): {' '.join(cmd)}")
            sandbox_info = f"Sandbox ID: {sandbox_id}" if sandbox_id else "Sandbox ID not set"
            logger.info(f"Using {sandbox_info}")

            await FileListenerService._terminate_existing_uploader()

            process_manager = ProcessManager.get_instance()
            worker_name = "storage_uploader"

            pid = await process_manager.start_worker(
                worker_name,
                run_storage_uploader_process,
                cmd,
                str(project_root),
                log_level_to_pass
            )

            logger.info(f"Storage uploader subprocess started, PID: {pid}")

        except Exception as e:
            logger.error(f"Failed to start storage upload program: {e}")
            import traceback
            logger.error(traceback.format_exc())

    @staticmethod
    async def _terminate_existing_uploader() -> None:
        """Terminate existing upload process"""
        try:
            process_manager = ProcessManager.get_instance()
            worker_name = "storage_uploader"
            worker_info = await process_manager.get_worker_info(worker_name)
            if worker_info and worker_name in worker_info:
                success = await process_manager.stop_worker(worker_name)
                if success:
                    logger.info("Terminated previous storage upload subprocess.")
                else:
                    logger.warning("Failed to terminate previous storage upload subprocess.")
        except Exception as e:
            logger.error(f"Failed to terminate previous upload process: {e}")

    @staticmethod
    async def _handle_after_init(event: Event) -> None:
        """
        Handle initialization completion event, start storage upload program.

        Args:
            event: Event object, should contain agent_context in event.data.
        """
        try:
            agent_context = event.data.agent_context
            if agent_context:
                project_root = Path(__file__).resolve().parent.parent.parent.parent 
                credentials_dir = project_root / ".credentials"
                if not credentials_dir.exists():
                    try:
                        credentials_dir.mkdir(parents=True, exist_ok=True)
                        logger.info(f"Created credentials directory: {credentials_dir}")
                    except Exception as e_mkdir:
                        logger.error(f"Failed to create credentials directory ({credentials_dir}): {e_mkdir}")

                asyncio.create_task(FileListenerService._start_uploader_process(agent_context))
                logger.info("Storage upload program startup task created (ProcessManager mode).")

        except AttributeError:
             logger.error("Failed to handle initialization event: Event data structure does not meet expectations (missing agent_context).")
        except Exception as e:
            logger.error(f"Error handling initialization event and starting upload program: {e}")
            import traceback
            logger.error(traceback.format_exc())
