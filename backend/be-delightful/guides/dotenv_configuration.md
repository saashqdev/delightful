# .env Priority Configuration

## Overview

The project uses `python-dotenv` to load environment variables. By default, the library does not override existing system environment variables. This change allows the project to prioritize values from the `.env` file even when the same variables already exist in the system.

## Technical Implementation

Added `override=True` to all `load_dotenv()` calls so that values in `.env` take precedence over system environment variables.

Updated `load_dotenv()` in the following files:

1. `main.py`
2. `bin/v6.py`
3. `app/vector_store/example.py`
4. `app/vector_store/examples/collection_prefix_example.py`

## Usage

No changes to how you use the project: just write the variables you want to override into `.env`. Those entries will override existing system environment variables.

## Notes

- If you need the original behavior (not overriding system environment variables) for a specific scenario, temporarily set `override=False` on that `load_dotenv()` call.
- This is especially helpful in development and testing to switch configurations without touching system environment variables.
