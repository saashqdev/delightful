"""Excel parsing plugin implementation"""

from pathlib import Path
from typing import Any, BinaryIO

import pandas as pd
from markitdown import (
    DocumentConverter,
    DocumentConverterResult,
    StreamInfo,
)

__plugin_interface_version__ = 1  # Plugin interface version

ACCEPTED_MIME_TYPE_PREFIXES = [
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "application/vnd.ms-excel",
]

ACCEPTED_FILE_EXTENSIONS = [".xlsx", ".xls"]

# Maximum row limits for Excel processing
EXCEL_MAX_ROWS = 1000
EXCEL_MAX_PREVIEW_ROWS = 50

class ExcelConverter(DocumentConverter):
    """Excel file converter"""

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
        """Convert Excel file to Markdown"""
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
            read_limit = EXCEL_MAX_ROWS if limit is None or limit <= 0 else limit

            # Provide pandas/openpyxl install hint
            try:
                import openpyxl  # Only imported to verify dependency availability
            except ImportError:
                return DocumentConverterResult(
                    title=None,
                    markdown="error: openpyxl is required to read Excel files: pip install openpyxl pandas",
                )

            # Get all sheet names
            excel_file = pd.ExcelFile(file_path)
            sheet_names = excel_file.sheet_names

            result_text = []
            result_text.append(f"# Excelfile: {file_path.name}")
            result_text.append(f"## contains {len(sheet_names)} sheet(s): {', '.join(sheet_names)}\n")

            # Extract data for each sheet
            for sheet_name in sheet_names:
                # Read sheet info (column count)
                df_info = pd.read_excel(file_path, sheet_name=sheet_name, nrows=0)
                col_count = len(df_info.columns)

                # Read actual data with offset/limit
                df = pd.read_excel(
                    file_path,
                    sheet_name=sheet_name,
                    skiprows=offset,
                    nrows=read_limit
                )

                # Get actual row count
                row_count = len(df)
                total_row_count = offset + row_count

                # Add sheet info
                result_text.append(f"## Sheet: {sheet_name}")
                result_text.append(f"* Columns: {col_count}")
                result_text.append(f"* Rows read: {row_count}")

                if row_count >= read_limit:
                    result_text.append(f"* Note: Actual rows may exceed {read_limit}; showing partial data")
                    result_text.append("* Tip: Use code to process this Excel, e.g.:")
                    result_text.append("```python")
                    result_text.append("import pandas as pd")
                    result_text.append(f"df = pd.read_excel('{file_path.name}', sheet_name='{sheet_name}')")
                    result_text.append("# Then use DataFrame methods to process data")
                    result_text.append("```")

                # Convert DataFrame to string
                if row_count > 0:
                    # For many rows, preview first rows
                    if row_count > EXCEL_MAX_PREVIEW_ROWS:
                        preview_df = df.head(EXCEL_MAX_PREVIEW_ROWS)
                        result_text.append(f"\n### Data preview (first {EXCEL_MAX_PREVIEW_ROWS} rows):")
                        result_text.append("```")
                        result_text.append(preview_df.to_string(index=False))
                        result_text.append("```")
                        result_text.append(f"\n* Note: Showing only {EXCEL_MAX_PREVIEW_ROWS} rows; use code for full data")
                    else:
                        result_text.append("\n### Data content:")
                        result_text.append("```")
                        result_text.append(df.to_string(index=False))
                        result_text.append("```")
                else:
                    result_text.append("\n* Sheet is empty or has no data in the specified range")

                result_text.append("\n")

            return DocumentConverterResult(
                title=None,
                markdown="\n".join(result_text),
            )
        except Exception as e:
            return DocumentConverterResult(
                title=None,
                markdown=f"Parsing Excel failed: {e!s}",
            ) 
