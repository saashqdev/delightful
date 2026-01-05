"""
File utility module providing async file operations
"""

import asyncio
import os
import re
import shutil
from datetime import datetime
from pathlib import Path
from typing import List, Optional, Set

import aiofiles
import aiofiles.os

from agentlang.logger import get_logger
from agentlang.utils.token_estimator import num_tokens_from_string

logger = get_logger(__name__)

# Cache trash command availability check
_has_trash_command = shutil.which("trash") is not None
if _has_trash_command:
    logger.debug("Detected installed trash command; will prefer trash for deletion")
else:
    logger.debug("No trash command detected; will use standard library deletion")

async def safe_delete(path: Path):
    """
    Safely delete file or directory asynchronously.

    Tries using the trash command first (if available).
    If trash is unavailable or fails, uses aiofiles for deletion.
    For recursive directory removal, falls back to asyncio.to_thread(shutil.rmtree).

    Args:
        path: Path object to the file or directory to delete.

    Raises:
        OSError: If an OS-related error occurs during deletion.
        RuntimeError: If trash command fails and fallback is unsuccessful.
        Exception: Other unexpected errors.
    """
    # Use aiofiles.os.path.exists to check if path exists
    if not await aiofiles.os.path.exists(path):
        logger.warning(f"Attempting to delete non-existent path: {path}")
        return # Path doesn't exist, no action needed

    trash_failed = False
    try:
        if _has_trash_command:
            # Try to use trash command asynchronously
            process = await asyncio.create_subprocess_exec(
                "trash", str(path),
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE
            )
            stdout, stderr = await process.communicate()

            if process.returncode == 0:
                logger.info(f"Path moved to trash via trash command: {path}")
                return
            else:
                error_message = stderr.decode().strip() if stderr else "Unknown error"
                logger.warning(f"Failed to delete {path} using trash command (return code: {process.returncode}): {error_message}. Falling back to aiofiles/stdlib deletion.")
                trash_failed = True

        # If no trash command or trash command failed, use aiofiles or stdlib deletion
        if trash_failed or not _has_trash_command:
            # Use aiofiles.os.path.isfile to check if it's a file
            if await aiofiles.os.path.isfile(path):
                # Use aiofiles.os.remove to delete file
                await aiofiles.os.remove(path)
                logger.info(f"File deleted via aiofiles: {path}")
            # Use aiofiles.os.path.isdir to check if it's a directory
            elif await aiofiles.os.path.isdir(path):
                # For directories, aiofiles doesn't have rmtree, we still use shutil.rmtree + asyncio.to_thread
                try:
                    # Try using aiofiles to delete empty directory (if need to distinguish empty/non-empty)
                    # await aiofiles.os.rmdir(path)
                    # logger.info(f"Empty directory deleted via aiofiles: {path}")
                    # But usually using rmtree directly is simpler, it can also handle empty directories
                    await asyncio.to_thread(shutil.rmtree, path)
                    logger.info(f"Directory deleted via shutil.rmtree (async thread): {path}")
                except OSError as rmtree_error:
                    # If rmtree fails (e.g. permission issues)
                    logger.error(f"Failed to delete directory {path} using shutil.rmtree: {rmtree_error}")
                    raise # Re-raise exception
            else:
                # Handle symbolic links or other types of filesystem objects
                try:
                    # Try using aiofiles.os.remove (usually works for symbolic links)
                    await aiofiles.os.remove(path)
                    logger.info(f"Path (possibly symbolic link) deleted via aiofiles: {path}")
                except OSError as e:
                    logger.error(f"Cannot determine path type or deletion via aiofiles failed: {path}, error: {e}")
                    raise # Re-raise exception

    except OSError as e:
        # Catch OS errors that aiofiles or shutil.rmtree may throw
        logger.exception(f"OS error occurred when async deleting path {path}: {e}")
        raise
    except Exception as e:
        # Catch errors that asyncio.create_subprocess_exec or other unexpected errors may throw
        logger.exception(f"Unexpected error occurred when async deleting path {path}: {e}")
        raise


