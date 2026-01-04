"""
Volcano Engine Automatic Speech Recognition service implementation
"""

import json
import time
import uuid
from typing import Any, Dict, Optional, Tuple

import requests
from loguru import logger

from app.infrastructure.asr.types import ASRConfig, ASRResult, Utterance


class ASRException(Exception):
    """Automatic Speech Recognition service exception"""
    def __init__(self, message: str, code: Optional[str] = None):
        self.message = message
        self.code = code
        super().__init__(message)


class VEASRService:
    """Volcano Engine Automatic Speech Recognition service"""

    def __init__(self, config: ASRConfig):
        """
        Initialize Volcano Engine ASR service

        Args:
            config: ASR configuration
        """
        self.config = config
        self.submit_url = "https://openspeech.bytedance.com/api/v3/auc/bigmodel/submit"
        self.query_url = "https://openspeech.bytedance.com/api/v3/auc/bigmodel/query"

    def transcribe(
        self,
        audio_url: str,
        audio_format: str = "mp3",
        sample_rate: int = 16000,
        max_retries: int = 60,
        retry_interval: int = 1
    ) -> ASRResult:
        """
        Transcribe audio file

        Args:
            audio_url: Audio file URL
            audio_format: Audio format
            sample_rate: Sample rate
            max_retries: Maximum number of retries
            retry_interval: Retry interval (seconds)

        Returns:
            ASRResult: Speech recognition result

        Raises:
            ASRException: Raised when transcription fails
        """
        try:
            # Submit transcription task
            task_id, x_tt_logid = self._submit_task(audio_url, audio_format, sample_rate)

            # Query transcription result
            attempts = 0
            while attempts < max_retries:
                query_response = self._query_task(task_id, x_tt_logid)
                code = query_response.headers.get('X-Api-Status-Code', "")

                if code == '20000000':  # Task completed
                    # Parse result
                    return self._parse_response(task_id, query_response.json())
                elif code != '20000001' and code != '20000002':  # Task failed
                    raise ASRException(
                        f"ASR task failed with code: {code}",
                        code
                    )

                attempts += 1
                time.sleep(retry_interval)

            raise ASRException(f"ASR task timeout after {max_retries} attempts")

        except requests.RequestException as e:
            logger.error(f"Network error during ASR request: {e}")
            raise ASRException(f"Network error: {e!s}")
        except Exception as e:
            logger.error(f"Unexpected error in ASR service: {e}")
            raise ASRException(f"ASR service error: {e!s}")

    def _submit_task(
        self,
        audio_url: str,
        audio_format: str = "mp3",
        sample_rate: int = 16000
    ) -> Tuple[str, str]:
        """
        Submit speech transcription task

        Args:
            audio_url: Audio file URL
            audio_format: Audio format
            sample_rate: Sample rate

        Returns:
            Tuple[str, str]: Task ID and log ID

        Raises:
            ASRException: Raised when submission fails
        """
        task_id = str(uuid.uuid4())

        headers = {
            "X-Api-App-Key": self.config.app_id,
            "X-Api-Access-Key": self.config.token,
            "X-Api-Resource-Id": "volc.bigasr.auc",
            "X-Api-Request-Id": task_id,
            "X-Api-Sequence": "-1"
        }

        request = {
            "user": {
                "uid": "fake_uid"
            },
            "audio": {
                "url": audio_url,
                "format": audio_format,
                "codec": "raw",
                "rate": sample_rate,
                "bits": 16,
                "channel": 1
            },
            "request": {
                "model_name": "bigmodel",
                "enable_itn": True,
                "enable_punc": True,
                "show_utterances": True,
                "corpus": {
                    "correct_table_name": "",
                    "context": ""
                }
            }
        }

        logger.info(f"Submitting ASR task: {task_id}")
        response = requests.post(
            self.submit_url,
            data=json.dumps(request),
            headers=headers
        )

        if 'X-Api-Status-Code' in response.headers and response.headers["X-Api-Status-Code"] == "20000000":
            logger.info(f"ASR task submitted successfully: {task_id}")
            x_tt_logid = response.headers.get("X-Tt-Logid", "")
            return task_id, x_tt_logid
        else:
            error_msg = f"Submit ASR task failed: {response.headers}"
            logger.error(error_msg)
            raise ASRException(error_msg)

    def _query_task(self, task_id: str, x_tt_logid: str) -> requests.Response:
        """
        Query speech transcription task

        Args:
            task_id: Task ID
            x_tt_logid: Log ID

        Returns:
            requests.Response: HTTP response object
        """
        headers = {
            "X-Api-App-Key": self.config.app_id,
            "X-Api-Access-Key": self.config.token,
            "X-Api-Resource-Id": "volc.bigasr.auc",
            "X-Api-Request-Id": task_id,
            "X-Tt-Logid": x_tt_logid
        }

        return requests.post(
            self.query_url,
            data=json.dumps({}),
            headers=headers
        )

    def _parse_response(self, task_id: str, response_data: Dict[str, Any]) -> ASRResult:
        """
        Parse speech recognition result

        Args:
            task_id: Task ID
            response_data: Response data

        Returns:
            ASRResult: Parsed speech recognition result
        """
        try:
            # Extract raw result
            result = response_data.get("result", {})

            # Build complete recognition text
            full_text = result.get("text", "")

            # Extract segmented results
            utterances = []
            for item in result.get("utterances", []):
                utterances.append(
                    Utterance(
                        start_time=item.get("start_time", 0),
                        end_time=item.get("end_time", 0),
                        text=item.get("text", "")
                    )
                )

            # Build result
            return ASRResult(
                status="success",
                message="Transcription completed successfully",
                task_id=task_id,
                text=full_text,
                utterances=utterances,
                raw_response=result
            )

        except Exception as e:
            logger.error(f"Error parsing ASR response: {e}")
            # Return error result
            return ASRResult(
                status="error",
                message=f"Failed to parse response: {e!s}",
                task_id=task_id,
                raw_response=response_data
            ) 
