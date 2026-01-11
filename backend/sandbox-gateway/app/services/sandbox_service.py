"""
Sandbox service core logic
"""
import asyncio
import json
import logging
import time
import uuid
import os
from typing import Dict, List, Optional, Tuple, cast

import docker
import websockets
import aiohttp
from docker.errors import DockerException, ImageNotFound
from fastapi import WebSocket, WebSocketDisconnect
from starlette.websockets import WebSocketState
from websockets.legacy.client import WebSocketClientProtocol
from dotenv import dotenv_values

from app.config import (
    SANDBOX_LABEL,
    AGENT_LABEL_PREFIX,
    QDRANT_LABEL,
    QDRANT_LABEL_PREFIX,
    WS_MESSAGE_TYPE_ERROR,
    settings
)
from app.config.constants import AGENT_LABEL
from app.models.sandbox import ContainerInfo, SandboxInfo
from app.utils.exceptions import (
    ContainerOperationError,
    SandboxNotFoundError,
    handle_exceptions,
    async_handle_exceptions
)

logger = logging.getLogger("sandbox_gateway")


class SandboxService:
    """Sandbox service responsible for managing Docker containers and WebSocket communication"""

    def __init__(self):
        """Initialize sandbox service"""
        try:
            self.docker_client = docker.from_env()
            self.image_name = settings.be_delightful_image_name
            self.qdrant_image_name = settings.qdrant_image_name
            self.running_container_expire_time = settings.running_container_expire_time
            self.exited_container_expire_time = settings.exited_container_expire_time
            self.container_ws_port = settings.container_ws_port
            self.qdrant_port = settings.qdrant_port
            self.qdrant_grpc_port = settings.qdrant_grpc_port
            # Get network configuration, default to 'bridge'
            self.network_name = os.environ.get('SANDBOX_NETWORK', 'bridge')
            logger.info(
                f"Docker client initialized successfully, using image: {self.image_name}, "
                f"running container timeout: {self.running_container_expire_time} seconds, "
                f"exited container expiration time: {self.exited_container_expire_time} seconds, "
                f"network: {self.network_name}, "
                f"Qdrant image: {self.qdrant_image_name}"
            )
        except Exception as e:
            logger.error(f"Docker client initialization failed: {e}")
            raise

    def _get_agent_container_by_sandbox_id(self, sandbox_id: str) -> Optional[docker.models.containers.Container]:
        """
        Get the corresponding container by sandbox ID

        Args:
            sandbox_id: Sandbox ID

        Returns:
            Container: Container object, returns None if not found
        """
        try:
            # Find container by label
            containers = self.docker_client.containers.list(
                all=True,
                filters={"label": f"{AGENT_LABEL}={sandbox_id}"}
            )
            return containers[0] if containers else None
        except Exception as e:
            logger.error(f"Error querying container: {e}")
            return None

    def _get_qdrant_container_by_sandbox_id(self, sandbox_id: str) -> Optional[docker.models.containers.Container]:
        """
        Get the corresponding container by Qdrant ID

        Args:
            qdrant_id: Qdrant ID

        Returns:
            Container: Container object, returns None if not found
        """
        try:
            # Find container by label
            containers = self.docker_client.containers.list(
                all=True,
                filters={"label": f"{QDRANT_LABEL}={sandbox_id}"}
            )
            return containers[0] if containers else None
        except Exception as e:
            logger.error(f"Error querying Qdrant container: {e}")
            return None

    def _get_container_info(self, container: docker.models.containers.Container) -> ContainerInfo:
        """
        Get detailed information about the container

        Args:
            container: Docker container object

        Returns:
            ContainerInfo: Container information
        """
        container.reload()

        # Get container information and network settings
        network_settings = container.attrs['NetworkSettings']
        networks_data = network_settings['Networks']

        # Get IP address
        container_ip = None
        for net_name, net_config in networks_data.items():
            container_ip = net_config['IPAddress']
            if container_ip:
                break

        # Get creation time
        created_at = time.mktime(time.strptime(
            container.attrs['Created'].split('.')[0],
            '%Y-%m-%dT%H:%M:%S'
        ))

        # Convert UTC time to local time
        created_at = time.time() - (time.mktime(time.gmtime()) - created_at)

        # Get status
        status = container.status

        # Get container start time
        started_at = None
        if 'State' in container.attrs and 'StartedAt' in container.attrs['State']:
            started_at_str = container.attrs['State']['StartedAt'].split('.')[0]
            # Check if it's "0001-01-01T00:00:00Z" (indicates container has not started)
            if started_at_str != "0001-01-01T00:00:00":
                try:
                    # Convert string time to timestamp
                    started_at_time = time.mktime(time.strptime(
                        started_at_str,
                        '%Y-%m-%dT%H:%M:%S'
                    ))
                    # Convert UTC time to local time
                    started_at = time.time() - (time.mktime(time.gmtime()) - started_at_time)
                    logger.debug(f"Container {container.name} start time: {started_at}")
                except Exception as e:
                    logger.error(f"Error parsing container start time: {e}, raw value: {container.attrs['State']['StartedAt']}")

        # Get exit time (if container has exited)
        exited_at = None
        if status == "exited" and 'State' in container.attrs and 'FinishedAt' in container.attrs['State']:
            finished_at_str = container.attrs['State']['FinishedAt'].split('.')[0]
            # Check if it's "0001-01-01T00:00:00Z" (indicates container has not finished)
            if finished_at_str != "0001-01-01T00:00:00":
                try:
                    # Convert string time to timestamp
                    finished_at = time.mktime(time.strptime(
                        finished_at_str,
                        '%Y-%m-%dT%H:%M:%S'
                    ))
                    # Convert UTC time to local time
                    exited_at = time.time() - (time.mktime(time.gmtime()) - finished_at)
                    logger.debug(f"Container {container.name} exit time: {exited_at}")
                except Exception as e:
                    logger.error(f"Error parsing container exit time: {e}, raw value: {container.attrs['State']['FinishedAt']}")

        return ContainerInfo(
            id=container.id,
            ip=container_ip,
            ws_port=self.container_ws_port,
            created_at=created_at,
            started_at=started_at,
            status=status,
            exited_at=exited_at
        )

    def _get_container_logs(self, container: docker.models.containers.Container, tail: int = 100) -> str:
        """
        Get container logs

        Args:
            container: Docker container object
            tail: Number of log lines to return, default 100 lines

        Returns:
            str: Container log content
        """
        try:
            logs = container.logs(tail=tail, timestamps=True, stream=False).decode('utf-8')
            return logs
        except Exception as e:
            logger.error(f"Error getting logs: {e}")
            return f"Unable to get logs: {e}"

    @async_handle_exceptions
    async def _get_auth_token(self) -> Optional[str]:
        """
        Send request to authentication service to get auth token. Returns None if DELIGHTFUL_GATEWAY_BASE_URL environment variable is not set

        Returns:
            Optional[str]: Authentication token, returns None if DELIGHTFUL_GATEWAY_BASE_URL environment variable is not set

        Raises:
            ContainerOperationError: When request fails, response format doesn't match expectations, or DELIGHTFUL_GATEWAY_API_KEY is not set
        """
        delightful_gateway_url = os.environ.get("DELIGHTFUL_GATEWAY_BASE_URL")

        if not delightful_gateway_url:
            logger.info("DELIGHTFUL_GATEWAY_BASE_URL environment variable not set, skipping authentication step")
            return None

        delightful_gateway_api_key = os.environ.get("DELIGHTFUL_GATEWAY_API_KEY")
        if not delightful_gateway_api_key:
            error_msg = "DELIGHTFUL_GATEWAY_API_KEY environment variable not set, cannot perform authentication"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

        try:
            headers = {
                "X-USER-ID": "user",
                "X-Gateway-API-Key": delightful_gateway_api_key
            }

            auth_url = f"{delightful_gateway_url}/auth"
            logger.info(f"Requesting authentication service: {auth_url}")

            async with aiohttp.ClientSession() as session:
                async with session.post(auth_url, headers=headers) as response:
                    if response.status != 200:
                        raise ContainerOperationError(f"Authentication service request failed, status code: {response.status}")

                    auth_data = await response.json()

                    if "token" not in auth_data:
                        raise ContainerOperationError("Authentication service response format doesn't match expectations, missing token field")

                    return auth_data["token"]

        except aiohttp.ClientError as e:
            error_msg = f"Request to authentication service failed: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

    @async_handle_exceptions
    async def _create_agent_container(self, sandbox_id: str) -> str:
        """
        Create new sandbox container and perform health check

        Args:
            container_id: Container ID

        Returns:
            str: Docker container ID

        Raises:
            ContainerOperationError: Container operation failed
        """
        try:
            # Check if there's already an exited container
            container = self._get_agent_container_by_sandbox_id(sandbox_id)

            if container:
                if container.status == "running":
                    logger.info(f"Agent container already exists: {container.name}, associated sandbox ID: {sandbox_id}")
                elif container.status == "exited":
                    logger.info(f"Found exited Agent container: {container.name}, attempting to restart")
                    # Start existing container
                    container.start()
                else:
                    raise ContainerOperationError(f"Agent container status abnormal: {container.status}")
            else:
                # Check if image exists
                try:
                    self.docker_client.images.get(self.image_name)
                    logger.info(f"Using image: {self.image_name}")
                except ImageNotFound:
                    raise ContainerOperationError(f"Image does not exist: {self.image_name}")

                # Prepare container environment variables
                environment = {
                    "QDRANT_BASE_URI": f"http://{QDRANT_LABEL_PREFIX}{sandbox_id}:{self.qdrant_port}",
                    "SANDBOX_ID": sandbox_id,
                    "APP_ENV": settings.app_env,
                }

                token = await self._get_auth_token()
                if token:
                    environment["DELIGHTFUL_AUTHORIZATION"] = token
                    logger.info("Successfully obtained auth token and added to container environment variables")

                # Read Agent environment file variables (file must exist as verified during settings load)
                env_vars = dotenv_values(settings.agent_env_file_path)
                if env_vars:
                    # Merge environment variables, allowing variables from the environment file to override default values
                    environment.update(env_vars)
                    logger.info(f"Added {len(env_vars)} environment variables from Agent environment file {settings.agent_env_file_path}")
                else:
                    logger.warning(f"Agent environment file {settings.agent_env_file_path} exists but no environment variables were read")

                # Create and start container
                # Mount config file, check if /app/config/config.yaml exists
                config_file_path = os.environ.get("BE_DELIGHTFUL_CONFIG_FILE_PATH")
                if config_file_path:
                    volumes = {
                        config_file_path: {
                            'bind': '/app/config/config.yaml',
                            'mode': 'rw'
                        }
                    }
                    logger.info(f"Using config file: {config_file_path}")
                else:
                    logger.warning(f"BE_DELIGHTFUL_CONFIG_FILE_PATH config file does not exist: {config_file_path}")
                    volumes = {}

                # Mount config file
                container = self.docker_client.containers.run(
                    self.image_name,
                    detach=True,
                    environment=environment,
                    name=f"{AGENT_LABEL_PREFIX}{sandbox_id}",
                    labels={
                        AGENT_LABEL: sandbox_id,
                        SANDBOX_LABEL: sandbox_id
                    },
                    network=self.network_name,  # Use same network as gateway
                    volumes=volumes
                )
                logger.info(f"Container created: {container.name}, using network: {self.network_name}")

            # The following code is the same whether starting an existing container or creating a new one
            # Wait for container to start
            container.reload()

            # Get container information
            container_info = self._get_container_info(container)

            # Use health check endpoint to confirm container is ready
            container_ready = await self._wait_for_container_ready(container_info)
            if not container_ready:
                # Get container logs
                container_logs = self._get_container_logs(container)
                error_msg = "Container startup timeout, health check failed"
                logger.error(f"{error_msg}\nContainer logs:\n{container_logs}")
                # Clean up container
                try:
                    container.stop()
                    container.remove()
                except Exception as e:
                    logger.error(f"Error cleaning up failed container: {e}")
                raise ContainerOperationError(f"{error_msg}, see logs for detailed error information")

            if not container_info.ip:
                # Get container logs
                container_logs = self._get_container_logs(container)
                error_msg = "Unable to get container IP address"
                logger.error(f"{error_msg}\nContainer logs:\n{container_logs}")
                # Clean up container
                try:
                    container.stop()
                    container.remove()
                except Exception as e:
                    logger.error(f"Error cleaning up failed container: {e}")
                raise ContainerOperationError(error_msg)

            # Print sandbox container IP
            is_restarted = container and container.status == "exited"
            if is_restarted:
                logger.info(f"Restarted sandbox container name: {container.name}, sandbox container IP: {container_info.ip}")
            else:
                logger.info(f"Sandbox container name: {container.name}, sandbox container IP: {container_info.ip}")

            # Return container ID
            return container.id

        except ContainerOperationError:
            # Re-throw container operation exception
            raise
        except DockerException as e:
            error_msg = f"Docker operation failed: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)
        except Exception as e:
            error_msg = f"Error creating sandbox container: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

    @async_handle_exceptions
    async def create_sandbox(self, sandbox_id: Optional[str] = None) -> str:
        """
        Create new sandbox container

        Args:
            sandbox_id: Optional sandbox ID, auto-generated if not provided

        Returns:
            str: Sandbox container ID

        Raises:
            ContainerOperationError: Container operation failed
        """
        # If no sandbox_id is provided, generate a random ID
        if not sandbox_id:
            sandbox_id = str(uuid.uuid4())[:8]

        try:
            await self._create_qdrant_container(sandbox_id)
            logger.info(f"Qdrant container created, associated sandbox ID: {sandbox_id}")

            await self._create_agent_container(sandbox_id)
            logger.info(f"Agent container created, associated sandbox ID: {sandbox_id}")

            return sandbox_id

        except ContainerOperationError:
            raise
        except DockerException as e:
            error_msg = f"Docker operation failed: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)
        except Exception as e:
            error_msg = f"Error creating sandbox: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

    @async_handle_exceptions
    async def _create_qdrant_container(self, sandbox_id: str) -> str:
        """
        Create corresponding Qdrant container for sandbox

        Args:
            sandbox_id: Sandbox ID, used to associate Qdrant container

        Returns:
            str: Qdrant container ID

        Raises:
            ContainerOperationError: Container operation failed
        """
        try:
            # Check if there's already an exited Qdrant container
            qdrant_container = self._get_qdrant_container_by_sandbox_id(sandbox_id)

            if qdrant_container:
                if qdrant_container.status == "running":
                    logger.info(f"Qdrant container already exists: {qdrant_container.name}, associated sandbox ID: {sandbox_id}")
                    return qdrant_container.id
                elif qdrant_container.status == "exited":
                    logger.info(f"Found exited Qdrant container: {qdrant_container.name}, attempting to restart")
                    # Start existing container
                    qdrant_container.start()
                else:
                    raise ContainerOperationError(f"Qdrant container status abnormal: {qdrant_container.status}")
            else:
                # Check if Qdrant image exists
                try:
                    self.docker_client.images.get(self.qdrant_image_name)
                    logger.info(f"Using Qdrant image: {self.qdrant_image_name}")
                except ImageNotFound:
                    raise ContainerOperationError(f"Qdrant image does not exist: {self.qdrant_image_name}")

                # Set container name and labels
                qdrant_name = f"{QDRANT_LABEL_PREFIX}{sandbox_id}"

                # Create and start Qdrant container
                qdrant_container = self.docker_client.containers.run(
                    self.qdrant_image_name,
                    detach=True,
                    environment={},
                    name=qdrant_name,
                    labels={
                        QDRANT_LABEL: sandbox_id,  # Use same sandbox_id as association
                        SANDBOX_LABEL: sandbox_id
                    },
                    network=self.network_name  # Use same network as sandbox container
                )
                logger.info(f"Qdrant container created: {qdrant_container.name}, associated sandbox ID: {sandbox_id}, using network: {self.network_name}")

            # The following code is the same whether starting an existing container or creating a new one
            # Wait for container to start
            qdrant_container.reload()

            # Get container information
            container_info = self._get_container_info(qdrant_container)

            # Check if Qdrant container is ready
            qdrant_ready = await self._wait_for_qdrant_ready(container_info)
            if not qdrant_ready:
                # Get container logs
                container_logs = self._get_container_logs(qdrant_container)
                error_msg = "Qdrant container startup timeout, health check failed"
                logger.error(f"{error_msg}\nContainer logs:\n{container_logs}")
                # Clean up container
                try:
                    qdrant_container.stop()
                    qdrant_container.remove()
                except Exception as e:
                    logger.error(f"Error cleaning up failed Qdrant container: {e}")
                raise ContainerOperationError(f"{error_msg}, see logs for detailed error information")

            # Record container status
            is_restarted = qdrant_container and qdrant_container.status == "exited"
            if is_restarted:
                logger.info(f"Restarted Qdrant container: {qdrant_container.name}, associated sandbox ID: {sandbox_id}")

            return qdrant_container.id

        except ContainerOperationError:
            # Re-raise container operation exception
            raise
        except DockerException as e:
            error_msg = f"Docker operation failed: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)
        except Exception as e:
            error_msg = f"Error creating Qdrant container: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

    async def _wait_for_qdrant_ready(self, container_info: ContainerInfo, max_attempts: int = 30, sleep_time: int = 1) -> bool:
        """
        Determine if Qdrant container has fully started by requesting Qdrant health check endpoint

        Args:
            container_info: Container information
            max_attempts: Maximum number of attempts
            sleep_time: Interval time between attempts (seconds)

        Returns:
            bool: Whether container is ready
        """
        if not container_info.ip:
            return False

        health_url = f"http://{container_info.ip}:{self.qdrant_port}"
        logger.info(f"Waiting for Qdrant container health check: {health_url}, max attempts: {max_attempts}")

        for attempt in range(1, max_attempts + 1):
            try:
                async with aiohttp.ClientSession() as session:
                    async with session.get(health_url, timeout=2) as response:
                        if response.status == 200:
                            logger.info(f"Qdrant container health check successful, attempts: {attempt}")
                            return True
            except Exception as e:
                logger.debug(f"Qdrant health check attempt {attempt}/{max_attempts} failed: {e}")

            await asyncio.sleep(sleep_time)

        logger.warning(f"Qdrant container health check failed, reached maximum attempts: {max_attempts}")
        return False

    @handle_exceptions
    def get_agent_container(self, sandbox_id: str) -> Optional[SandboxInfo]:
        """
        Get sandbox information

        Args:
            sandbox_id: Sandbox ID

        Returns:
            SandboxInfo: Sandbox information, returns None if sandbox doesn't exist
        """
        container = self._get_agent_container_by_sandbox_id(sandbox_id)

        if not container:
            return None

        container_info = self._get_container_info(container)

        return SandboxInfo(
            sandbox_id=sandbox_id,
            status=container_info.status,
            created_at=container_info.created_at,
            started_at=container_info.started_at,
            ip_address=container_info.ip
        )

    @handle_exceptions
    def list_sandboxes(self) -> List[SandboxInfo]:
        """
        List all sandbox containers

        Returns:
            List[SandboxInfo]: Sandbox information list
        """
        result = []
        try:
            # Get all containers with sandbox label
            containers = self.docker_client.containers.list(
                all=True,
                filters={"label": [f"{SANDBOX_LABEL}"]}
            )

            for container in containers:
                sandbox_id = container.labels.get(SANDBOX_LABEL)
                # Exclude Qdrant containers
                if sandbox_id and container.name.startswith(AGENT_LABEL_PREFIX):
                    container_info = self._get_container_info(container)
                    result.append(SandboxInfo(
                        sandbox_id=sandbox_id,
                        status=container_info.status,
                        created_at=container_info.created_at,
                        started_at=container_info.started_at,
                        ip_address=container_info.ip
                    ))
        except Exception as e:
            logger.error(f"Error listing sandbox containers: {e}")

        return result

    @handle_exceptions
    def delete_sandbox(self, sandbox_id: str) -> bool:
        """
        Delete sandbox container

        Args:
            sandbox_id: Sandbox ID

        Returns:
            bool: Whether deletion was successful

        Raises:
            SandboxNotFoundError: Sandbox doesn't exist
            ContainerOperationError: Container operation failed
        """
        container = self._get_agent_container_by_sandbox_id(sandbox_id)

        if not container:
            raise SandboxNotFoundError(sandbox_id)

        try:
            # Delete corresponding Qdrant container first
            qdrant_container = self._get_qdrant_container_by_sandbox_id(sandbox_id)
            if qdrant_container:
                try:
                    qdrant_container.stop()
                    qdrant_container.remove()
                    logger.info(f"Qdrant container deleted, associated sandbox ID: {sandbox_id}")
                except Exception as e:
                    logger.error(f"Error deleting Qdrant container {sandbox_id}: {e}")

            # Delete sandbox container
            container.stop()
            container.remove()
            logger.info(f"Sandbox container deleted: {sandbox_id}")
            return True
        except Exception as e:
            error_msg = f"Error deleting sandbox container {sandbox_id}: {e}"
            logger.error(error_msg)
            raise ContainerOperationError(error_msg)

    @async_handle_exceptions
    async def handle_websocket(self, websocket: WebSocket, sandbox_id: str) -> None:
        """
        Handle WebSocket connection, connecting to specified sandbox container

        Args:
            websocket: WebSocket connection
            sandbox_id: Sandbox ID to connect to

        Raises:
            SandboxNotFoundError: Sandbox doesn't exist
            ContainerOperationError: Container operation failed
        """
        await websocket.accept()
        logger.info(f"Sandbox WebSocket connection accepted, connecting to sandbox: {sandbox_id}")

        # Check if sandbox exists
        container = self._get_agent_container_by_sandbox_id(sandbox_id)

        if not container:
            error_msg = f"Sandbox {sandbox_id} does not exist or has expired"
            logger.error(error_msg)
            await websocket.send_text(json.dumps({
                "type": WS_MESSAGE_TYPE_ERROR,
                "error": error_msg
            }))
            await websocket.close()
            return

        try:
            # Get container information
            container_info = self._get_container_info(container)
            container_ip = container_info.ip
            ws_port = container_info.ws_port

            # Connect to container's WebSocket service
            container_ws_url = f"ws://{container_ip}:{ws_port}/ws"
            logger.info(f"Connecting to container WebSocket: {container_ws_url}, sandbox ID: {sandbox_id}")

            # Create connection to container WebSocket service
            try:
                async with websockets.connect(container_ws_url, ping_interval=None) as container_ws:
                    logger.info(f"Connected to container WebSocket: {container_ws_url}, sandbox ID: {sandbox_id}")

                    # Bidirectionally forward messages
                    await self._proxy_websocket(websocket, container_ws, sandbox_id)

            except websockets.exceptions.InvalidStatusCode as e:
                error_msg = f"Unable to connect to container WebSocket: {e}, sandbox ID: {sandbox_id}"
                logger.error(error_msg)
                await websocket.send_text(json.dumps({"type": WS_MESSAGE_TYPE_ERROR, "error": error_msg}))
            except Exception as e:
                error_msg = f"WebSocket proxy error: {e}, sandbox ID: {sandbox_id}"
                logger.error(error_msg)
                await websocket.send_text(json.dumps({"type": WS_MESSAGE_TYPE_ERROR, "error": error_msg}))

        except Exception as e:
            error_msg = f"Error connecting to sandbox: {e}, sandbox ID: {sandbox_id}"
            logger.error(error_msg)
            await websocket.send_text(json.dumps({
                "type": WS_MESSAGE_TYPE_ERROR,
                "error": error_msg
            }))
        finally:
            # Close WebSocket connection
            if websocket.client_state != WebSocketState.DISCONNECTED:
                await websocket.close()
            logger.info(f"WebSocket connection closed, sandbox ID: {sandbox_id}")

    async def _proxy_websocket(
        self,
        client_ws: WebSocket,
        container_ws: WebSocketClientProtocol,
        sandbox_id: str
    ) -> None:
        """
        Proxy WebSocket connection

        Args:
            client_ws: Client WebSocket connection
            container_ws: Container WebSocket connection
            sandbox_id: Container ID
        """
        async def forward_to_container() -> None:
            """Forward messages from client to container"""
            try:
                while True:
                    data = await client_ws.receive_text()
                    try:
                        # Try to parse received JSON
                        json_data = json.loads(data)
                        # Reformat to indented JSON
                        formatted_data = json.dumps(json_data, ensure_ascii=False, indent=2)
                        logger.debug(f"Forwarding to container {sandbox_id}: {formatted_data}")
                        await container_ws.send(data)  # Send original data instead of formatted
                    except json.JSONDecodeError:
                        # If not valid JSON, pass raw data directly
                        logger.debug(f"Forwarding to container {sandbox_id}: {data}")
                        await container_ws.send(data)
            except WebSocketDisconnect:
                logger.info(f"Client WebSocket disconnected {sandbox_id}")
            except Exception as e:
                logger.error(f"Error forwarding to container {sandbox_id}: {e}")

        async def forward_to_client() -> None:
            """Forward messages from container to client"""
            try:
                while True:
                    try:
                        # Use configured timeout
                        data = await asyncio.wait_for(
                            container_ws.recv(),
                            timeout=settings.ws_receive_timeout
                        )
                        try:
                            # Try to parse received JSON (for logging only)
                            json_data = json.loads(data)
                            # Reformat to indented JSON (for logging only)
                            formatted_data = json.dumps(json_data, ensure_ascii=False, indent=2)
                            logger.debug(f"Forwarding to client {sandbox_id}: {formatted_data}")
                        except json.JSONDecodeError:
                            # If not valid JSON, log raw data directly
                            logger.debug(f"Forwarding to client {sandbox_id}: {data}")

                        # Always send original data
                        await client_ws.send_text(data)
                    except asyncio.TimeoutError:
                        logger.warning(f"Container {sandbox_id} receive message timeout, closing connection")
                        return
            except websockets.exceptions.ConnectionClosed:
                logger.info(f"Container WebSocket disconnected {sandbox_id}")
            except Exception as e:
                logger.error(f"Error forwarding to client {sandbox_id}: {e}")

        # Run both forwarding tasks concurrently
        client_task = asyncio.create_task(forward_to_container())
        container_task = asyncio.create_task(forward_to_client())

        # Wait for any task to complete
        done, pending = await asyncio.wait(
            [client_task, container_task],
            return_when=asyncio.FIRST_COMPLETED
        )

        # Cancel unfinished tasks
        for task in pending:
            task.cancel()
            try:
                await task
            except asyncio.CancelledError:
                pass

    async def _check_container_health(self, container_id: str) -> Tuple[bool, str]:
        """
        Check container health status

        Args:
            container_id: Container ID

        Returns:
            Tuple[bool, str]: (is healthy, status information)
        """
        container = self._get_agent_container_by_sandbox_id(container_id)
        if not container:
            return False, "Container does not exist"

        try:
            container.reload()

            # Check if container is running
            if container.status != "running":
                # Get container logs to understand failure reason
                container_logs = self._get_container_logs(container)
                logger.error(f"Container status abnormal: {container.status}\nContainer logs:\n{container_logs}")
                return False, f"Container status: {container.status}"

            # Get container information
            container_info = self._get_container_info(container)

            # Try to connect to container WebSocket service
            container_ws_url = f"ws://{container_info.ip}:{container_info.ws_port}/ws"

            try:
                # Try to connect without waiting, only verify if connection succeeds
                async with websockets.connect(container_ws_url, close_timeout=2, ping_interval=None):
                    return True, "Container healthy"
            except Exception as e:
                # Get container logs to understand why WebSocket service failed to start
                container_logs = self._get_container_logs(container)
                logger.error(f"WebSocket connection failed: {e}\nContainer logs:\n{container_logs}")
                return False, f"WebSocket connection failed: {e}"

        except Exception as e:
            # Try to get container logs, even if an exception occurred during health check
            try:
                container_logs = self._get_container_logs(container)
                logger.error(f"Health check failed: {e}\nContainer logs:\n{container_logs}")
            except Exception as log_error:
                logger.error(f"Health check failed: {e}, and unable to get container logs: {log_error}")
            return False, f"Health check failed: {e}"

    async def _cleanup_running_containers(self, current_time: float) -> None:
        """
        Clean up containers that have been running too long (stop operation)

        Args:
            current_time: Current timestamp
        """
        try:
            # Get all running containers with sandbox labels
            running_containers = self.docker_client.containers.list(
                filters={"label": [f"{SANDBOX_LABEL}"], "status": "running"}
            )

            for container in running_containers:
                try:
                    container_info = self._get_container_info(container)

                    # Use start time instead of creation time
                    started_at = container_info.started_at
                    if not started_at:
                        logger.warning(f"Container {container.name} has no valid start time, using creation time instead")
                        started_at = container_info.created_at

                    running_seconds = (current_time - started_at)

                    # Record container status and running time
                    logger.info(
                        f"Running container {container.name} has been running: {running_seconds:.2f} seconds, "
                        f"start time: {started_at}, current time: {current_time}"
                    )

                    # Check if exceeded running time limit
                    if running_seconds > self.running_container_expire_time:
                        logger.info(f"Starting to pause expired container: {container.name}, running time: {running_seconds:.2f} seconds")
                        container.stop()
                        logger.info(f"Successfully paused container: {container.name}")
                except Exception as e:
                    logger.error(f"Error pausing container: {container.name}, {e}")
        except Exception as e:
            logger.error(f"Error during container pause process: {e}")

    async def _cleanup_exited_containers(self, current_time: float) -> None:
        """
        Clean up expired exited containers (delete operation)

        Args:
            current_time: Current timestamp
        """
        try:
            # Get all exited containers with sandbox labels
            exited_containers = self.docker_client.containers.list(
                all=True,  # Include containers of all statuses
                filters={"label": [f"{SANDBOX_LABEL}"], "status": "exited"}
            )

            for container in exited_containers:
                try:
                    container_info = self._get_container_info(container)
                    created_at = container_info.created_at
                    exited_at = container_info.exited_at

                    # Use exit time to calculate how long it has been exited
                    idle_seconds = (current_time - exited_at)
                    logger.info(
                        f"Exited container {container.name} has been exited: {idle_seconds:.2f} seconds, "
                        f"creation time: {created_at}, exit time: {exited_at}"
                    )

                    # Check if exceeded exited container retention time
                    if idle_seconds > self.exited_container_expire_time:
                        logger.info(f"Starting to delete expired exited container: {container.name}, exited time: {idle_seconds:.2f} seconds")
                        container.remove()
                        logger.info(f"Successfully deleted exited container: {container.name}")
                except Exception as e:
                    logger.error(f"Error processing exited container: {container.name} - {e}")
        except Exception as e:
            logger.error(f"Error during exited container cleanup process: {e}")

    async def cleanup_idle_containers(self) -> None:
        """Periodically clean up long-running containers and exited containers"""
        while True:
            try:
                current_time = time.time()

                # Pause running containers
                await self._cleanup_running_containers(current_time)

                # Clean up exited containers
                # await self._cleanup_exited_containers(current_time)

                await asyncio.sleep(settings.cleanup_interval)
            except Exception as e:
                logger.error(f"Error during container cleanup process: {e}")
                await asyncio.sleep(60)

    async def _wait_for_container_ready(self, container_info: ContainerInfo, max_attempts: int = 30, sleep_time: int = 1) -> bool:
        """
        Determine if container has fully started by requesting container's health check endpoint

        Args:
            container_info: Container information
            max_attempts: Maximum number of attempts
            sleep_time: Interval time between attempts (seconds)

        Returns:
            bool: Whether container is ready
        """
        if not container_info.ip:
            return False

        health_url = f"http://{container_info.ip}:{container_info.ws_port}/api/health"
        logger.info(f"Waiting for container health check: {health_url}, max attempts: {max_attempts}")

        for attempt in range(1, max_attempts + 1):
            try:
                async with aiohttp.ClientSession() as session:
                    async with session.get(health_url, timeout=2) as response:
                        if response.status == 200:
                            logger.info(f"Container health check successful, attempts: {attempt}")
                            return True
            except Exception as e:
                logger.debug(f"Health check attempt {attempt}/{max_attempts} failed: {e}")

            await asyncio.sleep(sleep_time)

        logger.warning(f"Container health check failed, reached maximum attempts: {max_attempts}")
        return False


# Create global sandbox service instance
sandbox_service = SandboxService()