async def clear_directory_contents(directory_path: Path) -> bool:
    """
    Async clear all contents (files and subdirectories) in specified directory but preserve the directory itself.

    Concurrently deletes items under directory to improve efficiency.

    Args:
        directory_path: Directory path to clear contents from.

    Returns:
        bool: Whether operation completed successfully
    """
    try:
        # Async check if directory exists
        if not await aiofiles.os.path.exists(directory_path):
            logger.info(f"Directory {directory_path} does not exist, no cleaning needed")
            return True  # Considered successful because target state (empty directory or non-existent) is satisfied

        if not await aiofiles.os.path.isdir(directory_path):
            logger.error(f"Provided path is not a directory: {directory_path}")
            return False

        logger.info(f"Starting async clearing of directory contents: {directory_path}")
        items_deleted = 0
        items_failed = 0
        # Use asyncio.gather to concurrently execute deletions
        tasks = []
        item_paths = []  # For error logging purposes

        # Note: iterdir is synchronous, but executing before creating async tasks is acceptable
        # For very large directories, consider async iterators like aiofiles.os.scandir
        for item in directory_path.iterdir():
            tasks.append(asyncio.create_task(safe_delete(item)))
            item_paths.append(item)  # Record path when creating task

        # Wait for all deletion tasks to complete
        results = await asyncio.gather(*tasks, return_exceptions=True)

        # Count results
        for i, result in enumerate(results):
            item_path = item_paths[i]  # Get path from previously saved list
            if isinstance(result, Exception):
                logger.warning(f"Encountered issue while clearing {item_path}: {result}")
                items_failed += 1
            elif result is False:
                logger.warning(f"Failed to clear {item_path}")
                items_failed += 1
            else:
                items_deleted += 1

        if items_failed == 0:
            logger.info(f"Successfully cleared {items_deleted} items in {directory_path} directory asynchronously")
            return True
        else:
            logger.warning(f"Async clearing of {directory_path} directory complete, succeeded: {items_deleted}, failed: {items_failed}")
            return items_failed == 0  # Only return True if all succeeded

    except Exception as e:
        logger.error(f"Unexpected error occurred while asynchronously clearing {directory_path} directory contents: {e}", exc_info=True)
        return False


async def ensure_directory(directory_path: Path) -> bool:
    """
    Ensure directory exists, create if it does not

    Args:
        directory_path: Directory path to ensure exists

    Returns:
        bool: Whether operation succeeded
    """
    try:
        if await aiofiles.os.path.exists(directory_path):
            if await aiofiles.os.path.isdir(directory_path):
                return True  # Directory already exists
            else:
                logger.error(f"Path exists but is not a directory: {directory_path}")
                return False  # Path exists but is not a directory

        # Create directory (including any necessary intermediate directories)
        await asyncio.to_thread(os.makedirs, directory_path, exist_ok=True)
        logger.info(f"Directory created: {directory_path}")
        return True

    except Exception as e:
        logger.error(f"Error creating directory {directory_path}: {e}")
        return False


async def list_files(directory_path: Path, recursive: bool = False,
                    pattern: Optional[str] = None) -> List[Path]:
    """
    Asynchronously list files in directory

    Args:
        directory_path: Directory path to list files from
        recursive: Whether to recursively list files in subdirectories
        pattern: File name matching pattern (supports glob syntax)

    Returns:
        List[Path]: List of file paths
    """
    if not directory_path.exists() or not directory_path.is_dir():
        logger.warning(f"Directory does not exist or is not a directory: {directory_path}")
        return []

    result = []

    try:
        if recursive:
            # Recursively find all files
            if pattern:
                paths = list(directory_path.glob(f"**/{pattern}"))
                # Only return files, not directories
                result = [p for p in paths if p.is_file()]
            else:
                # Use rglob(*) to recursively find all files
                result = [p for p in directory_path.rglob("*") if p.is_file()]
        else:
            # Only search in current directory
            if pattern:
                paths = list(directory_path.glob(pattern))
                result = [p for p in paths if p.is_file()]
            else:
                result = [p for p in directory_path.iterdir() if p.is_file()]

        return result
    except Exception as e:
        logger.error(f"Error listing files in directory {directory_path}: {e}")
        return []


