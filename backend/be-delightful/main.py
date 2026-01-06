# AI Warning: Environment variable loading must be placed before all imports
from dotenv import load_dotenv
load_dotenv(override=True)

import os
import sys
from pathlib import Path
import multiprocessing
import asyncio
from typing import Optional

# Get project root directory, using parent directory of file location
project_root = Path(__file__).resolve().parent

# Add project root directory to Python path
sys.path.append(str(project_root))

# Initialize PathManager
from app.paths import PathManager
PathManager.set_project_root(project_root)
from agentlang.context.application_context import ApplicationContext
ApplicationContext.set_path_manager(PathManager)

import traceback
import typer

from agentlang.logger import setup_logger, get_logger, configure_logging_intercept
from agentlang.context.application_context import ApplicationContext
from app.command.ws_server import start_ws_server
from app.command.storage_uploader_tool import start_storage_uploader_watcher
from agentlang.utils.file import clear_directory_contents

# Initialize logging configuration
os.makedirs("logs", exist_ok=True)
# Use the configuration function from agentlang.logger module, get log level from environment variable, default to INFO
log_level = os.getenv("LOG_LEVEL", "INFO")
# Set up logger and automatically save to ApplicationContext
logger = setup_logger(log_name="app", console_level=log_level)
configure_logging_intercept()


# Get the logger named for the current module
logger = get_logger(__name__)

cli = typer.Typer(help="SuperDelightful CLI", no_args_is_help=True)

app_cmds = typer.Typer(help="Startup related commands")
cli.add_typer(app_cmds, name="start")

storage_uploader_cmds = typer.Typer(help="Generic storage uploader tool commands") 
cli.add_typer(storage_uploader_cmds, name="storage-uploader")

# Clean related functions (from HEAD)
async def clean_chat_history():
    """
    Asynchronously clean files in .chat_history directory
    but preserve the directory itself
    """
    result = await clear_directory_contents(PathManager.get_chat_history_dir())
    if not result:
        logger.error("Failed to clean chat history")
    return result

async def clean_workspace():
    """
    Asynchronously clean files in .workspace directory
    but preserve the directory itself
    """
    result = await clear_directory_contents(PathManager.get_workspace_dir())
    if not result:
        logger.error("Failed to clean workspace")
    return result

async def clean_directories():
    """
    Asynchronously clean files in .chat_history and .workspace directories
    but preserve the directories themselves
    """
    chat_result, workspace_result = await asyncio.gather(
        clean_chat_history(),
        clean_workspace()
    )
    return chat_result and workspace_result

@cli.command("clean", help="Clean history chat records and workspace files") # from HEAD
def clean_command(
    clean_all: bool = typer.Option(False, "--all", "-a", help="Clean history chat records and workspace files"), # Changed -c to -a to avoid conflict
    clean_chat: bool = typer.Option(False, "--chat", "-cc", help="Only clean history chat record files"),
    clean_workspace: bool = typer.Option(False, "--workspace", "-cw", help="Only clean workspace files")
):
    """Command wrapper for cleaning related directories"""
    try:
        cleaned = False
        if not (clean_all or clean_chat or clean_workspace):
            clean_all = True
            
        if clean_all:
            result = asyncio.run(clean_directories())
            if result:
                logger.info("History chat records and workspace files cleaned successfully")
                cleaned = True
            else:
                logger.error("Failed to clean history chat records and workspace files")
        elif clean_chat:
            result = asyncio.run(clean_chat_history())
            if result:
                logger.info("History chat records cleaned successfully")
                cleaned = True
            else:
                logger.error("Failed to clean history chat records")
        elif clean_workspace:
            result = asyncio.run(clean_workspace())
            if result:
                logger.info("Workspace files cleaned successfully")
                cleaned = True
            else:
                logger.error("Failed to clean workspace files")
                
        return cleaned
    except Exception as e:
        logger.error(f"Error occurred during cleanup: {e}")
        logger.error(f"Stack trace: {traceback.format_exc()}")
        return False

