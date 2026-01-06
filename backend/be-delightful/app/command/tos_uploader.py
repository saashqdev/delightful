"""
TOS uploader command module - monitors workspace changes and uploads to Volcengine TOS automatically.
"""
import asyncio
import hashlib
import json
import os
import time
from pathlib import Path

from watchdog.events import FileSystemEvent, FileSystemEventHandler
from watchdog.observers import Observer

from agentlang.logger import get_logger
from app.infrastructure.storage.exceptions import InitException, UploadException
from app.infrastructure.storage.factory import StorageFactory
from app.infrastructure.storage.types import VolcEngineCredentials

# Get logger
logger = get_logger(__name__)


class TOSUploader:
    """TOS uploader utility"""

    def __init__(self, sandbox_id: str, workspace_dir: str, credentials_file: str = None,
                task_id: str = None, organization_code: str = None):
        """
        Initialize TOS uploader.
        
        Args:
            sandbox_id: Sandbox ID used to build upload path
            workspace_dir: Workspace directory to monitor
            credentials_file: Path to TOS credential file
            task_id: Task ID for registering files after upload (deprecated, kept for backward compatibility)
            organization_code: Organization code for post-upload registration
        """
        self.sandbox_id = sandbox_id
        self.workspace_dir = Path(workspace_dir).resolve()
        self.credentials_file = credentials_file
        self.credentials = None
        self.storage_service = None
        self.file_hashes = {}  # Cache file hashes to avoid duplicate uploads
        self.task_id = None  # task_id no longer used
        self.organization_code = organization_code
        self.uploaded_files = []  # Track uploaded files for batch registration

        # Get API base URL from environment
        self.api_base_url = os.getenv("DELIGHTFUL_API_SERVICE_BASE_URL")

        if not self.api_base_url:
            logger.warning("DELIGHTFUL_API_SERVICE_BASE_URL is not set; file registration will be unavailable")
        else:
            # Add https:// prefix if missing
            if not self.api_base_url.startswith(("http://", "https://")):
                self.api_base_url = f"https://{self.api_base_url}"
                logger.info("API URL lacked protocol prefix; https:// added automatically")

            # Ensure URL ends with /
            if not self.api_base_url.endswith("/"):
                self.api_base_url += "/"

        if self.api_base_url:
            logger.info(f"Using API service URL: {self.api_base_url}")

    async def initialize(self) -> bool:
        """
        Initialize the TOS uploader
        
        Returns:
            bool: Whether initialization succeeded
        """
        # Load credentials from file
        if not await self._load_credentials():
            logger.error("Unable to load TOS credentials")
            return False

        # _load_credentials already initializes storage service
        return True

    async def _load_credentials(self) -> bool:
        """
        Load TOS credentials
        
        Returns:
            bool: Whether loading succeeded
        """
        try:
            # Prefer default credential file
            default_file = Path(".credentials/upload_credentials.json")

            # Use provided credential file if present
            if self.credentials_file and os.path.exists(self.credentials_file):
                credentials_path = self.credentials_file
                logger.info(f"Using specified credential file: {self.credentials_file}")
            elif default_file.exists():
                credentials_path = default_file
                logger.info(f"Using default credential file: {default_file}")
            else:
                logger.error("No usable TOS credential file found")
                return False

            # Read credential file
            with open(credentials_path, "r") as f:
                credentials_data = json.load(f)

            # Validate credential format
            if not credentials_data.get("upload_config"):
                logger.error(f"upload_config missing in credential file {credentials_path}")
                return False

            # Ensure sandbox_id exists (unless provided via CLI)
            if not self.sandbox_id and not credentials_data.get("sandbox_id"):
                logger.error(f"Credential file {credentials_path} lacks required sandbox_id and none was provided via CLI")
                return False

            # Derive sandbox_id and organization_code
            # Use sandbox_id from credentials if not provided via CLI
            if not self.sandbox_id:
                self.sandbox_id = credentials_data.get("sandbox_id")

            # Use organization_code from credentials if not provided via CLI
            if not self.organization_code:
                self.organization_code = credentials_data.get("organization_code")

            # Build credential object
            self.credentials = VolcEngineCredentials(**credentials_data["upload_config"])
            logger.debug(f"Loaded latest TOS credentials from {credentials_path}")

            # Reinitialize storage service after each credential reload
            try:
                self.storage_service = await StorageFactory.get_storage()
                logger.debug("Successfully reinitialized TOS upload service")
            except Exception as e:
                logger.error(f"Failed to reinitialize TOS upload service: {e}")
                return False

            return True

        except Exception as e:
            logger.error(f"Failed to load TOS credentials: {e}")
            import traceback
            logger.error(traceback.format_exc())
            return False

    def get_file_hash(self, file_path: str) -> str:
        """
        Calculate MD5 hash of a file
        
        Args:
            file_path: Path to the file
            
        Returns:
            str: File MD5 hash
        """
        try:
            md5_hash = hashlib.md5()
            with open(file_path, "rb") as f:
                # Read in chunks for large files
                for chunk in iter(lambda: f.read(4096), b""):
                    md5_hash.update(chunk)
            return md5_hash.hexdigest()
        except Exception as e:
            logger.error(f"Failed to calculate file hash: {e}")
            return ""

    async def upload_file(self, file_path: str) -> bool:
        """
        Upload a file to TOS
        
        Args:
            file_path: Path to the file
            
        Returns:
            bool: Whether upload succeeded
        """
        # Reload credentials before each upload to ensure freshness
        if not await self._load_credentials():
            logger.error("Failed to reload TOS credentials before upload")
            return False

        if not self.storage_service or not self.credentials:
            logger.error("TOS upload service not initialized")
            return False

        try:
            # Ensure file exists
            file_path = str(file_path)
            if not os.path.exists(file_path):
                logger.warning(f"File does not exist; cannot upload: {file_path}")
                return False

            # Calculate file hash
            file_hash = self.get_file_hash(file_path)
            if not file_hash:
                return False                
            # Compute relative path
            try:
                rel_path = os.path.relpath(file_path, str(self.workspace_dir))
            except ValueError:
                # If file is outside workspace, fall back to file name
                rel_path = os.path.basename(file_path)

            # Build storage key using credential dir and relative path (no sandbox prefix)
            base_dir = self.credentials.get_dir()
            # Directly combine base_dir and rel_path for key
            key = f"{base_dir}{rel_path}"

            # Skip upload if file content unchanged
            if key in self.file_hashes and self.file_hashes[key] == file_hash:
                logger.info(f"File unchanged; skipping upload: {rel_path}")
                return True
            self.storage_service.set_credentials(self.credentials)

            # Upload file
            logger.info(f"Uploading file: {rel_path}, key: {key}")
            response = await self.storage_service.upload(
                file=file_path,
                key=key
            )            
            # Cache file hash
            self.file_hashes[key] = file_hash

            # Record uploaded file info for later registration
            if self.sandbox_id:
                file_ext = os.path.splitext(file_path)[1].lstrip('.')
                # Derive host from credentials to build full URL
                host = self.credentials.temporary_credential.host if hasattr(self.credentials, 'temporary_credential') else None
                if host:
                    # Ensure host lacks trailing slash and key lacks leading slash
                    if host.endswith('/'):
                        host = host[:-1]
                    file_key = key if not key.startswith('/') else key[1:]
                    external_url = f"{host}/{file_key}"
                else:
                    external_url = None

                self.uploaded_files.append({
                    "file_key": key,
                    "file_extension": file_ext,
                    "filename": os.path.basename(file_path),
                    "file_size": os.path.getsize(file_path),
                    "external_url": external_url,
                    "sandbox_id": self.sandbox_id
                })
                logger.info(f"Added file to pending registration list; count={len(self.uploaded_files)}")
            else:
                logger.warning("Sandbox ID not set; file uploaded but will not be registered")

            logger.info(f"File uploaded: {rel_path}, key: {key}")
            return True

        except (InitException, UploadException) as e:
            logger.error(f"File upload failed: {e}")
            return False
        except Exception as e:
            logger.error(f"Unexpected error during upload: {e}")
            return False

    async def register_uploaded_files(self) -> bool:
        """
        Register uploaded files with the API
        
        Returns:
            bool: Whether registration succeeded
        """
        if not self.sandbox_id:
            logger.error("Sandbox ID not set; cannot register files")
            return False

        if not self.uploaded_files:
            logger.info("No files to register; skipping")
            return True

        logger.info(f"Preparing to register files to API, count={len(self.uploaded_files)}, sandbox_id={self.sandbox_id}")

        api_url_env = os.getenv("DELIGHTFUL_API_SERVICE_BASE_URL", "unset")

        try:
            import aiohttp

            # Ensure API base URL exists
            if not self.api_base_url:
                logger.error("DELIGHTFUL_API_SERVICE_BASE_URL is not set; cannot register files")
                return False

            # API endpoint
            api_url = f"{self.api_base_url}api/v1/super-agent/file/process-attachments"

            # Build request payload
            request_data = {
                "attachments": self.uploaded_files,
                "sandbox_id": self.sandbox_id
            }

            # Add organization code if present
            if self.organization_code:
                request_data["organization_code"] = self.organization_code

            # Prepare headers
            headers = {
                "Content-Type": "application/json",
                "User-Agent": "TOS-Uploader/1.0"
            }

            # Log request details
            logger.info("========= File registration request =========")
            logger.info(f"Request URL: {api_url}")
            logger.info(f"Headers: {json.dumps(headers, ensure_ascii=False, indent=2)}")
            logger.info(f"Body: {json.dumps(request_data, ensure_ascii=False, indent=2)}")
            logger.info("====================================")

            # Send request
            logger.info(f"Registering uploaded files with API, sandbox_id={self.sandbox_id}, count={len(self.uploaded_files)}")
            async with aiohttp.ClientSession() as session:
                async with session.post(api_url, json=request_data, headers=headers) as response:
                    response_text = await response.text()
                    logger.info(f"Response status: {response.status}")
                    logger.info(f"Response body: {response_text}")

                    if response.status == 200:
                        try:
                            result = json.loads(response_text)
                            if result.get("code") == 1000:
                                logger.info(
                                    f"File registration succeeded: total={result.get('data', {}).get('total', 0)}, "
                                    f"success={result.get('data', {}).get('success', 0)}, "
                                    f"skipped={result.get('data', {}).get('skipped', 0)}"
                                )
                                # Clear list after successful registration
                                self.uploaded_files = []
                                return True
                            else:
                                logger.error(f"File registration API returned error: {result.get('message')}")
                        except json.JSONDecodeError:
                            logger.error("Response is not valid JSON")
                    else:
                        logger.error(f"File registration request failed, status: {response.status}")

            return False
        except Exception as e:
            logger.error(f"Error while registering uploaded files: {e}")
            import traceback
            logger.error(traceback.format_exc())
            return False

    async def scan_existing_files(self, refresh: bool = False) -> None:
        """
        Scan and upload existing files
        
        Args:
            refresh: Whether to force refresh all files
        """
        if refresh:
            self.file_hashes.clear()

        logger.info(f"Scanning directory: {self.workspace_dir}")

        # Recursively scan directory
        for root, _, files in os.walk(str(self.workspace_dir)):
            for file in files:
                file_path = os.path.join(root, file)
                await self.upload_file(file_path)

        logger.info("Directory scan finished")

        # Register uploaded files when sandbox_id is set
        if self.sandbox_id and self.uploaded_files:
            await self.register_uploaded_files()

    async def watch_command(self, sandbox_id: str, workspace_dir: str, once: bool = False, 
                          refresh: bool = False, credentials_file: str = None,
                          task_id: str = None, organization_code: str = None) -> None:
        """
        Implementation of the watch command
        
        Args:
            sandbox_id: Sandbox ID
            workspace_dir: Workspace directory
            once: Whether to scan once only
            refresh: Whether to force refresh all files
            credentials_file: Path to TOS credential file
            task_id: Task ID (deprecated, kept for backward compatibility)
            organization_code: Organization code
        """
        # Reset parameters
        self.sandbox_id = sandbox_id
        self.workspace_dir = Path(workspace_dir).resolve()
        self.credentials_file = credentials_file
        # self.task_id = task_id  # task_id not used anymore
        self.organization_code = organization_code

        # Initialize
        if not await self.initialize():
            logger.error("Initialization failed; exiting command")
            return

        # Scan existing files
        await self.scan_existing_files(refresh)

        # If only scanning once, exit
        if once:
            logger.info("One-time scan completed; exiting")
            return

        # Configure file system event handler
        event_handler = TOSFileEventHandler(self)

        # Pass current loop to the handler
        loop = asyncio.get_running_loop()
        event_handler.set_loop(loop)

        # Create observer
        observer = Observer()
        observer.schedule(event_handler, str(self.workspace_dir), recursive=True)

        # Start observer
        observer.start()
        logger.info(f"Started watching directory: {self.workspace_dir}")

        try:
            # Keep process alive
            while True:
                await asyncio.sleep(1)
        except KeyboardInterrupt:
            logger.info("Interrupt received; stopping watcher")
        finally:
            # Stop observer
            observer.stop()
            observer.join()


