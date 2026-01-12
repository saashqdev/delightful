# BeDelightful Class Documentation

BeDelightful is the core agent class of the project, integrating the critical capabilities of an intelligent agent. It handles user queries, invokes large language models, executes tools, manages state, and coordinates resources. This guide walks through its design, implementation, and workflow.

## Core Features

BeDelightful implements a full AI agent system with:

1. Interaction with large language models (LLMs)
2. Tool invocation and execution
3. Chat history management
4. Event and callback handling
5. Dynamic prompt handling
6. Agent state management
7. Resource lifecycle management

## Key Components

BeDelightful collaborates with several components:

- **LLMAdapter**: communicates with LLMs (e.g., GPT-4)
- **ToolExecutor**: executes tool calls
- **PromptProcessor**: handles system prompts
- **AgentContext**: maintains runtime context
- **ToolCollection**: manages the available tools

## Workflow

The primary workflow consists of:

1. **initialize**: load configuration and components
2. **receive user query**: handle user input
3. **loop execution**: send requests to the LLM, parse tool calls, and execute them
4. **task completion**: stop when the task finishes or the max iteration count is reached

### Detailed Flow

```
user query -> initialize environment -> set status RUNNING
-> loop {
   check whether chat history needs replacement
   -> send request to LLM
   -> parse tool calls in the response
   -> execute tool calls
   -> handle tool results
   -> check whether the task is complete
}
-> clean up resources -> return result
```

## Core Methods

### Initialization and Configuration

#### `__init__`
- Purpose: initialize a BeDelightful instance
- Highlights:
  - Initialize state, tool executor, and LLM adapter
  - Set dynamic prompt flags
  - Initialize token counter
  - Register callbacks
  - Create working directory
  - Sync configuration from `agent_context`
  - Register completion callbacks

#### `set_context`
- Purpose: set the agent context
- Related methods: `_initialize_history_manager_from_context`, `_update_file_tools_base_dir`
- Highlights:
  - Accept `AgentContext`
  - Sync model settings, streaming mode, and dynamic prompts
  - Initialize the history manager

#### `set_agent`
- Purpose: set the active agent and prompts
- Related method: `_setup_agent_and_model`
- Highlights:
  - Set agent name
  - Update the history manager’s agent name
  - Use the model defined in the agent file

#### `set_llm_model`
- Purpose: set the LLM model
- Highlights:
  - Try setting the LLM adapter’s default model
  - Update current model name

### Tool Management

#### `load_tools_by_config`
- Purpose: load tools based on configuration
- Related methods: `_initialize_available_tools`, `register_tool`
- Highlights:
  - Clear current tool set
  - Validate tool names
  - Load and register configured tools
  - Update the tool executor’s collection

#### `_initialize_available_tools`
- Purpose: initialize the list of available tool instances
- Highlights:
  - Get all tool instances from the registry
  - Update the list of available tools
  - Set base directory for workspace-bound tools

#### `register_tool`
- Purpose: register a tool
- Highlights:
  - Add the tool to the collection
  - Handle resource-managed tools
  - Set the tool’s agent reference
  - Update the executor’s collection

### Execution Flow

#### `run`
- Purpose: run the BeDelightful agent for a user query
- Related method: `run_async`
- Highlights:
  - Create an event loop
  - Call the async runner
  - Handle keyboard interrupts

#### `run_async`
- Purpose: run the agent asynchronously
- Related methods: `_initialize_agent_environment`, `_get_next_function_call_response`, `_parse_tool_calls`, `_execute_tool_calls`, `_process_tool_results`, `_cleanup_resources`
- Highlights:
  - Initialize the agent environment and chat history
  - Set status to running
  - Main loop:
    - Get tool descriptions
    - Check model tool-call support
    - Get LLM response
    - Parse tool calls
    - Execute tool calls
    - Handle tool results
    - Check task completion
  - Handle final results
  - Clean up resources

#### `_initialize_agent_environment`
- Purpose: initialize environment and history
- Related methods: `set_context`, `_initialize_history_manager_from_context`, `_setup_agent_and_model`, `_update_file_tools_base_dir`
- Highlights:
  - Set context
  - Initialize history manager
  - Set agent and model
  - Check tool-call support
  - Update working directory
  - Set system prompts
  - Load chat history
  - Compress history if needed

