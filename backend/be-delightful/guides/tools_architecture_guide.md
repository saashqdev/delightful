# Tools System Architecture Guide

This document provides a detailed description of the BeDelightful tools system architecture, including design principles, core components, tool development methods, and best practices.

## 1. Architecture Overview

The tools system adopts a modular design, mainly composed of the following core components:

- **BaseTool**: Base tool class, all tools inherit from this class
- **BaseToolParams**: Tool parameter base class, all parameter classes inherit from this class
- **tool_factory**: Tool factory singleton, responsible for tool scanning, registration, and instantiation
- **tool_executor**: Tool executor singleton, responsible for tool execution and error handling
- **@tool()**: Tool decorator, used for automatically registering tool classes
- **ToolContext**: Tool context, contains environment information for tool execution
- **ToolResult**: Tool result, contains result information from tool execution

### 1.1 Architecture Diagram

```
                           ┌────────────────┐
                           │ @tool()        │
                           │ Decorator      │
                           └───────┬────────┘
                                   │
                                   ▼
┌────────────────┐         ┌───────────────┐         ┌────────────────┐
│ BaseToolParams │◄────────┤   BaseTool    │────────►│  ToolResult    │
└────────────────┘         └───────┬───────┘         └────────────────┘
                                   │
                                   │
                  ┌────────────────┴────────────────┐
                  │                                  │
                  ▼                                  ▼
         ┌────────────────┐                 ┌───────────────────┐
         │  tool_factory  │◄────────────────┤   tool_executor   │
         └────────────────┘                 └───────────────────┘
                  ▲                                  ▲
                  │                                  │
                  └────────────────┬────────────────┘
                                   │
                                   ▼
┌────────────────┐         ┌──────────────────────┐
│  ToolContext   │────────►│ Concrete Tool Impls  │
└────────────────┘         │  (ListDir, ReadFile) │
                           └──────────────────────┘
```

### 1.2 Design Principles

1. **Single Responsibility**: Each tool is responsible for a single function, factory manages, executor executes
2. **Dependency Injection**: Pass dependencies through constructors and context, avoid hard-coded dependency relationships
3. **Type Safety**: Use Pydantic models to ensure parameter type safety and validation
4. **Auto Registration**: Use decorators to implement automatic tool registration, reduce manual registration code
5. **Error Isolation**: Capture and handle tool execution errors to avoid affecting the main process
6. **Friendly Errors**: Provide detailed error messages and context for easy debugging and fixing

## 2. Core Components

### 2.1 Base Tool Class (BaseTool)

All tools must inherit from the `BaseTool` base class, which provides the basic interface and implementation for tools.

```python
class BaseTool(ABC, Generic[T]):
    """Base tool class"""
    # Tool metadata
    name: str = ""
    description: str = ""

    # Parameter model type
    params_class: Type[T] = None

    @abstractmethod
    async def execute(self, tool_context: ToolContext, params: T) -> ToolResult:
        """Execute tool, must be implemented by subclass"""
        pass

    async def __call__(self, tool_context: ToolContext, **kwargs) -> ToolResult:
        """Tool call entry point, handles parameter conversion and other common logic"""
        # ...Handle parameter conversion and error capture
        return result
```

Main features of the `BaseTool` class:
- Uses generics to support typed parameter models
- Abstract `execute` method must be implemented by subclasses
- `__call__` method provides a unified entry point, handles parameter validation and error capture
- Provides a friendly error message generation mechanism

### 2.2 Base Tool Parameters Class (BaseToolParams)

Tool parameters must inherit from the `BaseToolParams` base class, which provides basic fields and validation rules for parameters.

```python
class BaseToolParams(BaseModel):
    """Base tool parameters class"""
    explanation: str = Field(
        "",
        description="Explain why you're using this tool in first person - briefly state your purpose, expected outcome, and how you'll use the results to help the user."
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error message"""
        return None
```

