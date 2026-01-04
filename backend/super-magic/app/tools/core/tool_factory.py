"""Tool factory module responsible for discovery, registration, and creation."""

import importlib
import importlib.metadata
import inspect
import os
import pkgutil
import time
from dataclasses import dataclass
from typing import Dict, List, Optional, Type, TypeVar

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.tools.core import BaseTool

logger = get_logger(__name__)

# Tool type variable
T = TypeVar('T', bound=BaseTool)


@dataclass
class ToolInfo:
    """Metadata holder for tools."""
    # Tool class
    tool_class: Type[BaseTool]
    # Tool name
    name: str
    # Tool description
    description: str
    # Tool params type (optional)
    params_class: Optional[Type] = None
    # Error message if registration failed
    error: Optional[str] = None

    def __post_init__(self):
        """Validate constructed ToolInfo."""
        if not self.tool_class:
            raise ValueError("Tool class cannot be empty")
        if not self.name:
            raise ValueError("Tool name cannot be empty")

    def is_valid(self) -> bool:
        """Check whether the tool info is valid."""
        return self.error is None


class ToolFactory:
    """Factory to scan, register, and create tool instances."""
    _instance = None  # Singleton instance

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(ToolFactory, cls).__new__(cls)
            cls._instance._initialized = False
        return cls._instance

    def __init__(self):
        if self._initialized:
            return

        self._tools: Dict[str, ToolInfo] = {}  # Tool info: name -> ToolInfo
        self._tool_instances: Dict[str, BaseTool] = {}  # Cached instances: name -> instance
        self._initialized = True

    def register_tool(self, tool_class: Type[BaseTool]) -> None:
        """Register a tool class."""
        if not hasattr(tool_class, '_tool_name'):
            logger.warning(f"Tool class {tool_class.__name__} is missing @tool decorator; skip registration")
            return

        tool_name = tool_class._tool_name

        # Retrieve params class
        params_class = getattr(tool_class, 'params_class', None) or getattr(tool_class, '_params_class', None)

        try:
            # Build tool metadata
            tool_info = ToolInfo(
                tool_class=tool_class,
                name=tool_name,
                description=tool_class._tool_description,
                params_class=params_class
            )

            # Store tool metadata
            self._tools[tool_name] = tool_info

            # Mark as registered
            tool_class._registered = True
            logger.debug(f"Registered tool: {tool_name}")
        except Exception as e:
            logger.error(f"Error extracting params for tool {tool_name}: {e}")
            # Preserve detailed error when param validation fails
            if hasattr(e, '__str__'):
                logger.error(f"Detailed error: {e!s}")
            # Retain a tool record even on error but mark as invalid
            self._tools[tool_name] = ToolInfo(
                tool_class=tool_class,
                name=tool_name,
                description=getattr(tool_class, '_tool_description', "Description unavailable"),
                params_class=None,
                error=str(e)
            )

    def auto_discover_tools(self) -> None:
        """Auto-discover and register tools under app.tools and entry points."""
        # Tool package path
        package_name = 'app.tools'
        tools_entry_points = list(importlib.metadata.entry_points(group='agentlang.tools'))
        package_names = [package_name]
        for entry_point in tools_entry_points:
            package_names.append(entry_point.value)
            logger.info(f"Found tool package: {entry_point.value}")
        try:
            # Recursive scanner
            def scan_package(pkg_name: str, pkg_path: str) -> None:
                logger.info(f"Scanning package: {pkg_name}")

                # Iterate modules in package
                for _, module_name, is_pkg in pkgutil.iter_modules([pkg_path]):
                    # Skip app.tools.core only inside app.tools
                    if is_pkg and module_name == 'core' and pkg_name == 'app.tools':
                        continue

                    # Recurse into subpackages
                    if is_pkg:
                        subpackage_name = f"{pkg_name}.{module_name}"
                        logger.info(f"Found subpackage: {subpackage_name}")
                        try:
                            subpackage = importlib.import_module(subpackage_name)
                            # Ensure subpackage.__file__ exists
                            if not hasattr(subpackage, '__file__') or subpackage.__file__ is None:
                                logger.warning(f"Subpackage {subpackage_name} missing __file__; skipping")
                                continue

                            subpackage_path = os.path.dirname(subpackage.__file__)

                            # Recurse into subpackage
                            scan_package(subpackage_name, subpackage_path)
                        except Exception as e:
                            logger.error(f"Failed scanning subpackage {subpackage_name}: {e!s}")
                        continue

                    # Dynamically import module
                    module_fullname = f"{pkg_name}.{module_name}"
                    try:
                        # Import module
                        logger.info(f"Import module: {module_fullname}")
                        module = importlib.import_module(module_fullname)

                        # Find classes marked as tools
                        for name, obj in inspect.getmembers(module):
                            if (inspect.isclass(obj) and
                                hasattr(obj, '_is_tool') and
                                obj._is_tool and
                                not getattr(obj, '_registered', False)):
                                # Register tool class
                                logger.info(f"Found tool class: {name} in module {module_fullname}")
                                self.register_tool(obj)
                    except Exception as e:
                        logger.error(f"Failed loading module {module_fullname}: {e!s}")
            for package_name in package_names:
                try:
                    package = importlib.import_module(package_name)
                    package_path = os.path.dirname(package.__file__)
                    # Begin scanning
                    scan_package(package_name, package_path)
                except ImportError:
                    logger.error(f"Tool package not found: {package_name}")
                    continue
                except Exception as e:
                    logger.error(f"Failed loading tool package {package_name}: {e!s}")
                    continue
        except Exception as e:
            logger.error(f"Error scanning tools: {e!s}", exc_info=True)

    def initialize(self) -> None:
        """Initialize factory by scanning and registering all tools."""
        self.auto_discover_tools()
        logger.info(f"ToolFactory initialization finished, discovered {len(self._tools)} tools")

    def get_tool(self, tool_name: str) -> Optional[ToolInfo]:
        """Retrieve tool metadata by name."""
        if not self._tools:
            self.initialize()

        return self._tools.get(tool_name)

    def get_tool_instance(self, tool_name: str) -> BaseTool:
        """Get (or create) a tool instance by name."""
        # Check cache first
        if tool_name in self._tool_instances:
            return self._tool_instances[tool_name]

        # Fetch metadata
        tool_info = self.get_tool(tool_name)
        if not tool_info:
            raise ValueError(f"Tool {tool_name} does not exist")

        # Create tool instance
        try:
            # Resolve class attributes
            name = getattr(tool_info.tool_class, 'name', tool_name)
            description = getattr(tool_info.tool_class, 'description', tool_info.description)

            # Explicitly pass name/description into constructor
            tool_instance = tool_info.tool_class(name=name, description=description)

            # Cache instance
            self._tool_instances[tool_name] = tool_instance

            return tool_instance
        except Exception as e:
            logger.error(f"Error creating instance for tool {tool_name}: {e}")
            raise ValueError(f"Unable to create instance for tool {tool_name}: {e}")

    def get_all_tools(self) -> Dict[str, ToolInfo]:
        """Return metadata for all tools."""
        if not self._tools:
            self.initialize()

        return self._tools

    def get_tool_names(self) -> List[str]:
        """Return all tool names."""
        if not self._tools:
            self.initialize()

        return list(self._tools.keys())

    def get_all_tool_instances(self) -> List[BaseTool]:
        """Return instantiated tools for all registered tool names."""
        all_tools = self.get_all_tools()
        return [self.get_tool_instance(tool_name) for tool_name in all_tools.keys()]

    async def run_tool(self, tool_context: ToolContext, tool_name: str, **kwargs) -> ToolResult:
        """Execute a tool by name with given kwargs."""
        try:
            start_time = time.time()

            # Get instance
            tool_instance = self.get_tool_instance(tool_name)

            # Convert params (when model exists)
            tool_info = self.get_tool(tool_name)
            params_class = tool_info.params_class if tool_info else None
            if params_class:
                try:
                    params = params_class(**kwargs)
                    result = await tool_instance.execute(tool_context, params)
                except Exception as e:
                    logger.error(f"Parameter validation failed: {e!s}")
                    result = ToolResult(
                        error=f"Parameter validation failed: {e!s}",
                        name=tool_name
                    )
            else:
                # Backward compatibility: tools without param models
                result = await tool_instance.execute(tool_context, **kwargs)

            # Set execution duration
            result.execution_time = time.time() - start_time

            return result
        except Exception as e:
            logger.error(f"Failed executing tool {tool_name}: {e!s}", exc_info=True)

            # Build error result
            result = ToolResult(
                content="",
                error=f"Tool execution failed: {e!s}",
                name=tool_name
            )

            return result


# Global tool factory instance
tool_factory = ToolFactory()
