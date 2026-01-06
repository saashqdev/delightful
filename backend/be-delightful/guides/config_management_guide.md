# BeDelightful Configuration Management Guide

## Overview

BeDelightful uses a flexible configuration system based on YAML files and environment variables. It supports layered config, type validation, a unified access interface, and hot reloading with dynamic updates.

## Components

- **Config manager**: Loads, parses, and manages config data from multiple sources
- **Config models**: Pydantic models define schema and defaults
- **Config file**: Primary file stored at `config/config.yaml`
- **Environment variables**: Referenced via placeholders with optional defaults

## Config File Structure

The main YAML config file at `config/config.yaml` typically includes:

- **browser**: Browser-related settings
- **llm**: Shared LLM API settings
- **agent**: Agent system settings
- **image_generator**: Image generation service settings
- **models**: LLM model definitions
  - Each model includes api_key, api_base_url, name, type, supports_tool_use, etc.
- **service configs**: Service-specific settings
- **system configs**: Core system settings

## Environment Variable Placeholders

Two placeholder formats are supported:

1. `${ENV_VAR}` – Reference an env var with no default
2. `${ENV_VAR:-default}` – Reference an env var with a fallback default

Example:
```yaml
browser:
  headless: ${BROWSER_HEADLESS:-false}
  cookies_file: ${BROWSER_COOKIES_FILE:-.browser/cookies.json}

models:
  gpt-4o:
    api_key: "${OPENAI_API_KEY}"
    api_base_url: "${OPENAI_API_BASE_URL:-https://api.openai.com/v1}"
    name: "${OPENAI_MODEL:-gpt-4o}"
```

## Type Conversion

The config system converts types automatically:

- "true" and "false" become booleans
- Numeric strings become integers or floats
- Lists and dicts keep their structure

## Usage

### Read config

```python
from agentlang.config import config

# Read specific keys
headless = config.get("browser.headless")
api_key = config.get("models.gpt-4o.api_key")

# With defaults
timeout = config.get("llm.api_timeout", 60)
```

### Config manager

```python
from agentlang.config.config import Config

# Create a manager
config_manager = Config()

# Load config
config_manager.load_config("/path/to/config.yaml")

# Access with dotted paths
api_key = config_manager.get("models.gpt-4o.api_key")
model_name = config_manager.get("models.gpt-4o.name", "default-model")
```

### Set and reload config

```python
from agentlang.config import config

# Set a value
config.set("models.gpt-4o.temperature", 0.8)

# Reload (e.g., after env var changes at runtime)
config.reload_config()
```

## Config Search Order

Files are located in this order:

1. Path specified by `CONFIG_PATH`
2. `config/config.yaml` under the project root

## Precedence

Load priority from highest to lowest:

1. Runtime overrides via `config.set()`
2. Environment variables
3. Values in the config file
4. Defaults in Pydantic models

## Security Notes

- Keep secrets (API keys) in env vars or `.env`; do not hardcode them in the config file
- Use env placeholders for sensitive values
- Do not commit `.env` to version control
- Follow the security hints at the top of the config file to avoid leaking secrets

## Using .env Files

BeDelightful can load env vars from a `.env` file. See [dotenv_configuration.md](dotenv_configuration.md) for details.

## FAQ

### Config not loading

Check that:
- The file is in the expected location
- Env vars are set correctly
- The YAML is valid

### Env vars not applied

- Verify placeholder syntax
- Confirm the env var exists
- Check env var casing

### Custom config path

You can point to a custom path via env var:

```python
import os
os.environ["CONFIG_PATH"] = "/path/to/custom/config.yaml"

# Then load
from agentlang.config.config import Config
config_manager = Config()
config_manager.load_config()
``` 