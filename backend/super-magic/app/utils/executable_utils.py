"""
Utility functions for executable commands
"""
import os
import sys

from agentlang.logger import get_logger

logger = get_logger(__name__)

def get_executable_command():
    """Get executable command, compatible with both frozen and non-frozen programs
    
    Returns:
        list: Executable command list, frozen program returns [executable_path], non-frozen returns [python_interpreter, script_path]
    """
    # Check if this is a PyInstaller packaged program
    is_frozen = getattr(sys, 'frozen', False)

    if is_frozen:
        # If frozen program, use the program itself as executable file
        logger.info(f"Current is frozen program, using program itself as executable: {sys.executable}")
        return [sys.executable]
    else:
        # If non-frozen program, use sys.argv[0] to get the currently executing script
        current_script = os.path.abspath(sys.argv[0])
        logger.info(f"Current is non-frozen program, using sys.argv[0] to get currently executing script: {current_script}")
        return [sys.executable, current_script] 
