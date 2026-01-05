import io  # Import io module
from pathlib import Path

import aiofiles
from markitdown import MarkItDown, StreamInfo

from agentlang.logger import get_logger
from app.tools.markitdown_plugins.pdf_plugin import PDFConverter

logger = get_logger(__name__)

# Initialize MarkItDown instance here to reuse it
md = MarkItDown()
md.register_converter(PDFConverter()) # Register only PDF for this util

async def convert_pdf_locally(pdf_path: Path) -> str | None:
    """
    Convert local PDF files to Markdown text using MarkItDown.

    No longer responsible for file writing.

    Args:
        pdf_path: Path object pointing to the source PDF file.

    Returns:
        Converted Markdown text string, returns None if conversion fails.
    """
    logger.info(f"Starting local PDF to text conversion: {pdf_path}")

    try:
        # 1. Asynchronously read the entire PDF file content into memory
        async with aiofiles.open(pdf_path, "rb") as f:
            pdf_content_bytes = await f.read()

        # 2. Wrap the bytes in memory into a BytesIO object
        pdf_stream = io.BytesIO(pdf_content_bytes)

        # 3. Pass the BytesIO object to MarkItDown for conversion
        # MarkItDown/Magika requires synchronous BinaryIO
        result = md.convert(
            pdf_stream,
            stream_info=StreamInfo(extension='.pdf', mimetype='application/pdf'),
            offset=0,
            limit=-1
        )

        if not result or not result.markdown:
            logger.error(f"Local PDF conversion failed (MarkItDown did not return content): {pdf_path}")
            return None

        logger.info(f"Local PDF to text conversion succeeded: {pdf_path}")
        return result.markdown # Return text content directly

    except FileNotFoundError:
        logger.error(f"Local PDF to text conversion failed: source file not found {pdf_path}")
        return None
    except Exception as e:
        logger.exception(f"Unexpected error occurred during local PDF to text conversion ({pdf_path}): {e!s}")
        return None

# Ensure the directory exists for the module if needed
# (usually handled by Python's import system, but good practice if creating dynamically)
# Example: Path(__file__).parent.mkdir(parents=True, exist_ok=True)
