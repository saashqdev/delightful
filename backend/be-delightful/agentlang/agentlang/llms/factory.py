"""
LLM Factory module for creating different LLM clients.

This module provides a factory pattern for creating different LLM clients
based on the model ID provided.
"""

import os
from typing import Any, Dict, List, Optional

from openai import AsyncOpenAI
from openai.types.chat import ChatCompletion
from pydantic import BaseModel

from agentlang.config.config import config
from agentlang.interface.context import AgentContextInterface
from agentlang.llms.token_usage.pricing import ModelPricing
from agentlang.llms.token_usage.report import TokenUsageReport
from agentlang.llms.token_usage.tracker import TokenUsageTracker
from agentlang.logger import get_logger

logger = get_logger(__name__)

DEFAULT_TIMEOUT = int(config.get("llm.api_timeout", 600))
MAX_RETRIES = int(config.get("llm.api_max_retries", 3))


class LLMClientConfig(BaseModel):
    """Configuration for LLM clients."""

    model_id: str
    api_key: str
    api_base_url: Optional[str] = None
    name: str
    provider: str
    temperature: float = 0.7
    max_output_tokens: int = 4 * 1024
    max_context_tokens: int = 8 * 1024
    top_p: float = 1.0
    stop: Optional[List[str]] = None
    extra_params: Dict[str, Any] = {}
    supports_tool_use: bool = True
    type: str = "llm"

