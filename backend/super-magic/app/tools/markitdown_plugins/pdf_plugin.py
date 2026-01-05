"""PDF parsing plugin implementation"""

import re
from pathlib import Path
from typing import Any, BinaryIO

import PyPDF2
from markitdown import (
    DocumentConverter,
    DocumentConverterResult,
    StreamInfo,
)

__plugin_interface_version__ = 1  # Plugin interface version

ACCEPTED_MIME_TYPE_PREFIXES = [
    "application/pdf",
]

ACCEPTED_FILE_EXTENSIONS = [".pdf"]

class PDFConverter(DocumentConverter):
    """PDF file converter"""

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
        """Convert PDF file to Markdown"""
        try:
            result_text = []

            file_name = "PDF Document"
            if hasattr(file_stream, 'name') and file_stream.name:
                file_name = Path(file_stream.name).stem

            result_text.append(f"# {file_name}")
            result_text.append("")

            # Extract metadata
            doc_info = []

            # Read PDF file
            pdf_reader = PyPDF2.PdfReader(file_stream)

            # Get page count
            num_pages = len(pdf_reader.pages)
            doc_info.append(f"- Pages: {num_pages}")

            # Extract document metadata
            if pdf_reader.metadata:
                metadata = pdf_reader.metadata
                if metadata.title:
                    doc_info.append(f"- Title: {metadata.title}")
                if metadata.author:
                    doc_info.append(f"- Author: {metadata.author}")
                if metadata.subject:
                    doc_info.append(f"- Subject: {metadata.subject}")
                if metadata.creator:
                    doc_info.append(f"- Created by: {metadata.creator}")
                if hasattr(metadata, 'creation_date') and metadata.creation_date:
                    doc_info.append(f"- Creation date: {metadata.creation_date}")

            # Add document info block
            if doc_info:
                result_text.append("## Document info")
                result_text.append("")
                result_text.extend(doc_info)
                result_text.append("")

            # Get offset and limit parameters
            offset = kwargs.get('offset', 0)
            limit = kwargs.get('limit', None)

            # Calculate page range to read
            start_page = max(0, offset)
            end_page = num_pages if limit is None or limit <= 0 else min(num_pages, start_page + limit)

            # Add page range note when partial read
            if start_page > 0 or (end_page < num_pages and limit is not None and limit > 0):
                result_text.append(f"## Showing pages {start_page + 1} to {end_page} (total {num_pages})")
                result_text.append("")

            # Extract content page by page
            for page_num in range(start_page, end_page):
                page = pdf_reader.pages[page_num]

                # Add page heading
                result_text.append(f"## Page {page_num + 1}")

                # Extract text
                try:
                    text = page.extract_text()
                    if text:
                        # Detect potential tables
                        table_detected = self._detect_table(text)
                        if table_detected:
                            text = self._process_potential_table(text)

                        # Mark possible image regions
                        text = self._mark_potential_images(text)

                        # Format text
                        text = re.sub(r'\n{3,}', '\n\n', text)
                        text = re.sub(r'(?m)^(\s*)\*(\s+)', r'\1-\2', text)
                        text = re.sub(r'(?m)^(\s*)(\d+)\.(\s+)', r'\1\2.\3', text)
                        text = re.sub(r'(?m)^(\s*)>(\s+)', r'\1>\2', text)
                        text = self._detect_headings(text)

                        # Add formatted text
                        result_text.append(text)
                    else:
                        result_text.append("[No text content]")
                        result_text.append("\n*Note: This page may be image-only; text could not be extracted*\n")
                except Exception as e:
                    result_text.append(f"[Text extraction failed: {e!s}]")

                # Add page separator
                result_text.append("")

            # Add before/after page hints
            if start_page > 0:
                result_text.insert(len(doc_info) + 5 if doc_info else 3, f"*Note: First {start_page} page(s) not shown; set offset=0 to view*\n")

            if end_page < num_pages:
                result_text.append(f"*Note: {num_pages - end_page} page(s) not shown; increase limit or adjust offset to view*")

            return DocumentConverterResult(
                title=None,
                markdown="\n".join(result_text),
            )
        except Exception as e:
            return DocumentConverterResult(
                title=None,
                markdown=f"Parsing PDF failed: {e!s}",
            )

    def _detect_table(self, text: str) -> bool:
        """Detect whether text likely contains tables"""
        lines = text.split('\n')
        if len(lines) < 3:
            return False

        space_patterns = []
        for line in lines:
            pattern = ''.join('s' if char.isspace() else 'c' for char in line)
            compressed = ''
            current = ''
            count = 0

            for char in pattern:
                if char == current:
                    count += 1
                else:
                    if current:
                        compressed += f"{current}{count}"
                    current = char
                    count = 1

            if current:
                compressed += f"{current}{count}"

            space_patterns.append(compressed)

        similar_patterns = 0
        for i in range(1, len(space_patterns)):
            if self._patterns_similar(space_patterns[i-1], space_patterns[i]):
                similar_patterns += 1
                if similar_patterns >= 2:
                    return True
            else:
                similar_patterns = 0

        return False

    def _patterns_similar(self, pattern1: str, pattern2: str) -> bool:
        """Check if two space patterns are similar"""
        p1_spaces = pattern1.count('s')
        p2_spaces = pattern2.count('s')

        return (abs(p1_spaces - p2_spaces) / max(p1_spaces, p2_spaces, 1) < 0.3 and
                abs(len(pattern1) - len(pattern2)) / max(len(pattern1), len(pattern2), 1) < 0.3)

    def _process_potential_table(self, text: str) -> str:
        """Process potential table text into markdown table format"""
        lines = text.split('\n')
        result_lines = []

        i = 0
        while i < len(lines):
            line = lines[i]

            if i + 2 < len(lines) and self._is_potential_table_row(line) and self._is_potential_table_row(lines[i+1]):
                table_lines = []
                header = line
                table_lines.append(header)

                separator = "|"
                for col in self._split_table_columns(header):
                    separator += " --- |"
                table_lines.append(separator)

                j = i + 1
                while j < len(lines) and self._is_potential_table_row(lines[j]):
                    table_lines.append(lines[j])
                    j += 1

                markdown_table = []
                for table_line in table_lines:
                    md_line = "|"
                    for col in self._split_table_columns(table_line):
                        md_line += f" {col.strip()} |"
                    markdown_table.append(md_line)

                result_lines.append("\n*Detected potential table content:*\n")
                result_lines.extend(markdown_table)
                result_lines.append("\n*Note: Table generated automatically from text; may be inaccurate*\n")

                i = j
            else:
                result_lines.append(line)
                i += 1

        return '\n'.join(result_lines)

    def _is_potential_table_row(self, line: str) -> bool:
        """Check if a line is likely part of a table"""
        if len(line.strip()) < 10:
            return False

        space_blocks = len(re.findall(r'\s{2,}', line))
        return space_blocks >= 2 and len(re.sub(r'\s+', '', line)) > 5

    def _split_table_columns(self, line: str) -> list:
        """Split a table row into columns"""
        return re.split(r'\s{2,}', line.strip())

    def _mark_potential_images(self, text: str) -> str:
        """Mark potential image regions"""
        patterns = [
            r'\u56fe\s*\d+[\s\.:\uff1a]',
            r'figure\s*\d+[\s\.:\uff1a]',
            r'fig\.\s*\d+[\s\.:\uff1a]',
            r'image\s*\d+[\s\.:\uff1a]',
            r'photo\s*\d+[\s\.:\uff1a]',
            r'illustration\s*\d+[\s\.:\uff1a]'
        ]

        for pattern in patterns:
            text = re.sub(
                pattern=f'({pattern})(.*?)($|\n\n)',
                repl=r'\1\2\n\n*[Image placeholder: likely image content here]*\n\3',
                string=text,
                flags=re.DOTALL | re.IGNORECASE
            )

        return text

    def _detect_headings(self, text: str) -> str:
        """Detect and format headings"""
        lines = text.split('\n')
        result_lines = []

        for i, line in enumerate(lines):
            line_stripped = line.strip()

            if (len(line_stripped) < 60 and
                ((re.match(r'^\d+\.\s+\S+', line_stripped) and
                 (i+1 >= len(lines) or not lines[i+1].strip())) or
                (line_stripped and
                 (i+1 >= len(lines) or not lines[i+1].strip()) and
                 (i == 0 or not lines[i-1].strip())))):

                if re.match(r'^\d+\.\s+\S+', line_stripped):
                    level = len(re.match(r'^(\d+)\.', line_stripped).group(1))
                    level = min(level, 3)
                    result_lines.append('#' * level + ' ' + line_stripped)
                else:
                    result_lines.append('### ' + line_stripped)
            else:
                result_lines.append(line)

        return '\n'.join(result_lines) 
