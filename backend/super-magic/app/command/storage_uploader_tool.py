import asyncio
import hashlib
import json
import os
import time
import traceback
from pathlib import Path
from typing import Optional, Dict

import typer
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler, FileSystemEvent

from app.infrastructure.storage.factory import StorageFactory
from app.infrastructure.storage.types import PlatformType, BaseStorageCredentials
from app.infrastructure.storage.base import AbstractStorage
from app.infrastructure.storage.exceptions import InitException, UploadException
from agentlang.logger import get_logger, setup_logger
from app.paths import PathManager

cli_app = typer.Typer(name="storage-uploader", help="Storage Uploader Tool for various backends.", no_args_is_help=True)
logger = get_logger(__name__)


class FileHashCache:
    """Encapsulates caching logic for file object keys to their content hashes."""
    def __init__(self):
        self._cache: Dict[str, str] = {}

    def get_hash(self, object_key: str) -> Optional[str]:
        """Get cached file hash by object key. Returns None if not found."""
        return self._cache.get(object_key)

    def set_hash(self, object_key: str, file_hash: str) -> None:
        """Set or update file hash for object key."""
        self._cache[object_key] = file_hash

    def clear(self) -> None:
        """Clear entire cache."""
        self._cache.clear()

    def __len__(self) -> int:
        return len(self._cache)


