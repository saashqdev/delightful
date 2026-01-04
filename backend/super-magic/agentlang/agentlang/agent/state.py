# Define agent state enum
import enum


class AgentState(enum.Enum):
    """Agent state enum"""

    IDLE = "idle"  # Idle state
    RUNNING = "running"  # Running
    FINISHED = "finished"  # Successfully completed state
    ERROR = "error"  # Error state
