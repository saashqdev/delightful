"""
Automatic Speech Recognition (ASR) service type definitions
"""

from typing import Any, Dict, List, Optional

from pydantic import BaseModel, Field


class ASRConfig(BaseModel):
    """ASR service configuration"""
    app_id: str = Field(..., description="Volcano Engine application ID")
    token: str = Field(..., description="Volcano Engine access token")
    cluster: str = Field(..., description="Volcano Engine cluster name")
    secret_key: str = Field(..., description="Volcano Engine secret key")


class Utterance(BaseModel):
    """Single utterance in speech recognition result"""
    start_time: int = Field(..., description="Start time (milliseconds)")
    end_time: int = Field(..., description="End time (milliseconds)")
    text: str = Field(..., description="Recognized text")


class ASRResult(BaseModel):
    """Speech recognition result"""
    status: str = Field(..., description="Recognition status")
    message: str = Field(..., description="Status description")
    task_id: str = Field(..., description="Task ID")
    text: str = Field("", description="Complete recognized text")
    utterances: List[Utterance] = Field([], description="Segmented recognition results")
    raw_response: Optional[Dict[str, Any]] = Field(None, description="Raw response") 
