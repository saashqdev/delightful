import asyncio
import json
import os
import re
from typing import Any, Dict, List, Optional, Set, Tuple

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.message.server_message import DisplayType, TerminalContent, ToolDetail
from app.core.entity.tool.tool_result import TerminalToolResult
from app.tools.core import BaseToolParams, tool
from app.tools.workspace_guard_tool import WorkspaceGuardTool

logger = get_logger(__name__)


class ShellExecParams(BaseToolParams):
    """Shell command execution parameters"""
    command: str = Field(
        ...,
        description="Shell command to execute"
    )
    cwd: Optional[str] = Field(
        None,
        description="Working directory for command execution (optional)"
    )
    timeout: int = Field(
        60,
        description="Command execution timeout time (seconds), default 60 seconds"
    )


@tool()
class ShellExec(WorkspaceGuardTool[ShellExecParams]):
    """
    Shell command execution tool for executing system commands.

    Use cases:
    - Execute system commands
    - Run scripts
    - Manage processes
    - File operations

    Notes:
    - Commands will be executed in the system shell
    - Support setting working directory
    - Can set timeout
    - Only allow executing safe commands in whitelist, absolutely cannot execute harmful commands
    - Some dangerous operations (such as deletion) require additional confirmation
    - Support safe composite command execution (e.g.: mkdir -p /path && curl -o /file)
    - Try to execute only one command at a time, avoid executing composite commands
    """

    # Command whitelist configuration
    SAFE_COMMANDS: Dict[str, Set[str]] = {
        # File and directory operations
        "ls": {"ls", "ls -l", "ls -la", "ls -lh", "ls -lah"},
        "cd": {"cd", "cd ..", "cd -"},
        "pwd": {"pwd"},
        "mkdir": {"mkdir", "mkdir -p"},
        "cp": {"cp", "cp -r", "cp -R"},
        "mv": {"mv"},
        "cat": {"cat"},
        "head": {"head", "head -n"},
        "tail": {"tail", "tail -n", "tail -f"},
        "touch": {"touch"},
        "chmod": {"chmod +x", "chmod 644", "chmod 755"},
        "find": {"find . -name", "find . -type", "find * -name", "find * -type"},
        # Process management
        "ps": {"ps", "ps aux", "ps -ef"},
        "kill": {"kill", "kill -9", "kill -15"},
        "pkill": {"pkill", "pkill -f"},
        # System information
        "df": {"df", "df -h"},
        "du": {"du", "du -h", "du -sh"},
        "free": {"free", "free -h", "free -m"},
        "top": {"top -n"},
        "htop": {"htop"},
        # Network tools
        "ping": {"ping -c"},
        "curl": {"curl", "curl -I", "curl -L", "curl -O", "curl -o"},
        "wget": {"wget"},
        "netstat": {"netstat", "netstat -an", "netstat -tulpn"},
        "ss": {"ss", "ss -tulpn"},
        # Package management
        "pip": {"pip list", "pip show", "pip install", "pip uninstall"},
        "pip3": {"pip3 list", "pip3 show", "pip3 install", "pip3 uninstall"},
        "npm": {"npm list", "npm install", "npm uninstall", "npm run"},
        "yarn": {"yarn", "yarn add", "yarn remove", "yarn install"},
        # Compression and extraction
        "tar": {"tar -czf", "tar -xzf", "tar -cjf", "tar -xjf"},
        "zip": {"zip", "zip -r"},
        "unzip": {"unzip"},
        # Git action
        "git": {
            "git status",
            "git log",
            "git branch",
            "git checkout",
            "git pull",
            "git push",
            "git fetch",
            "git merge",
            "git add",
            "git commit",
            "git diff",
            "git show",
            "git remote",
            "git clone",
            "git init",
        },
        # Text processing
        "grep": {"grep", "grep -r", "grep -i", "grep -v"},
        "sed": {"sed 's/", "sed -i 's/"},
        "awk": {"awk '{print", "awk -F"},
        "wc": {"wc", "wc -l", "wc -w"},
        "sort": {"sort", "sort -r", "sort -n"},
        "uniq": {"uniq", "uniq -c"},
        # Python related
        "python": {"python", "python3", "python -m", "python3 -m"},
        "pytest": {"pytest", "pytest -v", "pytest -k"},
        "coverage": {"coverage run", "coverage report"},
        # Environment variables
        "env": {"env", "env | grep"},
        "export": {"export"},
        "echo": {"echo", "echo $"},
    }

    # Allowed command separators
    ALLOWED_SEPARATORS = ["&&", ";"]

    def _split_commands(self, command: str) -> List[str]:
        """
        Split composite command into list of individual commands

        Args:
            command: Command string that may contain separators

        Returns:
            List[str]: List of split commands
        """
        # First save separators for temporary replacement
        temp_replacements = {}
        preserved_command = command

        # Temporarily replace content within quotes to prevent incorrect splitting
        in_single_quote = False
        in_double_quote = False
        quote_content = ""
        i = 0
        quote_start = -1

        while i < len(preserved_command):
            char = preserved_command[i]
            if char == "'" and not in_double_quote:
                if in_single_quote:
                    in_single_quote = False
                    quote_content = preserved_command[quote_start:i+1]
                    placeholder = f"__QUOTE_{len(temp_replacements)}__"
                    temp_replacements[placeholder] = quote_content
                    preserved_command = preserved_command[:quote_start] + placeholder + preserved_command[i+1:]
                    i = quote_start + len(placeholder)
                    continue
                else:
                    in_single_quote = True
                    quote_start = i
            elif char == '"' and not in_single_quote:
                if in_double_quote:
                    in_double_quote = False
                    quote_content = preserved_command[quote_start:i+1]
                    placeholder = f"__QUOTE_{len(temp_replacements)}__"
                    temp_replacements[placeholder] = quote_content
                    preserved_command = preserved_command[:quote_start] + placeholder + preserved_command[i+1:]
                    i = quote_start + len(placeholder)
                    continue
                else:
                    in_double_quote = True
                    quote_start = i
            i += 1

        # Split commands using regex
        # Note: This regex matches command separators (&& or ;)
        commands = []
        for separator in self.ALLOWED_SEPARATORS:
            if separator in preserved_command:
                preserved_command = preserved_command.replace(separator, "##SEP##")

        raw_commands = preserved_command.split("##SEP##")

        # Restore original quote content
        for cmd in raw_commands:
            cmd = cmd.strip()
            if not cmd:
                continue

            # Restore all temporary replacements
            for placeholder, original in temp_replacements.items():
                cmd = cmd.replace(placeholder, original)

            commands.append(cmd)

        return commands

    def _is_command_safe(self, command: str) -> bool:
        """
        Check if command is in whitelist

        Args:
            command: The command to check

        Returns:
            bool: Whether the command is safe
        """
        # Split command and parameters
        cmd_parts = command.strip().split()
        if not cmd_parts:
            return False

        base_cmd = cmd_parts[0]

        # Check if base command is in whitelist
        if base_cmd not in self.SAFE_COMMANDS:
            return False

        # Check if complete command pattern matches whitelist rules
        for safe_pattern in self.SAFE_COMMANDS[base_cmd]:
            pattern = f"^{safe_pattern.replace('*', '.*')}.*$"
            if re.match(pattern, command):
                return True

        return False

    def _sanitize_command(self, command: str) -> str:
        """
        Clean and normalize command, but preserve allowed separators

        Args:
            command: The original command

        Returns:
            str: The cleaned command
        """
        # Remove dangerous shell operators, but preserve allowed separators
        dangerous_operators = ["|", ">", "<", "`", "$", "\\"]
        cleaned_command = command

        for op in dangerous_operators:
            cleaned_command = cleaned_command.replace(op, "")

        # Remove extra spaces, but preserve separators between commands
        for sep in self.ALLOWED_SEPARATORS:
            cleaned_command = cleaned_command.replace(sep, f" {sep} ")

        # Normalize whitespace
        cleaned_command = " ".join(filter(None, cleaned_command.split()))

        return cleaned_command

    def _validate_commands(self, commands: List[str]) -> Tuple[bool, Optional[str]]:
        """
        Validate if all split commands are safe

        Args:
            commands: List of split commands

        Returns:
            Tuple[bool, Optional[str]]: (Whether all are safe, error information)
        """
        for cmd in commands:
            if not self._is_command_safe(cmd):
                return False, f"Command not in safe whitelist: {cmd}"
        return True, None

    def _check_workspace_path_safety(self, command: str) -> bool:
        """
        Check if paths in command are all within workspace

        Args:
            command: The command to check

        Returns:
            bool: Whether the paths are safe
        """
        # Extract all path parameters in command
        # This is a simplified implementation, may need more complex logic to process all cases
        workspace_path = str(self.base_dir)

        # Simply check if paths mentioned in command are all within workspace
        words = command.split()
        for word in words:
            if word.startswith('/') and not word.startswith(workspace_path):
                # Check if it's a workspace path
                if not any(workspace_path in word for workspace_path in ['.workspace', 'super-magic/.workspace']):
                    return False

        return True

    async def execute(self, tool_context: ToolContext, params: ShellExecParams) -> TerminalToolResult:
        """
        Execute shell command, supports composite commands

        Args:
            tool_context: Tool context
            params: Parameters object containing command, working directory and timeout

        Returns:
            TerminalToolResult: Structured result object containing execution result
        """
        try:
            # Process working directory
            work_dir = self.base_dir
            if params.cwd:
                # Use parent class method to get safe working directory path
                cwd_path, error = self.get_safe_path(params.cwd)
                if error:
                    return TerminalToolResult(
                        error=error,
                        command=params.command
                    )
                work_dir = cwd_path

            # Ensure working directory exists
            if not work_dir.exists():
                return TerminalToolResult(
                    error=f"Working directory does not exist: {work_dir}",
                    command=params.command
                )

            # Check if it is a composite command
            if any(sep in params.command for sep in self.ALLOWED_SEPARATORS):
                # Split command
                commands = self._split_commands(params.command)

                # Validate all commands are safe
                all_safe, error_msg = self._validate_commands(commands)
                if not all_safe:
                    return TerminalToolResult(
                        error=error_msg,
                        command=params.command
                    )

                # Check if paths in all commands are safe
                for cmd in commands:
                    if not self._check_workspace_path_safety(cmd):
                        return TerminalToolResult(
                            error=f"Command contains path outside workspace, execution not allowed: {cmd}",
                            command=params.command
                        )

                # For composite commands, preserve separators and clean dangerous operators
                cleaned_command = self._sanitize_command(params.command)

                logger.debug(f"Executing composite command: {cleaned_command}, working directory: {work_dir}")

            else:
                # Single command, use original logic to process
                cleaned_command = self._sanitize_command(params.command)
                if not self._is_command_safe(cleaned_command):
                    return TerminalToolResult(
                        error=f"Command not in safe whitelist: {params.command}",
                        command=params.command
                    )

                # Check path safety
                if not self._check_workspace_path_safety(cleaned_command):
                    return TerminalToolResult(
                        error=f"Command contains path outside workspace, execution not allowed: {cleaned_command}",
                        command=params.command
                    )

                logger.debug(f"Execute command: {cleaned_command}, working directory: {work_dir}")

            # Create result object
            result = TerminalToolResult(command=cleaned_command, content="Command executing...")

            env_vars = {
                **os.environ,
                'PYTHONIOENCODING': 'utf-8',
                'MPLCONFIGDIR': '/root/.config/matplotlib',
                'LC_ALL': 'C.UTF-8',
                'LANG': 'C.UTF-8'
            }

            # Create subprocess
            process = await asyncio.create_subprocess_shell(
                cleaned_command,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
                cwd=str(work_dir),
                env=env_vars,
            )

            try:
                # Wait for process to complete with timeout
                stdout, stderr = await asyncio.wait_for(
                    process.communicate(), timeout=params.timeout
                )
                stdout_str = stdout.decode().strip() if stdout else ""
                stderr_str = stderr.decode().strip() if stderr else ""
                exit_code = process.returncode

                # Set exit code
                result.set_exit_code(exit_code)

                # Build structured content
                if exit_code == 0:
                    # Success case
                    content = "Command execution successful\n"
                    if stdout_str:
                        content += f"stdout:\n{stdout_str}\n"
                    if stderr_str:
                        content += f"stderr:\n{stderr_str}\n"
                    result.content = content.strip()
                else:
                    # Failed case
                    error_content = f"Command execution failed (exit code: {exit_code})\n"
                    if stdout_str:
                        error_content += f"stdout:\n{stdout_str}\n"
                    if stderr_str:
                        error_content += f"stderr:\n{stderr_str}\n"
                    result.content = error_content.strip()
                    result.ok = False

                # Construct command information JSON and save to system field
                system_info = {
                    "command": cleaned_command,
                    "cwd": str(work_dir),
                    "stdout": stdout_str,
                    "stderr": stderr_str,
                    "exit_code": exit_code,
                    "execution_time": params.timeout,
                }
                result.system = json.dumps(system_info, ensure_ascii=False)

                return result

            except asyncio.TimeoutError:
                # Timeout, forcefully terminate process
                if process.returncode is None:
                    try:
                        process.kill()
                        await process.wait()
                    except:
                        pass

                return TerminalToolResult(
                    error=f"Command execution timeout ({params.timeout} seconds): {cleaned_command}",
                    command=cleaned_command,
                    exit_code=-1  # Use -1 to indicate timeout
                )

        except Exception as e:
            logger.exception(f"Error executing command: {e}")
            return TerminalToolResult(
                error=f"Error executing command: {e}",
                command=params.command,
                exit_code=-2  # Use -2 to indicate exception
            )

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call
        """
        command = arguments.get("command", "") if arguments else ""
        return {
            "action": "Executed shell command",
            "remark": command
        }

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Get corresponding ToolDetail based on tool execution result

        Args:
            tool_context: Tool context
            result: Tool execution result
            arguments: Tool execution parameters dictionary

        Returns:
            Optional[ToolDetail]: Tool detail object, may be None
        """
        if not result.ok:
            return None

        if not isinstance(result, TerminalToolResult):
            logger.warning("Result is not of TerminalToolResult type")
            return None

        # Get command, output and exit code
        command = result.command if hasattr(result, 'command') else arguments.get("command", "")
        output = result.content if result.content else ""
        exit_code = result.exit_code if hasattr(result, 'exit_code') else 0

        # Create terminal content object
        terminal_content = TerminalContent(
            command=command,
            output=output,
            exit_code=exit_code
        )

        # Return tool details
        return ToolDetail(
            type=DisplayType.TERMINAL,
            data=terminal_content
        )
