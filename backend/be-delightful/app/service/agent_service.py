import asyncio
import json
import os
import re
import shutil
import tempfile
import zipfile
from typing import Any, Dict, List, Optional

from agentlang.context.tool_context import ToolContext
from agentlang.environment import Environment
from agentlang.event.data import AfterInitEventData
from agentlang.event.event import EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.stream.base import Stream
from app.infrastructure.storage.base import BaseFileProcessor
from app.infrastructure.storage.factory import StorageFactory
from app.delightful.agent import Agent
from app.paths import PathManager
from app.service.agent_event.file_storage_listener_service import FileStorageListenerService
from app.service.attachment_service import AttachmentService

logger = get_logger(__name__)


class AgentService:
    def is_support_fetch_workspace(self) -> bool:
        """Check if fetching workspace is supported
        
        Returns:
            bool: Whether fetching workspace is supported
        """
        # Get from environment variable first, fallback to config file if not exists
        env_value = Environment.get_env("FETCH_WORKSPACE", None, bool)
        if env_value is not None:
            return env_value

        # Get from config file
        try:
            from agentlang.config.config import config
            return config.get("sandbox.fetch_workspace", False)
        except (ImportError, AttributeError):
            return False

    async def _download_and_check_project_archive_info(self, agent_context: AgentContext) -> bool:
        """
        Download and check project archive info file

        Args:
            agent_context: Agent context

        Returns:
            bool: Whether workspace needs to be updated
        """
        # Get storage_service
        sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()
        metadata = agent_context.get_init_client_message_metadata()

        storage_service = await StorageFactory.get_storage(
            sts_token_refresh=sts_token_refresh,
            metadata=metadata
        )

        # Try downloading project archive info file
        project_archive_info_file_relative_path = PathManager.get_project_archive_info_file_relative_path()
        project_archive_info_file = PathManager.get_project_archive_info_file()

        project_archive_info_file_key = BaseFileProcessor.combine_path(dir_path=storage_service.credentials.get_dir(), file_path=project_archive_info_file_relative_path)
        # Check if file exists
        if not await storage_service.exists(project_archive_info_file_key):
            logger.info(f"Project archive info file does not exist: {project_archive_info_file_key}")
            return False
        logger.info(f"Attempting to download project archive info file: {project_archive_info_file_key}")
        info_file_stream = await storage_service.download(
            key=project_archive_info_file_key,
            options=None
        )

        # Parse remote version info
        remote_info = json.loads(info_file_stream.read().decode('utf-8'))
        remote_version = remote_info.get('version', 0)
        logger.info(f"Remote project archive info version: {remote_version}")

        # Get local version info
        local_version = 0
        if os.path.exists(project_archive_info_file):
            with open(project_archive_info_file, 'r') as f:
                local_info = json.load(f)
                local_version = local_info.get('version', 0)
                logger.info(f"Local project archive info version: {local_version}")
        else:
            logger.info("Local project archive info file does not exist")

        # If remote version is not greater than local version, no update needed
        if remote_version <= local_version:
            logger.info(f"Remote version ({remote_version}) is not greater than local version ({local_version}), no workspace update needed")
            return False

        logger.info(f"Remote version ({remote_version}) is greater than local version ({local_version}), will update workspace")

        # Version is newer, save remote version info locally
        project_schema_absolute_dir = PathManager.get_project_schema_absolute_dir()
        project_schema_absolute_dir.mkdir(exist_ok=True, parents=True)
        with open(project_archive_info_file, 'w') as f:
            json.dump(remote_info, f)
            logger.info(f"Updated local project archive info file: {project_archive_info_file}")
        return True

    async def download_and_extract_workspace(self, agent_context: AgentContext) -> None:
        """
        Download and extract workspace from OSS

        Args:
            agent_context: Agent context, contains storage configuration and update_workspace state

        Returns:
            bool: Whether download and extraction succeeded
        """
        # Get configuration
        sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()
        metadata = agent_context.get_init_client_message_metadata()

        storage_service = await StorageFactory.get_storage(
            sts_token_refresh=sts_token_refresh,
            metadata=metadata
        )

        # Download and check project archive info file
        need_update = await self._download_and_check_project_archive_info(
            agent_context=agent_context
        )

        # If no update needed, return directly
        if not need_update or not self.is_support_fetch_workspace():
            logger.info("Workspace does not need update, skipping download and extraction")
            return

        # Download project_archive.zip from OSS
        file_key = BaseFileProcessor.combine_path(dir_path=storage_service.credentials.get_dir(), file_path="project_archive.zip")
        logger.info(f"Starting to download archive from OSS: {file_key}")
        file_stream = await storage_service.download(
            key=file_key,
            options=None
        )

        # Save to temp file
        temp_zip = tempfile.NamedTemporaryFile(delete=False, suffix='.zip')
        temp_zip_path = temp_zip.name
        temp_zip.close()

        # Write to temp file
        with open(temp_zip_path, 'wb') as f:
            f.write(file_stream.read())

        logger.info(f"Archive download complete: {temp_zip_path}")
        # Print archive size
        zip_size = os.path.getsize(temp_zip_path)
        logger.info(f"Archive size: {zip_size} bytes ({zip_size/1024/1024:.2f} MB)")

        # Clear existing directories
        workspace_dir = PathManager.get_workspace_dir()
        chat_history_dir = PathManager.get_chat_history_dir()
        if workspace_dir.exists():
            logger.info(f"Clearing existing workspace directory: {workspace_dir}")
            shutil.rmtree(workspace_dir)
        if chat_history_dir.exists():
            logger.info(f"Clearing existing chat history directory: {chat_history_dir}")
            shutil.rmtree(chat_history_dir)

        # Recreate directories
        workspace_dir.mkdir(exist_ok=True)
        chat_history_dir.mkdir(exist_ok=True)

        # Extract archive
        project_root = PathManager.get_project_root()
        logger.info(f"Starting to extract files to: {project_root}")
        with zipfile.ZipFile(temp_zip_path, 'r') as zip_ref:
            # Extract all files to project root
            zip_ref.extractall(project_root)

        logger.info("Workspace initialization archive extraction complete")

        # Delete temp file
        os.unlink(temp_zip_path)
        logger.info(f"Temp file deleted: {temp_zip_path}")

    def _save_init_client_message_to_credentials(self, agent_context: AgentContext) -> None:
        """
        Save client initialization message

        Args:
            agent_context: Agent context, contains client initialization message
        """
        try:
            init_client_message = agent_context.get_init_client_message()
            if init_client_message:
                init_client_message_file = PathManager.get_init_client_message_file()
                with open(init_client_message_file, "w", encoding="utf-8") as f:
                    json.dump(init_client_message.model_dump(), f, indent=2, ensure_ascii=False)

                logger.info(f"Saved client initialization message to: {init_client_message_file}")
        except Exception as e:
            logger.error(f"Error saving client initialization message: {e}")

    async def init_workspace(
        self,
        agent_context: AgentContext,
    ):
        """
        Initialize agent

        Args:
            agent_context: Agent context
        """

        for stream in agent_context.streams.values():
            agent_context.add_stream(stream)

        logger.info("BeDelightful initialization started")

        # Initialize OSS credentials
        sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()
        metadata = agent_context.get_init_client_message_metadata()
        await StorageFactory.get_storage(
            sts_token_refresh=sts_token_refresh,
            metadata=metadata
        )

        # Save init_client_message to .credentials directory
        self._save_init_client_message_to_credentials(agent_context)

        await self.download_and_extract_workspace(agent_context)

        # Create ToolContext instance
        tool_context = ToolContext(metadata=agent_context.get_metadata())
        # Register AgentContext as extension
        tool_context.register_extension("agent_context", agent_context)

        after_init_data = AfterInitEventData(
            tool_context=tool_context,
            agent_context=agent_context,
            success=True
        )
        await agent_context.dispatch_event(EventType.AFTER_INIT, after_init_data)

        logger.info("BeDelightful initialization complete")

        return

    async def create_agent(
        self,
        agent_name: str,
        agent_context: AgentContext,
    ) -> Agent:
        """
        Create agent instance

        Args:
            agent_type: Agent type name
            stream_mode: Whether to enable streaming output
            agent_context: Optional agent context object, will override other parameters
            stream: Streaming output object
            storage_credentials: Object storage credentials, used to configure object storage service

        Returns:
            Agent instance and error message list
        """

        try:
            agent = Agent(agent_name, agent_context)

            return agent
        except Exception as e:
            logger.error(f"Error creating BeDelightful instance: {e}")
            import traceback

            logger.error(traceback.format_exc())
            return None

    async def run_agent(
        self,
        agent: Agent,
    ):
        agent_context = agent.agent_context
        chat_client_message = agent_context.get_chat_client_message()
        query = chat_client_message.prompt
        if chat_client_message and hasattr(chat_client_message, "attachments") and chat_client_message.attachments:
            query = await self._process_attachments(agent_context, query, chat_client_message.attachments)

        try:
            await agent.run_main_agent(query)
        finally:
            # Persist project directory
            asyncio.create_task(
                FileStorageListenerService._archive_and_upload_project(agent_context)
            )

    def create_agent_context(
        self,
        stream_mode: bool,
        streams: Optional[List[Stream]] = [],
        agent_type: Optional[str] = None,
        llm: Optional[str] = None,
        task_id: Optional[str] = "",
        is_main_agent: bool = False,
        interrupt_queue: Optional[asyncio.Queue] = None,
        sandbox_id: Optional[str] = "",
    ) -> AgentContext:
        """
        Create agent context object

        Args:
            stream_mode: Whether to enable streaming output
            streams: Optional stream instances for communication
            agent_type: Agent type, used to extract model name from agent file
            llm: Large language model name
            task_id: Task ID, auto-generated if None
            is_main_agent: Flag indicating if current agent is the main agent, defaults to False

        Returns:
            AgentContext: Agent context object
        """
        agent_context = AgentContext()
        agent_context.set_task_id(task_id)
        agent_context.set_llm(llm)
        agent_context.stream_mode = stream_mode
        agent_context.ensure_workspace_dir()
        agent_context.is_main_agent = is_main_agent
        agent_context.set_sandbox_id(sandbox_id)

        if interrupt_queue:
            agent_context.set_interrupt_queue(interrupt_queue)

        for stream in streams:
            agent_context.add_stream(stream)

        # If agent_type is provided, extract llm from file
        if agent_type:
            # Read agent file content
            agent_file = self.get_agent_file(agent_type)
            if os.path.exists(agent_file):
                try:
                    with open(agent_file, "r", encoding="utf-8") as f:
                        content = f.read()

                    # Use regex to extract model name directly from content
                    model_pattern = r"<!--\s*llm:\s*([a-zA-Z0-9-_.]+)\s*-->"
                    match = re.search(model_pattern, content, re.IGNORECASE)

                    if match:
                        model_name = match.group(1).strip()
                        agent_context.llm = model_name
                        logger.info(f"Extracted model name from {agent_type}.agent file and set to: {model_name}")
                except Exception as e:
                    logger.error(f"Error extracting llm from agent file: {e}")

        return agent_context

    def get_agent_file(self, agent_type: str) -> str:
        """
        Get agent file path
        """
        return os.path.join(PathManager.get_project_root(), "agents", f"{agent_type}.agent")

    async def _process_attachments(self, agent_context: AgentContext, query: str, attachments: List[Dict[str, Any]]) -> str:
        """
        Process attachments and integrate them into the query

        Args:
            query: Original user query
            attachments: List of attachments, each attachment is a dictionary containing attachment info

        Returns:
            str: Query with attachment path information added
        """
        if not attachments:
            logger.info("No attachments in message, returning original query")
            return query

        logger.info(f"Received message with attachments, count: {len(attachments)}")
        logger.info(f"Message content: {query[:100]}{'...' if len(query) > 100 else ''}")
        logger.info(f"Attachment details: {json.dumps([{k: v for k, v in a.items()} for a in attachments], ensure_ascii=False)}")

        # Initialize attachment service
        logger.info("Initializing attachment download service")
        attachment_service = AttachmentService(agent_context)

        # Download attachments
        logger.info("Starting attachment download...")
        attachment_paths = await attachment_service.download_attachments(attachments)

        # If successfully downloaded attachments, add paths to prompt
        if attachment_paths:
            logger.info(f"Successfully downloaded {len(attachment_paths)} attachments")
            # Generate path information for each attachment
            attachment_info = "\n\nThe following are user-uploaded attachment paths:\n"
            for i, path in enumerate(attachment_paths, 1):
                attachment_info += f"{i}. {path}\n"
                logger.info(f"Attachment {i}: {path}")

            # Add attachment information to prompt
            message_with_attachments = f"{query}\n{attachment_info}"
            logger.info(f"Added attachment path info to message, new message length: {len(message_with_attachments)}")

            return message_with_attachments
        else:
            logger.info("No successfully downloaded attachments, returning original query")
            return query
