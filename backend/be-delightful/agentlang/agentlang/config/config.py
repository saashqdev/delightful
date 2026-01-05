import os
import re
from typing import Any, Dict, Generic, Optional, Type, TypeVar

import yaml
from pydantic import BaseModel

from agentlang.context.application_context import ApplicationContext
from agentlang.logger import get_logger

T = TypeVar("T", bound=BaseModel)


class Config(Generic[T]):
    """Configuration manager supporting YAML config files, Pydantic model validation, and Agent mode switching"""

    _instance = None
    _config: Dict[str, Any] = {}
    _model_aliases: Dict[str, str] = {}
    _model: Optional[T] = None
    _logger = get_logger("agentlang.config.config_manager")
    _config_loaded = False
    _config_path = None
    _raw_config: Dict[str, Any] = {} # Save original loaded config for reload

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(Config, cls).__new__(cls)
        return cls._instance

    def __init__(self):
        self.load_config()

    def set(self, key_path: str, value: Any) -> None:
        """Set configuration value, supports using dot notation (.) for hierarchy

        Args:
            key_path: Configuration key path, e.g. 'openai.api_key'
            value: Value to set
        """
        if not key_path:
            return

        # Convert dot-separated path to key list
        keys = key_path.split(".")

        # Set value layer by layer in config dictionary
        current = self._config
        for key in keys[:-1]:
            current = current.setdefault(key, {})
        current[keys[-1]] = value

        # Reload model aliases as related config may have changed
        self._load_model_aliases()

        # If there's a model class, revalidate
        if self._model is not None:
            self._model = self._model.__class__(**self._config)

    def _ensure_config_loaded(self) -> None:
        """Ensure configuration is loaded; load it if not"""
        if not self._config_loaded:
            self.load_config()
            self._config_loaded = True

    def load_config(self, config_path: Optional[str] = None, model: Optional[Type[T]] = None) -> None:
        """Load the config file and apply modes

        Args:
            config_path: Path to the config file; if None, it will be auto-discovered
            model: Pydantic model class used for validation
        """
        # Try to determine the config file path
        if config_path is None:
            # First check environment variable
            config_path = os.getenv("CONFIG_PATH")

            # If not set, try to resolve from project root
            if not config_path:
                try:
                    # Prefer ApplicationContext to get the path manager
                    path_manager = ApplicationContext.get_path_manager()
                    config_path = str(path_manager.get_project_root() / "config/config.yaml")
                    self._logger.info(f"Config path resolved via ApplicationContext: {config_path}")
                except (ImportError, AttributeError, RuntimeError) as e:
                    self._logger.debug(f"Could not get path via ApplicationContext: {e}")

                # If still not found, fall back to empty config
                if not config_path or not os.path.exists(config_path):
                    config_file_found = False
                else:
                    config_file_found = True
            else:
                config_file_found = os.path.exists(config_path)
        else:
            config_file_found = os.path.exists(config_path)

        # Handle missing config file
        if not config_file_found:
            if config_path:
                self._logger.warning(f"Config file not found: {config_path}; using empty config")
            else:
                self._logger.warning("Config path not determined; using empty config")
            self._config = {}
            self._raw_config = {}
            self._model_aliases = {}
            self._config_loaded = True
            return

        try:
            # Load YAML config
            with open(config_path, "r", encoding="utf-8") as f:
                self._raw_config = yaml.safe_load(f) or {}
        except Exception as e:
            self._logger.error(f"Failed to load or parse config file {config_path}: {e}")
            self._config = {}
            self._raw_config = {}
            self._model_aliases = {}
            self._config_loaded = True
            return

        # Expand environment variable placeholders
        self._config = self._process_env_placeholders(self._raw_config)
        self._config_loaded = True

        # Load model aliases (based on processed config, especially active_agent_mode)
        self._load_model_aliases()

        # Validate with provided model class if supplied
        if model is not None:
            try:
                self._model = model(**self._config)
                # Refresh config dict to include defaults
                self._config = self._model.model_dump()
                # Reload aliases in case Pydantic model affects them
                self._load_model_aliases()
            except Exception as e:
                self._logger.error(f"Config validation with Pydantic model failed: {e}")
                self._model = None

    def _load_model_aliases(self) -> None:
        """Load and resolve model aliases based on the active mode"""
        if not self._config:
            self._logger.debug("Config not loaded yet; cannot load model aliases.")
            self._model_aliases = {}
            return

        # Get active mode name, defaulting to 'apex'
        active_mode_name = self.get('active_agent_mode', 'apex')
        self._logger.info(f"Current active Agent mode: {active_mode_name}")

        agent_modes_config = self.get('agent_modes', {})
        if not isinstance(agent_modes_config, dict):
            self._logger.warning("'agent_modes' config is not a dict; mode config will be ignored.")
            agent_modes_config = {}

        active_mode_data = agent_modes_config.get(active_mode_name, {})
        if not isinstance(active_mode_data, dict):
             self._logger.warning(f"Config for mode '{active_mode_name}' is not a dict; ignoring it.")
             active_mode_data = {}

        if not active_mode_data and active_mode_name != 'apex':  # If the specified mode is missing and not the default
            self._logger.warning(f"No Agent mode named '{active_mode_name}' found. Will check for global default aliases.")

        # Pull aliases from the active mode
        mode_aliases = active_mode_data.get('model_aliases', {})
        if not isinstance(mode_aliases, dict):
            self._logger.warning(f"'model_aliases' in mode '{active_mode_name}' is not a dict; ignoring.")
            mode_aliases = {}

        # Optionally take global defaults as a base
        fallback_aliases = self.get('model_aliases', {})
        if not isinstance(fallback_aliases, dict):
             self._logger.warning("Global 'model_aliases' config is not a dict; ignoring.")
             fallback_aliases = {}

        # Merge: mode aliases override global aliases
        combined_aliases = {**fallback_aliases, **mode_aliases}

        final_aliases = {}
        # Handle environment variable overrides
        for alias_key, model_in_config in combined_aliases.items():
            # Build env var name (e.g., main_llm -> MAIN_LLM)
            env_var_name = alias_key.upper()
            env_value = os.getenv(env_var_name)

            if env_value:
                resolved_model = self._convert_value_type(env_value)
                final_aliases[alias_key] = resolved_model
                if resolved_model != model_in_config:
                     self._logger.debug(f"Model alias '{alias_key}' (mode '{active_mode_name}') overridden by env '{env_var_name}' -> '{resolved_model}' (was '{model_in_config}')")
                else:
                     self._logger.debug(f"Model alias '{alias_key}' (mode '{active_mode_name}') confirmed by env '{env_var_name}' as '{resolved_model}'")
            else:
                final_aliases[alias_key] = model_in_config  # Use config value
                self._logger.debug(f"Model alias '{alias_key}' set by mode '{active_mode_name}' (or default) to '{model_in_config}'")

        # Also consider aliases defined solely via env vars (e.g., FOO_LLM when config lacks foo_llm)
        for env_key, env_val in os.environ.items():
            # Simple heuristic; adjust if naming differs
            if env_key.endswith("_LLM") and not env_key.startswith("_"):
                potential_alias_key = env_key.lower()
                if potential_alias_key not in final_aliases:
                    resolved_model = self._convert_value_type(env_val)
                    final_aliases[potential_alias_key] = resolved_model
                    self._logger.debug(f"Model alias '{potential_alias_key}' added directly from env '{env_key}' with value '{resolved_model}'")

        self._model_aliases = final_aliases
        self._logger.info(f"Loaded model aliases for mode {active_mode_name}: {self._model_aliases}")

    def get_model(self) -> Optional[T]:
        """Return the validated Pydantic model instance"""
        self._ensure_config_loaded()
        return self._model

    def get(self, key_path: str, default: Any = None) -> Any:
        """Get a config value using dot notation, honoring mode overrides

        Args:
            key_path: Config key path, e.g., 'openai.api_key'
            default: Fallback value when the key is missing

        Returns:
            The config value or the default
        """
        self._ensure_config_loaded()

        if not key_path:
            return default

        keys = key_path.split(".")

        # First try to read from the active mode
        active_mode_name = self._config.get('active_agent_mode', 'apex')
        mode_config = self._config.get('agent_modes', {}).get(active_mode_name, {})

        current_mode = mode_config
        found_in_mode = True
        try:
            for key in keys:
                if isinstance(current_mode, dict) and key in current_mode:
                    current_mode = current_mode[key]
                elif isinstance(current_mode, list):
                     index = int(key)
                     if 0 <= index < len(current_mode):
                         current_mode = current_mode[index]
                     else:
                        found_in_mode = False
                        break
                else:
                    found_in_mode = False
                    break
        except (TypeError, ValueError, IndexError):
            found_in_mode = False

        if found_in_mode:
             return current_mode

        # If not found in the mode, fall back to global config
        current_global = self._config
        found_in_global = True
        try:
             for key in keys:
                if isinstance(current_global, dict) and key in current_global:
                    current_global = current_global[key]
                elif isinstance(current_global, list):
                    index = int(key)
                    if 0 <= index < len(current_global):
                        current_global = current_global[index]
                    else:
                        found_in_global = False
                        break
                else:
                    found_in_global = False
                    break
        except (TypeError, ValueError, IndexError):
             found_in_global = False

        if found_in_global:
            return current_global

        return default

    def resolve_model_alias(self, alias_or_model_name: str) -> str:
        """Resolve a model alias or return the original name

        Args:
            alias_or_model_name: Alias or actual model name

        Returns:
            The resolved model name
        """
        self._ensure_config_loaded()  # Ensure aliases are loaded
        resolved_name = self._model_aliases.get(alias_or_model_name, alias_or_model_name)

        if resolved_name != alias_or_model_name:
            self._logger.debug(f"Model alias '{alias_or_model_name}' resolved to '{resolved_name}'")

        # Check that the model is defined in self._config['models']
        models_dict = self._config.get('models', {})  # Directly fetch models dict
        if not isinstance(models_dict, dict):
             self._logger.error("Config structure error: 'models' key missing or not a dict.")
             # Return explicit marker for downstream handling
             return "error-config-models-missing"
        if resolved_name not in models_dict:
             self._logger.error(f"Fatal: model '{resolved_name}' (from alias '{alias_or_model_name}') is not defined in config.models. Please check the config file.")
             # Return explicit marker
             return "error-model-not-defined"

        return resolved_name

    def reload_config(self) -> None:
        """Reload configuration, reprocessing env vars and modes"""
        self._logger.info("Reloading configuration...")

        if self._config_path and os.path.exists(self._config_path):
            self.load_config(config_path=self._config_path)
        else:
            self._config_loaded = False
            self.load_config()

        self._logger.info("Configuration reload finished")

    def _process_env_placeholders(self, config_dict: Dict[str, Any]) -> Dict[str, Any]:
        """Process environment variable placeholders within the config

        Supported formats:
        1. ${ENV_VAR} - use env value, no default
        2. ${ENV_VAR:-default} - use env value, fallback to default if missing

        Performs type conversion:
        - "true" / "false" -> booleans
        - Numeric-looking strings -> numbers

        Args:
            config_dict: Raw configuration dictionary

        Returns:
            Processed configuration dictionary
        """
        if not isinstance(config_dict, dict):
            return config_dict

        result = {}
        for key, value in config_dict.items():
            if isinstance(value, dict):
                # Recursively process nested dicts
                result[key] = self._process_env_placeholders(value)
            elif isinstance(value, list):
                # Recursively handle dicts or strings inside lists
                result[key] = [self._process_env_placeholders(item) if isinstance(item, dict) else self._process_string_placeholder(item) if isinstance(item, str) else item for item in value]
            elif isinstance(value, str):
                # Process string placeholders
                result[key] = self._process_string_placeholder(value)
            else:
                # Preserve non-str/dict/list values as-is
                result[key] = value

        return result

    def _process_string_placeholder(self, value: str) -> Any:
        """Process env placeholders in a string and convert types"""
        pattern = r"\${([A-Za-z0-9_]+)(?::-([^}]*))?\}"
        match = re.fullmatch(pattern, value)
        if match:
            env_var = match.group(1)
            default_value = match.group(2) if match.group(2) is not None else ""

            # Pull from env, otherwise use default
            env_value = os.getenv(env_var)
            if env_value is not None:
                return self._convert_value_type(env_value)
            else:
                return self._convert_value_type(default_value)
        else:
            # If not in ${ENV_VAR:-default} form, still attempt type conversion
            return self._convert_value_type(value)

    def _convert_value_type(self, value: Any) -> Any:
        """Convert value types

        - "true"/"false" -> booleans
        - Numeric strings -> ints or floats

        Args:
            value: String value to convert

        Returns:
            Converted value
        """
        if not isinstance(value, str):  # Already typed; return as-is
            return value

        # Handle booleans
        val_lower = value.lower()
        if val_lower == "true":
            return True
        elif val_lower == "false":
            return False
        elif val_lower == "none" or value == "":  # Handle "none" and empty string
            return None  # Or return "" if needed

        # Handle numbers
        try:
            # Try converting to int
            if value.isdigit() or (value.startswith("-") and value[1:].isdigit()):
                return int(value)

            # Try converting to float
            if "." in value:
                float_val = float(value)
                # If the float is effectively an int (e.g., 5.0), return int
                if float_val.is_integer():
                    return int(float_val)
                return float_val
        except (ValueError, TypeError):
            pass

        # If conversion fails, return original value
        return value

# Create global config manager instance
config = Config()
