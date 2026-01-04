"""
Core data models for the token usage statistics system

Defines strongly-typed data objects for passing token usage related information within the system
"""

from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, ClassVar, Dict, List, Optional, Protocol, Type

from agentlang.logger import get_logger

logger = get_logger(__name__)


@dataclass
class InputTokensDetails:
    """Detailed information about input tokens"""
    cached_tokens: Optional[int] = 0  # Number of tokens read from cache
    cache_write_tokens: Optional[int] = 0  # Number of tokens written to cache

    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary, only including non-zero values"""
        data = {k: v for k, v in self.__dict__.items() if v is not None}
        return data if any(v != 0 for v in data.values() if isinstance(v, (int, float))) else None

    @classmethod
    def from_dict(cls, data: Optional[Dict[str, Any]]) -> Optional['InputTokensDetails']:
        """Create object from dictionary, returns None if all values are 0"""
        if not data:
            return None

        # Check if all values are 0 or None
        all_zero_or_none = True
        for value in data.values():
            if value is not None and value != 0:
                all_zero_or_none = False
                break

        if all_zero_or_none:
            return None

        return cls(
            cached_tokens=data.get("cached_tokens", 0),
            cache_write_tokens=data.get("cache_write_tokens", 0)
        )


@dataclass
class OutputTokensDetails:
    """Detailed information about output tokens"""
    reasoning_tokens: Optional[int] = 0  # Number of tokens used for reasoning

    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary, only including non-zero values"""
        data = {k: v for k, v in self.__dict__.items() if v is not None}
        return data if any(v != 0 for v in data.values() if isinstance(v, (int, float))) else None

    @classmethod
    def from_dict(cls, data: Optional[Dict[str, Any]]) -> Optional['OutputTokensDetails']:
        """Create object from dictionary, returns None if all values are 0"""
        if not data:
            return None

        # Check if all values are 0 or None
        all_zero_or_none = True
        for value in data.values():
            if value is not None and value != 0:
                all_zero_or_none = False
                break

        if all_zero_or_none:
            return None

        return cls(
            reasoning_tokens=data.get("reasoning_tokens", 0)
        )


class TokenUsageParser(Protocol):
    """Token使用量解析器协议

    定义解析不同API响应格式的接口，新的解析器只需实现这个协议
    """
    @classmethod
    def can_parse(cls, response: Any) -> bool:
        """
        检查是否可以解析特定响应格式

        Args:
            response: API响应

        Returns:
            bool: 是否可以解析
        """
        ...

    @classmethod
    def parse(cls, response: Any) -> tuple[int, int, int, Optional[InputTokensDetails], Optional[OutputTokensDetails]]:
        """
        解析响应，提取token使用信息

        Args:
            response: API响应

        Returns:
            tuple: (input_tokens, output_tokens, total_tokens, input_tokens_details, output_tokens_details)
        """
        ...


@dataclass
class TokenUsage:
    """基本token使用量数据"""
    input_tokens: int
    output_tokens: int
    total_tokens: int
    input_tokens_details: Optional[InputTokensDetails] = None
    output_tokens_details: Optional[OutputTokensDetails] = None

    # 注册的解析器，按优先级顺序排列
    _parsers: ClassVar[List[Type[TokenUsageParser]]] = []

    def to_dict(self) -> Dict[str, Any]:
        """转换为字典"""
        data = {
            "input_tokens": self.input_tokens,
            "output_tokens": self.output_tokens,
            "total_tokens": self.total_tokens,
        }

        # 只有当details对象存在且其to_dict()结果不为None时才加入
        if self.input_tokens_details:
            input_details_dict = self.input_tokens_details.to_dict()
            if input_details_dict:
                data["input_tokens_details"] = input_details_dict

        if self.output_tokens_details:
            output_details_dict = self.output_tokens_details.to_dict()
            if output_details_dict:
                data["output_tokens_details"] = output_details_dict

        return data

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> 'TokenUsage':
        """从字典创建对象"""
        return cls(
            input_tokens=data.get("input_tokens", 0),
            output_tokens=data.get("output_tokens", 0),
            total_tokens=data.get("total_tokens", 0),
            input_tokens_details=InputTokensDetails.from_dict(data.get("input_tokens_details")),
            output_tokens_details=OutputTokensDetails.from_dict(data.get("output_tokens_details")),
        )

    @classmethod
    def register_parser(cls, parser: Type[TokenUsageParser]) -> None:
        """
        Register a new parser

        Args:
            parser: Parser class, must implement TokenUsageParser protocol
        """
        if parser not in cls._parsers:
            # Check if parser implements necessary methods
            if not (hasattr(parser, 'can_parse') and hasattr(parser, 'parse')):
                raise ValueError(f"Parser {parser.__name__} must implement can_parse and parse methods")
            cls._parsers.append(parser)
            logger.debug(f"Registered token usage parser: {parser.__name__}")

    @classmethod
    def from_response(cls, response_usage: Any) -> 'TokenUsage':
        """
        Extract token usage data from API response and create TokenUsage object.
        Try all registered parsers until a suitable parser is found.

        Args:
            response_usage: usage part of API response

        Returns:
            TokenUsage: Standardized TokenUsage object
        """
        if not response_usage:
            return cls(input_tokens=0, output_tokens=0, total_tokens=0)

        # Try all registered parsers
        for parser in cls._parsers:
            try:
                if parser.can_parse(response_usage):
                    input_tokens, output_tokens, total_tokens, input_details, output_details = parser.parse(response_usage)
                    logger.debug(f"Parsed token info using {parser.__name__}: input={input_tokens}, output={output_tokens}, total={total_tokens}")
                    return cls(
                        input_tokens=input_tokens,
                        output_tokens=output_tokens,
                        total_tokens=total_tokens,
                        input_tokens_details=input_details,
                        output_tokens_details=output_details
                    )
            except Exception as e:
                logger.warning(f"{parser.__name__} parsing failed: {e}")
                continue

        # If no suitable parser found, use default parser
        logger.warning("No suitable parser found, using default parser")
        try:
            return DefaultParser.create_token_usage(response_usage)
        except Exception as e:
            logger.error(f"Default parser failed: {e}")
            return cls(input_tokens=0, output_tokens=0, total_tokens=0)