Main features of the `BaseToolParams` class:
- Inherits from Pydantic's `BaseModel`, supports parameter validation and type conversion
- Contains `explanation` field for explaining the purpose of the tool call
- Provides custom error message mechanism, subclasses can provide friendly error messages for specific fields and error types

### 2.3 Tool Decorator (@tool)

The tool decorator is used to automatically register tool classes, simplifying tool definition and management.

```python
@tool()
class MyTool(BaseTool):
    """My tool description"""
    # Tool implementation...
```

Main functions of the `@tool()` decorator:
- Automatically generates tool name from class name (converts to snake_case)
- Extracts tool description from docstring
- Marks tool properties for factory scanning and registration
- Automatically associates class name with corresponding filename

### 2.4 Tool Factory (tool_factory)

The tool factory is responsible for automatic discovery, registration, and instantiation of tools. It uses a singleton pattern to ensure global consistency.

```python
# Use tool factory to get tool instance
from app.tools.core.tool_factory import tool_factory

tool_instance = tool_factory.get_tool_instance("list_dir")

# Get all tool names
tool_names = tool_factory.get_tool_names()

# Initialize factory (usually no need to call manually)
tool_factory.initialize()
```

Main functions of `tool_factory`:
- Automatically scans and discovers all tool classes under the `app.tools` package
- Registers tools and caches tool information
- Creates tool instances and caches them
- Provides tool information query interface

### 2.5 Tool Executor (tool_executor)

The tool executor is responsible for tool execution and error handling. It also uses a singleton pattern to ensure global consistency.

```python
# Use tool executor to execute tool
from app.tools.core.tool_executor import tool_executor

result = await tool_executor.execute_tool_call(tool_context, arguments)

# Get tool instance
tool = tool_executor.get_tool("list_dir")

# Get all tool function call patterns
schemas = tool_executor.get_tool_schemas()
```

Main functions of `tool_executor`:
- Execute tool calls, including parameter handling and error capture
- Provide friendly error handling mechanism
- Get tool instances and schema information
- Performance timing and logging

### 2.6 Tool Context (ToolContext)

The tool context contains environment information for tool execution, such as tool name, call ID, and other metadata.

```python
# Create tool context
from agentlang.context.tool_context import ToolContext

tool_context = ToolContext(
    tool_name="list_dir",
    tool_call_id="some-id",
    # Other context information...
)
```

### 2.7 Tool Result (ToolResult)

The tool result contains result information from tool execution, such as content, errors, execution time, etc.

```python
# Create tool result
from app.core.entity.tool.tool_result import ToolResult

result = ToolResult(
    content="Tool execution result",
    error=None,
    name="list_dir",
    execution_time=0.1
)
```

## 3. Tool Development Guide

### 3.1 Define Tool Parameters

First define the tool parameter class, inheriting from `BaseToolParams`:

```python
from pydantic import Field
from app.tools.core import BaseToolParams

class MyToolParams(BaseToolParams):
    """Tool parameters"""
    param1: str = Field(..., description="Description of parameter 1")
    param2: int = Field(10, description="Description of parameter 2")
    param3: bool = Field(False, description="Description of parameter 3")
    
    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error message"""
        if field_name == "param1" and error_type == "missing":
            return "param1 is a required parameter, please provide a string value"
        return None
```

Parameter definition recommendations:
- Use Pydantic's `Field` to add detailed descriptions for each parameter
- Provide reasonable default values for optional parameters
- Use type annotations to specify parameter types
- Provide friendly error messages through `get_custom_error_message`

### 3.2 Define Tool Class

Then define the tool class, inheriting from `BaseTool`, using the `@tool()` decorator for registration:

