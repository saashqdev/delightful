"""
Token usage report module

Responsible for generating reports from data in TokenUsageTracker
"""

import json
import os
from datetime import datetime

# To avoid circular imports, use string type annotations
from typing import TYPE_CHECKING, Any, Dict, Optional

from agentlang.config import config
from agentlang.context.application_context import ApplicationContext
from agentlang.llms.token_usage.models import (
    CostReport,
    InputTokensDetails,
    ModelUsage,
    TokenUsage,
    get_currency_symbol,
)
from agentlang.llms.token_usage.pricing import ModelPricing
from agentlang.logger import get_logger

if TYPE_CHECKING:
    from agentlang.llms.token_usage.tracker import TokenUsageTracker

logger = get_logger(__name__)


class TokenUsageReport:
    """Token usage statistics report generator

    Responsible for generating various format reports from data in TokenUsageTracker
    Supports aggregating token usage by sandbox_id dimension
    Uses standardized data objects for report processing
    """

    # Save global report instances, indexed by sandbox_id
    _instances = {}

    @classmethod
    def get_instance(cls, sandbox_id: str = "default", token_tracker: Optional[Any] = None,
                     pricing: Optional[ModelPricing] = None,
                    report_dir: str = None) -> 'TokenUsageReport':
        """Get or create TokenUsageReport instance for specified sandbox_id

        Args:
            sandbox_id: Sandbox ID
            token_tracker: Token usage tracker
            pricing: Model pricing configuration
            report_dir: Report file save directory, None by default to use default directory

        Returns:
            TokenUsageReport: Instance corresponding to sandbox_id
        """
        if sandbox_id not in cls._instances:
            # Create default instance when pricing not provided
            if pricing is None:
                try:
                    # Try loading model pricing from configuration
                    models_config = config.get("models", {})
                    pricing = ModelPricing(models_config=models_config)
                    logger.info("Loaded model pricing information from configuration")
                except Exception as e:
                    # Use default pricing when configuration loading fails
                    logger.warning(f"Unable to load model pricing from configuration, using default pricing: {e}")
                    pricing = ModelPricing()

            # Create instance
            cls._instances[sandbox_id] = cls(token_tracker, pricing, sandbox_id, report_dir)

            # Set token_tracker's report_manager (if provided)
            if token_tracker:
                token_tracker.set_report_manager(cls._instances[sandbox_id])

        return cls._instances[sandbox_id]

    def __init__(self, token_tracker: 'TokenUsageTracker', pricing: ModelPricing,
                sandbox_id: str = "default", report_dir: Optional[str] = None):
        """Initialize a token usage report generator.

        Args:
            token_tracker: Token usage tracker
            pricing: Model pricing configuration
            sandbox_id: Sandbox ID to differentiate environments
            report_dir: Directory to save reports; default None uses the standard path
        """
        self.token_tracker = token_tracker
        self.pricing = pricing
        self.sandbox_id = sandbox_id
        self.report_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        if report_dir is None:
            try:
                path_manager = ApplicationContext.get_path_manager()
                self.report_dir = path_manager.get_chat_history_dir()
            except (ImportError, RuntimeError):
                # Fall back when PathManager lookup fails
                logger.warning("Failed to import PathManager or get chat history dir; using fallback directory")
                self.report_dir = os.path.join(os.getcwd(), ".chat_history")
        else:
            self.report_dir = report_dir

        # Ensure the report directory exists
        os.makedirs(self.report_dir, exist_ok=True)

    def get_report_file_path(self) -> str:
        """Get the report file path."""
        # Use sandbox_id to create unique filename
        file_name = f"{self.sandbox_id}_token_usage.json"
        return os.path.join(self.report_dir, file_name)

    def _serialize_report(self, report: CostReport) -> Dict[str, Any]:
        """Serialize CostReport into a JSON-friendly dict."""
        models_data = []
        for model in report.models:
            model_data = {
                "model_name": model.model_name,
                "input_tokens": model.usage.input_tokens,
                "output_tokens": model.usage.output_tokens,
                "total_tokens": model.usage.total_tokens,
                "cost": model.cost,
                "currency": model.currency
            }

            # Add cache-related data when present
            if model.usage.input_tokens_details:
                if model.usage.input_tokens_details.cache_write_tokens:
                    model_data["cache_write_tokens"] = model.usage.input_tokens_details.cache_write_tokens
                if model.usage.input_tokens_details.cached_tokens:
                    model_data["cache_hit_tokens"] = model.usage.input_tokens_details.cached_tokens

            models_data.append(model_data)

        return {
            "timestamp": report.timestamp,
            "models": models_data,
            "currency_code": report.currency_code
        }

    def _deserialize_report(self, data: Dict[str, Any]) -> CostReport:
        """Deserialize CostReport from a dictionary."""
        report = CostReport()
        report.timestamp = data.get("timestamp", report.timestamp)
        report.currency_code = data.get("currency_code", report.currency_code)

        # Parse model data
        for model_data in data.get("models", []):
            model_name = model_data.get("model_name", "")

            # Build input token details
            input_details = InputTokensDetails(
                cache_write_tokens=model_data.get("cache_write_tokens", 0),
                cached_tokens=model_data.get("cache_hit_tokens", 0)
            )

            # Build token usage object
            usage = TokenUsage(
                input_tokens=model_data.get("input_tokens", 0),
                output_tokens=model_data.get("output_tokens", 0),
                total_tokens=model_data.get("input_tokens", 0) + model_data.get("output_tokens", 0),
                input_tokens_details=input_details
            )

            # Create model usage entry
            model_usage = ModelUsage(
                model_name=model_name,
                usage=usage,
                cost=model_data.get("cost", 0.0),
                currency=model_data.get("currency", report.currency_code)
            )

            report.models.append(model_usage)

        return report

    def _get_or_create_report(self) -> CostReport:
        """Load existing report or create a new one."""
        file_path = self.get_report_file_path()

        # If file exists, try to load
        if os.path.exists(file_path):
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    report_data = json.load(f)
                    return self._deserialize_report(report_data)
            except Exception as e:
                logger.error(f"Failed to read existing token usage report: {e!s}")

        # File missing or load failed: create new report
        return CostReport(currency_code=self.pricing.display_currency)

    def _save_report(self, report: CostReport) -> bool:
        """Save report to file."""
        file_path = self.get_report_file_path()

        try:
            # Convert to JSON-ready dict
            report_dict = self._serialize_report(report)

            # Write to disk
            with open(file_path, 'w', encoding='utf-8') as f:
                json.dump(report_dict, f, ensure_ascii=False, indent=2)

            logger.info(f"Saved token usage report to file: {file_path}")
            return True
        except Exception as e:
            logger.error(f"Failed to save token usage report to file: {e!s}")
            return False

    def update_and_save_usage(self, model_id: str, token_usage: TokenUsage) -> None:
        """Update and persist current token usage to JSON file."""
        # Ensure tracker is present
        if not self.token_tracker:
            logger.error("Cannot update token usage because token_tracker is not set")
            return

        # Load or create report
        report = self._get_or_create_report()

        # Calculate cost
        cost, currency = self.pricing.calculate_cost(model_id, token_usage)

        # Convert currency if needed
        if currency != report.currency_code:
            cost = self.pricing.convert_currency(cost, currency, report.currency_code)

        # Find existing model entry or create a new one
        existing_model = next((m for m in report.models if m.model_name == model_id), None)

        if existing_model:
            # Update existing model
            existing_model.usage.input_tokens += token_usage.input_tokens
            existing_model.usage.output_tokens += token_usage.output_tokens

            # Update cache-related data
            if token_usage.input_tokens_details:
                if not existing_model.usage.input_tokens_details:
                    existing_model.usage.input_tokens_details = InputTokensDetails()

                if token_usage.input_tokens_details.cache_write_tokens:
                    existing_model.usage.input_tokens_details.cache_write_tokens = (
                        (existing_model.usage.input_tokens_details.cache_write_tokens or 0) +
                        token_usage.input_tokens_details.cache_write_tokens
                    )

                if token_usage.input_tokens_details.cached_tokens:
                    existing_model.usage.input_tokens_details.cached_tokens = (
                        (existing_model.usage.input_tokens_details.cached_tokens or 0) +
                        token_usage.input_tokens_details.cached_tokens
                    )

            # Recompute totals
            existing_model.usage.total_tokens = existing_model.usage.input_tokens + existing_model.usage.output_tokens

            # Update cost
            existing_model.cost += cost
        else:
            # Create a new model usage entry
            model_usage = ModelUsage(
                model_name=model_id,
                usage=token_usage,
                cost=cost,
                currency=report.currency_code
            )
            report.models.append(model_usage)

        # Save report
        self._save_report(report)

        # Reset tracker accumulation to avoid double counting
        self.token_tracker.reset()

    def format_report(self, report: CostReport) -> str:
        """Format a report into a readable string."""
        currency_symbol = get_currency_symbol(report.currency_code)

        formatted = "Token usage report\n"
        formatted += "-" * 40 + "\n"

        # Add usage per model
        for model in report.models:
            formatted += f"Model: {model.model_name}\n"
            formatted += f"  Input tokens: {model.usage.input_tokens:,}\n"
            formatted += f"  Output tokens: {model.usage.output_tokens:,}\n"

            # Cache-related info
            if model.usage.input_tokens_details:
                if model.usage.input_tokens_details.cache_write_tokens:
                    formatted += f"  Cache write tokens: {model.usage.input_tokens_details.cache_write_tokens:,}\n"
                if model.usage.input_tokens_details.cached_tokens:
                    formatted += f"  Cache hit tokens: {model.usage.input_tokens_details.cached_tokens:,}\n"

            formatted += f"  Total tokens: {model.usage.total_tokens:,}\n"
            formatted += f"  Estimated cost: {currency_symbol}{model.cost:.6f}\n\n"

        # Totals
        formatted += "Totals:\n"
        formatted += f"  Total input tokens: {report.total_input_tokens:,}\n"
        formatted += f"  Total output tokens: {report.total_output_tokens:,}\n"

        # Aggregate cache-related totals
        total_cache_write_tokens = sum(
            (m.usage.input_tokens_details.cache_write_tokens or 0)
            for m in report.models
            if m.usage.input_tokens_details and m.usage.input_tokens_details.cache_write_tokens
        )

        total_cache_hit_tokens = sum(
            (m.usage.input_tokens_details.cached_tokens or 0)
            for m in report.models
            if m.usage.input_tokens_details and m.usage.input_tokens_details.cached_tokens
        )

        if total_cache_write_tokens > 0:
            formatted += f"  Total cache write tokens: {total_cache_write_tokens:,}\n"
        if total_cache_hit_tokens > 0:
            formatted += f"  Total cache hit tokens: {total_cache_hit_tokens:,}\n"

        formatted += f"  Overall tokens: {report.total_tokens:,}\n"
        formatted += f"  Total estimated cost: {currency_symbol}{report.total_cost:.6f}\n"

        formatted += f"\nReport time: {report.timestamp}"

        return formatted

    def get_cost_report(self) -> CostReport:
        """Get token usage and cost report."""
        file_path = self.get_report_file_path()

        # Try reading existing file
        if os.path.exists(file_path):
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    report_data = json.load(f)

                # Convert to CostReport
                return self._deserialize_report(report_data)

            except Exception as e:
                logger.error(f"Failed to read token usage report: {e}")

        # Missing or failed read: return new report
        return CostReport(currency_code=self.pricing.display_currency)