class TOSFileEventHandler(FileSystemEventHandler):
    """TOS file event handler"""

    def __init__(self, uploader: TOSUploader):
        """
        Initialize event handler
        
        Args:
            uploader: TOS uploader instance
        """
        super().__init__()
        self.uploader = uploader
        self._tasks = set()
        self._upload_queue = asyncio.Queue()
        self._main_loop = None
        self._register_timer = None
        self._last_upload_time = time.time()

    def set_loop(self, loop):
        """Set main event loop"""
        self._main_loop = loop
        # Start consumer task
        asyncio.run_coroutine_threadsafe(self._process_queue(), loop)
        # Start periodic registration task if task_id is set
        if self.uploader.task_id:
            asyncio.run_coroutine_threadsafe(self._periodic_register(), loop)

    async def _process_queue(self):
        """Process upload queue tasks"""
        while True:
            file_path = await self._upload_queue.get()
            try:
                # Delay briefly to allow file operations to finish
                await asyncio.sleep(1)
                # Credentials are reloaded during upload; no extra call needed
                uploaded = await self.uploader.upload_file(file_path)
                # Update last upload time
                self._last_upload_time = time.time()

                # If uploads succeeded, immediately attempt registration
                if uploaded and self.uploader.uploaded_files and self.uploader.sandbox_id:
                    logger.info(f"Upload succeeded; attempting immediate registration, uploaded count: {len(self.uploader.uploaded_files)}")
                    asyncio.create_task(self.uploader.register_uploaded_files())

            except Exception as e:
                logger.error(f"Failed to process upload task: {e}")
            finally:
                self._upload_queue.task_done()

    async def _periodic_register(self):
        """Periodically register uploaded files"""
        while True:
            try:
                # Wait 30s before attempting registration
                await asyncio.sleep(30)

                # Register if files exist and no uploads in last 20s
                current_time = time.time()
                if (self.uploader.uploaded_files and 
                    self.uploader.sandbox_id and
                    current_time - self._last_upload_time > 20):
                    logger.info("No uploads in last 30s; registering uploaded files")
                    await self.uploader.register_uploaded_files()
            except Exception as e:
                logger.error(f"Periodic registration task failed: {e}")
                # Continue loop despite errors

    def on_created(self, event: FileSystemEvent) -> None:
        """
        Handle file creation event
        
        Args:
            event: File system event
        """
        if event.is_directory:
            return

        logger.info(f"File created: {event.src_path}")
        self._schedule_upload(event.src_path)

    def on_modified(self, event: FileSystemEvent) -> None:
        """
        Handle file modification event
        
        Args:
            event: File system event
        """
        if event.is_directory:
            return

        logger.info(f"File modified: {event.src_path}")
        self._schedule_upload(event.src_path)

    def on_deleted(self, event: FileSystemEvent) -> None:
        """
        Handle file deletion event
        
        Args:
            event: File system event
        """
        if event.is_directory:
            return

        logger.info(f"File deleted: {event.src_path}")
        # Currently only logs deletion; TOS deletion might be added later

    def on_moved(self, event: FileSystemEvent) -> None:
        """
        Handle file move event
        
        Args:
            event: File system event
        """
        if event.is_directory:
            return

        logger.info(f"File moved: {event.src_path} -> {event.dest_path}")
        # Treat move as delete + create; future work may add move support
        self._schedule_upload(event.dest_path)

    def _schedule_upload(self, file_path: str) -> None:
        """
        Schedule an upload task
        
        Args:
            file_path: File path
        """
        if not self._main_loop:
            logger.error("Main event loop not set; cannot schedule upload task")
            return

        # Push task to queue in a thread-safe way
        asyncio.run_coroutine_threadsafe(
            self._upload_queue.put(file_path), 
            self._main_loop
        )