class LLMFactory:
    """Factory for creating LLM clients."""

    _clients = {}
    _configs = {}

    # Initialize token usage tracker and related services
    token_tracker = TokenUsageTracker()

    # Load pricing configuration from config
    models_config = config.get("models", {})
    pricing = ModelPricing(models_config=models_config)
    sandbox_id = os.environ.get("SANDBOX_ID", "default")

    # Initialize TokenUsageReport
    _ = TokenUsageReport.get_instance(
        sandbox_id=sandbox_id,
        token_tracker=token_tracker,
        pricing=pricing
    )

    @classmethod
    def register_config(cls, llm_config: LLMClientConfig) -> None:
        """Register a configuration for a model ID.

        Args:
            config: The configuration to register.
        """
        cls._configs[llm_config.model_id] = llm_config

    @classmethod
    def get(cls, model_id: str) -> Any:
        """Get a client for the given model ID.

        Args:
            model_id: The model ID to get a client for.

        Returns:
            The client for the given model ID.

        Raises:
            ValueError: If the model ID is not supported.
        """
        if model_id in cls._clients:
            return cls._clients[model_id]

        if model_id not in cls._configs:
            # Read model configuration from config file
            model_config = config.get("models", {}).get(model_id)
            if not model_config:
                raise ValueError(f"Unsupported model ID: {model_id}")
            # Filter configurations where type is not llm
            if model_config.get("type") != "llm":
                raise ValueError(f"Model {model_id} is not a LLM model")

            llm_config = LLMClientConfig(
                model_id=model_id,
                api_key=model_config["api_key"],
                api_base_url=model_config["api_base_url"],
                name=str(model_config["name"]),
                provider=model_config["provider"],
                supports_tool_use=model_config.get("supports_tool_use", False),
                max_output_tokens=model_config.get("max_output_tokens", 4 * 1024),
                max_context_tokens=model_config.get("max_context_tokens", 8 * 1024),
                temperature=model_config.get("temperature", 0.7),
                top_p=model_config.get("top_p", 1.0),
            )
            cls._configs[model_id] = llm_config

        llm_config = cls._configs[model_id]
        available_providers = ["openai"]
        if llm_config.provider not in available_providers:
            raise ValueError(f"Unsupported provider: {llm_config.provider}")

        if llm_config.provider == "openai":
            client = cls._create_openai_client(llm_config)
            cls._clients[model_id] = client
            return client

    @classmethod
    async def call_with_tool_support(
        cls,
        model_id: str,
        messages: List[Dict[str, Any]],
        tools: Optional[List[Dict[str, Any]]] = None,
        stop: Optional[List[str]] = None,
        agent_context: Optional[AgentContextInterface] = None
    ) -> ChatCompletion:
        """Call LLM with tool support.

        Uses tool calling based on model configuration.
        For models that support tool calling, directly use OpenAI API's tool calling feature.

        Args:
            model_id: The model ID to use.
            messages: Chat message history.
            tools: List of available tools, optional.
            stop: List of stop sequences, optional.
            agent_context: Agent context interface, optional.

        Returns:
            LLM response.

        Raises:
            ValueError: If model ID is not supported.
        """
        # Note: No longer check cost limit here, handled through event mechanism

        client = cls.get(model_id)
        if not client:
            raise ValueError(f"Unable to get client for model ID {model_id}")

        # Get model configuration
        llm_config = cls._configs.get(model_id)
        if not llm_config:
            raise ValueError(f"Configuration not found for model ID {model_id}")

        # Use native tool calling
        # Build request parameters
        request_params = {
            "model": llm_config.name,
            "messages": messages,
            "temperature": llm_config.temperature,
            #"max_output_tokens": llm_config.max_output_tokens,  # Remove this param for now, not sure how to calculate
            "top_p": llm_config.top_p,
        }

        # --- Start: Add AWS AutoCache configuration ---
        aws_autocache_config = config.get("llm.aws_autocache", {})
        aws_autocache_enabled_str = str(aws_autocache_config.get("enabled", "true")).lower()
        aws_autocache_enabled = aws_autocache_enabled_str == "true"

        if aws_autocache_enabled:
            try:
                max_cache_points = int(aws_autocache_config.get("max_cache_points", 4))
                max_cache_points = max(min(max_cache_points, 4), 1) # Constrain to [1, 4]

                min_cache_tokens = int(aws_autocache_config.get("min_cache_tokens", 2048))
                min_cache_tokens = max(min_cache_tokens, 2048) # Minimum 2048

                refresh_point_min_tokens = int(aws_autocache_config.get("refresh_point_min_tokens", 5000))
                refresh_point_min_tokens = max(refresh_point_min_tokens, 2048) # Minimum 2048

                autocache_params = {
                    'auto_cache': True, # Enter this branch because enabled=True
                    'auto_cache_config': {
                        'max_cache_points': max_cache_points,
                        'min_cache_tokens': min_cache_tokens,
                        'refresh_point_min_tokens': refresh_point_min_tokens,
                    }
                }
            except ValueError as e:
                logger.warning(f"Error parsing AWS AutoCache configuration: {e}. Will not apply AutoCache configuration.", exc_info=True)
        # --- End: Add AWS AutoCache configuration ---

        # Add stop sequences (if provided)
        if stop:
            request_params["stop"] = stop

        # Add tools (if provided and model supports tool use)
        if tools and llm_config.supports_tool_use:
            request_params["tools"] = tools
            # Add tool_choice parameter, set to "auto" to allow LLM to return multiple tool calls
            request_params["tool_choice"] = "auto"

        # Add extra parameters
        for key, value in llm_config.extra_params.items():
            request_params[key] = value

        # Send request and get response
        # logger.debug(f"Sending chat completion request to {llm_config.name}: {request_params}")
        try:
            response = await client.chat.completions.create(**request_params)

            # Use TokenUsageTracker to record token usage
            cls.token_tracker.record_llm_usage(
                response.usage,
                model_id,
                user_id=agent_context.get_user_id() if agent_context else None,
                model_name=llm_config.name
            )

            return response
        except Exception as e:
            logger.critical(f"Error calling LLM {model_id}: {e!r}", exc_info=True)
            raise

    @classmethod
    def get_embedding_client(cls, model_id: str) -> Any:
        """Get an embedding client for the given model ID.

        Args:
            model_id: The model ID to get an embedding client for.

        Returns:
            The embedding client for the given model ID.
        """
        if model_id in cls._clients:
            return cls._clients[model_id]

        if model_id not in cls._configs:
            # Read model configuration from config file
            model_config = config.get("models", {}).get(model_id)
            if not model_config:
                raise ValueError(f"Unsupported model ID: {model_id}")
            # Filter configurations where type is not embedding
            if model_config.get("type") != "embedding":
                raise ValueError(f"Model {model_id} is not an Embedding model")

            llm_config = LLMClientConfig(
                model_id=model_id,
                api_key=model_config["api_key"],
                api_base_url=model_config["api_base_url"],
                name=str(model_config["name"]),
                provider=model_config["provider"],
                type=model_config["type"],
                max_output_tokens=model_config.get("max_output_tokens", 4 * 1024),
                max_context_tokens=model_config.get("max_context_tokens", 8 * 1024),
                temperature=model_config.get("temperature", 0.7),
                top_p=model_config.get("top_p", 1.0),
            )
            logger.info(f"Creating embedding client - llm_config: {llm_config}")
            cls._configs[model_id] = llm_config

        llm_config = cls._configs[model_id]
        available_providers = ["openai"]
        if llm_config.provider not in available_providers:
            raise ValueError(f"Unsupported provider: {llm_config.provider}")

        if llm_config.provider == "openai":
            client = cls._create_openai_client(llm_config)
            cls._clients[model_id] = client
            return client

    @classmethod
    def get_model_config(cls, model_id: str) -> Dict[str, Any]:
        """Get the model config for the given model ID.

        Args:
            model_id: The model ID to get the config for.

        Returns:
            The model config for the given model ID.
        """
        return cls._configs[model_id]

    @classmethod
    def is_supports_tool_use(cls, model_id: str) -> bool:
        """Check if the model supports tool use.

        Args:
            model_id: The model ID to check.

        Returns:
            True if the model supports tool use, False otherwise.
        """
        return cls._configs[model_id].supports_tool_use

    @classmethod
    def _create_openai_client(cls, llm_config: LLMClientConfig) -> Any:
        """Create an OpenAI client.

        Args:
            model_id: The model ID to create a client for.

        Returns:
            An AsyncOpenAI client.
        """
        # Get custom request header configuration
        default_headers = {}

        # Add Delightful-Authorization authentication header
        delightful_authorization = config.get("sandbox.delightful_authorization")
        if delightful_authorization:
            default_headers["Delightful-Authorization"] = delightful_authorization

        # Add custom request headers defined in config file (read from environment variables)
        try:
            custom_headers = config.get("llm.custom_api_headers", {})
            if custom_headers and isinstance(custom_headers, dict):
                # Merge custom headers into default headers
                default_headers.update(custom_headers)
            else:
                logger.debug("No valid custom API request header configuration found or format is incorrect")
        except Exception as e:
            logger.warning(f"Error processing custom API request header configuration: {e}", exc_info=True)

        return AsyncOpenAI(
            api_key=llm_config.api_key,
            base_url=llm_config.api_base_url,
            timeout=DEFAULT_TIMEOUT,
            max_retries=MAX_RETRIES,
            default_headers=default_headers
        )

    @classmethod
    def get_embedding_dimension(cls, model_id: str) -> int:
        """Get the embedding dimension for the given model ID.

        Args:
            model_id: The model ID to get the embedding dimension for.

        Returns:
            The embedding dimension for the given model ID.
        """
        if model_id not in cls._configs:
            # Read model configuration from config file
            model_config = config.get("models", {}).get(model_id)
            if not model_config:
                raise ValueError(f"Unsupported model ID: {model_id}")

            llm_config = LLMClientConfig(
                model_id=model_id,
                api_key=model_config["api_key"],
                api_base_url=model_config["api_base_url"],
                name=str(model_config["name"]),
                provider=model_config["provider"],
                type=model_config["type"],
            )
            cls._configs[model_id] = llm_config

        llm_config = cls._configs[model_id]
        model_name = llm_config.name.lower()

        # Return corresponding vector dimension based on model name
        if "text-embedding-3-large" in model_name:
            return 3072
        elif "text-embedding-3-small" in model_name:
            return 1536
        elif "text-embedding-ada-002" in model_name:
            return 1536
        else:
            # Default dimension is 1536
            return 1536
