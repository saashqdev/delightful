#!/usr/bin/env python
"""
Command-line interface for invoking Agent classes

Usage:
    python -m bin.magic [--agent-name AGENT_NAME] [--clean] [--clean-chat] [--clean-workspace] [--mount DIRECTORY_PATH] [--mode MODE] [query text]
    or directly execute: ./bin/magic.py [--agent-name AGENT_NAME] [--clean] [--clean-chat] [--clean-workspace] [--mount DIRECTORY_PATH] [--mode MODE] [query text]

Arguments:
    --agent-name: Specify the agent name to use. Default is determined by mode ("magic" for normal mode, "super-magic" for super mode)
    --clean, -c: Clear chat history and workspace files
    --clean-chat, -cc: Clear only chat history files
    --clean-workspace, -cw: Clear only workspace files
    --mount, -m: Mount contents from specified directory to .workspace directory
    --mode: Execution mode, can be "normal" or "super", default is "super". Normal mode uses magic.agent, super mode uses super-magic.agent
    query text: Optional, message to send directly to agent. If provided, executes single query and exits; otherwise enters interactive mode.
"""
# Import base Python libraries first
import os
import sys
import traceback
from pathlib import Path
import inspect
import asyncio
import argparse
import shutil

# Set correct working directory
# Get project root directory using parent of file location
project_root = Path(__file__).resolve().parent.parent

# Add project root to Python path
sys.path.append(str(project_root))

# Load environment variables - import after setting Python path
from dotenv import load_dotenv
load_dotenv(override=True)

# Initialization steps - all project module imports after sys.path setup
from app.paths import PathManager
PathManager.set_project_root(project_root)
from agentlang.context.application_context import ApplicationContext
ApplicationContext.set_path_manager(PathManager)

from agentlang.logger import setup_logger, get_logger, configure_logging_intercept
from agentlang.utils.file import clear_directory_contents

# Import other project modules
import aiofiles # Import aiofiles
import aiofiles.os # Import aiofiles.os
from app.core.context.agent_context import AgentContext

# Use agentlang.logger configuration function, get log level from environment, default is INFO
log_level = os.getenv("LOG_LEVEL", "INFO")
# Setup logger and automatically save to ApplicationContext
logger = setup_logger(log_name="app", console_level=log_level)
configure_logging_intercept()

# Get logger named for current module
logger = get_logger(__name__)

async def clean_chat_history():
    """
    Asynchronously clean files in .chat_history directory
    but keep the directory itself

    Returns:
        bool: Whether operation was successful
    """
    result = await clear_directory_contents(PathManager.get_chat_history_dir())
    if not result:
        logger.error("Failed to clean chat history")
    return result

async def clean_workspace():
    """
    Asynchronously clean files in .workspace directory
    but keep the directory itself

    Returns:
        bool: Whether the operation was successful
    """
    result = await clear_directory_contents(PathManager.get_workspace_dir())
    if not result:
        logger.error("Failed to clean workspace")
    return result

async def clean_directories():
    """
    Asynchronously clean files in .chat_history and .workspace directories
    but keep the directories themselves

    Returns:
        bool: Whether the operation was successful
    """
    # Execute two cleanup tasks concurrently
    chat_result, workspace_result = await asyncio.gather(
        clean_chat_history(),
        clean_workspace()
    )
    
    return chat_result and workspace_result


def print_banner(mode='super'):
    """Print welcome banner"""
    mode_display = "NORMAL" if mode == 'normal' else "SUPER"
    banner = f"""
    ========================================================
                MAGIC CLI - v6 [{mode_display} MODE]
    ========================================================
    Use this command-line tool to interact with AI agents
    Enter your question, or type 'exit' to quit
    ========================================================
    """
    logger.info(banner)


def create_agent(agent_name, agent_context=None):
    """
    Create and initialize an Agent instance

    The Agent class's __init__ is now a synchronous method and can be directly instantiated

    Args:
        agent_name: Agent name
        agent_context: Optional agent context, if not provided it will be created internally by the Agent class

    Returns:
        Agent: Initialized Agent instance
    """
    from app.magic.agent import Agent

    # Directly instantiate Agent class
    agent = Agent(agent_name, agent_context, "main")

    return agent


