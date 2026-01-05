"""Token estimator module

Provides helper functions for calculating LLM model token counts.
"""

import tiktoken

from agentlang.logger import get_logger

logger = get_logger(__name__)


def num_tokens_from_string(string: str, model: str = "gpt-3.5-turbo") -> int:
    """Estimate token count for a string.

    Args:
        string: String to calculate
        model: Model name, supports OpenAI model families

    Returns:
        int: Estimated token count
    """
    if not string:
        return 0

    try:
        # Get encoder for the model
        try:
            if model.startswith(("gpt-4", "gpt-3.5-turbo")):
                encoding = tiktoken.encoding_for_model(model)
            else:
                # Default to general-purpose encoder
                encoding = tiktoken.get_encoding("cl100k_base")
        except Exception as enc_err:
            # Encoder acquisition failed, raise exception to use simulation method
            logger.error(f"Failed to get encoder: {enc_err!s}, using simulation method")
            raise

        # Calculate and return token count
        return len(encoding.encode(string))
    except Exception as e:
        # Log error
        logger.error(f"Token calculation error: {e!s}, using simulation method")

        # Use custom simulation calculation method
        return _simulate_token_count(string)


def _simulate_token_count(text: str) -> int:
    """Simulate token count calculation method.

    English: approximately 1 token per 4 characters
    Chinese: approximately 1 token per 1.5 characters

    Args:
        text: Text to calculate

    Returns:
        int: Estimated token count
    """
    if not text:
        return 0

    # Count Chinese characters
    chinese_char_count = sum(1 for char in text if '\u4e00' <= char <= '\u9fff')

    # Count non-Chinese characters
    non_chinese_char_count = len(text) - chinese_char_count

    # Estimate tokens: Chinese characters/1.5 + non-Chinese characters/4
    estimated_tokens = int(chinese_char_count / 1.5 + non_chinese_char_count / 4)

    # Ensure at least 1 token is returned
    return max(1, estimated_tokens)


def truncate_text_by_token(text: str, max_tokens: int) -> tuple[str, bool]:
    """Truncate text to not exceed specified token count.

    Args:
        text: Text to truncate
        max_tokens: Maximum token count

    Returns:
        tuple[str, bool]: (truncated text, whether it was truncated)
    """
    if not text:
        return "", False

    # If text is very short, return directly
    if len(text) < max_tokens:
        return text, False

    # Initialize counter and current position
    token_count = 0
    position = 0

    # Iterate through text characters
    for i, char in enumerate(text):
        # Increment token count
        if '\u4e00' <= char <= '\u9fff':
            # Chinese character
            token_count += 1 / 1.5
        else:
            # Non-Chinese character
            token_count += 1 / 4

        # Check if max tokens reached
        if int(token_count) >= max_tokens:
            position = i
            break

    # If max tokens not reached, entire text is within limit
    if position == 0 or position >= len(text) - 1:
        return text, False

    # Truncate text and add ellipsis indicator
    truncated_text = text[:position] + "\n\n... [Content too long, truncated] ..."
    return truncated_text, True 