class StorageUploaderTool:
    """Universal storage upload tool"""

    def __init__(self,
                 credentials_file: Optional[str] = None,
                 sandbox_id: Optional[str] = None,
                 task_id: Optional[str] = None,
                 organization_code: Optional[str] = None):
        """
        Initialize upload tool

        Args:
            credentials_file: Credentials file path
            sandbox_id: Sandbox ID
            task_id: Task ID (mostly deprecated, mainly handled at API layer)
            organization_code: Organization code
        """
        self.credentials_file = credentials_file
        self.sandbox_id = sandbox_id
        self.task_id = task_id
        self.organization_code = organization_code

        self.storage_service: Optional[AbstractStorage] = None
        self.platform: Optional[PlatformType] = None
        self.uploaded_files_cache = FileHashCache()
        self.uploaded_files_for_registration: list = []
        self.last_upload_time = time.time()  # Record last upload time

        self.api_base_url = os.getenv("MAGIC_API_SERVICE_BASE_URL")
        if self.api_base_url:
            if not self.api_base_url.startswith(("http://", "https://")):
                self.api_base_url = f"https://{self.api_base_url}"
            if not self.api_base_url.endswith("/"):
                self.api_base_url += "/"
            logger.info(f"Uploader Tool: API service URL: {self.api_base_url}")
        else:
            logger.warning("Uploader Tool: MAGIC_API_SERVICE_BASE_URL environment variable not set, unable to perform file registration")

    async def _load_credentials(self) -> bool:
        """
        Load credentials file, determine platform, and initialize/update storage service and its credentials.
        """
        try:
            # Use specified credentials file first, otherwise use default path
            credentials_path_to_load = None
            if self.credentials_file and Path(self.credentials_file).exists():
                credentials_path_to_load = Path(self.credentials_file)
                logger.info(f"Using specified credentials file: {credentials_path_to_load}")
            else:
                default_path = Path(".credentials/upload_credentials.json").resolve()
                if default_path.exists():
                    credentials_path_to_load = default_path
                    logger.info(f"Using default credentials file: {credentials_path_to_load}")

            if not credentials_path_to_load:
                logger.error("No available credentials file found")
                return False

            with open(credentials_path_to_load, "r") as f:
                credentials_data = json.load(f)

            # Read and print batch_id
            batch_id = credentials_data.get("batch_id", "Not set")
            logger.info(f"Current operation batch ID: {batch_id}")

            upload_config_dict = credentials_data.get("upload_config")
            if not upload_config_dict:
                logger.error(f"'upload_config' key not found in credentials file {credentials_path_to_load}")
                return False

            # Read sandbox_id and organization_code (if not set)
            if self.sandbox_id is None and "sandbox_id" in credentials_data:
                self.sandbox_id = credentials_data.get("sandbox_id")
                logger.info(f"Loaded sandbox_id from credentials file: {self.sandbox_id}")
            if self.organization_code is None and "organization_code" in credentials_data:
                self.organization_code = credentials_data.get("organization_code")
                logger.info(f"Loaded organization_code from credentials file: {self.organization_code}")

            # Get platform type directly from credentials
            platform_type = None
            if 'platform' in upload_config_dict:
                try:
                    platform_type = PlatformType(upload_config_dict['platform'])
                    logger.info(f"Determined platform type from credentials file: {platform_type.value}")
                except (ValueError, TypeError):
                    logger.warning(f"Unable to convert platform value '{upload_config_dict['platform']}' from credentials to PlatformType enum")

            # Initialize storage service
            self.storage_service = await StorageFactory.get_storage(
                sts_token_refresh=None,
                metadata=None,
                platform=platform_type
            )

            # Set credentials
            self.storage_service.set_credentials(upload_config_dict)

            # Set platform type
            self.platform = platform_type

            logger.info(f"Credentials loaded and storage service ready, using platform: {self.platform.value if self.platform else 'unknown'}")
            return True

        except Exception as e:
            logger.error(f"Error occurred while loading credentials or initializing storage service: {e}", exc_info=True)
            return False

    def _get_file_hash(self, file_path: Path) -> str:
        md5_hash = hashlib.md5()
        try:
            with open(file_path, "rb") as f:
                for chunk in iter(lambda: f.read(4096), b""):
                    md5_hash.update(chunk)
            return md5_hash.hexdigest()
        except Exception as e:
            logger.error(f"Failed to calculate file hash ({file_path}): {e}")
            return ""

    async def upload_file(self, file_path: Path, workspace_dir: Path) -> bool:
        # Reload credentials each time to ensure using latest credentials
        await self._load_credentials()

        try:
            if not file_path.exists():
                logger.warning(f"File does not exist, cannot upload: {file_path}")
                return False

            file_hash = self._get_file_hash(file_path)
            if not file_hash: return False

            try:
                relative_path_str = file_path.relative_to(workspace_dir).as_posix()
            except ValueError:
                relative_path_str = file_path.name

            base_dir = self.storage_service.credentials.get_dir()

            # Simplify object key construction logic, remove sandbox ID, directly use base_dir and relative path
            object_key = f"{base_dir}{relative_path_str}"

            cached_hash = self.uploaded_files_cache.get_hash(object_key)
            if cached_hash == file_hash:
                logger.info(f"File content unchanged, skipping upload: {relative_path_str} (platform: {self.platform.value if self.platform else 'N/A'})")
                return True

            logger.info(f"Starting file upload to platform {self.platform.value if self.platform else 'N/A'}: {relative_path_str}, storage key: {object_key}")

            await self.storage_service.upload(file=str(file_path), key=object_key)
            self.uploaded_files_cache.set_hash(object_key, file_hash)
            # Update last upload time
            self.last_upload_time = time.time()
            logger.info(f"File uploaded successfully: {relative_path_str}, storage key: {object_key}")

            if self.sandbox_id:
                file_ext = file_path.suffix.lstrip('.')
                external_url = None
                base_url = self.storage_service.credentials.get_public_access_base_url()
                if base_url:
                    external_url = f"{base_url.strip('/')}/{object_key.lstrip('/')}"
                else:
                    logger.warning(f"Platform {self.platform.value if self.platform else 'N/A'} credentials cannot generate public access base URL for {object_key}")

                self.uploaded_files_for_registration.append({
                    "file_key": object_key,
                    "file_extension": file_ext,
                    "filename": file_path.name,
                    "file_size": file_path.stat().st_size,
                    "external_url": external_url,
                    "sandbox_id": self.sandbox_id
                })
                logger.debug(f"File added to registration list, current list size: {len(self.uploaded_files_for_registration)}")

            return True
        except (InitException, UploadException) as e:
            logger.error(f"File upload failed ({relative_path_str if 'relative_path_str' in locals() else file_path}): {e}")
            return False
        except Exception as e:
            logger.error(f"Unknown error occurred during upload ({relative_path_str if 'relative_path_str' in locals() else file_path}): {e}", exc_info=True)
            logger.error(traceback.format_exc())
            return False

    async def register_uploaded_files(self) -> bool:
        if not self.sandbox_id:
            logger.info("Sandbox ID not set, unable to register files")
            return True

        if not self.uploaded_files_for_registration:
            logger.info("No new files to register, skipping registration")
            return True

        if not self.api_base_url:
            logger.error("API base URL not set (MAGIC_API_SERVICE_BASE_URL), unable to register files")
            return False

        api_url = f"{self.api_base_url.strip('/')}/api/v1/super-agent/file/process-attachments"

        request_data = {
            "attachments": self.uploaded_files_for_registration,
            "sandbox_id": self.sandbox_id
        }
        # Add organization code (if any)
        if self.organization_code:
            request_data["organization_code"] = self.organization_code

        # Add task_id if present (though rarely used)
        if self.task_id:
            request_data["task_id"] = self.task_id

        headers = {"Content-Type": "application/json", "User-Agent": "StorageUploaderTool/2.0"}

        logger.info(f"========= File Registration Request Info =========")
        logger.info(f"Preparing to register {len(self.uploaded_files_for_registration)} files with API (Sandbox ID: {self.sandbox_id}) ...")
        logger.info(f"Request URL: {api_url}")
        logger.debug(f"Request headers: {json.dumps(headers, ensure_ascii=False, indent=2)}")
        logger.debug(f"Request body: {json.dumps(request_data, ensure_ascii=False, indent=2)}")
        logger.info(f"===================================================")

        try:
            import aiohttp
            async with aiohttp.ClientSession() as session:
                async with session.post(api_url, json=request_data, headers=headers) as response:
                    response_text = await response.text()
                    logger.info(f"File registration API response status code: {response.status}")
                    logger.debug(f"File registration API response content: {response_text}")
                    if response.status == 200:
                        try:
                            result = json.loads(response_text)
                            if result.get("code") == 1000:
                                logger.info(f"File registration API call successful, total: {result.get('data', {}).get('total', 0)}, "
                                          f"success: {result.get('data', {}).get('success', 0)}, "
                                          f"skipped: {result.get('data', {}).get('skipped', 0)}")
                                self.uploaded_files_for_registration.clear()
                                return True
                            else:
                                logger.error(f"File registration API returned business error: {result.get('message', 'unknown error')}")
                        except json.JSONDecodeError:
                            logger.error(f"File registration API response is not valid JSON format: {response_text[:200]}...")
                    else:
                        logger.error(f"File registration API request failed, status code: {response.status}, response: {response_text[:200]}...")
            return False
        except Exception as e:
            logger.error(f"Severe error occurred while registering uploaded files: {e}", exc_info=True)
            logger.error(traceback.format_exc())
            return False

    async def scan_existing_files(self, workspace_dir: Path, refresh: bool = False):
        if refresh:
            self.uploaded_files_cache.clear()
            logger.info("Force refresh mode: cleared local file hash cache.")

        logger.info(f"Starting to scan existing files in directory: {workspace_dir}")
        for item in workspace_dir.rglob('*'):
            if item.is_file():
                await self.upload_file(item, workspace_dir)
        logger.info("Existing file scan complete.")
        # Add conditional check, consistent with original TOSUploader
        if self.sandbox_id and self.uploaded_files_for_registration:
            await self.register_uploaded_files()

    async def _periodic_register(self):
        """Periodically check and register uploaded files"""
        while True:
            try:
                # Wait 30 seconds before attempting registration
                await asyncio.sleep(30)

                # If there are uploaded files and it's been more than 20 seconds since last upload, register
                current_time = time.time()
                if (self.uploaded_files_for_registration and
                    self.sandbox_id and
                    current_time - self.last_upload_time > 20):
                    logger.info("Detected no new uploads within 30 seconds, starting to register uploaded files")
                    await self.register_uploaded_files()
            except Exception as e:
                logger.error(f"Periodic registration task exception: {e}")
                logger.error(traceback.format_exc())
                # Continue loop, don't interrupt due to exception

    async def watch_command(self, workspace_dir: Path, once: bool, refresh: bool):
        if not await self._load_credentials():
            logger.error("Failed to initialize credentials and storage service, watch command cannot start.")
            return

        logger.info(f"Watch command started, monitoring directory: {workspace_dir}, one-time scan: {once}, force refresh: {refresh}")

        await self.scan_existing_files(workspace_dir, refresh)
        if once:
            logger.info("One-time scan completed, program exiting.")
            return

        # Start periodic registration task
        asyncio.create_task(self._periodic_register())
        logger.info("Started periodic file registration task (checks every 30 seconds)")

        event_handler = FileChangeEventHandler(tool_instance=self, workspace_dir_to_watch=workspace_dir)
        observer = Observer()
        observer.schedule(event_handler, str(workspace_dir), recursive=True)
        observer.start()
        logger.info(f"Started monitoring file changes in directory: {workspace_dir}...")
        try:
            while True:
                await asyncio.sleep(1)
        except KeyboardInterrupt:
            logger.info("Received interrupt signal, stopping monitoring.")
        finally:
            observer.stop()
            observer.join()
            logger.info("File monitoring stopped.")
            if self.uploaded_files_for_registration:
                logger.info("Before program exit, attempting to register final batch of uploaded files...")
                await self.register_uploaded_files()


