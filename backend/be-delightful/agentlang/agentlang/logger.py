import logging
import sys
from pathlib import Path
from typing import ClassVar, Optional

from loguru import logger as _logger

from agentlang.context.application_context import ApplicationContext


class Logger:
    """
    BeDelightful logger built on loguru.
    Provides unified log formatting and level control.
    """
    # Class variable to store instance
    _instance: ClassVar['Logger'] = None

    def __init__(self, name: str = "agentlang"):
        """
        Initialize the logger.

        Args:
            name: Logger name
        """
        self.name = name
        self.logger = _logger.bind(name=name)

    @classmethod
    def setup(cls,
              log_name: str = "agentlang",
              console_level: str = "INFO",
              logfile_level: Optional[str] = "DEBUG",
              log_file: Optional[str] = None) -> 'Logger':
        """
        Configure and return a logger instance.

        Args:
            log_name: Log file name
            console_level: Console log level
            logfile_level: File log level; if None, skip file logging
            log_file: Log file path; if None, use default path

        Returns:
            Configured Loguru logger instance
        """
        # Create or reuse a singleton instance
        if cls._instance is None:
            cls._instance = cls(log_name)

        logger_instance = cls._instance

        # Remove all default handlers
        _logger.remove()

        # Add console handler; render DEBUG level in dim gray
        _logger.configure(
            handlers=[
                {
                    "sink": sys.stderr,
                    "level": console_level,
                    "format": "<green>{time:HH:mm:ss.SSS}</green> | "
                    "<level>{level: <8}</level> | "
                    "<cyan>{file.path}</cyan>:<cyan>{line}</cyan> - "
                    "<level>{message}</level>",
                    "colorize": True,
                }
            ],
            levels=[
                {"name": "DEBUG", "color": "<dim>"}  # Use dim (gray) instead of default blue
            ],
        )

        # Add file handler if requested
        if logfile_level:
            if log_file:
                file_path = Path(log_file)
            else:
                path_manager = ApplicationContext.get_path_manager()
                log_dir = path_manager.get_logs_dir() if path_manager else Path("logs")
                file_path = log_dir / f"{log_name}.log"

            # Ensure log directory exists
            file_path.parent.mkdir(parents=True, exist_ok=True)

            _logger.add(
                file_path,
                level=logfile_level,
                format="{time:HH:mm:ss.SSS} | {level: <8} | {file.path}:{line} - {message}",
            )

        # Register with ApplicationContext
        ApplicationContext.set_logger(logger_instance)

        return logger_instance

    def bind(self, **kwargs) -> 'Logger':
        """
        Create a logger bound with context.

        Args:
            **kwargs: Keyword args to bind, e.g., name=module name

        Returns:
            A new logger instance sharing handlers but with different context
        """
        new_logger = Logger()
        new_logger.logger = self.logger.bind(**kwargs)
        if 'name' in kwargs:
            new_logger.name = kwargs['name']
        return new_logger

    # Forward all logging methods to the underlying loguru instance
    # Forward all log methods to the underlying loguru logger
    def debug(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).debug(message, *args, **kwargs)

    def info(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).info(message, *args, **kwargs)

    def warning(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).warning(message, *args, **kwargs)

    def error(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).error(message, *args, **kwargs)

    def critical(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).critical(message, *args, **kwargs)

    def exception(self, message, *args, **kwargs):
        return self.logger.opt(depth=1).exception(message, *args, **kwargs)

    # Allow use of opt like loguru
    def opt(self, *args, **kwargs):
        return self.logger.opt(*args, **kwargs)


# Export default instance
logger = Logger.setup()


def setup_logger(log_name: str = "agentlang", console_level: str = "INFO",
                logfile_level: Optional[str] = "DEBUG", log_file: Optional[str] = None) -> Logger:
    """
    Configure the logger.

    Args:
        log_name: Log file name
        console_level: Console log level
        logfile_level: File log level; if None, skip file logging
        log_file: Log file path; if None, use default path
    """
    return Logger.setup(log_name, console_level, logfile_level, log_file)


def get_logger(name: str = None) -> Logger:
    """
    Get a named logger.

    Args:
        name: Logger name, usually module name

    Returns:
        Logger instance
    """
    if name:
        return logger.bind(name=name)
    return logger


# Intercept logging from stdlib into LoguruLogger
class InterceptHandler(logging.Handler):
    """
    Handler that redirects stdlib logging messages to LoguruLogger.

    Ensures code using the stdlib logging module still uses unified output formatting.
    """

    def emit(self, record):
        # Resolve the corresponding loguru level
        # Resolve loguru level
        try:
            level = logger.logger.level(record.levelname).name
        except ValueError:
            level = record.levelno

        # Find caller frame
        frame, depth = logging.currentframe(), 2
        while frame.f_code.co_filename == logging.__file__:
            frame = frame.f_back
            depth += 1

        # Log with loguru
        logger.logger.opt(depth=depth, exception=record.exc_info).log(level, record.getMessage())


def configure_logging_intercept():
    """
    Configure interception of stdlib logging.

    Should be invoked once at startup so all stdlib logging is redirected to LoguruLogger.
    """
    # Remove all other handlers
    logging.basicConfig(handlers=[InterceptHandler()], level=0)

    # Replace existing handlers
    for name in logging.root.manager.loggerDict.keys():
        logging.getLogger(name).handlers = []
        logging.getLogger(name).propagate = True
