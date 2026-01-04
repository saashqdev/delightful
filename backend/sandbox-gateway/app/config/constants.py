"""
Constant definitions
"""

# Sandbox container labels
SANDBOX_LABEL = "sandbox_id"


AGENT_LABEL = "agent_id"
AGENT_LABEL_PREFIX = "sandbox-agent-"

# Qdrant container labels
QDRANT_LABEL = "qdrant_id"
QDRANT_LABEL_PREFIX = "sandbox-qdrant-"

# WebSocket message types
WS_MESSAGE_TYPE_ERROR = "error"
WS_MESSAGE_TYPE_DATA = "data"
WS_MESSAGE_TYPE_STATUS = "status"

# Container statuses
CONTAINER_STATUS_RUNNING = "running"
CONTAINER_STATUS_STOPPED = "stopped"
CONTAINER_STATUS_EXITED = "exited"
CONTAINER_STATUS_CREATED = "created" 