# Update DefaultParser to handle all mainstream formats
class DefaultParser:
    """Default parser, handles all mainstream formats including OpenAI and Anthropic"""

    @classmethod
    def can_parse(cls, response: Any) -> bool:
        """
        Default parser attempts to handle all formats, prioritizing common formats

        - OpenAI format: prompt_tokens/completion_tokens
        - Anthropic format: prompt_tokens/completion_tokens with special details structure
        - Other formats: any response containing token information
        """
        # Check for token-related fields
        if isinstance(response, dict):
            token_fields = ["prompt_tokens", "completion_tokens", "total_tokens",
                           "input_tokens", "output_tokens"]
            return any(field in response for field in token_fields)
        elif response:
            # For objects, check if they have any token-related attributes
            return any(hasattr(response, field) for field in ["prompt_tokens", "completion_tokens",
                                                             "input_tokens", "output_tokens"])
        return False

    @staticmethod
    def get_value(obj: Any, key: str, default: Any = 0) -> Any:
        """从对象或字典中获取值"""
        if isinstance(obj, dict):
            return obj.get(key, default)
        return getattr(obj, key, default)

    @classmethod
    def parse(cls, response: Any) -> tuple[int, int, int, Optional[InputTokensDetails], Optional[OutputTokensDetails]]:
        """解析响应，支持所有主流格式（OpenAI, Anthropic等）"""
        try:
            # 提取基本token统计信息
            input_tokens = cls.get_value(response, "prompt_tokens", 0)
            output_tokens = cls.get_value(response, "completion_tokens", 0)
            total_tokens = cls.get_value(response, "total_tokens", 0)

            # 尝试替代字段名
            if input_tokens == 0:
                input_tokens = cls.get_value(response, "input_tokens", 0)

            if output_tokens == 0:
                output_tokens = cls.get_value(response, "output_tokens", 0)

            # 如果没有提供total_tokens，则计算得出
            if total_tokens == 0 and (input_tokens > 0 or output_tokens > 0):
                total_tokens = input_tokens + output_tokens

            # 处理输入详情
            parsed_input_details: Optional[InputTokensDetails] = None
            prompt_details = cls.get_value(response, "prompt_tokens_details", None)

            if prompt_details:
                # 提取cached_tokens
                cached_tokens = cls.get_value(prompt_details, "cached_tokens", 0)
                # 映射规则: prompt_tokens_details.cache_read_input_tokens => input_tokens_details.cached_tokens
                if cached_tokens == 0:
                    cached_tokens = cls.get_value(prompt_details, "cache_read_input_tokens", 0)

                # 提取cache_write_tokens
                cache_write_tokens = cls.get_value(prompt_details, "cache_write_input_tokens", 0)

                # 只有当至少有一个值非零时才创建对象
                if cached_tokens > 0 or cache_write_tokens > 0:
                    parsed_input_details = InputTokensDetails(
                        cached_tokens=cached_tokens,
                        cache_write_tokens=cache_write_tokens
                    )

            # 处理输出详情
            parsed_output_details: Optional[OutputTokensDetails] = None
            completion_details = cls.get_value(response, "completion_tokens_details", None)

            if completion_details:
                reasoning_tokens = cls.get_value(completion_details, "reasoning_tokens", 0)
                if reasoning_tokens > 0:
                    parsed_output_details = OutputTokensDetails(reasoning_tokens=reasoning_tokens)

            return input_tokens, output_tokens, total_tokens, parsed_input_details, parsed_output_details

        except Exception as e:
            logger.error(f"默认解析器解析失败: {e}")
            return 0, 0, 0, None, None

    @classmethod
    def create_token_usage(cls, response: Any) -> TokenUsage:
        """创建TokenUsage对象"""
        input_tokens, output_tokens, total_tokens, input_details, output_details = cls.parse(response)
        return TokenUsage(
            input_tokens=input_tokens,
            output_tokens=output_tokens,
            total_tokens=total_tokens,
            input_tokens_details=input_details,
            output_tokens_details=output_details
        )