#### `_get_next_function_call_response`
- Purpose: get the next response containing a function call from the LLM
- Related method: `_create_api_error_response`
- Highlights:
  - Trigger before-LLM-request event
  - Get response from the LLM adapter
  - Trigger after-LLM-request event
  - Validate the response

### Tool Execution

#### `_execute_tool_calls`
- Purpose: execute tool calls
- Highlights:
  - Iterate over tool call list
  - Get tool name and parameters
  - Trigger before-tool-call event
  - Execute tool
  - Trigger after-tool-call event

#### `_process_tool_results`
- Purpose: handle tool results and add them to chat history
- Related method: `_save_chat_history`
- Highlights:
  - Add tool results to history
  - Handle special system directives (e.g., FINISH_TASK)
  - Check ASK_USER tools
  - Save chat history

### Message and History Management

#### `_save_chat_history`
- Purpose: save chat history to file
- Highlights:
  - Ensure history manager is initialized
  - Call history manager’s save method
  - Log save results

#### `_load_chat_history`
- Purpose: load chat history from file
- Highlights:
  - Ensure history manager is initialized
  - Call history manager’s load method
  - Log loaded records

#### `_parse_tool_calls`
- Purpose: parse tool calls from model response
- Highlights:
  - Parse tool calls from an OpenAI response
  - Return the list of tool calls

#### `_parse_tool_content`
- Purpose: convert parsed tool content to tool-call objects
- Highlights:
  - Support multiple matching strategies
  - Handle direct-call format
  - Handle JSON format
  - Handle Python-style calls

### Resource Management and Cleanup

#### `_cleanup_resources`
- Purpose: clean up active resources
- Highlights:
  - Iterate active_resources dictionary
- Call each resource’s cleanup method
  - Log cleanup progress

#### `_on_finish_task`
- Purpose: callback when FINISH_TASK succeeds
- Highlights:
  - Set agent status to complete
  - Log completion

### Special Cases

#### `_handle_non_tool_model_response`
- Purpose: handle responses from models without tool-call support
- Related methods: `_save_chat_history`, `_trigger_assistant_message`, `_on_finish_task`
- Highlights:
  - Record assistant reply
  - Save chat history
  - Trigger assistant message event
  - Invoke completion callback

#### `_handle_potential_loop`
- Purpose: handle potential infinite loops
- Related method: `_save_chat_history`
- Highlights:
  - Log warning
  - Update history
  - Decide final reply
  - Set status to complete

## State Management

BeDelightful uses the AgentState enum to track status:

- **IDLE**: idle
- **RUNNING**: running
- **FINISHED**: finished
- **ERROR**: error
- **INIT**: initialized

State transitions:
```
INIT -> IDLE -> RUNNING -> [FINISHED | ERROR]
```

## Event System

BeDelightful exposes events at key points:

- **BEFORE_LLM_REQUEST**: before sending the LLM request
- **AFTER_LLM_REQUEST**: after receiving the LLM response
- **BEFORE_TOOL_CALL**: before executing a tool call
- **AFTER_TOOL_CALL**: after executing a tool call

## Extensibility

BeDelightful offers multiple extension points:

1. **Tool system**: implement `BaseTool` to add tools
2. **Model adapters**: extend LLMAdapter for new models
3. **Event callbacks**: hook custom logic into events
4. **Prompt handling**: customize agent behavior via dynamic prompts

## Example Execution Flow

1. User asks: “Find the latest research on climate change.”
2. BeDelightful initializes the environment and sets status to RUNNING.
3. It sends the request to the LLM and receives tool calls.
4. LLM suggests calling `bing_search` to fetch research.
5. BeDelightful executes `bing_search`.
6. Adds results to chat history.
7. Continues requesting with the search results.
8. LLM might suggest `browser_use` to visit a page.
9. BeDelightful executes `browser_use`.
10. Loop continues until `finish_task` or max iterations.
11. BeDelightful cleans up resources and returns the final result.

## Best Practices and Notes

1. **Resource management**: ensure all resources are registered in `active_resources` and cleaned up.
2. **Error handling**: capture exceptions in tool execution to avoid stopping the agent flow.
3. **State tracking**: use the status system to track lifecycle accurately.
4. **Model compatibility**: different models support tool calls differently; handle accordingly.

## Conclusion

BeDelightful is the project’s core component, coordinating subsystems to deliver a robust AI agent. Its design emphasizes extensibility, reliability, and performance, enabling complex multi-step tasks from user queries.