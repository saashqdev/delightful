"""
HTTP subscription stream implementation

Provides HTTP-based stream implementation for sending messages to subscribed HTTP endpoints
"""

import asyncio
import json
from typing import Dict, Optional, Tuple

import aiohttp

from agentlang.event.event import EventType
from agentlang.logger import get_logger
from app.core.entity.message.client_message import MessageSubscriptionConfig
from app.core.stream import Stream

logger = get_logger(__name__)

class HTTPSubscriptionStream(Stream):
    """A Stream implementation for sending data to HTTP subscription endpoints.
    
    This class provides a way to send messages to an HTTP endpoint specified in the
    message_subscription_config.
    """

    def __init__(self, config: MessageSubscriptionConfig):
        """Initialize the HTTP subscription stream.
        
        Args:
            config: The subscription configuration containing method, endpoint, and headers.
        """
        super().__init__()
        self._config = config
        self._session = None
        self._max_retries = 3
        self._base_retry_delay = 1.0  # Base delay (seconds)
        self._max_retry_delay = 10.0  # Max delay (seconds)

        self.ignore_events([EventType.AFTER_CLIENT_CHAT])
        logger.info("Configured HTTPSubscriptionStream to ignore AFTER_CLIENT_CHAT events")

    async def _ensure_session(self):
        """Ensure an HTTP session exists."""
        if self._session is None:
            # Create session with tracing functionality
            trace_config = aiohttp.TraceConfig()

            async def on_request_start(session, trace_config_ctx, params):
                logger.debug(f"Starting request: {params.url}")

            async def on_request_end(session, trace_config_ctx, params):
                logger.debug(f"Request completed, status: {params.response.status}")

            trace_config.on_request_start.append(on_request_start)
            trace_config.on_request_end.append(on_request_end)

            self._session = aiohttp.ClientSession(trace_configs=[trace_config])

    async def read(self, size: Optional[int] = None) -> str:
        """Read is not supported for HTTPSubscriptionStream.
        
        Args:
            size: Ignored.
            
        Raises:
            NotImplementedError: Always raised as read is not supported.
        """
        raise NotImplementedError("HTTPSubscriptionStream does not support read operations")

    async def _should_retry(self, response) -> bool:
        """
        Determine if retry is needed based on response status code and body content
        
        Args:
            response: HTTP response object
            
        Returns:
            bool: Whether to retry the request
        """
        retry_decision = False  # Don't retry by default

        # Log request information
        logger.debug(f"Checking if retry needed: URL={response.url}, status code={response.status}")

        if response.status != 200:
            retry_decision = True
            logger.warning(f"Server error status code: {response.status}, will retry: {retry_decision}")
            return retry_decision

        # Check response body content
        try:
            content = await response.text()

            try:
                response_json = json.loads(content)

                if 'code' in response_json:
                    code = response_json.get('code')
                    if code != 1000:
                        retry_decision = True
                        logger.warning(f"Response code not equal to 1000 (code={code}), will retry: {retry_decision}")
                else:
                    retry_decision = True
                    logger.warning(f"Response missing code field, will retry: {retry_decision}")

            except json.JSONDecodeError:
                retry_decision = True
                logger.warning(f"Response is not valid JSON, will retry: {retry_decision}")
        except Exception as e:
            retry_decision = True
            logger.warning(f"Error checking response content: {e!s}, will retry: {retry_decision}")

        # Final decision log
        logger.debug(f"should_retry final return: {retry_decision}")
        return retry_decision

    async def _send_request_with_retry(self, method: str, url: str, headers: Dict, data: str) -> Tuple[bool, Optional[aiohttp.ClientResponse]]:
        """
        Send HTTP request with custom retry logic
        
        Returns:
            Tuple (success, response)
        """
        retry_count = 0
        last_exception = None
        last_response = None

        while retry_count <= self._max_retries:
            current_attempt = retry_count + 1
            try:
                # Send the request
                logger.debug(f"Attempt {current_attempt} in progress...")
                async with self._session.request(
                    method=method,
                    url=url,
                    headers=headers,
                    data=data
                ) as response:
                    # Keep a copy of the response to evaluate retry
                    response_copy = response

                    # Decide whether to retry
                    if await self._should_retry(response):
                        if retry_count < self._max_retries:
                            retry_count += 1
                            delay = min(self._base_retry_delay * (2 ** (retry_count - 1)), self._max_retry_delay)
                            logger.info(f"Attempt {current_attempt} failed; retrying in {delay:.2f} seconds...")
                            await asyncio.sleep(delay)
                            continue
                        else:
                            # Max retries reached
                            logger.warning(f"Max retries reached ({self._max_retries}); will not retry")
                            return True, response
                    else:
                        # No retry needed, return success
                        return True, response
            except Exception as e:
                last_exception = e
                logger.error(f"Request raised an exception (attempt {current_attempt}/{self._max_retries + 1}): {e!s}")

                if retry_count < self._max_retries:
                    retry_count += 1
                    delay = min(self._base_retry_delay * (2 ** (retry_count - 1)), self._max_retry_delay)
                    logger.info(f"Retrying in {delay:.2f} seconds...")
                    await asyncio.sleep(delay)
                else:
                    # Max retries reached
                    logger.warning(f"Max retries reached ({self._max_retries}); will not retry")
                    return False, None

        # If we reach here, all retries failed
        return False, last_response

    async def write(self, data: str, data_type: str = "json") -> int:
        """Write string data to the HTTP subscription endpoint.
        
        Args:
            data: The string data to be sent.
            data_type: The type of data being sent, defaults to "json".
            
        Returns:
            The number of bytes written.
            
        Raises:
            IOError: When there's an error writing to the HTTP endpoint.
        """
        try:
            await self._ensure_session()

            # Prepare headers and payload
            headers = dict(self._config.headers)

            if data_type == "json":
                # Ensure Content-Type is application/json
                if "Content-Type" not in headers:
                    headers["Content-Type"] = "application/json"

            # Send request with custom retry logic
            success, response = await self._send_request_with_retry(
                method=self._config.method,
                url=self._config.url,
                headers=headers,
                data=data
            )

            if not success:
                raise IOError("Failed to write to HTTP endpoint after retries")

            # 检查最终响应状态
            if response.status >= 400:
                error_text = await response.text()
                logger.error(f"HTTP subscription failed with status {response.status}: {error_text}")
                raise IOError(f"Failed to write to HTTP endpoint: Status {response.status}")

            return len(data)

        except Exception as e:
            logger.error(f"Failed to write to HTTP endpoint: {e!s}")
            raise IOError(f"Failed to write to HTTP endpoint: {e!s}")
