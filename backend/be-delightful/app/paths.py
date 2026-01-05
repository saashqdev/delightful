"""
Path-related constants and utility functions, implemented in object-oriented manner
"""

from pathlib import Path
from typing import ClassVar, Optional

from agentlang.paths import PathManager as BasePathManager


class PathManager(BasePathManager):
    """
    Application layer path manager, inherits from base framework and adds application-specific paths
    """

    # Browser storage state file
    _browser_storage_state_file: ClassVar[Optional[Path]] = None

    # Credentials directory
    _credentials_dir_name: ClassVar[str] = ".credentials"
    _credentials_dir: ClassVar[Optional[Path]] = None
    _init_client_message_file: ClassVar[Optional[Path]] = None

    # Project schema directory
    _project_schema_dir_name: ClassVar[str] = ".project_schemas"
    _project_schema_absolute_dir: ClassVar[Optional[Path]] = None

    # Project archive directory
    _project_archive_dir_name: ClassVar[str] = "project_archive"
    _project_archive_info_file_relative_path: ClassVar[Optional[str]] = None
    _project_archive_info_file: ClassVar[Optional[Path]] = None

    @classmethod
    def set_project_root(cls, project_root: Path) -> None:
        """
        Set project root directory and initialize all paths (framework layer + application layer)

        Args:
            project_root: Project root directory path
        """
        super().set_project_root(project_root)

        cls._credentials_dir = cls._project_root.joinpath(cls._credentials_dir_name)
        cls._browser_storage_state_file = cls.get_browser_data_dir() / "storage_state.json"
        cls._init_client_message_file = cls.get_credentials_dir() / "init_client_message.json"
        cls._project_schema_absolute_dir = cls._project_root / cls._project_schema_dir_name
        cls._project_archive_info_file_relative_path = f"{cls._project_schema_dir_name}/project_archive_info.json"
        cls._project_archive_info_file = cls.get_project_schema_absolute_dir() / "project_archive_info.json"

        # Ensure application layer specific directories exist
        cls._ensure_app_directories_exist()

    @classmethod
    def _ensure_app_directories_exist(cls) -> None:
        """Ensure application layer specific directories exist (core directories are guaranteed by framework layer)"""
        if cls._project_root is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")

        # Only create directories managed by application layer
        cls._credentials_dir.mkdir(exist_ok=True)
        cls._project_schema_absolute_dir.mkdir(exist_ok=True)

    # Getter methods for application layer specific paths

    @classmethod
    def get_browser_storage_state_file(cls) -> Path:
        """Get browser storage state file path"""
        if cls._browser_storage_state_file is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._browser_storage_state_file

    @classmethod
    def get_project_archive_dir_name(cls) -> str:
        """Get project archive directory name"""
        return cls._project_archive_dir_name

    @classmethod
    def get_credentials_dir_name(cls) -> str:
        """Get credentials directory name"""
        return cls._credentials_dir_name

    @classmethod
    def get_credentials_dir(cls) -> Path:
        """Get credentials directory path"""
        if cls._credentials_dir is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._credentials_dir

    @classmethod
    def get_init_client_message_file(cls) -> Path:
        """Get initial client message file path"""
        if cls._init_client_message_file is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._init_client_message_file

    @classmethod
    def get_project_schema_dir_name(cls) -> str:
        """Get project schema directory name"""
        return cls._project_schema_dir_name

    @classmethod
    def get_project_schema_absolute_dir(cls) -> Path:
        """Get project schema absolute directory path"""
        if cls._project_schema_absolute_dir is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._project_schema_absolute_dir

    @classmethod
    def get_project_archive_info_file_relative_path(cls) -> str:
        """Get project archive info file relative path"""
        if cls._project_archive_info_file_relative_path is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._project_archive_info_file_relative_path

    @classmethod
    def get_project_archive_info_file(cls) -> Path:
        """Get project archive info file path"""
        if cls._project_archive_info_file is None:
            raise RuntimeError("Must call set_project_root to set project root directory first")
        return cls._project_archive_info_file
