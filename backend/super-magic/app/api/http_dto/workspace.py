from typing import Any, Optional

from pydantic import BaseModel


class WorkspaceInfo(BaseModel):
    """Workspace information model."""

    id: Any  # Can be a number or string
    workspace_name: str
    sort: int


# Request models
class GetWorkspacesRequest(BaseModel):
    """Request model for fetching workspace list."""
    pass


class SaveWorkspaceRequest(BaseModel):
    """Request model for creating or updating a workspace."""

    id: Optional[Any] = None  # Optional, provide when editing
    name: str  # Workspace name


class DeleteWorkspaceRequest(BaseModel):
    """Request model for deleting a workspace."""

    id: Any  # Workspace ID


# Response models
class WorkspaceResponse(BaseModel):
    """Workspace response model."""

    id: Any
