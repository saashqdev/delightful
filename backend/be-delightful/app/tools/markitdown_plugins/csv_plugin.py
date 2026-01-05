"""CSV parsing plugin implementation"""

from pathlib import Path
from typing import Any, BinaryIO

from markitdown import (
    DocumentConverter,
    DocumentConverterResult,
    StreamInfo,
)

__plugin_interface_version__ = 1  # Plugin interface version

ACCEPTED_MIME_TYPE_PREFIXES = [
    "text/csv",
]

ACCEPTED_FILE_EXTENSIONS = [".csv"]

# Maximum row limits for CSV processing
CSV_MAX_ROWS = 1000
CSV_MAX_PREVIEW_ROWS = 50

class CSVConverter(DocumentConverter):
    """CSV file converter"""

    def accepts(
        self,
        file_stream: BinaryIO,
        stream_info: StreamInfo,
        **kwargs: Any,
    ) -> bool:
        """Check whether this file type is accepted"""
        mimetype = (stream_info.mimetype or "").lower()
        extension = (stream_info.extension or "").lower()

        if extension in ACCEPTED_FILE_EXTENSIONS:
            return True

        for prefix in ACCEPTED_MIME_TYPE_PREFIXES:
            if mimetype.startswith(prefix):
                return True

        return False

    def convert(
        self,
        file_stream: BinaryIO,
        stream_info: StreamInfo,
        **kwargs: Any,
    ) -> DocumentConverterResult:
        """Convert CSV file to Markdown"""
        try:
            # Get file path
            file_path = Path(file_stream.name) if hasattr(file_stream, 'name') and file_stream.name else None
            if not file_path:
                return DocumentConverterResult(
                    title=None,
                    markdown="error: cannot get file path",
                )

            # Get offset and limit parameters
            offset = kwargs.get('offset', 0)
            limit = kwargs.get('limit', None)

            # Use default max rows if limit not specified or <=0
            read_limit = CSV_MAX_ROWS if limit is None or limit <= 0 else limit

            # Provide pandas install hint
            try:
                import pandas as pd
            except ImportError:
                return DocumentConverterResult(
                    title=None,
                    markdown="error: pandas is required to read CSV files: pip install pandas",
                )

            # Try different encodings to read CSV
            encodings = ['utf-8', 'gbk', 'gb2312', 'latin1']
            df = None
            used_encoding = None

            for encoding in encodings:
                try:
                    if offset > 0:
                        df = pd.read_csv(
                            file_path,
                            encoding=encoding,
                            skiprows=range(1, offset + 1),  # Keep header, skip other rows
                            nrows=read_limit
                        )
                    else:
                        df = pd.read_csv(
                            file_path,
                            encoding=encoding,
                            nrows=read_limit
                        )
                    used_encoding = encoding
                    break
                except UnicodeDecodeError:
                    continue
                except Exception as e:
                    return DocumentConverterResult(
                        title=None,
                        markdown=f"Reading CSV with encoding {encoding} failed: {e!s}",
                    )

            if df is None:
                return DocumentConverterResult(
                    title=None,
                    markdown=f"error: failed to read CSV with common encodings (utf-8, gbk, etc.): {file_path.name}",
                )

            # Get row/column info
            row_count = len(df)
            col_count = len(df.columns)

            result_text = []
            result_text.append(f"# CSVfile: {file_path.name}")
            result_text.append(f"* Encoding used: {used_encoding}")
            result_text.append(f"* Columns: {col_count}")
            result_text.append(f"* Rows read: {row_count}")

            if row_count >= read_limit:
                result_text.append(f"* Note: Actual rows may exceed {read_limit}; showing partial data")
                result_text.append("* Tip: Use code to process this CSV, e.g.:")
                result_text.append("```python")
                result_text.append("import pandas as pd")
                result_text.append(f"df = pd.read_csv('{file_path.name}', encoding='{used_encoding}')")
                result_text.append("# Then use DataFrame methods to process data")
                result_text.append("```")

            # Convert DataFrame to string
            if row_count > 0:
                # For many rows, preview first rows
                if row_count > CSV_MAX_PREVIEW_ROWS:
                    preview_df = df.head(CSV_MAX_PREVIEW_ROWS)
                    result_text.append(f"\n## Data preview (first {CSV_MAX_PREVIEW_ROWS} rows):")
                    result_text.append("```")
                    result_text.append(preview_df.to_string(index=False))
                    result_text.append("```")
                    result_text.append(f"\n* Note: Showing only {CSV_MAX_PREVIEW_ROWS} rows; use code for full data")
                else:
                    result_text.append("\n## Data content:")
                    result_text.append("```")
                    result_text.append(df.to_string(index=False))
                    result_text.append("```")
            else:
                result_text.append("\n* CSV file is empty or has no data in the specified range")

            return DocumentConverterResult(
                title=None,
                markdown="\n".join(result_text),
            )
        except Exception as e:
            return DocumentConverterResult(
                title=None,
                markdown=f"Parsing CSV failed: {e!s}",
            ) 