```python
from app.tools.core import BaseTool, tool
from agentlang.context.tool_context import ToolContext
from app.core.entity.tool.tool_result import ToolResult

@tool()
class MyTool(BaseTool):
    """My tool description

    This is a detailed description of the tool, the first line will be automatically extracted as a short description.
    """

    # Set parameter type
    params_class = MyToolParams

    async def execute(self, tool_context: ToolContext, params: MyToolParams) -> ToolResult:
        """Execute tool logic"""
        try:
            # Implement tool logic
            result_content = f"Processing parameters: {params.param1}, {params.param2}, {params.param3}"
            
            # Return result
            return ToolResult(content=result_content)
        except Exception as e:
            # Error handling
            return ToolResult(error=f"Tool execution failed: {e}")
```

Tool class definition recommendations:
- Provide detailed docstring, especially the first line
- Explicitly specify the `params_class` attribute
- Implement tool logic in the `execute` method
- Use try-except blocks to capture possible errors
- Return formatted `ToolResult` objects

### 3.3 Tool Execution Workflow

The complete workflow for tool execution is as follows:

1. When the application starts, `tool_factory` automatically scans and registers all tool classes with the `@tool()` decorator
2. The caller creates a `ToolContext` object containing the tool name and call information
3. The caller executes the tool through `tool_executor.execute_tool_call()`
4. The executor gets the tool instance through the tool factory
5. The executor converts parameters to the tool parameter model
6. The executor calls the tool instance's `__call__` method
7. The `__call__` method validates parameters and calls the `execute` method
8. The `execute` method executes the tool logic and returns `ToolResult`
9. The executor handles possible errors and returns the result

## 4. Best Practices

### 4.1 Tool Naming

- Tool class names use CamelCase, e.g., `ListDir`
- Tool names are automatically converted to snake_case, e.g., `list_dir`
- Filenames should match the tool name, e.g., `list_dir.py`
- Tool descriptions should be concise and clear, especially the first line

### 4.2 Parameter Design

- Use clear parameter names, avoid abbreviations
- Use Pydantic's Field to add detailed descriptions for each parameter
- Provide reasonable default values for optional parameters
- Use precise type annotations
- Provide friendly error messages through `get_custom_error_message`

### 4.3 Tool Implementation

- Implement focused tools following the single responsibility principle
- Use try-except blocks to handle possible errors
- Use type annotations in the execute method
- Extract common logic to base classes or helper methods
- Return formatted results, avoid complex nested structures

### 4.4 Error Handling

- Capture and handle possible exceptions
- Provide detailed error messages including error type and context
- Use custom error message mechanism to provide friendly hints
- Log detailed error logs including call stack
- Return meaningful error codes and descriptions

### 4.5 Performance Optimization

- Avoid unnecessary computations and I/O operations
- Use async I/O to improve concurrent performance
- Cache frequently used data and results
- Limit the scope of resource-intensive operations
- Provide timeout mechanisms for long-running operations

## 5. FAQ

### 5.1 Tool Not Discovered

**Problem**: Added a new tool, but the system did not discover it.

**Solution**:
1. Ensure the tool class uses the `@tool()` decorator
2. Ensure the tool file is in the `app/tools` directory or its subdirectories
3. Ensure the tool class name matches the filename
4. Restart the application or manually call `tool_factory.initialize()`

### 5.2 Parameter Validation Failed

**Problem**: Parameter validation error when tool executes.

**Solution**:
1. Check if the passed parameters conform to the parameter model definition
2. Check if all required parameters are provided
3. Check if parameter types are correct
4. Implement `get_custom_error_message` to provide friendly error hints

### 5.3 Tool Execution Failed

**Problem**: Tool execution reports an error.

**Solution**:
1. Check the detailed error information and call stack in the logs
2. Check the error handling in the tool logic
3. Ensure all dependent services and resources are available
4. Verify tool functionality through unit tests in the development environment

### 5.4 Performance Issues

**Problem**: Tool execution is slow or resource consumption is high.

**Solution**:
1. Use performance profiling tools to find bottlenecks
2. Optimize I/O operations, use async or batch processing
3. Cache frequently used data
4. Limit the scope of resource-intensive operations
5. Consider batch processing scenarios for large amounts of data
