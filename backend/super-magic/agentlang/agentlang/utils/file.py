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
                    raise # 重新抛出异常

    except OSError as e:
        # 捕获 aiofiles 或 shutil.rmtree 可能抛出的 OS 错误
        logger.exception(f"异步删除路径 {path} 时发生 OS 错误: {e}")
        raise
    except Exception as e:
        # 捕获 asyncio.create_subprocess_exec 或其他意外错误
        logger.exception(f"异步删除路径 {path} 时发生意外错误: {e}")
        raise


async def clear_directory_contents(directory_path: Path) -> bool:
    """
    异步清理指定目录中的所有内容（文件和子目录），但保留目录本身。

    会并发删除目录下的项目以提高效率。

    Args:
        directory_path: 要清空内容的目录路径。

    Returns:
        bool: 操作是否成功完成
    """
    try:
        # 异步检查目录是否存在
        if not await aiofiles.os.path.exists(directory_path):
            logger.info(f"{directory_path} 目录不存在，无需清理")
            return True  # 视为成功，因为目标状态（目录为空或不存在）已满足

        if not await aiofiles.os.path.isdir(directory_path):
            logger.error(f"Provided path is not a directory: {directory_path}")
            return False

        logger.info(f"Starting async clearing of directory contents: {directory_path}")
        items_deleted = 0
        items_failed = 0
        # 使用 asyncio.gather 并发执行删除
        tasks = []
        item_paths = []  # 用于错误日志记录

        # 注意：iterdir 是同步的，但在进入异步任务创建前执行是可接受的
        # 对于非常大的目录，可以考虑异步迭代器，如 aiofiles.os.scandir
        for item in directory_path.iterdir():
            tasks.append(asyncio.create_task(safe_delete(item)))
            item_paths.append(item)  # 在创建任务时记录路径

        # 等待所有删除任务完成
        results = await asyncio.gather(*tasks, return_exceptions=True)

        # 统计结果
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
    确保目录存在，如果不存在则创建

    Args:
        directory_path: 要确保存在的目录路径

    Returns:
        bool: 操作是否成功
    """
    try:
        if await aiofiles.os.path.exists(directory_path):
            if await aiofiles.os.path.isdir(directory_path):
                return True  # Directory already exists
            else:
                logger.error(f"Path exists but is not a directory: {directory_path}")
                return False  # 路径存在但不是目录

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
    异步列出目录中的文件

    Args:
        directory_path: 要列出文件的目录路径
        recursive: 是否递归列出子目录中的文件
        pattern: 文件名匹配模式（支持glob语法）

    Returns:
        List[Path]: 文件路径列表
    """
    if not directory_path.exists() or not directory_path.is_dir():
        logger.warning(f"Directory does not exist or is not a directory: {directory_path}")
        return []

    result = []

    try:
        if recursive:
            # 递归查找所有文件
            if pattern:
                paths = list(directory_path.glob(f"**/{pattern}"))
                # 只返回文件，不返回目录
                result = [p for p in paths if p.is_file()]
            else:
                # 使用rglob(*)递归查找所有文件
                result = [p for p in directory_path.rglob("*") if p.is_file()]
        else:
            # 只在当前目录查找
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
    生成安全的文件名，处理各操作系统不支持的字符
    注意，这个函数只处理文件名的名字，不包含文件扩展名后缀

    Args:
        name: 原始文本内容

    Returns:
        str: 处理后的安全文件名
    """
    if not name:
        return "unnamed_file"

    # 1. 清除常见不支持的字符 (Windows: \ / : * ? " < > |) (Unix: /)
    # 替换为下划线
    safe_name = re.sub(r'[\\/:*?"<>|]', '_', name)

    # 2. 替换连续的空白字符为单个下划线
    safe_name = re.sub(r'\s+', '_', safe_name)

    # 3. 删除控制字符和其他不可打印字符
    safe_name = re.sub(r'[\x00-\x1f\x7f-\x9f]', '', safe_name)

    # 4. 删除可能导致问题的前导和尾随字符
    safe_name = safe_name.strip('._-')

    # 5. 确保不是空字符串
    if not safe_name:
        return "webpage"

    # 6. 避免Windows保留文件名 (CON, PRN, AUX, NUL, COM1-9, LPT1-9)
    reserved_names = ['CON', 'PRN', 'AUX', 'NUL'] + [f'COM{i}' for i in range(1, 10)] + [f'LPT{i}' for i in range(1, 10)]
    if safe_name.upper() in reserved_names:
        safe_name = f"{safe_name}_file"

    # 7. 限制文件名长度，避免路径过长问题
    safe_name = safe_name[:32]

    return safe_name


def generate_safe_filename_with_timestamp(name: str) -> str:
    """
    生成安全的文件名，处理各操作系统不支持的字符
    使用短格式时间戳作为后缀，使文件名可以根据时间顺序排序
    注意，这个函数只处理文件名的名字，不包含文件扩展名后缀

    Args:
        name: 原始文本内容

    Returns:
        str: 处理后的安全文件名
    """
    # 先通过基础函数生成安全文件名
    safe_name = generate_safe_filename(name)

    # 添加短格式时间戳，使文件名按时间顺序排序
    now = datetime.now()
    # 使用年份后两位+月日时分秒+毫秒前两位，共12位
    timestamp = f"{now.year % 100:02d}{now.month:02d}{now.day:02d}{now.hour:02d}{now.minute:02d}{now.second:02d}{now.microsecond // 10000:02d}"

    # 否则添加时间戳
    return f"{safe_name}_{timestamp}"

# 定义文本/代码文件后缀集合
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

# 定义二进制文件后缀集合
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
    """判断文件是否为文本/代码文件"""
    if file_path.name == "Dockerfile":
        return True
    ext = file_path.suffix.lower()
    return ext in TEXT_FILE_EXTENSIONS or (
        ext and ext not in BINARY_FILE_EXTENSIONS and not ext.startswith('.')
    )

def format_file_size(size: int) -> str:
    """格式化文件大小"""
    if size < 0: return "无效大小"
    for unit in ["B", "KB", "MB", "GB"]:
        if size < 1024:
            # 对于 B 和 KB，保留整数；对于 MB 及以上，保留一位小数
            if unit in ["B", "KB"]:
                return f"{int(size)}{unit}"
            return f"{size:.1f}{unit}"
        size /= 1024
    return f"{size:.1f}TB"

def count_file_lines(file_path: Path) -> Optional[int]:
    """计算文件行数"""
    try:
        # 优化：对于大文件，不实际读取所有行
        if file_path.stat().st_size > 10 * 1024 * 1024: # 超过 10MB 不计数
             return None
        with file_path.open("r", encoding="utf-8", errors='ignore') as f:
            return sum(1 for _ in f)
    except Exception as e:
        logger.debug(f"Failed to count file lines: {file_path}, error: {e}")
        return None

def count_file_tokens(file_path: Path) -> Optional[int]:
    """计算文件token数量"""
    try:
        with file_path.open("r", encoding="utf-8", errors='ignore') as f:
            content = f.read()
            return num_tokens_from_string(content)
    except Exception as e:
        logger.debug(f"Failed to count file tokens: {file_path}, error: {e}")
        return None

def get_file_info(file_path: str) -> str:
    """获取文件信息，包括大小、行数、token数和修改时间"""
    try:
        path = Path(file_path)
        if not path.exists():
            return f"{file_path} (文件不存在)"

        stat_result = path.stat()
        file_size = stat_result.st_size
        size_str = format_file_size(file_size)

        # 获取修改时间
        last_modified = stat_result.st_mtime
        modified_time = datetime.fromtimestamp(last_modified).strftime("%Y-%m-%d %H:%M:%S")

        # 收集属性
        attributes = [size_str]

        # 对于文本文件计算行数和token数量
        if is_text_file(path):
            line_count = count_file_lines(path)
            if line_count is not None:
                attributes.append(f"{line_count}行")

            token_count = count_file_tokens(path)
            if token_count is not None:
                attributes.append(f"{token_count}个token")

        attributes_str = ", ".join(attributes)
        return f"{file_path} ({attributes_str}, 最后修改：{modified_time})"
    except Exception as e:
        logger.debug(f"Failed to get file info: {file_path}, error: {e}")
        return file_path

def get_file_metadata(file_path: str) -> dict:
    """
    获取文件的元数据信息，以字典形式返回

    Returns:
        包含以下字段的字典：
        - exists: 文件是否存在
        - size: 文件大小（字节）
        - size_formatted: 格式化的文件大小
        - is_text: 是否为文本文件
        - line_count: 行数（仅文本文件）
        - token_count: token数量（仅文本文件）
        - last_modified: 最后修改时间戳
        - modified_time: 格式化的修改时间
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
