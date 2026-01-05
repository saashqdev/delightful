"""Browser operation registry

Dynamically loads and manages all operation groups.

# Registry Design

## Goals

Core component of the browser operation architecture, providing:
- Central management of operation groups and operations
- Dynamic discovery and loading of operations
- Unified access interface
- Grouped/category queries

## How it works

1. **Discovery**:
    - Scans all modules under use_browser_operations
    - Finds and registers all OperationGroup subclasses
    - No manual registration needed; new groups auto-discovered

2. **Lazy init**:
    - Delayed initialization
    - Instantiates groups on first request
    - Reduces startup time/resources

3. **Plugin architecture**:
    - Open/closed principle; extend without modifying
    - New operations can live in separate modules
    - Loose coupling between groups

## Usage

- Get operation: operations_registry.get_operation(name)
- Get all operations: operations_registry.get_all_operations()
- Get by group: operations_registry.get_operations_by_group(group_name)
- Get group info: operations_registry.get_group_info()

## Extending

To add a new operation group:
1. Create a class inheriting OperationGroup
2. Define group_name and group_description
3. Implement operations decorated with @operation

No registry code changes required; discovery is automatic.
"""

import importlib
import inspect
import os
import pkgutil
import sys
from typing import Any, Dict, List, Optional, Type

from agentlang.logger import get_logger
from app.tools.use_browser_operations.base import OperationGroup

# Registry logger
logger = get_logger(__name__)

class OperationsRegistry:
    """Browser operation registry

    Dynamically loads and manages all operation groups.
    """
    def __init__(self):
        self._operation_groups: Dict[str, Type[OperationGroup]] = {}
        self._operations: Dict[str, Dict[str, Any]] = {}
        self._group_instances: Dict[str, OperationGroup] = {}
        self._initialized = False

    def register_operation_group(self, group_class: Type[OperationGroup]):
        """Register an operation group."""
        group_name = group_class.group_name
        self._operation_groups[group_name] = group_class
        logger.debug(f"Registered operation group: {group_name}")

    def auto_discover_operation_groups(self):
        """Auto-discover and register operation groups.

        Scans use_browser_operations for OperationGroup subclasses.
        """
        # Locate the current package path
        package_name = 'app.tools.use_browser_operations'
        package = sys.modules[package_name]
        package_path = os.path.dirname(package.__file__)

        logger.debug(f"Scanning {package_path} for operation groups")

        # Scan all modules under the package
        for _, module_name, is_pkg in pkgutil.iter_modules([package_path]):
            # Skip packages and current modules to avoid circular imports
            if is_pkg or module_name in ['operations_registry', 'base']:
                continue

            # Dynamically import the module
            module_fullname = f"{package_name}.{module_name}"
            try:
                module = importlib.import_module(module_fullname)

                # Find all OperationGroup subclasses in the module
                for name, obj in inspect.getmembers(module):
                    if (inspect.isclass(obj) and
                        issubclass(obj, OperationGroup) and
                        obj != OperationGroup):
                        # Register the discovered action group
                        self.register_operation_group(obj)
                        logger.debug(f"Discovered and registered operation group from module {module_name}: {obj.group_name}")
            except Exception as e:
                logger.error(f"Failed to load module {module_fullname}: {e!s}")

        logger.info(f"Auto-discovery complete; found {len(self._operation_groups)} operation groups")

    def initialize(self):
        """Initialize registry: create group instances and register operations."""
        if self._initialized:
            return

        logger.debug(f"Initializing operation registry; groups: {len(self._operation_groups)}")

        # Create action group instances
        for group_name, group_class in self._operation_groups.items():
            self._group_instances[group_name] = group_class()

        # Register all actions
        for group_name, group_instance in self._group_instances.items():
            operations = group_instance.get_operations()
            for op_name, op_info in operations.items():
                self._operations[op_name] = {
                    "group": group_name,
                    "handler": op_info["handler"],
                    "params_class": op_info["params_class"],
                    "description": op_info["description"],
                    "examples": op_info.get("examples", []),
                }
                logger.debug(f"Registered operation: {op_name} (from {group_name})")

        self._initialized = True
            logger.info(f"Operation registry initialized; {len(self._operations)} operations")

    def get_operation(self, operation_name: str) -> Optional[Dict[str, Any]]:
        """Get operation info by name."""
        if not self._initialized:
            self.initialize()

        op_info = self._operations.get(operation_name)
        if not op_info:
            logger.warning(f"Operation not found: {operation_name}")

        return op_info

    def get_all_operations(self) -> Dict[str, Dict[str, Any]]:
        """Get all operations."""
        if not self._initialized:
            self.initialize()
        return self._operations

    def get_operations_by_group(self, group_name: str) -> Dict[str, Dict[str, Any]]:
        """Get operations by group name."""
        if not self._initialized:
            self.initialize()
        operations = {}
        for op_name, op_info in self._operations.items():
            if op_info["group"] == group_name:
                operations[op_name] = op_info
        return operations

    def get_group_names(self) -> List[str]:
        """Get all group names."""
        if not self._initialized:
            self.initialize()
        return list(self._operation_groups.keys())

    def get_group_info(self) -> Dict[str, Dict[str, Any]]:
        """Get metadata for all groups."""
        if not self._initialized:
            self.initialize()
        group_info = {}
        for group_name, group_class in self._operation_groups.items():
            group_info[group_name] = {
                "name": group_name,
                "description": group_class.group_description,
                "operations_count": len(self.get_operations_by_group(group_name))
            }
        return group_info


# Create global registry
operations_registry = OperationsRegistry()

# Auto-discover and register all operation groups
operations_registry.auto_discover_operation_groups()

# Initialize registry
operations_registry.initialize()
