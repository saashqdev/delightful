# BeDelightful Event System Architecture Guide

## Overview

The BeDelightful event system is an event-driven architecture based on the publish-subscribe pattern, designed to achieve decoupling and communication between various components during Agent execution. The event system consists of two layers:

1. **Foundation Layer (agentlang)**: Provides core event type definitions and event dispatching mechanisms
2. **Application Layer (app)**: Provides specific business event implementations and listener services

This event system enables BeDelightful to implement a highly scalable modular design, where listeners can respond to various events in the Agent lifecycle, such as file operations, task completion, LLM interactions, etc.

## 1. Event System Foundation Architecture (agentlang)

### 1.1 Core Event Types (EventType)

`agentlang/event/event.py` defines all event types supported by the system:

```python
class EventType(str, Enum):
    """Event type enumeration"""
    # Agent lifecycle events
    BEFORE_INIT = "before_init"            # Before initialization event
    AFTER_INIT = "after_init"              # After initialization event
    AGENT_SUSPENDED = "agent_suspended"    # Agent termination event
    MAIN_AGENT_FINISHED = "main_agent_finished"  # Main agent execution completed event
    
    # Safety check events
    BEFORE_SAFETY_CHECK = "before_safety_check"  # Before safety check event
    AFTER_SAFETY_CHECK = "after_safety_check"    # After safety check event
    
    # User interaction events
    AFTER_CLIENT_CHAT = "after_client_chat"      # After client chat event
    
    # LLM interaction events
    BEFORE_LLM_REQUEST = "before_llm_request"    # Before LLM request event
    AFTER_LLM_REQUEST = "after_llm_request"      # After LLM request event
    
    # Tool invocation events
    BEFORE_TOOL_CALL = "before_tool_call"        # Before tool call event
    AFTER_TOOL_CALL = "after_tool_call"          # After tool call event
    
    # File operation events
    FILE_CREATED = "file_created"                # File creation event
    FILE_UPDATED = "file_updated"                # File update event
    FILE_DELETED = "file_deleted"                # File deletion event
    
    # Error handling events
    ERROR = "error"                              # Error event
```

### 1.2 Event Base Class (Event)

The event base class defines the basic structure of events:

```python
class Event(Generic[T]):
    def __init__(self, event_type: EventType, data: BaseEventData):
        self._event_type = event_type
        self._data = data
        
    @property
    def event_type(self) -> EventType:
        return self._event_type
        
    @property
    def data(self) -> T:
        return self._data
```

### 1.3 Stoppable Event (StoppableEvent)

Some events can interrupt the propagation flow:

```python
class StoppableEvent(Event[T]):
    def __init__(self, event_type: EventType, data: BaseEventData):
        super().__init__(event_type, data)
        self._propagation_stopped = False
        
    def stop_propagation(self) -> None:
        self._propagation_stopped = True
        
    def is_propagation_stopped(self) -> bool:
        return self._propagation_stopped
```

### 1.4 Event Dispatcher (EventDispatcher)

`EventDispatcher` is responsible for event registration and dispatching:

```python
# In agentlang/event/dispatcher.py
class EventDispatcher:
    def __init__(self):
        self._listeners = defaultdict(list)
        
    def add_listener(self, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        self._listeners[event_type].append(listener)
        
    async def dispatch(self, event_type: EventType, data: BaseEventData) -> Event[Any]:
        event = Event(event_type, data)
        for listener in self._listeners.get(event_type, []):
            await asyncio.ensure_future(listener(event))
        return event
        
    async def dispatch_stoppable(self, event_type: EventType, data: BaseEventData) -> StoppableEvent[Any]:
        event = StoppableEvent(event_type, data)
        for listener in self._listeners.get(event_type, []):
            if event.is_propagation_stopped():
                break
            await asyncio.ensure_future(listener(event))
        return event
```

## 2. Application Layer Event System (app)

### 2.1 Event Data Structures

The application layer defines specific event data structures in `app/core/entity/event/event.py`:

```python
# Examples of key event data structures:
class BeforeLlmRequestEventData(BaseEventData):
    """Event data structure before LLM request"""
    model_name: str
    chat_history: List[Dict[str, Any]]
    tools: Optional[List[Dict[str, Any]]] = None
    tool_context: ToolContext

class AfterLlmResponseEventData(BaseEventData):
    """Event data structure after LLM request"""
    model_name: str
    request_time: float
    success: bool
    error: Optional[str] = None
    tool_context: ToolContext
    llm_response_message: ChatCompletionMessage
    show_in_ui: bool = True
```

### 2.2 Base Listener Service

All listener services inherit from `BaseListenerService`, providing common event registration logic:

