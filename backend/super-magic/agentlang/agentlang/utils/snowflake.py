"""Snowflake algorithm service module, provides unique ID generation functionality."""

import random
import time
from typing import Optional

from agentlang.logger import get_logger

logger = get_logger(__name__)


class Snowflake:
    """Snowflake ID generator.

    The generated ID structure is as follows (64 bits):
    0 - Sign bit, always 0
    1~41 - Timestamp (milliseconds), 41 bits, usable for about 69 years
    42~51 - Worker machine ID, 10 bits, supports up to 1024 nodes
    52~63 - Sequence number, 12 bits, max 4096 IDs per millisecond

    Based on JavaScript implementation, uses random machine ID and time offset based on year 2021.

    Attributes:
        machine_id: Machine ID (0-1023)
        sequence: Sequence number (0-4095)
        last_timestamp: Last timestamp when ID was generated
        offset: Timestamp offset, defaults to timestamp of Jan 1, 2021
    """

    # Bit counts for each part
    MACHINE_ID_BITS = 10
    SEQUENCE_BITS = 12

    # Maximum values
    MAX_MACHINE_ID = -1 ^ (-1 << MACHINE_ID_BITS)  # 1023
    MAX_SEQUENCE = -1 ^ (-1 << SEQUENCE_BITS)  # 4095

    # Offsets
    MACHINE_ID_SHIFT = SEQUENCE_BITS  # 12
    TIMESTAMP_SHIFT = SEQUENCE_BITS + MACHINE_ID_BITS  # 22

    # Default start timestamp: Jan 1, 2021 00:00:00 UTC
    DEFAULT_OFFSET = (2021 - 1970) * 365 * 24 * 3600 * 1000

    def __init__(self, machine_id: Optional[int] = None, offset: Optional[int] = None):
        """Initialize snowflake ID generator.

        Args:
            machine_id: Machine ID (0-1023), defaults to random value
            offset: Timestamp offset, defaults to timestamp of Jan 1, 2021

        Raises:
            ValueError: If machine_id is out of range
        """
        # If machine_id is not provided, generate random machine_id
        if machine_id is None:
            machine_id = random.randint(0, self.MAX_MACHINE_ID)

        if machine_id > self.MAX_MACHINE_ID or machine_id < 0:
            raise ValueError(f"machine_id cannot be greater than {self.MAX_MACHINE_ID} or less than 0")

        self.machine_id = machine_id
        self.sequence = 0
        self.last_timestamp = -1

        # If offset is not provided, use default value
        self.offset = offset if offset is not None else self.DEFAULT_OFFSET

        logger.info(f"Snowflake ID generator initialized successfully, machine_id: {machine_id}, offset: {self.offset}")

    def _get_next_timestamp(self, last_timestamp: int) -> int:
        """Get next timestamp, wait if current timestamp is less than or equal to last timestamp.

        Args:
            last_timestamp: Last timestamp when ID was generated

        Returns:
            int: New timestamp
        """
        timestamp = self._get_time()
        while timestamp <= last_timestamp:
            timestamp = self._get_time()
        return timestamp

    def _get_time(self) -> int:
        """Get current timestamp (milliseconds).

        Returns:
            int: Current timestamp
        """
        return int(time.time() * 1000)

    def get_id(self) -> int:
        """Generate next snowflake algorithm ID.

        Returns:
            int: Generated unique ID

        Raises:
            RuntimeError: When clock moves backward
        """
        timestamp = self._get_time()

        # Clock backward detection
        if timestamp < self.last_timestamp:
            clock_backward = self.last_timestamp - timestamp
            logger.warning(f"Clock backward detected: {clock_backward}ms")

            # If backward time is less than 5ms, can wait
            if clock_backward < 5:
                time.sleep(clock_backward / 1000)
                timestamp = self._get_time()
            else:
                raise RuntimeError(f"Clock moved backward, refusing to generate ID, backward time: {clock_backward}ms")

        # If within the same millisecond, increment sequence number
        if timestamp == self.last_timestamp:
            self.sequence = (self.sequence + 1) & self.MAX_SEQUENCE
            # If sequence number exhausted within same millisecond, wait for next millisecond
            if self.sequence == 0:
                timestamp = self._get_next_timestamp(self.last_timestamp)
        else:
            # Different millisecond, reset sequence number
            self.sequence = 0

        # Update last timestamp
        self.last_timestamp = timestamp

        # Generate ID
        snowflake_id = (
            ((timestamp - self.offset) << self.TIMESTAMP_SHIFT)
            | (self.machine_id << self.MACHINE_ID_SHIFT)
            | self.sequence
        )

        return snowflake_id

    @classmethod
    def parse_id(cls, snowflake_id: int, offset: Optional[int] = None) -> dict:
        """Parse snowflake ID, extract timestamp, machine ID, and sequence number.

        Args:
            snowflake_id: Snowflake algorithm ID
            offset: Timestamp offset, defaults to class default value

        Returns:
            dict: Dictionary containing ID component information
        """
        if not isinstance(snowflake_id, int):
            raise ValueError("snowflake_id must be an integer")

        if offset is None:
            offset = cls.DEFAULT_OFFSET

        timestamp_bits = 41
        machine_id_bits = 10
        sequence_bits = 12

        sequence_mask = -1 ^ (-1 << sequence_bits)
        machine_id_mask = -1 ^ (-1 << machine_id_bits)

        sequence = snowflake_id & sequence_mask
        machine_id = (snowflake_id >> sequence_bits) & machine_id_mask
        timestamp = (snowflake_id >> (sequence_bits + machine_id_bits)) + offset

        # Convert to readable time
        time_obj = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(timestamp / 1000))

        return {"timestamp": timestamp, "datetime": time_obj, "machine_id": machine_id, "sequence": sequence}

    @classmethod
    def create_default(cls) -> "Snowflake":
        """Create snowflake algorithm service instance with default configuration.

        Returns:
            Snowflake: New snowflake ID generator instance
        """
        # Generate a random machine ID, similar to Math.floor(Math.random() * 1e10) in JavaScript
        # But needs to be limited to valid range (0-1023)
        random_machine_id = random.randint(0, cls.MAX_MACHINE_ID)
        return cls(machine_id=random_machine_id) 
