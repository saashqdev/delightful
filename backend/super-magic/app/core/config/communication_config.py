"""
Communication configuration module

Defines configuration classes related to external service communication
"""
from typing import Dict

from pydantic import BaseModel


class MessageSubscriptionConfig(BaseModel):
    """
    Message subscription configuration

    Used to configure message subscription methods and callback interfaces
    """
    method: str  # HTTP method, e.g. "POST"
    url: str  # API endpoint
    headers: Dict[str, str]  # HTTP request headers


class STSTokenRefreshConfig(BaseModel):
    """
    STS Token refresh configuration
    
    Used to configure the method and interface for refreshing STS Tokens
    """
    method: str  # HTTP method, e.g. "POST"
    url: str  # API endpoint
    headers: Dict[str, str]  # HTTP request headers 