class FileChangeEventHandler(FileSystemEventHandler):
    def __init__(self, tool_instance: StorageUploaderTool, workspace_dir_to_watch: Path):
        super().__init__()
        self.tool = tool_instance
        self.workspace_dir = workspace_dir_to_watch
        self.upload_queue = asyncio.Queue()
        self.loop = asyncio.get_event_loop()
        asyncio.create_task(self._process_upload_queue())

    async def _process_upload_queue(self):
        while True:
            file_path_to_upload = await self.upload_queue.get()
            try:
                # Delay 1 second to wait for file operation to complete (consistent with original TOSUploader)
                await asyncio.sleep(1)
                logger.info(f"Queue processor: Starting to process file {file_path_to_upload}")
                success = await self.tool.upload_file(file_path_to_upload, self.workspace_dir)

                # More precise immediate registration logic judgment, consistent with original TOSUploader
                if success and self.tool.uploaded_files_for_registration and self.tool.sandbox_id:
                    logger.info(f"File uploaded successfully, attempting immediate registration, uploaded files count: {len(self.tool.uploaded_files_for_registration)}")
                    await self.tool.register_uploaded_files()

            except Exception as e:
                logger.error(f"Failed to process file {file_path_to_upload} in upload queue: {e}", exc_info=True)
                logger.error(traceback.format_exc())
            finally:
                self.upload_queue.task_done()

    def _schedule_upload(self, file_path_str: str):
        file_path = Path(file_path_str)
        if not file_path.is_absolute():
             file_path = self.workspace_dir / file_path

        asyncio.run_coroutine_threadsafe(self.upload_queue.put(file_path), self.loop)
        logger.debug(f"Added file {file_path} to upload queue.")

    def on_created(self, event):
        if not event.is_directory:
            logger.info(f"Detected file creation: {event.src_path}")
            self._schedule_upload(event.src_path)

    def on_modified(self, event):
        if not event.is_directory:
            logger.info(f"Detected file modification: {event.src_path}")
            self._schedule_upload(event.src_path)

    def on_deleted(self, event):
        if not event.is_directory:
            logger.info(f"Detected file deletion: {event.src_path}")
            # Currently only records deletion event, no action performed
            # TODO: May need to implement file deletion from storage in the future

    def on_moved(self, event):
        if not event.is_directory:
            logger.info(f"Detected file move: {event.src_path} -> {event.dest_path}")
            # Treat move as deletion of original file and creation of new file
            self._schedule_upload(event.dest_path)


