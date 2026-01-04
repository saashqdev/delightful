"""Base class for tool parameter models with shared fields."""

import inspect
from typing import Any, Dict, Optional

from pydantic import BaseModel, Field


class BaseToolParams(BaseModel):
    """Base class for all tool parameter models."""
    # TODO: High repetition wastes tokens
    explanation: str = Field(
        "", # Default exists, but calls treat it as required to ensure the model explains intent; missing text won't raise
        description="""
        In first person (use "I"), explain what you are about to do with this tool—briefly cover your goal, expected output, and how you'll use it to help the user.
        **Never mention the tool name when speaking to the user.** For example, say "I will edit your file" instead of "I need to use the write_to_file tool to edit your file".
        """
    )

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Return a custom error message for a given field/error type.

        Subclasses can override to supply friendlier, more instructive errors
        for common validation issues.
        """
        return None

    @classmethod
    def model_json_schema_clean(cls, **kwargs) -> Dict:
        """Generate a cleaned JSON Schema removing unnecessary fields."""
        schema = cls.model_json_schema(**kwargs)
        # Clean schema
        if 'properties' in schema:
            cls._clean_schema_properties(schema['properties'])
            cls._remove_title_recursive(schema)
            cls._clean_description_fields(schema['properties'])
        return schema

    @classmethod
    def _clean_schema_properties(cls, properties: Dict[str, Any]):
        """Recursively clean Pydantic-generated schema properties."""
        if not isinstance(properties, dict):
            return

        for prop_name, prop_schema in list(properties.items()):
            if not isinstance(prop_schema, dict):
                continue

            # Only explanation should drop default
            if prop_name == 'explanation':
                prop_schema.pop('default', None)
            # Remove unnecessary fields
            prop_schema.pop('additionalProperties', None)

            # Recurse into nested properties
            if 'properties' in prop_schema:
                cls._clean_schema_properties(prop_schema['properties'])

            # Recurse into nested items (arrays)
            if 'items' in prop_schema and isinstance(prop_schema['items'], dict):
                prop_schema['items'].pop('additionalProperties', None)

                if 'properties' in prop_schema['items']:
                    cls._clean_schema_properties(prop_schema['items']['properties'])

                if 'items' in prop_schema['items']:
                    cls._clean_schema_properties(prop_schema['items'])

    @classmethod
    def _remove_title_recursive(cls, schema_obj: Any):
        """Recursively remove title fields from schema objects."""
        if isinstance(schema_obj, dict):
            schema_obj.pop('title', None)  # Remove title from current dict
            for key, value in schema_obj.items():
                cls._remove_title_recursive(value)  # Recurse into values
        elif isinstance(schema_obj, list):
            for item in schema_obj:
                cls._remove_title_recursive(item)  # Recurse into list items

    @classmethod
    def _clean_description_fields(cls, properties: Dict[str, Any]):
        """Trim description fields to remove extra whitespace and newlines."""
        if not isinstance(properties, dict):
            return

        for prop_name, prop_schema in properties.items():
            if not isinstance(prop_schema, dict):
                continue

            # Clean description on current property
            if 'description' in prop_schema:
                # Use inspect.cleandoc to tidy docstrings
                if isinstance(prop_schema['description'], str):
                    prop_schema['description'] = inspect.cleandoc(prop_schema['description']).strip()

            # Recurse into nested properties
            if 'properties' in prop_schema and isinstance(prop_schema['properties'], dict):
                cls._clean_description_fields(prop_schema['properties'])

            # Recurse into array item properties
            if 'items' in prop_schema and isinstance(prop_schema['items'], dict):
                if 'description' in prop_schema['items']:
                    if isinstance(prop_schema['items']['description'], str):
                        prop_schema['items']['description'] = inspect.cleandoc(prop_schema['items']['description']).strip()

                if 'properties' in prop_schema['items'] and isinstance(prop_schema['items']['properties'], dict):
                    cls._clean_description_fields(prop_schema['items']['properties'])