async def _run_tos_uploader_watch(sandbox_id: str = "default", 
                            workspace_dir: str = ".workspace", 
                            once: bool = False,
                            refresh: bool = False, 
                            credentials_file: str = None,
                            task_id: str = None, 
                            organization_code: str = None):
    """Run watcher for TOS uploader (internal async function)
    
    Args:
        sandbox_id: Sandbox ID used to build upload path
        workspace_dir: Workspace directory
        once: Scan existing files only once
        refresh: Force refresh all files
        credentials_file: Path to TOS credential file
        task_id: Task ID (deprecated, kept for compatibility)
        organization_code: Organization code
    """
    # Handle credential file path
    context_creds = "config/upload_credentials.json"
    if os.path.exists(context_creds) and not credentials_file:
        credentials_file = context_creds
        logger.info(f"Using contextual credential file: {context_creds}")

    # Log actual credential file path
    logger.info(f"Credential file path: {credentials_file or 'unspecified'}")

    # Check credential file existence
    if credentials_file:
        if os.path.exists(credentials_file):
            logger.info(f"Credential file exists: {credentials_file}")
        else:
            logger.warning(f"Specified credential file does not exist: {credentials_file}")

    tos_uploader = TOSUploader(
        sandbox_id, 
        workspace_dir, 
        credentials_file,
        task_id,
        organization_code
    )

    await tos_uploader.watch_command(
        sandbox_id, 
        workspace_dir, 
        once, 
        refresh,
        credentials_file,
        task_id,
        organization_code
    )

def start_tos_uploader_watcher(sandbox_id: str = "default", 
                 workspace_dir: str = ".workspace", 
                 once: bool = False,
                 refresh: bool = False, 
                 credentials_file: str = None,
                 use_context: bool = False,
                 task_id: str = None, 
                 organization_code: str = None):
    """Entry point for watching directory changes and uploading to TOS
    
    Args:
        sandbox_id: Sandbox ID used to build upload path
        workspace_dir: Workspace directory
        once: Scan existing files once
        refresh: Force refresh all files
        credentials_file: Path to TOS credential file
        use_context: Whether to use contextual credentials
        task_id: Task ID
        organization_code: Organization code
    """
    # Handle credential file path
    creds_file = credentials_file

    # Prefer config credentials when use_context is enabled
    if use_context and not creds_file:
        context_creds = "config/upload_credentials.json"
        if os.path.exists(context_creds):
            creds_file = context_creds
            logger.info(f"Using contextual credential file: {context_creds}")

    # Run async task
    asyncio.run(_run_tos_uploader_watch(
        sandbox_id=sandbox_id, 
        workspace_dir=workspace_dir, 
        once=once, 
        refresh=refresh,
        credentials_file=creds_file,
        task_id=task_id,
        organization_code=organization_code
    )) 
