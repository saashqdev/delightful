"""
Chat history management API module.

Provides chat history utilities such as packaging and downloading history records.
"""
import os
import shutil
import tempfile

from fastapi import APIRouter, BackgroundTasks, HTTPException
from fastapi.responses import FileResponse

from agentlang.logger import get_logger
from app.paths import PathManager

logger = get_logger(__name__)

router = APIRouter(prefix="/chat-history", tags=["chat_history"])

def remove_file(path: str):
    """Delete a file at the given path."""
    try:
        os.unlink(path)
        logger.info(f"Temporary file removed: {path}")
    except Exception as e:
        logger.error(f"Failed to delete file: {e}")

@router.get("/download")
async def download_chat_history(background_tasks: BackgroundTasks):
    """Package and download the .chat_history directory."""
    try:
        # Check if directory exists
        chat_history_dir = PathManager.get_chat_history_dir()
        if not chat_history_dir.exists() or not chat_history_dir.is_dir():
            logger.error("Chat history directory does not exist")
            raise HTTPException(status_code=404, detail="Chat history directory not found")

        # Create temporary file
        with tempfile.NamedTemporaryFile(delete=False, suffix='.zip') as tmp:
            tmp_path = tmp.name

        logger.info(f"Created temporary file: {tmp_path}")

        # Create zip archive
        archive_path = shutil.make_archive(
            tmp_path.replace('.zip', ''), 
            'zip', 
            root_dir=PathManager.get_project_root(),
            base_dir=PathManager.get_chat_history_dir_name()
        )

        logger.info(f"Created chat history archive: {archive_path}")

        # Add background task to delete the temp file after response completes
        background_tasks.add_task(remove_file, archive_path)

        return FileResponse(
            path=archive_path,
            media_type="application/zip",
            filename="chat_history.zip"
        )
    except Exception as e:
        logger.error(f"Failed to package chat history: {e!s}")
        raise HTTPException(status_code=500, detail=f"Packaging failed: {e!s}")
