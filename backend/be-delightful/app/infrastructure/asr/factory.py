"""
ASR service factory module
"""

from typing import Optional

from agentlang.config.config import config
from app.infrastructure.asr.types import ASRConfig
from app.infrastructure.asr.ve_asr_service import VEASRService


class ASRServiceFactory:
    """ASR service factory for creating different ASR service instances"""

    _instance: Optional[VEASRService] = None

    @classmethod
    def get_ve_asr_service(cls) -> VEASRService:
        """
        Get Volcano Engine ASR service instance
        
        Returns:
            VEASRService: Volcano Engine ASR service instance
        """
        if cls._instance is None:
            # Get ASR configuration from config
            asr_config = config.get("asr", {})

            # Create ASR config object
            asr_service_config = ASRConfig(
                app_id=asr_config.get("app_id", ""),
                token=asr_config.get("token", ""),
                cluster=asr_config.get("cluster", ""),
                secret_key=asr_config.get("secret_key", "")
            )

            # Create service instance
            cls._instance = VEASRService(asr_service_config)

        return cls._instance 
