"""Tool decorator module.

Provides a decorator to register tools, capturing metadata for later use.
"""

from typing import Optional


def tool(name: Optional[str] = None, description: Optional[str] = None):
    """Decorator for registering tool classes and capturing metadata."""
    def decorator(cls):
        # Mark class as tool
        cls._is_tool = True

        # Store decorator-provided name/description when present
        cls._initial_name = name
        cls._initial_description = description

        # Prime values for ToolFactory; BaseTool.__init_subclass__ finalizes them
        cls._tool_name = name if name else getattr(cls, 'name', None)
        cls._tool_description = description if description else getattr(cls, 'description', None)
        cls._params_class = getattr(cls, 'params_class', None)

        # Mark as not yet registered
        cls._registered = False

        return cls
    return decorator
