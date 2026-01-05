"""Token counter module

Provides tools for tracking and managing LLM token usage.
"""


class TokenCounter:
    """Token counter class for tracking and managing LLM token usage."""

    def __init__(self):
        """Initialize token counter."""
        self.input_tokens = 0
        self.output_tokens = 0
        self.total_tokens = 0

    def add_input_tokens(self, count: int) -> None:
        """Add input token count.

        Args:
            count: Input token count
        """
        self.input_tokens += count
        self.total_tokens += count

    def add_output_tokens(self, count: int) -> None:
        """Add output token count.

        Args:
            count: Output token count
        """
        self.output_tokens += count
        self.total_tokens += count

    def reset(self) -> None:
        """Reset counter."""
        self.input_tokens = 0
        self.output_tokens = 0
        self.total_tokens = 0

    def get_stats(self) -> dict:
        """Get statistics.

        Returns:
            dict: Dictionary containing input, output, and total token counts
        """
        return {
            "input_tokens": self.input_tokens,
            "output_tokens": self.output_tokens,
            "total_tokens": self.total_tokens,
        } 