class StandardParser:
    """Standard format parser, compatible with TokenUsage.to_dict()"""

    @classmethod
    def can_parse(cls, response: Any) -> bool:
        """Check if it's standard format"""
        if isinstance(response, dict):
            # Standard format uses input_tokens and output_tokens
            return "input_tokens" in response and "output_tokens" in response
        return hasattr(response, "input_tokens") and hasattr(response, "output_tokens")

    @classmethod
    def parse(cls, response: Any) -> tuple[int, int, int, Optional[InputTokensDetails], Optional[OutputTokensDetails]]:
        """Parse standard format response"""
        get_value = DefaultParser.get_value

        # Directly extract standard fields
        input_tokens = get_value(response, "input_tokens", 0)
        output_tokens = get_value(response, "output_tokens", 0)
        total_tokens = get_value(response, "total_tokens", 0)

        # If no total_tokens, calculate it
        if total_tokens == 0:
            total_tokens = input_tokens + output_tokens

        # Extract details
        input_details_data = get_value(response, "input_tokens_details", None)
        output_details_data = get_value(response, "output_tokens_details", None)

        # Parse details
        input_details = InputTokensDetails.from_dict(input_details_data) if input_details_data else None
        output_details = OutputTokensDetails.from_dict(output_details_data) if output_details_data else None

        return input_tokens, output_tokens, total_tokens, input_details, output_details


# Simplified registration part, only register two parsers
# Register built-in parsers - register StandardParser first to prioritize standard format
TokenUsage.register_parser(StandardParser)
TokenUsage.register_parser(DefaultParser)


@dataclass
class ModelUsage:
    """单个模型的使用情况"""
    model_name: str
    usage: TokenUsage
    cost: float = 0.0
    currency: str = "CNY"


@dataclass
class CostReport:
    """成本报告对象"""
    models: List[ModelUsage] = field(default_factory=list)
    timestamp: str = field(default_factory=lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    currency_code: str = "CNY"

    @property
    def total_input_tokens(self) -> int:
        """计算总输入token数"""
        return sum(model.usage.input_tokens for model in self.models)

    @property
    def total_output_tokens(self) -> int:
        """计算总输出token数"""
        return sum(model.usage.output_tokens for model in self.models)

    @property
    def total_cache_write_tokens(self) -> int:
        """计算总缓存写入token数"""
        return sum(
            (model.usage.input_tokens_details.cache_write_tokens
             if model.usage.input_tokens_details else 0)
            for model in self.models
        )

    @property
    def total_cache_hit_tokens(self) -> int:
        """计算总缓存命中token数"""
        return sum(
            (model.usage.input_tokens_details.cached_tokens
             if model.usage.input_tokens_details else 0)
            for model in self.models
        )

    @property
    def total_tokens(self) -> int:
        """计算总token数"""
        return sum(model.usage.total_tokens for model in self.models)

    @property
    def total_cost(self) -> float:
        """计算总成本"""
        return sum(model.cost for model in self.models)


def get_currency_symbol(currency_code: str) -> str:
    """获取货币符号

    Args:
        currency_code: 货币代码

    Returns:
        str: 货币符号
    """
    currency_symbols = {
        "CNY": "¥",
        "USD": "$",
        "EUR": "€",
        "GBP": "£",
        "JPY": "¥"
    }
    return currency_symbols.get(currency_code, currency_code)


# Backward compatibility functions to support legacy code
def is_prompt_tokens_details(data: Any) -> bool:
    """
    Check if object is input token details data structure (backward compatible)

    Args:
        data: Object to check

    Returns:
        bool: Whether it is input token details structure
    """
    if not isinstance(data, dict):
        return False

    # Check for any cache-related fields
    cache_fields = ["cached_tokens", "cache_write_tokens", "cache_read_input_tokens", "cache_write_input_tokens"]
    return any(field in data for field in cache_fields)


def is_llm_usage_info(data: Any) -> bool:
    """
    Check if object is LLM usage information data structure (backward compatible)

    Args:
        data: Object to check

    Returns:
        bool: Whether it is LLM usage information structure
    """
    if not isinstance(data, dict):
        return False

    # Check for token-related fields
    token_fields = ["input_tokens", "output_tokens", "prompt_tokens", "completion_tokens", "total_tokens"]
    return any(field in data for field in token_fields)