@app_cmds.command("ws-server", help="Start WebSocket server")
def ws_server_command(
    clean_all: bool = typer.Option(False, "--clean", "-c", help="Clean history chat records and workspace files before startup"),
    clean_chat: bool = typer.Option(False, "--clean-chat", "-cc", help="Only clean history chat records before startup"),
    clean_workspace: bool = typer.Option(False, "--clean-workspace", "-cw", help="Only clean workspace files before startup")
):
    """Command wrapper for starting WebSocket server"""
    try:
        if clean_all or clean_chat or clean_workspace:
            clean_command(clean_all=clean_all, clean_chat=clean_chat, clean_workspace=clean_workspace)
            
        if not os.getenv("SANDBOX_ID"):
            os.environ["SANDBOX_ID"] = "default"
        # Start WebSocket server
        start_ws_server()
    except Exception as e:
        logger.error(f"Error occurred when starting WebSocket server: {e}")
        logger.error(f"Stack trace: {traceback.format_exc()}")

@storage_uploader_cmds.command("watch", help="Monitor directory changes and automatically upload to storage backend configured by environment variable STORAGE_PLATFORM")
def storage_uploader_watch_command(
    # Parameters from feature/support-aliyun-oss
    sandbox: Optional[str] = typer.Option(None, "--sandbox", help="Sandbox ID, default is None"),
    dir: str = typer.Option(".workspace", "--dir", help="Directory path to monitor, default is .workspace"),
    once: bool = typer.Option(False, "--once", help="Only scan existing files once then exit, do not continuously monitor"),
    refresh: bool = typer.Option(False, "--refresh", help="Force refresh all files"),
    credentials: Optional[str] = typer.Option(None, "--credentials", "-c", help="Specify credentials file path. If provided, will override --use-context."), # -c was for clean in HEAD, ensure no conflict or choose different short opts
    use_context: bool = typer.Option(False, "--use-context", help="Try to use 'config/upload_credentials.json' as credentials file, unless --credentials is provided."),
    task_id: Optional[str] = typer.Option(None, "--task-id", help="Task ID (for file registration)"),
    organization_code: Optional[str] = typer.Option(None, "--organization-code", help="Organization code"),
    log_level: str = typer.Option("INFO", "--log-level", help="Set log level (DEBUG, INFO, WARNING, ERROR)"),
    # Clean parameters from HEAD
    clean_all_storage: bool = typer.Option(False, "--clean-storage-all", "-csa", help="Clean history chat records and workspace files before starting monitor"), # Renamed to avoid conflict with -c for credentials
    clean_chat_storage: bool = typer.Option(False, "--clean-storage-chat", "-csc", help="Only clean history chat records before starting monitor"),
    clean_workspace_storage: bool = typer.Option(False, "--clean-storage-workspace", "-csw", help="Only clean workspace files before starting monitor")
):
    """Command wrapper for monitoring directory changes and automatically uploading to storage backend configured by environment variable STORAGE_PLATFORM"""
    try:
        # Handle clean options first (from HEAD)
        if clean_all_storage or clean_chat_storage or clean_workspace_storage:
            logger.info("storage_uploader_watch_command: Initiating pre-watch cleanup.")
            clean_command(
                clean_all=clean_all_storage, 
                clean_chat=clean_chat_storage, 
                clean_workspace=clean_workspace_storage
            )
            
        # Proceed with uploader logic (from feature/support-aliyun-oss)
        logger.info(f"CLI (main.py): storage-uploader watch called. Log: {log_level}. UseContext: {use_context}. STORAGE_PLATFORM by env.")
        start_storage_uploader_watcher( # Call the new uploader function
            sandbox_id=sandbox,
            workspace_dir=dir,
            once=once,
            refresh=refresh,
            credentials_file=credentials,
            use_context=use_context,
            task_id=task_id,
            organization_code=organization_code,
            log_level=log_level
        )
    except Exception as e:
        logger.error(f"Error occurred while monitoring directory and uploading: {e}") # Generic error message
        logger.error(f"Stack trace: {traceback.format_exc()}")

if __name__ == "__main__":
    multiprocessing.freeze_support()
    try:
        cli()
    except KeyboardInterrupt:
        logger.info("Program exited via KeyboardInterrupt")
    except Exception as e:
        # traceback import is already at the top
        traceback.print_exc()
        logger.error(f"Program abnormal exit: {e}")
    finally:
        logger.info("Program has completely exited")