def generate_safe_filename(name: str) -> str:
    """
    Generate safe filename, handle characters not supported by various operating systems
    Note: This function only handles filename name, not including file extension suffix

    Args:
        name: Original text content

    Returns:
        str: Safe filename after processing
    """
    if not name:
        return "unnamed_file"

    # 1. Remove common unsupported characters (Windows: \ / : * ? " < > |) (Unix: /)
    # Replace with underscore
    safe_name = re.sub(r'[\\/:*?"<>|]', '_', name)

    # 2. Replace consecutive whitespace characters with single underscore
    safe_name = re.sub(r'\s+', '_', safe_name)

    # 3. Remove control characters and other non-printable characters
    safe_name = re.sub(r'[\x00-\x1f\x7f-\x9f]', '', safe_name)

    # 4. Remove problematic leading and trailing characters
    safe_name = safe_name.strip('._-')

    # 5. Ensure not empty string
    if not safe_name:
        return "webpage"

    # 6. Avoid Windows reserved filenames (CON, PRN, AUX, NUL, COM1-9, LPT1-9)
    reserved_names = ['CON', 'PRN', 'AUX', 'NUL'] + [f'COM{i}' for i in range(1, 10)] + [f'LPT{i}' for i in range(1, 10)]
    if safe_name.upper() in reserved_names:
        safe_name = f"{safe_name}_file"

    # 7. Limit filename length to avoid path too long issues
    safe_name = safe_name[:32]

    return safe_name


def generate_safe_filename_with_timestamp(name: str) -> str:
    """
    Generate safe filename, handle characters not supported by various operating systems
    Use short format timestamp as suffix, making filename sortable by time order
    Note: This function only handles filename name, not including file extension suffix

    Args:
        name: Original text content

    Returns:
        str: Safe filename after processing
    """
    # First generate safe filename through base function
    safe_name = generate_safe_filename(name)

    # Add short format timestamp to make filename sortable by time order
    now = datetime.now()
    # Use last two digits of year + month/day/hour/minute/second + first two digits of milliseconds, total 12 characters
    timestamp = f"{now.year % 100:02d}{now.month:02d}{now.day:02d}{now.hour:02d}{now.minute:02d}{now.second:02d}{now.microsecond // 10000:02d}"

    # Otherwise add timestamp
    return f"{safe_name}_{timestamp}"

# Define text/code file extensions set
TEXT_FILE_EXTENSIONS: Set[str] = {
    # Programming Languages
    ".py", ".java", ".c", ".cpp", ".h", ".cs", ".go", ".rs", ".swift", ".kt",
    ".js", ".ts", ".jsx", ".tsx", ".vue", ".rb", ".php", ".pl", ".sh", ".bat",
    # Web Development
    ".html", ".htm", ".css", ".scss", ".less", ".json", ".yaml", ".yml", ".xml",
    # Data & Configuration
    ".csv", ".ini", ".toml", ".sql", ".env", ".xlsx", ".xls",
    # Documentation & Text
    ".md", ".txt", ".rst", ".tex", ".log", ".doc", ".docx",
    # Other common text-based formats
    ".ipynb" # Jupyter notebooks are JSON but often line-counted
}

# Define binary file extensions set
BINARY_FILE_EXTENSIONS: Set[str] = {
    # Images
    ".png", ".jpg", ".jpeg", ".gif", ".bmp", ".tiff", ".webp", ".ico", ".svg",
    # Audio
    ".mp3", ".wav", ".ogg", ".flac", ".aac", ".m4a",
    # Video
    ".mp4", ".avi", ".mov", ".wmv", ".flv", ".mkv", ".webm",
    # Archives
    ".zip", ".rar", ".gz", ".tar", ".7z",
    # Documents
    ".pdf", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx",
    # Executables
    ".exe", ".dll", ".so", ".bin",
    # Other binary formats
    ".db", ".sqlite", ".pyc", ".class"
}

