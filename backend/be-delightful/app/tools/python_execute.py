import asyncio
import json
import os
from typing import Any, Dict, Optional

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.message.server_message import DisplayType, FileContent, ToolDetail
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class PythonExecuteParams(BaseToolParams):
    """Python code execution parameters

    Note: code and file_path parameters are mutually exclusive, cannot be provided simultaneously, and cannot both be empty:
    - When code parameter is provided, the code string will be executed
    - When file_path parameter is provided, the Python file at the specified path will be executed
    """
    code: Optional[str] = Field(
        None,
        description="Python code string to execute, mutually exclusive with file_path, cannot be provided simultaneously"
    )
    file_path: Optional[str] = Field(
        None,
        description="Path to Python file to execute, mutually exclusive with code, cannot be provided simultaneously"
    )
    cwd: Optional[str] = Field(
        None,
        description="Working directory for command execution (optional)"
    )
    timeout: int = Field(
        60,
        description="Execution timeout (seconds), default 60 seconds"
    )
    args: Optional[str] = Field(
        None,
        description="Command line arguments to pass to Python script (optional)"
    )


@tool()
class PythonExecute(WorkspaceGuardTool[PythonExecuteParams]):
    """
    Tool for executing Python code, supports execution of code strings and file paths

    Use cases:
    - Execute Python code strings
    - Execute Python script files
    - Perform data processing
    - Test feature implementations

    Notes:
    - code and file_path parameters are mutually exclusive, cannot be provided simultaneously
    - Code will be executed in a new Python interpreter process
    - Supports setting working directory
    - Can set timeout duration
    - Can pass command line arguments to scripts
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: PythonExecuteParams
    ) -> ToolResult:
        """
        Execute provided Python code or script file with timeout limit.

        Args:
            tool_context: Tool context
            params: Parameter object containing code or file path to execute and timeout settings

        Returns:
            ToolResult: Contains execution output or error messages.
        """
        # Validate parameters
        if not params.code and not params.file_path:
            return ToolResult(error="Parameter error: must provide either code or file_path parameter")

        if params.code and params.file_path:
            return ToolResult(error="Parameter error: code and file_path parameters cannot be provided simultaneously, please choose only one method to execute Python code")

        try:
            # Handle working directory
            work_dir = self.base_dir
            if params.cwd:
                # Use parent class method to get safe working directory path
                cwd_path, error = self.get_safe_path(params.cwd)
                if error:
                    return ToolResult(error=f"Working directory error: {error}")
                work_dir = cwd_path

            # Ensure working directory exists
            if not work_dir.exists():
                return ToolResult(
                    error=f"Working directory error: directory does not exist - {work_dir}"
                )

            # Prepare execution command based on parameter type
            if params.file_path:
                # Validate file path safety
                file_path, error = self.get_safe_path(params.file_path)
                if error:
                    return ToolResult(error=f"File path error: {error}")

                # Ensure file exists
                if not file_path.exists():
                    return ToolResult(
                        error=f"File error: Python file does not exist - {file_path}"
                    )

                # Build command: python file_path [args]
                cmd_args = ["python", str(file_path)]
                if params.args:
                    cmd_args.extend(params.args.split())

                logger.debug(f"Executing Python file: {file_path}, working directory: {work_dir}")

            else:
                # Execute code string
                # Create temporary Python file
                temp_file = work_dir / "_temp_code.py"
                try:
                    with open(temp_file, "w", encoding="utf-8") as f:
                        f.write(params.code)

                    # Build command: python _temp_code.py [args]
                    cmd_args = ["python", str(temp_file)]
                    if params.args:
                        cmd_args.extend(params.args.split())

                    logger.debug(f"Executing Python code string, working directory: {work_dir}")

                except Exception as e:
                    logger.error(f"Failed to create temporary Python file: {e}")
                    return ToolResult(
                        error=f"Temporary file error: Unable to create temporary Python file - {e}"
                    )

            # Create process
            try:
                env_vars = {
                    **os.environ,
                    'PYTHONIOENCODING': 'utf-8',
                    'MPLCONFIGDIR': '/root/.config/matplotlib',
                    'LC_ALL': 'C.UTF-8',
                    'LANG': 'C.UTF-8'
                }

                process = await asyncio.create_subprocess_exec(
                    *cmd_args,
                    stdout=asyncio.subprocess.PIPE,
                    stderr=asyncio.subprocess.PIPE,
                    cwd=str(work_dir),
                    env=env_vars,
                )

                # Wait for process to complete with timeout
                stdout, stderr = await asyncio.wait_for(
                    process.communicate(), timeout=params.timeout
                )
                stdout_str = stdout.decode(errors='replace').strip() if stdout else ""
                stderr_str = stderr.decode(errors='replace').strip() if stderr else ""
                exit_code = process.returncode

                # Build result message to make it more structured and human-readable
                execution_type = "File execution" if params.file_path else "Code execution"
                execution_target = params.file_path if params.file_path else "code snippet"

                # Build more friendly and structured result message
                if exit_code == 0:
                    status = "Success"
                    result_sections = []

                    # Add basic information and output content
                    header = f"{execution_type}: {execution_target}"
                    if params.args:
                        header += f" (arguments: {params.args})"

                    result_sections.append(header)
                    result_sections.append(f"Status: {status}")

                    # Add output content (if any)
                    if stdout_str:
                        result_sections.append(f"Output:\n{stdout_str}")
                    else:
                        result_sections.append("Output: (none)")

                    result_message = "\n".join(result_sections)
                else:
                    status = f"Failed (exit code: {exit_code})"
                    result_sections = []

                    # Add basic information
                    header = f"{execution_type}: {execution_target}"
                    if params.args:
                        header += f" (arguments: {params.args})"

                    result_sections.append(header)
                    result_sections.append(f"Status: {status}")

                    # Add output and error messages
                    if stdout_str:
                        result_sections.append(f"Standard output:\n{stdout_str}")

                    if stderr_str:
                        result_sections.append(f"Error message:\n{stderr_str}")

                    result_message = "\n".join(result_sections)

                # Build detailed information JSON and save to system field (for internal system use, not directly displayed to users)
                execution_info = {
                    "command": " ".join(cmd_args),
                    "execution_type": "file" if params.file_path else "code",
                    "target": params.file_path if params.file_path else "code_snippet",
                    "cwd": str(work_dir),
                    "args": params.args,
                    "stdout": stdout_str,
                    "stderr": stderr_str,
                    "exit_code": exit_code,
                    "success": exit_code == 0
                }

                system_info = json.dumps(execution_info, ensure_ascii=False)

                if params.code and temp_file.exists():
                    try:
                        # Delete temporary file
                        os.unlink(temp_file)
                    except Exception as e:
                        logger.warning(f"Failed to delete temporary file: {e}")

                if exit_code == 0:
                    result = ToolResult(
                        content=result_message,
                        system=system_info,
                    )
                else:
                    result = ToolResult(
                        error=result_message,
                    )
                return result

            except asyncio.TimeoutError:
                # Timeout, force terminate process
                if process.returncode is None:
                    try:
                        process.kill()
                        await process.wait()
                    except:
                        pass

                if params.code and temp_file.exists():
                    try:
                        # Delete temporary file
                        os.unlink(temp_file)
                    except Exception as e:
                        logger.warning(f"Failed to delete temporary file: {e}")

                execution_type = "File execution" if params.file_path else "Code execution"
                execution_target = params.file_path if params.file_path else "code snippet"

                timeout_message = (
                    f"{execution_type}: {execution_target}\n"
                    f"Status: Execution timeout ({params.timeout} seconds)\n"
                    f"Reason: Code execution time exceeded the set time limit"
                )

                return ToolResult(
                    error=timeout_message
                )

        except Exception as e:
            logger.exception(f"Error executing Python code: {e}")
            error_message = (
                f"Execution error\n"
                f"Error type: {type(e).__name__}\n"
                f"Error details: {e!s}"
            )
            return ToolResult(
                error=error_message
            )

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get corresponding ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution parameter dictionary

        Returns:
            Optional[ToolDetail]: Tool detail object, may be None
        """
        if not result.ok and not result.error:
            return None

        # Get code content
        code = ""
        if arguments.get("file_path"):
            # If file execution, read file content
            file_path, error = self.get_safe_path(arguments.get("file_path"))
            if not error and file_path.exists():
                try:
                    with open(file_path, "r", encoding="utf-8") as f:
                        code = f.read()
                except Exception as e:
                    logger.warning(f"Failed to read file content: {e}")
                    code = f"# Unable to read file content: {file_path}\n# Error: {e}"
            else:
                code = f"# File does not exist or path is incorrect: {arguments.get('file_path')}"
        elif arguments.get("code"):
            # If code execution, use code parameter directly
            code = arguments.get("code")
        else:
            code = "# No code content provided"

        # Get command line arguments
        args = arguments.get("args")

        # Try to get detailed information from result.system
        execution_info = {}
        if result.system:
            try:
                execution_info = json.loads(result.system)
            except Exception as e:
                logger.warning(f"Failed to parse execution information: {e}")

        # Get stdout, stderr and exit_code
        stdout = execution_info.get("stdout", "")
        stderr = execution_info.get("stderr", "")
        exit_code = execution_info.get("exit_code", 0)
        success = execution_info.get("success", result.ok)

        # If no information obtained from system, use content from result
        if not stdout and not stderr:
            if result.ok:
                stdout = result.content
            else:
                stderr = result.error

        # Create script execution content object
        script_content = FileContent(
            file_name="",
            content=code,
        )

        # Return tool details
        return ToolDetail(
            type=DisplayType.CODE,
            data=script_content
        )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        action_type = "Execute Python file" if arguments.get("file_path") else "Execute Python code"
        action_target = arguments.get("file_path", "code snippet")

        return {
            "action": f"{action_type}",
            "remark": f"{action_target}"
        }
