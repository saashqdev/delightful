"""Browser operations module

Contains definitions and implementations for all browser operations, using Pydantic models for parameter validation.
"""

from app.tools.use_browser_operations.base import BaseOperationParams, OperationGroup, operation
from app.tools.use_browser_operations.content import ContentOperations
from app.tools.use_browser_operations.interaction import InteractionOperations
from app.tools.use_browser_operations.navigation import NavigationOperations
from app.tools.use_browser_operations.operations_registry import operations_registry

__all__ = [
    'BaseOperationParams',
    'ContentOperations',
    'InteractionOperations',
    'NavigationOperations',
    'OperationGroup',
    'operation',
    'operations_registry'
]
