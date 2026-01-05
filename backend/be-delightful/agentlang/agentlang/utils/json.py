"""
JSON utility functions providing JSON processing helper functions
"""

import json
from typing import Any


def json_dumps(obj: Any, **kwargs) -> str:
    """
    Wrapper for json.dumps with default ensure_ascii=False
    
    Args:
        obj: Python object to convert to JSON
        **kwargs: Additional keyword arguments passed to json.dumps
        
    Returns:
        str: JSON string representation preserving non-ASCII characters
    """
    kwargs.setdefault('ensure_ascii', False)
    return json.dumps(obj, **kwargs)