async def mount_directory(source_dir: str):
    """
    Asynchronously copy the contents of the given directory into the .workspace directory.

    Args:
        source_dir: Source directory path, relative or absolute

    Returns:
        bool: Whether the operation succeeded
    """
    copied_count = 0
    try:
        # Ensure the source directory exists and is a directory (using aiofiles.os)
        # Note: Path.resolve() is synchronous but typically fast
        source_path = Path(source_dir).resolve()
        if not await aiofiles.os.path.exists(source_path):
            logger.error(f"Source directory does not exist: {source_path}")
            return False

        if not await aiofiles.os.path.isdir(source_path):
            logger.error(f"Specified path is not a directory: {source_path}")
            return False

        # Ensure workspace directory exists (using aiofiles.os)
        await aiofiles.os.makedirs(PathManager.get_workspace_dir(), exist_ok=True)

        # Copy directory contents asynchronously
        copy_tasks = []
        # Walk directory structure (glob is synchronous but IO happens inside loop)
        for item in source_path.glob('**/*'):
            relative_path = item.relative_to(source_path)
            target_path = PathManager.get_workspace_dir() / relative_path

            if item.is_dir(): # is_dir is synchronous but quick
                # Create target directory asynchronously
                await aiofiles.os.makedirs(target_path, exist_ok=True)
            else:
                # Ensure parent directory exists asynchronously
                await aiofiles.os.makedirs(target_path.parent, exist_ok=True)
                # Use asyncio.to_thread to run shutil.copy2 asynchronously
                copy_tasks.append(
                    asyncio.create_task(asyncio.to_thread(shutil.copy2, item, target_path))
                )
                # copied_count can be tallied after gather for successful files

        # Wait for all copy tasks to complete
        results = await asyncio.gather(*copy_tasks, return_exceptions=True)

        # Count successful and failed copies
        successful_copies = 0
        failed_copies = 0
        for i, result in enumerate(results):
            # Note: would ideally map result back to item/target_path for detailed errors
            # Simplified to counting for now
            if isinstance(result, Exception):
                logger.error(f"Error copying file asynchronously: {result}", exc_info=result)
                failed_copies += 1
            else:
                successful_copies += 1

        if failed_copies == 0:
            logger.info(f"Successfully mounted {successful_copies} files from {source_path} to {PathManager.get_workspace_dir()} asynchronously")
            return True
        else:
            logger.error(f"Errors during async mount of {source_path}: success {successful_copies} files, failed {failed_copies} files")
            return False

    except Exception as e:
        logger.error(f"Unexpected error while mounting directory: {e}", exc_info=True)
        return False


async def main():
    """Main entry: parse CLI arguments and call Agent."""
    # Create argument parser
    parser = argparse.ArgumentParser(description='CLI for invoking the Agent class')

    # Add arguments
    parser.add_argument('--agent-name', type=str, default=None,
                        help='Agent name; defaults depend on mode')
    parser.add_argument('--clean', '-c', action='store_true',
                        help='Clean conversation history and workspace files')
    parser.add_argument('--clean-chat', '-cc', action='store_true',
                        help='Clean conversation history files only')
    parser.add_argument('--clean-workspace', '-cw', action='store_true',
                        help='Clean workspace files only')
    parser.add_argument('--mount', '-m', type=str,
                        help='Mount contents of specified directory to .workspace directory')
    parser.add_argument('--mode', type=str, choices=['normal', 'super'], default='super',
                        help='Run mode: normal uses magic.agent, super uses super-magic.agent, defaults to super')
    parser.add_argument('query', nargs='?', type=str, default=None,
                        help='Query text to send to the agent. If provided, executes a single query and exits')

    # Parse arguments
    args = parser.parse_args()

    # Handle cleanup options
    cleaned = False

    # If cleanup option specified, clean directories asynchronously
    if args.clean:
        if await clean_directories():
            logger.info("Directory cleanup completed")
            cleaned = True
        else:
            logger.error("Directory cleanup failed")

    # If clean chat history only option specified
    elif args.clean_chat:
        if await clean_chat_history():
            logger.info("历史对话记录清理完成")
            cleaned = True
        else:
            logger.error("历史对话记录清理失败")

    # 如果指定了仅清理工作空间选项
    elif args.clean_workspace:
        if await clean_workspace():
            logger.info("工作空间清理完成")
            cleaned = True
        else:
            logger.error("工作空间清理失败")

    # 如果只是清理而没有查询，则直接退出
    if cleaned and not args.query and not args.mount:
        return

    # If mount option specified, mount directory asynchronously
    if args.mount:
        if not await mount_directory(args.mount):
            logger.error("Mount directory failed, exiting program")
            return

    try:
        # Determine default agent_name based on mode
        agent_name = args.agent_name
        if agent_name is None:
            if args.mode == 'normal':
                agent_name = 'magic'
                logger.info(f"Using normal mode, agent_name set to {agent_name}")
            else:  # args.mode == 'super'
                agent_name = 'super-magic'
                logger.info(f"Using super mode, agent_name set to {agent_name}")
        else:
            logger.info(f"Using specified agent_name: {agent_name}")

        agent_context = AgentContext()
        if agent_name == 'magic' or agent_name == 'super-magic':
            agent_context.is_main_agent = True
        agent_context.set_sandbox_id("default_sandbox")
        agent = create_agent(agent_name, agent_context=agent_context)
        # Check if run method is implemented
        run_method = getattr(agent, 'run')
        run_source = inspect.getsource(run_method)

        if "pass" in run_source and len(run_source.strip().splitlines()) <= 2:
            logger.warning("\nWarning: Agent class's run method is not yet implemented, cannot handle query.")
            return

        # If query text provided, execute single query and exit
        if args.query:
            response = await agent.run(args.query)
            logger.info(f"\n{response}")
            return

        # Otherwise enter interactive mode and print welcome banner
        print_banner(args.mode)

        # Provide interactive interface
        while True:
            try:
                query = input("\nEnter your question (type 'exit' to quit): ")
                if query.lower() in ('exit', 'quit', 'q'):
                    break

                response = await agent.run(query)
                logger.info(f"\n{response}")
            except KeyboardInterrupt:
                logger.info("\nProgram terminated")
                break
            except Exception as e:
                logger.error(f"Error processing query: {e}", exc_info=True)
                logger.error(f"Traceback: {traceback.format_exc()}")
    except Exception as e:
        # Print stack trace
        logger.error(f"Error during initialization: {e}")
        logger.error(f"Traceback: {traceback.format_exc()}")
        return


if __name__ == "__main__":
    asyncio.run(main())