async def _run_storage_uploader_watch_async(
    tool: StorageUploaderTool,
    workspace_dir: Path,
    once: bool,
    refresh: bool
):
    await tool.watch_command(
        workspace_dir=workspace_dir,
        once=once,
        refresh=refresh
    )

@cli_app.command("watch")
def start_storage_uploader_watcher(
    sandbox_id: Optional[str] = typer.Option(None, "--sandbox", help="Sandbox ID for building upload path and file registration.", envvar="SUPER_MAGIC_SANDBOX_ID"),
    workspace_dir: str = typer.Option(".workspace", "--dir", help="Path to workspace directory to monitor for file changes.", envvar="SUPER_MAGIC_WORKSPACE_DIR", show_default=True),
    once: bool = typer.Option(False, "--once", help="Perform one-time file scan and upload, then exit without continuous directory monitoring."),
    refresh: bool = typer.Option(False, "--refresh", help="Force re-upload all files, ignoring local file hash cache records."),
    credentials_file: Optional[str] = typer.Option(None, "--credentials", "-c", help="Specify path to credentials file. If provided, this option takes precedence over '--use-context' and default lookup logic.", envvar="SUPER_MAGIC_CREDENTIALS_FILE"),
    use_context: bool = typer.Option(False, "--use-context", help="If credentials file not specified via '--credentials', try to use 'config/upload_credentials.json' under project as credentials file."),
    task_id: Optional[str] = typer.Option(None, "--task-id", help="Task ID for registration in backend system after successful file upload."),
    organization_code: Optional[str] = typer.Option(None, "--organization-code", help="Organization code, can be used for file registration or path building in multi-tenant scenarios.", envvar="SUPER_MAGIC_ORGANIZATION_CODE"),
    log_level: str = typer.Option("INFO", "--log-level", help="Set tool's logging output level (DEBUG, INFO, WARNING, ERROR).")
):
    setup_logger(log_name="app", console_level=log_level.upper())
    cmd_logger = get_logger("StorageUploaderToolCommand")

    cmd_logger.info(f"Uploader Watch CLI invoked. Current STORAGE_PLATFORM env: {os.environ.get('STORAGE_PLATFORM', 'Not set, default to tos')}")
    cmd_logger.info(f"  Sandbox ID: {sandbox_id or 'Not set'}")
    cmd_logger.info(f"  Workspace Dir: {workspace_dir}")
    cmd_logger.info(f"  Once: {once}")
    cmd_logger.info(f"  Refresh: {refresh}")
    cmd_logger.info(f"  Use Context Flag: {use_context}")
    cmd_logger.info(f"  Credentials File (CLI arg): {credentials_file}")
    cmd_logger.info(f"  Task ID: {task_id or 'Not set'}")
    cmd_logger.info(f"  Organization Code: {organization_code or 'Not set'}")
    cmd_logger.info(f"  Log Level: {log_level.upper()}")

    final_credentials_file = credentials_file
    if use_context and not final_credentials_file:
        if PathManager._initialized:
            context_creds_path = PathManager.get_project_root() / "config" / "upload_credentials.json"
            if context_creds_path.exists():
                final_credentials_file = str(context_creds_path)
                cmd_logger.info(f"'--use-context' is True and no --credentials provided. Using context credentials: {final_credentials_file}")
            else:
                cmd_logger.warning(f"'--use-context' is True, but context credentials file not found at: {context_creds_path}")
        else:
            cmd_logger.warning("PathManager not initialized. Cannot resolve context credentials path for '--use-context'.")

    cmd_logger.info(f"Final credentials file to be used by StorageUploaderTool: {final_credentials_file or 'Default lookup in StorageUploaderTool'}")

    try:
        tool_instance = StorageUploaderTool(
            credentials_file=final_credentials_file,
            sandbox_id=sandbox_id,
            task_id=task_id,
            organization_code=organization_code
        )

        asyncio.run(
            _run_storage_uploader_watch_async(
                tool=tool_instance,
                workspace_dir=Path(workspace_dir).resolve(),
                once=once,
                refresh=refresh
            )
        )
    except Exception as e:
        cmd_logger.error(f"Error in storage uploader watcher command: {e}", exc_info=True)
        cmd_logger.error(traceback.format_exc())
        raise typer.Exit(code=1)

if __name__ == "__main__":
    current_file_path = Path(__file__).resolve()
    project_root_for_direct_run = current_file_path.parent.parent.parent
    if not PathManager._initialized:
        PathManager.set_project_root(project_root_for_direct_run)
        print(f"PathManager initialized for direct run with root: {project_root_for_direct_run}")
    else:
        print(f"PathManager already initialized. Project root: {PathManager.get_project_root()}")

    cli_app()
