"""
Path-related constants and utility functions implemented using object-oriented approach
"""

from pathlib import Path
from typing import ClassVar, Optional


class PathManager:
    """
    Path manager that provides core and common path access across the project.
    Implemented with static methods, no instantiation required.
    """

    # Static class variables for core directories
    _project_root: ClassVar[Optional[Path]] = None
    _logs_dir_name: ClassVar[str] = "logs"
    _logs_dir: ClassVar[Optional[Path]] = None
    _workspace_dir_name: ClassVar[str] = ".workspace"
    _workspace_dir: ClassVar[Optional[Path]] = None
    _browser_data_dir_name: ClassVar[str] = ".browser"
    _browser_data_dir: ClassVar[Optional[Path]] = None
    _cache_dir_name: ClassVar[str] = "cache"
    _cache_dir: ClassVar[Optional[Path]] = None
    _chat_history_dir_name: ClassVar[str] = ".chat_history"  # Use top-level dot directory as suggested by app layer
    _chat_history_dir: ClassVar[Optional[Path]] = None

    _initialized: ClassVar[bool] = False

    @classmethod
    def set_project_root(cls, project_root: Path) -> None:
        """
        Set the project root and initialize all core paths.

        Args:
            project_root: Project root path
        """
        if cls._initialized:
            return

        cls._project_root = project_root

        # Initialize core paths
        cls._logs_dir = cls._project_root / cls._logs_dir_name
        cls._workspace_dir = cls._project_root / cls._workspace_dir_name
        cls._browser_data_dir = cls._project_root / cls._browser_data_dir_name
        cls._cache_dir = cls._project_root / cls._cache_dir_name
        cls._chat_history_dir = cls._project_root / cls._chat_history_dir_name

        # Ensure required directories exist
        cls._ensure_directories_exist()

        cls._initialized = True

    @classmethod
    def _ensure_directories_exist(cls) -> None:
        """Ensure all core directories exist."""
        if cls._project_root is None:
            raise RuntimeError("set_project_root must be called before accessing paths")

        cls._logs_dir.mkdir(exist_ok=True)
        cls._workspace_dir.mkdir(exist_ok=True)
        cls._browser_data_dir.mkdir(exist_ok=True)
        cls._cache_dir.mkdir(exist_ok=True)
        cls._chat_history_dir.mkdir(exist_ok=True)

    @classmethod
    def get_project_root(cls) -> Path:
        """Get the project root path."""
        if cls._project_root is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._project_root

    @classmethod
    def get_logs_dir_name(cls) -> str:
        """Get the logs directory name."""
        return cls._logs_dir_name

    @classmethod
    def get_logs_dir(cls) -> Path:
        """Get the logs directory path."""
        if cls._logs_dir is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._logs_dir

    @classmethod
    def get_workspace_dir_name(cls) -> str:
        """Get the workspace directory name."""
        return cls._workspace_dir_name

    @classmethod
    def get_workspace_dir(cls) -> Path:
        """Get the workspace directory path."""
        if cls._workspace_dir is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._workspace_dir

    @classmethod
    def get_browser_data_dir_name(cls) -> str:
        """Get the browser data directory name."""
        return cls._browser_data_dir_name

    @classmethod
    def get_browser_data_dir(cls) -> Path:
        """Get the browser data directory path."""
        if cls._browser_data_dir is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._browser_data_dir

    @classmethod
    def get_cache_dir_name(cls) -> str:
        """Get the cache directory name."""
        return cls._cache_dir_name

    @classmethod
    def get_cache_dir(cls) -> Path:
        """Get the cache directory path."""
        if cls._cache_dir is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._cache_dir

    @classmethod
    def get_chat_history_dir_name(cls) -> str:
        """Get the chat history directory name."""
        return cls._chat_history_dir_name

    @classmethod
    def get_chat_history_dir(cls) -> Path:
        """Get the chat history directory path."""
        if cls._chat_history_dir is None:
            raise RuntimeError("set_project_root must be called before accessing paths")
        return cls._chat_history_dir