```python
class BaseListenerService:
    @staticmethod
    def register_event_listener(agent_context: AgentContext, event_type: EventType, 
                             listener: Callable[[Event[Any]], None]) -> None:
        agent_context.add_event_listener(event_type, listener)

    @staticmethod
    def register_listeners(agent_context: AgentContext, 
                        event_listeners: Dict[EventType, Callable[[Event[Any]], None]]) -> None:
        for event_type, listener in event_listeners.items():
            BaseListenerService.register_event_listener(agent_context, event_type, listener)
```

### 2.3 Listener Registration Mechanism

All listeners are uniformly registered in the `setup` method of `AgentDispatcher`:

```python
async def setup(self):
    """Setup Agent context and register listeners"""
    self.agent_context = self.agent_service.create_agent_context(
        stream_mode=False,
        task_id="",
        streams=[StdoutStream()],
        is_main_agent=True,
        sandbox_id=str(config.get("sandbox.id"))
    )

    # Register various listeners
    FileStorageListenerService.register_standard_listeners(self.agent_context)
    TodoListenerService.register_standard_listeners(self.agent_context)
    FinishTaskListenerService.register_standard_listeners(self.agent_context)
    StreamListenerService.register_standard_listeners(self.agent_context)
    RagListenerService.register_standard_listeners(self.agent_context)
    FileListenerService.register_standard_listeners(self.agent_context)
    CostLimitListenerService.register_standard_listeners(self.agent_context)
```

### 2.4 Specific Listener Service Implementation

Each listener service implements corresponding functionality. Using `FileStorageListenerService` as an example:

```python
class FileStorageListenerService:
    @staticmethod
    def register_standard_listeners(agent_context: AgentContext) -> None:
        # Create mapping from event types to handler functions
        event_listeners = {
            EventType.FILE_CREATED: FileStorageListenerService._handle_file_event,
            EventType.FILE_UPDATED: FileStorageListenerService._handle_file_event,
            EventType.FILE_DELETED: FileStorageListenerService._handle_file_deleted,
            EventType.MAIN_AGENT_FINISHED: FileStorageListenerService._handle_main_agent_finished
        }

        # Use base class method to batch register listeners
        BaseListenerService.register_listeners(agent_context, event_listeners)
        
    @staticmethod
    async def _handle_file_event(event: Event[FileEventData]) -> None:
        # Implementation for handling file creation and update events...
```

## 3. Event Support in Agent Context

The `AgentContext` class provides core event mechanism functionality:

```python
class AgentContext(BaseContext, AgentContextInterface):
    def add_event_listener(self, event_type: EventType, listener: Callable[[Event[Any]], None]) -> None:
        """Add event listener"""
        self.agent_common_context._event_dispatcher.add_listener(event_type, listener)
        
    async def dispatch_event(self, event_type: EventType, data: BaseEventData) -> Event[Any]:
        """Dispatch event"""
        return await self.agent_common_context._event_dispatcher.dispatch(event_type, data)
        
    async def dispatch_stoppable_event(self, event_type: EventType, data: BaseEventData) -> StoppableEvent[Any]:
        """Dispatch stoppable event"""
        return await self.agent_common_context._event_dispatcher.dispatch_stoppable(event_type, data)
```

## 4. Main Listener Service Functionality Overview

BeDelightful includes various listener services, each responsible for handling specific types of events:

| Listener Service | Main Functionality |
|------------------|-------------------|
| FileStorageListenerService | Handles file events, uploads files to storage service |
| TodoListenerService | Handles todo item addition, updates, and deletion |
| FinishTaskListenerService | Handles task completion events, performs subsequent cleanup |
| StreamListenerService | Handles streaming output events, pushes messages to clients |
| RagListenerService | Handles retrieval-augmented generation related events |
| FileListenerService | Handles file system change monitoring |
| CostLimitListenerService | Monitors and limits API call costs |

## 5. Extending the Event System

To add new event handling, follow these recommended steps:

1. If you need a new event type, add it to the `EventType` enumeration in `agentlang/event/event.py`
2. Define the corresponding event data structure in `app/core/entity/event/event.py`
3. Create a new listener service class, inheriting from or referencing `BaseListenerService`
4. Implement event handler methods
5. Register the new listener service in `AgentDispatcher.setup()`

## Conclusion

BeDelightful's event system provides a flexible and extensible way to handle various state changes and interactions in the system. Through event-driven architecture, various components can communicate without tight coupling, making the system more modular and maintainable.

The layered design of the event system (agentlang provides the foundation, app provides business implementation) also reflects good architectural practices of separation of concerns, making the system easier to understand and extend.
