"""
File storage listener service for monitoring file events and uploading files to object storage
"""

import hashlib
import json
import os
import shutil
import tempfile
import time
import traceback
from pathlib import Path
from typing import List, Optional, Union

from agentlang.context.tool_context import ToolContext
from agentlang.event.data import AfterMainAgentRunEventData
from agentlang.event.event import Event, EventType
from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext
from app.core.entity.attachment import Attachment, AttachmentTag
from app.core.entity.event.file_event import FileEventData  # Import FileEventData from business layer
from app.core.entity.project_archive import ProjectArchiveInfo
from app.infrastructure.storage.base import BaseFileProcessor
from app.infrastructure.storage.exceptions import InitException, UploadException
from app.infrastructure.storage.factory import StorageFactory
from app.infrastructure.storage.types import StorageResponse
from app.paths import PathManager
from app.service.agent_event.base_listener_service import BaseListenerService

logger = get_logger(__name__)


class FileStorageListenerService:
    """
    File storage listener service for monitoring file events and uploading files to object storage
    """

    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        """
        Register file event listeners for agent context

        Args:
            agent_context: Agent context object
        """
        # Create mapping from event types to handler functions
        event_listeners = {
            EventType.FILE_CREATED: FileStorageListenerService._handle_file_event,
            EventType.FILE_UPDATED: FileStorageListenerService._handle_file_event,
            EventType.FILE_DELETED: FileStorageListenerService._handle_file_deleted,
            EventType.AFTER_MAIN_AGENT_RUN: FileStorageListenerService._handle_after_main_agent_run
        }

        # Use base class method to register listeners in batch
        BaseListenerService.register_listeners(agent_context, event_listeners)

        logger.info("Registered file event and main agent completion event listeners for agent context")

    @staticmethod
    async def _handle_file_event(event: Event[FileEventData]) -> None:
        """
        Handle file events (creation and update)

        Args:
            event: File event object containing FileEventData
        """
        event_type_name = "created" if event.event_type == EventType.FILE_CREATED else "updated"
        logger.info(f"Processing file {event_type_name} event: {event.data.filepath}")

        # 1. First upload file to storage service
        storage_response = await FileStorageListenerService._upload_file_to_storage(
            event.data.filepath,
            event.data.tool_context.get_extension_typed("agent_context", AgentContext)
        )

        # 2. If upload succeeds and returns response, create attachment and add to event context
        if storage_response:
            try:
                # Create attachment object, passing the entire StorageResponse object
                attachment = FileStorageListenerService._create_attachment_from_uploaded_file(
                    filepath=event.data.filepath,
                    response=storage_response,
                    file_event_data=event.data
                )

                # Add attachment to event context
                FileStorageListenerService._add_attachment_to_event_context(event.data.tool_context, attachment)
                # Add attachment to agent context
                FileStorageListenerService._add_attachment_to_agent_context(event.data.tool_context.get_extension_typed("agent_context", AgentContext), attachment)
            except Exception as e:
                logger.error(f"Failed to process attachment information: {e}")

    @staticmethod
    async def _handle_file_deleted(event: Event[FileEventData]) -> None:
        """
        Handle file deletion events

        Args:
            event: File deletion event object containing FileEventData
        """
        logger.info(f"Processing file deletion event: {event.data.filepath}")
        # File has been deleted, so no upload is needed
        # Based on business requirements, deletion logic for storage service can be implemented here
        pass

    @staticmethod
    async def _handle_after_main_agent_run(event: Event[AfterMainAgentRunEventData]) -> None:
        """
        Handle main agent completion events, compress and upload project directory

        Args:
            event: Main agent completion event object containing AfterMainAgentRunEventData
        """
        pass

    @staticmethod
    def _get_current_version() -> int:
        """
        Get the current version number of the project archive

        Returns:
            int: Current version number, returns 0 if file does not exist
        """
        try:
            project_archive_info_file = PathManager.get_project_archive_info_file()
            if os.path.exists(project_archive_info_file):
                with open(project_archive_info_file, 'r') as f:
                    info = json.load(f)
                    return info.get('version', 0)
            return 0
        except Exception as e:
            logger.error(f"Error getting current version number: {e}")
            return 0

    @staticmethod
    async def _save_and_upload_project_archive_info(
        project_archive_info: ProjectArchiveInfo,
        agent_context: AgentContext
    ) -> Optional[StorageResponse]:
        """
        Save project archive information to local file and upload to OSS

        Args:
            project_archive_info: Project archive information object
            agent_context: Agent context object

        Returns:
            Optional[StorageResponse]: Upload response, None if failed
        """
        # Persist project archive information to local file
        project_archive_info_file = PathManager.get_project_archive_info_file()
        with open(project_archive_info_file, 'w') as f:
            json.dump(project_archive_info.model_dump(), f)
            logger.info(f"Project archive information saved to local: {project_archive_info_file}")

        # Get storage_service from agent_context
        metadata = agent_context.get_init_client_message_metadata()
        sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()

        storage_service = await StorageFactory.get_storage(
            sts_token_refresh=sts_token_refresh,
            metadata=metadata
        )

        # Upload project archive information file to OSS
        project_archive_info_file_relative_path = PathManager.get_project_archive_info_file_relative_path()
        info_file_key = BaseFileProcessor.combine_path(storage_service.credentials.get_dir(), project_archive_info_file_relative_path)
        info_response = await storage_service.upload(
            file=str(project_archive_info_file),
            key=info_file_key,
            options={}
        )

        if info_response:
            logger.info(f"Project archive information file uploaded: {info_file_key}")
        else:
            logger.error("Project archive information file upload failed")

        return info_response

    @staticmethod
    async def _archive_and_upload_project(agent_context: AgentContext) -> None:
        """
        Compress and upload project directory

        Args:
            agent_context: Agent context object
        """
        # Compress .chat_history and .workspace directories into one archive
        chat_history_dir = PathManager.get_chat_history_dir()
        workspace_dir = PathManager.get_workspace_dir()
        project_archive_dir_name = PathManager.get_project_archive_dir_name()

        combined_zip = FileStorageListenerService._compress_directory(
            directory_paths=[str(chat_history_dir), str(workspace_dir)],
            output_filename=project_archive_dir_name
        )

        if not combined_zip:
            logger.error("Failed to compress directory, cannot upload")
            return

        # Calculate file MD5 and size
        file_size = os.path.getsize(combined_zip)

        # Calculate file MD5
        md5_hash = hashlib.md5()
        with open(combined_zip, "rb") as f:
            # Read file in chunks to handle large files
            for chunk in iter(lambda: f.read(4096), b""):
                md5_hash.update(chunk)
        file_md5 = md5_hash.hexdigest()

        metadata = agent_context.get_init_client_message_metadata()
        sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()

        storage_service = await StorageFactory.get_storage(
            sts_token_refresh=sts_token_refresh,
            metadata=metadata
        )

        # Prepare file key using storage_service.credentials.get_dir() to get directory
        file_key = BaseFileProcessor.combine_path(storage_service.credentials.get_dir(), project_archive_dir_name + ".zip")

        # Upload the merged archive file
        try:
            storage_response = await storage_service.upload(
                file=combined_zip,
                key=file_key,
                options={}
            )

            if not storage_response:
                logger.error(f"Failed to upload {project_archive_dir_name}")
                return

            logger.info(f"Successfully uploaded {project_archive_dir_name}")

            # Get and increment current version number
            current_version = FileStorageListenerService._get_current_version()
            new_version = current_version + 1
            logger.info(f"Project archive version number incremented from {current_version} to {new_version}")

            # Create project archive information with incremented version number
            project_archive_info = ProjectArchiveInfo(
                file_key=storage_response.key,
                file_size=file_size,
                file_md5=file_md5,
                version=new_version
            )

            # Save project archive information to local file and upload to OSS
            await FileStorageListenerService._save_and_upload_project_archive_info(
                project_archive_info=project_archive_info,
                agent_context=agent_context
            )

            # Save project archive information to agent context
            agent_context.set_project_archive_info(project_archive_info)

            logger.info(f"Project archive information saved: key={storage_response.key}, size={file_size}, md5={file_md5}, version={new_version}")
        except Exception as e:
            logger.error(traceback.format_exc())
            logger.error(f"Error occurred while uploading {project_archive_dir_name}: {e}")

        # Clean up temporary files
        if combined_zip and os.path.exists(combined_zip):
            try:
                os.remove(combined_zip)
                logger.info(f"Deleted temporary compressed file: {combined_zip}")
            except Exception as e:
                logger.error(f"Failed to delete temporary compressed file: {e}")

    @staticmethod
    async def _upload_file_to_storage(filepath: str, agent_context: AgentContext) -> Optional[StorageResponse]:
        """
        Upload file to storage service

        Args:
            filepath: File path
            agent_context: Agent context object

        Returns:
            Optional[StorageResponse]: Storage response object after successful upload, None if upload fails
        """
        try:
            # Check if file exists
            if not os.path.exists(filepath):
                logger.warning(f"File does not exist, cannot upload: {filepath}")
                return None

            sts_token_refresh = agent_context.get_init_client_message_sts_token_refresh()
            metadata = agent_context.get_init_client_message_metadata()

            storage_service = await StorageFactory.get_storage(
                sts_token_refresh=sts_token_refresh,
                metadata=metadata
            )

            # Get filename as storage key
            workspace_dir = agent_context.get_workspace_dir()
            # Remove workspace_dir - ensure both parameters are string type
            if isinstance(filepath, Path):
                filepath = str(filepath)
            if isinstance(workspace_dir, Path):
                workspace_dir = str(workspace_dir)
            file_key = filepath.replace(workspace_dir, "")
            # Set optional parameters such as progress callbacks
            options = {}

            # Upload file
            file_key = BaseFileProcessor.combine_path(storage_service.credentials.get_dir(), file_key)
            logger.info(f"Starting file upload: {filepath}, storage key: {file_key}")

            response = await storage_service.upload(
                file=filepath,
                key=file_key,
                options=options
            )

            logger.info(f"File upload successful: {filepath}, storage key: {response.key}")
            return response

        except (InitException, UploadException) as e:
            logger.error(f"File upload failed: {e}")
            return None
        except Exception as e:
            logger.error(f"Error occurred during file event processing: {e}")
            return None

    @staticmethod
    def _create_attachment_from_uploaded_file(filepath: str, response: StorageResponse, file_event_data: FileEventData) -> Attachment:
        """
        Create attachment object based on uploaded file information

        Args:
            filepath: Original file path
            response: Storage service upload response containing file key and possible URL
            event_type: Event type, optional

        Returns:
            Attachment: Created attachment object
        """
        # Get file information
        file_path_obj = Path(filepath)
        file_size = os.path.getsize(filepath)
        file_ext = file_path_obj.suffix.lstrip('.')
        file_name = file_path_obj.name
        display_name = file_name

        # Set attachment type based on file type
        if file_event_data.is_screenshot:
            file_tag = AttachmentTag.BROWSER
        else:
            file_tag = AttachmentTag.PROCESS

        # Get file_key and possible file_url from response
        file_key = response.key
        file_url = response.url  # This will be None if storage service doesn't provide URL

        # Create attachment object
        attachment = Attachment(
            file_key=file_key,
            file_tag=file_tag,
            file_extension=file_ext,
            filename=file_name,
            display_filename=display_name,
            file_size=file_size,
            file_url=file_url,  # Set file URL, may be None
            timestamp=int(time.time())
        )

        logger.info(f"Created attachment object: {file_name}, tag: {file_tag}, URL: {file_url or 'None'}")
        return attachment

    @staticmethod
    def _add_attachment_to_event_context(tool_context: ToolContext, attachment: Attachment) -> None:
        """
        Add attachment to event context

        Args:
            tool_context: Tool context
            attachment: Attachment object
        """
        try:
            # Use the already registered EventContext
            from app.core.entity.event.event_context import EventContext
            event_context = tool_context.get_extension_typed("event_context", EventContext)
            if event_context:
                event_context.add_attachment(attachment)
                logger.info(f"Added file {attachment.filename} as attachment to event context")
            else:
                logger.warning("Cannot add attachment to event context: EventContext not registered")
        except Exception as e:
            logger.error(f"Failed to add attachment to event context: {e}")

    @staticmethod
    def _add_attachment_to_agent_context(agent_context: AgentContext, attachment: Attachment) -> None:
        """
        Add attachment to agent context
        """
        agent_context.add_attachment(attachment)

    @staticmethod
    def _compress_directory(directory_paths: Union[str, List[str]], output_filename: str) -> Optional[str]:
        """
        Compress specified directory, supports single or multiple directories

        Args:
            directory_paths: Directory path(s) to compress, can be single string or list of paths
            output_filename: Output compressed filename (without extension)

        Returns:
            Optional[str]: Full path of compressed file on success, None on failure
        """
        try:
            # Convert single directory path to list for unified processing
            if isinstance(directory_paths, str):
                directories = [directory_paths]
            else:
                directories = directory_paths

            # If only one directory, use direct compression logic (more efficient)
            if len(directories) == 1 and os.path.exists(directories[0]) and os.path.isdir(directories[0]):
                directory_path = directories[0]
                # Create temporary directory to store compressed file
                tmp_dir = tempfile.mkdtemp()
                base_name = os.path.join(tmp_dir, output_filename)

                # Create .zip format compressed file
                compressed_file = shutil.make_archive(
                    base_name=base_name,  # Base name of compressed file (without extension)
                    format='zip',         # Compression format, using zip here
                    root_dir=os.path.dirname(directory_path),  # Root directory (parent of directory to compress)
                    base_dir=os.path.basename(directory_path)  # Name of directory to compress
                )

                logger.info(f"Successfully compressed directory {directory_path} to {compressed_file}")
                return compressed_file

            # Multiple directories case, need to copy to temporary directory first then compress
            # Create temporary directory as compression source
            temp_source_dir = tempfile.mkdtemp()

            # Copy all valid directories to temporary directory
            valid_dirs = False
            for directory in directories:
                if not os.path.exists(directory) or not os.path.isdir(directory):
                    logger.warning(f"Directory to compress does not exist: {directory}")
                    continue

                valid_dirs = True
                dir_name = os.path.basename(directory)
                target_dir = os.path.join(temp_source_dir, dir_name)
                shutil.copytree(directory, target_dir)

            if not valid_dirs:
                logger.error("No valid directories available for compression")
                shutil.rmtree(temp_source_dir)
                return None

            # Create temporary directory to store compressed file
            tmp_dir = tempfile.mkdtemp()
            base_name = os.path.join(tmp_dir, output_filename)

            # Create .zip format compressed file
            compressed_file = shutil.make_archive(
                base_name=base_name,  # Base name of compressed file (without extension)
                format='zip',         # Compression format, using zip here
                root_dir=temp_source_dir,  # Root directory
                base_dir='.'          # Compress all content in temporary directory
            )

            # Clean up temporary source directory
            shutil.rmtree(temp_source_dir)

            logger.info(f"Successfully compressed multiple directories to {compressed_file}")
            return compressed_file
        except Exception as e:
            logger.error(f"Error occurred during directory compression: {e}")
            return None
