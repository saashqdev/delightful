import re
from pathlib import Path
from typing import Any, Dict, Tuple

# New: Import global configuration
from agentlang.config import config
from agentlang.logger import get_logger

logger = get_logger(__name__)


class AgentLoader:
    def __init__(self, agents_dir: Path):
        self._agents: Dict[str, Dict[str, Any]] = {}
        # Set agent file directory
        self._agents_dir = agents_dir

    def set_variables(self, prompt: str, variables: Dict[str, Any]) -> str:
        """
        Set variables

        Args:
            prompt: Prompt text
            variables: Variables

        Returns:
            str: Prompt with variables replaced
        """
        pattern = r"\{\{([^}]+)\}\}"

        def replace_var(match):
            var_name = match.group(1).strip()
            if var_name not in variables:
                return f"{{{{Undefined variable: {var_name}}}}}"
            return str(variables[var_name])

        return re.sub(pattern, replace_var, prompt)

    def load_agent(self, agent_name: str, variables: Dict[str, Any] = None) -> Tuple[str, Dict[str, Any], Dict[str, Any], str]:
        """
        Load agent file content and set variables

        Args:
            agent_name: Agent name
            variables: Variables, optional parameter

        Returns:
            Tuple[str, Dict[str, Any], Dict[str, Any], str]: Parsed model ID, tools config, attributes config, and prompt
        """
        # Ensure variables is not None
        if variables is None:
            variables = {}

        # Check if agent_name has already been loaded
        if agent_name in self._agents:
            agent_data = self._agents[agent_name]
            return agent_data["model_id"], agent_data["tools_config"], agent_data["attributes_config"], agent_data["prompt"]

        # Get agent file content
        agent_file_content = self._get_agent_file_content(agent_name)
        # Parse agent file content
        model_id, tools_config, attributes_config, prompt = self._parse_agent_file_content(agent_file_content)
        # Set variables
        if variables:
            prompt = self.set_variables(prompt, variables)
        # Save to self._agents based on agent_name
        self._agents[agent_name] = {
            "model_id": model_id,
            "tools_config": tools_config,
            "attributes_config": attributes_config,
            "prompt": prompt,
        }
        return model_id, tools_config, attributes_config, prompt

    def _get_agent_file_content(self, agent_name: str) -> str:
        """
        Get agent file content

        Args:
            agent_name: Agent name

        Returns:
            str: Agent file content
        """
        # Get agent file path
        agent_file = self._agents_dir / f"{agent_name}.agent"
        # Check if agent file exists
        if not agent_file.exists():
            raise FileNotFoundError(f"Agent file does not exist: {agent_file}")
        # Read agent file content
        with open(agent_file, "r", encoding="utf-8") as f:
            return f.read()

    def _parse_agent_file_content(self, agent_file_content: str) -> Tuple[str, Dict[str, Any], Dict[str, Any], str]:
        """Parse agent file content

        Args:
            agent_file_content: Agent file content

        Returns:
            Tuple[str, Dict[str, Any], Dict[str, Any], str]: Parsed model ID, tools config, attributes config, and prompt
        """
        # Initialize configuration
        tools_config = {}
        model_id = "" # Modified: no longer a dictionary
        attributes_config = {}
        prompt = ""

        # Parse tools_config
        tools_pattern = r"<!--\s*tools:\s*([\w,\s\.-]+)\s*-->"
        match = re.search(tools_pattern, agent_file_content)
        if match:
            tools_str = match.group(1).strip()
            tools = {tool.strip(): {} for tool in tools_str.split(",") if tool.strip()}
            logger.debug(f"Parsed tools config from agent file: {tools}")
            tools_config = tools
        else:
            logger.error("Tool configuration not found in agent file")
            raise ValueError("Tool configuration not found in agent file")

        # Parse model_config
        model_pattern = r"<!--\s*llm:\s*([\w,\s\.-]+)\s*-->"
        match = re.search(model_pattern, agent_file_content)
        if match:
            # Extract model name or alias (assuming only one)
            model_str = match.group(1).strip()
            # Resolve alias
            resolved_model_id = config.resolve_model_alias(model_str)
            logger.debug(f"Parsed model identifier '{model_str}' from agent file, resolved to '{resolved_model_id}'")
            model_id = resolved_model_id # Modified: store resolved ID
        else:
            logger.error("Model configuration not found in agent file")
            # Consider whether an error should be raised here if model is required
            # raise ValueError("Model configuration not found in agent file")

        # Parse attributes_config
        attributes_pattern = r"<!--\s*attributes:\s*([\w,\s\.-]+)\s*-->"
        match = re.search(attributes_pattern, agent_file_content)
        if match:
            attributes_str = match.group(1).strip()
            attributes = {attribute.strip(): True for attribute in attributes_str.split(",") if attribute.strip()}
            logger.debug(f"Parsed attribute configuration from agent file: {attributes}")
            attributes_config = attributes
        else:
            logger.debug("Attribute configuration not found in agent file")

        # Parse prompt
        prompt = re.sub(r"<!--(.*?)-->", "", agent_file_content, flags=re.DOTALL)
        prompt = prompt.strip()
        # logger.debug(f"Parsed prompt from agent file: {prompt}")

        return model_id, tools_config, attributes_config, prompt