def is_text_file(file_path: Path) -> bool:
    """Check if file is text/code file"""
    if file_path.name == "Dockerfile":
        return True
    ext = file_path.suffix.lower()
    return ext in TEXT_FILE_EXTENSIONS or (
        ext and ext not in BINARY_FILE_EXTENSIONS and not ext.startswith('.')
    )

def format_file_size(size: int) -> str:
    """Format file size"""
    if size < 0: return "Invalid size"
    for unit in ["B", "KB", "MB", "GB"]:
        if size < 1024:
            # For B and KB, keep as integer; for MB and above, keep one decimal place
            if unit in ["B", "KB"]:
                return f"{int(size)}{unit}"
            return f"{size:.1f}{unit}"
        size /= 1024
    return f"{size:.1f}TB"

def count_file_lines(file_path: Path) -> Optional[int]:
    """Count file lines"""
    try:
        # Optimization: for large files, don't actually read all lines
        if file_path.stat().st_size > 10 * 1024 * 1024: # Don't count if over 10MB
             return None
        with file_path.open("r", encoding="utf-8", errors='ignore') as f:
            return sum(1 for _ in f)
    except Exception as e:
        logger.debug(f"Failed to count file lines: {file_path}, error: {e}")
        return None

def count_file_tokens(file_path: Path) -> Optional[int]:
    """Count file token count"""
    try:
        with file_path.open("r", encoding="utf-8", errors='ignore') as f:
            content = f.read()
            return num_tokens_from_string(content)
    except Exception as e:
        logger.debug(f"Failed to count file tokens: {file_path}, error: {e}")
        return None

def get_file_info(file_path: str) -> str:
    """Get file information including size, line count, token count and modification time"""
    try:
        path = Path(file_path)
        if not path.exists():
            return f"{file_path} (file does not exist)"

        stat_result = path.stat()
        file_size = stat_result.st_size
        size_str = format_file_size(file_size)

        # Get modification time
        last_modified = stat_result.st_mtime
        modified_time = datetime.fromtimestamp(last_modified).strftime("%Y-%m-%d %H:%M:%S")

        # Collect attributes
        attributes = [size_str]

        # For text files calculate line count and token count
        if is_text_file(path):
            line_count = count_file_lines(path)
            if line_count is not None:
                attributes.append(f"{line_count} lines")

            token_count = count_file_tokens(path)
            if token_count is not None:
                attributes.append(f"{token_count} tokens")

        attributes_str = ", ".join(attributes)
        return f"{file_path} ({attributes_str}, last modified: {modified_time})"
    except Exception as e:
        logger.debug(f"Failed to get file info: {file_path}, error: {e}")
        return file_path

def get_file_metadata(file_path: str) -> dict:
    """
    Get file metadata information, return as dictionary

    Returns:
        Dictionary containing the following fields:
        - exists: Whether file exists
        - size: File size (bytes)
        - size_formatted: Formatted file size
        - is_text: Whether file is text file
        - line_count: Line count (text files only)
        - token_count: Token count (text files only)
        - last_modified: Last modification timestamp
        - modified_time: Formatted modification time
    """
    try:
        path = Path(file_path)
        result = {
            "exists": path.exists(),
            "path": str(path),
        }

        if not result["exists"]:
            return result

        stat_result = path.stat()
        result["size"] = stat_result.st_size
        result["size_formatted"] = format_file_size(result["size"])
        result["last_modified"] = stat_result.st_mtime
        result["modified_time"] = datetime.fromtimestamp(stat_result.st_mtime).strftime("%Y-%m-%d %H:%M:%S")

        is_text = is_text_file(path)
        result["is_text"] = is_text

        if is_text:
            result["line_count"] = count_file_lines(path)
            result["token_count"] = count_file_tokens(path)

        return result
    except Exception as e:
        logger.debug(f"Failed to get file metadata: {file_path}, error: {e}")
        return {"exists": False, "path": file_path, "error": str(e)}
