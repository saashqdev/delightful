"""
Credential management utility module for exporting and loading credentials
"""

import json
import os
from typing import Any, Dict, Optional

from agentlang.logger import get_logger
from app.core.context.agent_context import AgentContext

logger = get_logger(__name__)

async def export_credentials(agent_context: AgentContext, file_path: str = "config/upload_credentials.json") -> bool:
    """
    Export upload credentials from AgentContext to file

    Args:
        agent_context: Agent context object
        file_path: Export file path, defaults to config/upload_credentials.json

    Returns:
        bool: Whether export was successful
    """
    try:
        # Check if credentials exist
        credentials = agent_context.storage_credentials
        if not credentials:
            logger.warning("No storage credentials in AgentContext, cannot export")
            return False

        # Get sandbox ID
        sandbox_id = agent_context.get_sandbox_id()
        if not sandbox_id:
            logger.warning("Sandbox ID not set in AgentContext")

        # Ensure directory exists
        os.makedirs(os.path.dirname(file_path), exist_ok=True)

        # Convert credentials to dictionary
        if hasattr(credentials, "model_dump"):
            # Use Pydantic's model_dump method
            creds_dict = credentials.model_dump()
        else:
            # Try using __dict__ attribute
            creds_dict = {k: v for k, v in credentials.__dict__.items()
                        if not k.startswith('_') and not callable(v)}

        # Create output structure
        output_data = {
            "upload_config": creds_dict
        }

        # Add sandbox ID (if exists)
        if sandbox_id:
            output_data["sandbox_id"] = sandbox_id

        # Add organization code (if exists)
        organization_code = agent_context.get_organization_code()
        if organization_code:
            output_data["organization_code"] = organization_code

        # Write to file
        with open(file_path, "w", encoding="utf-8") as f:
            json.dump(output_data, f, ensure_ascii=False, indent=2)

        sandbox_info = f"Sandbox ID: {sandbox_id}" if sandbox_id else "Sandbox ID not set"
        logger.info(f"Upload credentials exported to file: {file_path} ({sandbox_info})")
        return True

    except Exception as e:
        logger.error(f"Failed to export upload credentials: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return False

async def load_credentials(file_path: str = "config/upload_credentials.json") -> Optional[Dict[str, Any]]:
    """
    Load upload credentials from file

    Args:
        file_path: Credential file path

    Returns:
        Optional[Dict[str, Any]]: Credential data, returns None if loading fails
    """
    try:
        if not os.path.exists(file_path):
            logger.warning(f"Credential file does not exist: {file_path}")
            return None

        with open(file_path, "r", encoding="utf-8") as f:
            credentials_data = json.load(f)

        if not credentials_data.get("upload_config"):
            logger.error(f"Credential file format is incorrect, missing upload_config field: {file_path}")
            return None

        # Check if sandbox ID exists
        if not credentials_data.get("sandbox_id"):
            logger.error(f"Credential file missing required sandbox_id field: {file_path}")
            return None

        logger.info(f"Upload credentials loaded from file: {file_path}, Sandbox ID: {credentials_data.get('sandbox_id')}")

        result = credentials_data["upload_config"]

        # Add sandbox_id to result
        result["sandbox_id"] = credentials_data["sandbox_id"]

        # Add organization_code (if exists)
        if "organization_code" in credentials_data:
            result["organization_code"] = credentials_data["organization_code"]

        return result

    except Exception as e:
        logger.error(f"Failed to load upload credentials: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return None
