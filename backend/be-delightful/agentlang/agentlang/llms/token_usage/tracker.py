"""Token usage tracking module

Provides tracking for LLM request token usage.
"""

import copy
import threading

# Lazy import TokenUsageReport to avoid circular references
from typing import TYPE_CHECKING, Any, Dict, Optional, Protocol

from agentlang.llms.token_usage.models import InputTokensDetails, TokenUsage
from agentlang.logger import get_logger

if TYPE_CHECKING:
    from agentlang.llms.token_usage.report import TokenUsageReport


logger = get_logger(__name__)


class LlmUsageResponse(Protocol):
    """Protocol type for LLM usage response."""
    recorded: bool
    model_id: str
    model_name: str
    input_tokens: int
    output_tokens: int
    total_tokens: int
    cache_write_tokens: Optional[int]
    cache_hit_tokens: Optional[int]


class TokenUsageTracker:
    """Token usage tracker.

    Tracks token usage for LLM requests with thread-safe accumulation.
    Extracts token usage information from LLM responses.
    Uses standardized TokenUsage objects for uniform data format.
    """

    def __init__(self) -> None:
        """Initialize the token usage tracker."""
        # Dictionary to track TokenUsage per model
        self._usage: Dict[str, TokenUsage] = {}
        # Lock for thread safety
        self._lock = threading.Lock()
        # Report manager
        self._report_manager: Optional['TokenUsageReport'] = None

    def add_usage(self, model_id: str, token_usage: TokenUsage) -> None:
        """Add a token usage record.

        Args:
            model_id: Model ID
            token_usage: TokenUsage object
        """
        with self._lock:
            if model_id not in self._usage:
                # Create initial TokenUsage object
                self._usage[model_id] = TokenUsage(
                    input_tokens=0,
                    output_tokens=0,
                    total_tokens=0,
                    input_tokens_details=InputTokensDetails()
                )

            # Accumulate basic usage
            self._usage[model_id].input_tokens += token_usage.input_tokens
            self._usage[model_id].output_tokens += token_usage.output_tokens

            # Update total
            self._usage[model_id].total_tokens = (
                self._usage[model_id].input_tokens + self._usage[model_id].output_tokens
            )

            # Accumulate cache usage if present
            if token_usage.input_tokens_details:
                if not self._usage[model_id].input_tokens_details:
                    self._usage[model_id].input_tokens_details = InputTokensDetails()

                # Add cache write tokens
                if token_usage.input_tokens_details.cache_write_tokens:
                    self._usage[model_id].input_tokens_details.cache_write_tokens = (
                        (self._usage[model_id].input_tokens_details.cache_write_tokens or 0) +
                        (token_usage.input_tokens_details.cache_write_tokens or 0)
                    )

                # Add cache hit tokens
                if token_usage.input_tokens_details.cached_tokens:
                    self._usage[model_id].input_tokens_details.cached_tokens = (
                        (self._usage[model_id].input_tokens_details.cached_tokens or 0) +
                        (token_usage.input_tokens_details.cached_tokens or 0)
                    )

    def record_llm_usage(self, response_usage: Any, model_id: str,
                         user_id: Optional[str] = None,
                         model_name: Optional[str] = None) -> LlmUsageResponse:
        """Record LLM usage and generate a report.

        Args:
            response_usage: usage attribute from LLM response object
            model_id: Model ID
            user_id: User ID (optional)
            model_name: Model name (optional)

        Returns:
            LlmUsageResponse: Recording result
        """
        # Log raw usage data for debugging
        try:
            if response_usage:
                if hasattr(response_usage, '__dict__'):
                    logger.debug(f"Recording LLM usage - raw usage data (dict): {response_usage.__dict__}")
                else:
                    logger.debug(f"Recording LLM usage - raw usage data (str): {str(response_usage)[:500]}")
        except Exception as e:
            logger.warning(f"Error logging raw usage data: {e}")

        # Extract token usage from response
        token_usage = TokenUsage.from_response(response_usage)

        # Log parsed token_usage object
        logger.debug(f"Recording LLM usage - parsed token_usage: {token_usage.to_dict()}, model_id={model_id}")

        # Add usage record to tracker
        self.add_usage(model_id, token_usage)

        # Generate report if report manager is set
        if self._report_manager:
            self._report_manager.update_and_save_usage(model_id, token_usage)

        # Return result implementing LlmUsageResponse protocol
        class UsageResult:
            def __init__(self, usage: TokenUsage, mid: str, name: str):
                self.recorded = True
                self.model_id = mid
                self.model_name = name or mid
                self.input_tokens = usage.input_tokens
                self.output_tokens = usage.output_tokens
                self.total_tokens = usage.total_tokens

                # Get cache info from input_tokens_details
                self.cache_write_tokens = None
                self.cache_hit_tokens = None

                if usage.input_tokens_details:
                    if usage.input_tokens_details.cache_write_tokens and usage.input_tokens_details.cache_write_tokens > 0:
                        self.cache_write_tokens = usage.input_tokens_details.cache_write_tokens

                    if usage.input_tokens_details.cached_tokens and usage.input_tokens_details.cached_tokens > 0:
                        self.cache_hit_tokens = usage.input_tokens_details.cached_tokens

        return UsageResult(token_usage, model_id, model_name)

    def extract_chat_history_usage_data(self, chat_response: Any) -> TokenUsage:
        """Extract processed token usage data from LLM response for chat_history.

        Convenience method for agent.py to extract token usage data from LLM response
        without recording it in usage statistics.

        Args:
            chat_response: Complete LLM response object

        Returns:
            TokenUsage: Standardized token usage object, or empty object if extraction fails
        """
        # Check if response object is valid
        if not chat_response:
            logger.warning("extract_chat_history_usage_data: chat_response is empty")
            return TokenUsage(input_tokens=0, output_tokens=0, total_tokens=0)

        try:
            # Log raw chat_response object type and structure
            logger.debug(f"Extracting token usage - chat_response type: {type(chat_response)}")

            # Extract usage attribute using attribute access
            usage = getattr(chat_response, "usage", None)

            if not usage:
                logger.warning(f"Extracting token usage - usage attribute not found: {str(chat_response)[:200]}...")
                return TokenUsage(input_tokens=0, output_tokens=0, total_tokens=0)

            # Log raw usage data structure
            try:
                if hasattr(usage, '__dict__'):
                    logger.debug(f"Extracting token usage - raw usage data: {usage.__dict__}")
                else:
                    logger.debug(f"Extracting token usage - raw usage data: {str(usage)[:500]}")
            except Exception as e:
                logger.warning(f"Error logging usage data: {e}")

            # Use TokenUsage.from_response to extract standardized data directly from API response
            # This ensures proper handling of any possible response format (e.g., OpenAI old/new formats)
            token_usage = TokenUsage.from_response(usage)

            # Log parsed token_usage object
            logger.debug(f"Extracting token usage - parsed token_usage: {token_usage.to_dict()}")

            return token_usage
        except Exception as e:
            logger.error(f"Failed to extract chat_history token usage data: {e}", exc_info=True)
            return TokenUsage(input_tokens=0, output_tokens=0, total_tokens=0)

    def set_report_manager(self, report_manager: 'TokenUsageReport') -> None:
        """Set the report manager.

        Args:
            report_manager: TokenUsageReport instance
        """
        self._report_manager = report_manager

    def get_usage_data(self) -> Dict[str, TokenUsage]:
        """Get all usage data.

        Returns:
            Dict[str, TokenUsage]: Usage data for all models
        """
        with self._lock:
            # Return deep copy to prevent external modifications
            return copy.deepcopy(self._usage)

    def reset(self) -> None:
        """Reset all usage statistics."""
        with self._lock:
            self._usage.clear()
            logger.info("TokenUsageTracker has been reset")

    def get_formatted_report(self) -> str:
        """Get a formatted report (one-step).

        Returns:
            str: Formatted token usage report
        """
        if not self._report_manager:
            # Without report manager, output simple format with current accumulated data
            lines = ["Token usage statistics (temporary report):"]

            with self._lock:
                if not self._usage:
                    return "No token usage records"

                for model_name, usage in self._usage.items():
                    lines.append(f"\nModel: {model_name}")
                    lines.append(f"  Input tokens: {usage.input_tokens:,}")
                    lines.append(f"  Output tokens: {usage.output_tokens:,}")

                    # Use input_tokens_details instead of direct cache access
                    if usage.input_tokens_details:
                        if usage.input_tokens_details.cache_write_tokens and usage.input_tokens_details.cache_write_tokens > 0:
                            lines.append(f"  Cache write tokens: {usage.input_tokens_details.cache_write_tokens:,}")
                        if usage.input_tokens_details.cached_tokens and usage.input_tokens_details.cached_tokens > 0:
                            lines.append(f"  Cache hit tokens: {usage.input_tokens_details.cached_tokens:,}")

                    lines.append(f"  Total tokens: {usage.total_tokens:,}")

            return "\n".join(lines)
        else:
            # Use report manager to generate full report
            cost_report = self._report_manager.get_cost_report()
            return self._report_manager.format_report(cost_report)
