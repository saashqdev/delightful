"""
Model token pricing configuration module
"""

from enum import Enum
from typing import Any, Dict, Optional, Tuple, Type, TypedDict, TypeVar, cast

from agentlang.llms.token_usage.models import TokenUsage
from agentlang.logger import get_logger

logger = get_logger(__name__)


class CurrencyType(Enum):
    """Currency type enumeration"""
    USD = "USD"  # US Dollar
    RMB = "CNY"  # Chinese Yuan (Renminbi)


class PricingInfo(TypedDict, total=False):
    """Model pricing information dictionary type"""
    input_price: float
    output_price: float
    cache_write_price: float
    cache_hit_price: float
    currency: str


# Add generic type variable for type conversion
T = TypeVar('T')


def safe_cast(value: Any, to_type: Type[T], default: T) -> T:
    """Safe type conversion

    Args:
        value: Value to convert
        to_type: Target type
        default: Default value when conversion fails

    Returns:
        T: Converted value
    """
    try:
        return to_type(value)
    except (ValueError, TypeError):
        return default


class ModelPricing:
    """LLM model pricing configuration and cost calculation

    Used to configure token pricing for each model and calculate cost based on usage
    """

    # Default exchange rate (USD -> RMB)
    DEFAULT_EXCHANGE_RATE = 7.2

    def __init__(self,
                 models_config: Optional[Dict[str, Dict[str, Any]]] = None,
                 exchange_rate: float = DEFAULT_EXCHANGE_RATE,
                 display_currency: str = CurrencyType.RMB.value):
        """Initialize

        Args:
            models_config: Model configuration dictionary loaded from main config
            exchange_rate: Exchange rate for USD to RMB conversion, defaults to 7.2
            display_currency: Display currency for reports, defaults to Chinese Yuan
        """
        self.pricing: Dict[str, PricingInfo] = {}
        self.exchange_rate = exchange_rate
        self.display_currency = display_currency

        # Load pricing information from models_config if provided
        if models_config:
            self._load_pricing_from_config(models_config)
        else:
            logger.warning("No model configuration provided, will use default pricing")
            # Set default pricing
            self.pricing["default"] = {
                "input_price": 0.001,
                "output_price": 0.002,
                "currency": CurrencyType.USD.value
            }

    def _load_pricing_from_config(self, models_config: Dict[str, Dict[str, Any]]) -> None:
        """Load pricing information from configuration

        Args:
            models_config: Model configuration dictionary
        """
        for model_name, config in models_config.items():
            if "pricing" in config and isinstance(config["pricing"], dict):
                pricing_data = config["pricing"]

                # Create price information object
                price_info: PricingInfo = {}

                # Add basic pricing (required)
                if "input_price" in pricing_data:
                    price_info["input_price"] = safe_cast(pricing_data["input_price"], float, 0.0)

                if "output_price" in pricing_data:
                    price_info["output_price"] = safe_cast(pricing_data["output_price"], float, 0.0)

                # Add optional pricing
                if "cache_write_price" in pricing_data:
                    price_info["cache_write_price"] = safe_cast(pricing_data["cache_write_price"], float, 0.0)

                if "cache_hit_price" in pricing_data:
                    price_info["cache_hit_price"] = safe_cast(pricing_data["cache_hit_price"], float, 0.0)

                # Add currency
                if "currency" in pricing_data:
                    price_info["currency"] = str(pricing_data["currency"])
                else:
                    price_info["currency"] = CurrencyType.USD.value

                # Save pricing information
                self.pricing[model_name] = price_info
            elif model_name != "default":  # Non-default model missing pricing
                logger.warning(f"Model '{model_name}' configuration is missing pricing information")

        # Ensure there is default pricing configuration
        if "default" not in self.pricing:
            # Create default pricing configuration
            self.pricing["default"] = {
                "input_price": 0.001,
                "output_price": 0.002,
                "currency": CurrencyType.USD.value
            }
            logger.info("Created default pricing configuration")

    def add_model_pricing(self, model_name: str, price_info: PricingInfo) -> None:
        """Add or update model pricing configuration

        Args:
            model_name: Model name
            price_info: Pricing information dictionary containing input_price and output_price
        """
        self.pricing[model_name] = price_info

    def get_model_pricing(self, model_name: str) -> PricingInfo:
        """Get model pricing configuration

        Args:
            model_name: Model name

        Returns:
            PricingInfo: Dictionary containing pricing information
        """
        # Try to get exact matching model pricing
        if model_name in self.pricing:
            return self.pricing[model_name]

        # Try prefix matching
        for key in self.pricing:
            if model_name.startswith(key):
                return self.pricing[key]

        # Return default pricing
        logger.info(f"Pricing configuration not found for model '{model_name}', using default pricing")
        return self.pricing["default"]

    def get_currency_symbol(self, currency: Optional[str] = None) -> str:
        """Get currency symbol

        Args:
            currency: Currency code, defaults to display currency

        Returns:
            str: Currency symbol
        """
        if currency is None:
            currency = self.display_currency

        if currency == CurrencyType.USD.value:
            return "$"
        elif currency == CurrencyType.RMB.value:
            return "¥"
        else:
            return currency

    def convert_currency(self, amount: float, from_currency: str, to_currency: str) -> float:
        """Convert currency

        Args:
            amount: Amount
            from_currency: Source currency
            to_currency: Target currency

        Returns:
            float: Converted amount
        """
        if from_currency == to_currency:
            return amount

        if from_currency == CurrencyType.USD.value and to_currency == CurrencyType.RMB.value:
            return amount * self.exchange_rate
        elif from_currency == CurrencyType.RMB.value and to_currency == CurrencyType.USD.value:
            return amount / self.exchange_rate
        else:
            # Unsupported currency conversion
            return amount

    def calculate_cost(self, model_name: str, token_usage: TokenUsage) -> Tuple[float, str]:
        """Calculate token usage cost

        Args:
            model_name: Model name
            token_usage: TokenUsage object

        Returns:
            Tuple[float, str]: (cost, currency type)
        """
        pricing = self.get_model_pricing(model_name)
        input_price = pricing.get("input_price", 0.0)
        output_price = pricing.get("output_price", 0.0)
        currency = pricing.get("currency", CurrencyType.USD.value)

        # Use TokenUsage object
        input_tokens = token_usage.input_tokens
        output_tokens = token_usage.output_tokens

        # Get cache information from input_tokens_details
        cache_write_tokens = 0
        cache_hit_tokens = 0

        if token_usage.input_tokens_details:
            cache_write_tokens = token_usage.input_tokens_details.cache_write_tokens or 0
            cache_hit_tokens = token_usage.input_tokens_details.cached_tokens or 0

        # Basic cost calculation (per thousand tokens)
        cost = (input_tokens * input_price + output_tokens * output_price) / 1000

        # Calculate cache costs
        if cache_write_tokens > 0 and "cache_write_price" in pricing:
            cost += (cache_write_tokens * cast(float, pricing["cache_write_price"])) / 1000

        if cache_hit_tokens > 0 and "cache_hit_price" in pricing:
            cost += (cache_hit_tokens * cast(float, pricing["cache_hit_price"])) / 1000

        # Ensure result is a valid number
        cost = max(0.0, cost)

        return cost, currency
