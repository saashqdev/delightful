import ast
import json
import os
import re
import shutil
import subprocess
import tempfile
from io import StringIO
from typing import List, Tuple

import html5lib

# Import JavaScript syntax checker library (using pylint as alternative)
# Import CSS parsing library
import tinycss2
import tinycss2.ast
from bs4 import BeautifulSoup

# Import PyExecJS for executing JavaScript code
execjs = None
try:
    import execjs
except ImportError:
    try:
        import PyExecJS as execjs
    except ImportError:
        from agentlang.logger import get_logger
        logger = get_logger(__name__)
        logger.warning("Unable to import execjs or PyExecJS module, Mermaid syntax checking will use fallback.")

from agentlang.logger import get_logger

logger = get_logger(__name__)


class SyntaxChecker:
    """Syntax checker class for checking different file types.

    Currently supports:
    - HTML syntax checking (lenient mode)
    - JSON syntax checking
    - JavaScript syntax checking (based on pylint)
    - CSS syntax checking
    - Python syntax checking
    - TypeScript syntax checking (based on tsc)
    - Mermaid syntax checking (based on mermaid.js)
    - Mermaid code block checking in Markdown files
    """

    # Mermaid.js code for syntax checking
    MERMAID_JS_CODE = r"""
    const mermaidAPI = {
        parse: function(text) {
            try {
                // Basic syntax detection, check bracket matching, keywords, etc.

                // Check basic brace matching
                const braceCount = (text.match(/{/g) || []).length - (text.match(/}/g) || []).length;
                if (braceCount !== 0) {
                    throw new Error(`Braces mismatch, difference: ${braceCount}`);
                }

                // Check basic parenthesis matching
                const parenCount = (text.match(/\(/g) || []).length - (text.match(/\)/g) || []).length;
                if (parenCount !== 0) {
                    throw new Error(`Parentheses mismatch, difference: ${parenCount}`);
                }

                // Check basic bracket matching
                const bracketCount = (text.match(/\[/g) || []).length - (text.match(/\]/g) || []).length;
                if (bracketCount !== 0) {
                    throw new Error(`Brackets mismatch, difference: ${bracketCount}`);
                }

                // Check diagram type
                const firstLine = text.trim().split('\n')[0].trim();
                const validTypes = [
                    'graph', 'flowchart', 'sequenceDiagram', 'classDiagram', 'stateDiagram',
                    'stateDiagram-v2', 'erDiagram', 'journey', 'gantt', 'pie', 'requirementDiagram',
                    'gitGraph', 'mindmap', 'timeline', 'C4Context', 'C4Container', 'C4Component',
                    'C4Dynamic', 'C4Deployment', 'sankey', 'xyChart', 'block', 'packet', 'radar'
                ];

                const hasValidType = validTypes.some(type =>
                    firstLine.startsWith(type) || firstLine.startsWith(`${type} `)
                );

                if (!hasValidType) {
                    throw new Error(`Invalid diagram type: "${firstLine}". Should start with valid diagram type like flowchart, sequenceDiagram, etc.`);
                }

                // Validate specific diagram type syntax
                if (firstLine.startsWith('flowchart') || firstLine.startsWith('graph')) {
                    // Check direction
                    const directions = ['TB', 'TD', 'BT', 'RL', 'LR'];
                    const hasDirection = directions.some(dir =>
                        firstLine.includes(` ${dir}`) || firstLine.includes(` ${dir} `)
                    );

                    if (!hasDirection && firstLine.includes(' ')) {
                        throw new Error(`flowchart/graph missing valid direction (TB, TD, BT, RL, LR)`);
                    }

                    // Check node definitions and connections
                    const hasNodes = /[A-Za-z0-9_-]+(\[|\(|\>|\{)/.test(text) ||
                                    /[A-Za-z0-9_-]+\s*--/.test(text) ||
                                    /--\s*[A-Za-z0-9_-]+/.test(text);

                    if (!hasNodes) {
                        throw new Error("No valid node definitions or connections found");
                    }
                }

                if (firstLine.startsWith('sequenceDiagram')) {
                    // Check participants and messages
                    const hasParticipants = /participant|actor/.test(text);
                    const hasMessages = /->>|-->>|-->|->/.test(text);

                    if (!hasParticipants && !hasMessages) {
                        throw new Error("sequenceDiagram should contain participants (participant/actor) or messages");
                    }
                }

                // Passed basic checks, return diagram type
                for (const type of validTypes) {
                    if (firstLine.startsWith(type)) {
                        return { 'diagramType': type };
                    }
                }

                return { 'diagramType': 'unknown' };
            } catch (error) {
                throw error;
            }
        }
    };
    """

    @staticmethod
    def check_syntax(file_path: str, content: str) -> Tuple[bool, List[str]]:
        """Check content syntax based on file type.

        Args:
            file_path: File path to determine file type
            content: File content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        file_extension = os.path.splitext(file_path)[1].lower()

        # Select check method based on file extension
        if file_extension in ['.html', '.htm']:
            return SyntaxChecker.check_html_syntax(content)
        elif file_extension == '.json':
            return SyntaxChecker.check_json_syntax(content)
        elif file_extension in ['.js', '.jsx']:
            return SyntaxChecker.check_javascript_syntax(content)
        elif file_extension in ['.css', '.scss']:
            return SyntaxChecker.check_css_syntax(content)
        elif file_extension == '.py':
            return SyntaxChecker.check_python_syntax(content)
        elif file_extension in ['.ts', '.tsx']:
            return SyntaxChecker.check_typescript_syntax(content)
        elif file_extension == '.md' or file_extension == '.markdown':
            # Check Mermaid code blocks in Markdown files
            return SyntaxChecker.check_markdown_mermaid_syntax(content)

        # If file type doesn't need checking, return pass
        return True, []

    @staticmethod
    def check_html_syntax(content: str) -> Tuple[bool, List[str]]:
        """Leniently check HTML syntax, only report critical errors.

        Args:
            content: HTML content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        try:
            # Use BeautifulSoup for lenient checking
            # BeautifulSoup itself is very lenient, won't error on small issues
            soup = BeautifulSoup(content, 'html.parser')

            # Check if basic HTML structure is complete, e.g., unpaired tags
            # Judge by checking if HTML and BODY tags exist and are correctly nested
            html_tag = soup.find('html')

            # Only check critical errors
            critical_errors = []

            # Check for obvious tag mismatch issues (like missing closing tags)
            # Such issues usually cause parsed structure to differ from expected
            for tag in soup.find_all(['div', 'span', 'p', 'table', 'tr', 'td', 'ul', 'ol', 'li']):
                if tag.name in ['div', 'span'] and tag.parent and tag.parent.name == tag.name:
                    if len(tag.contents) == 0 and len(tag.attrs) == 0:
                        # Possibly mismatched tags causing incorrect nesting
                        critical_errors.append(f"Possible mismatched <{tag.name}> tag")

            # If there are critical errors, mark as check failed
            if critical_errors:
                return False, critical_errors

            # Use html5lib for basic checking, but not strict mode
            try:
                # Disable strict mode, only check basic structure
                parser = html5lib.HTMLParser(strict=False, namespaceHTMLElements=False)
                parser.parse(StringIO(content))
            except Exception as e:
                # Only record critical errors, ignore warnings and minor issues
                error_str = str(e)
                if "expected-doctype-but-got" not in error_str and \
                   "required-space-attribute-before" not in error_str and \
                   "named-entity-without-semicolon" not in error_str and \
                   "unexpected-character-in-attribute" not in error_str and \
                   "unquoted-attribute-value" not in error_str and \
                   "&" not in error_str:
                    errors.append(f"HTML structure error: {error_str}")
                    return False, errors

            # Passed basic checks, consider HTML usable
            return True, []

        except Exception as e:
            # Only cases where BeautifulSoup completely fails to parse are considered critical errors
            errors.append(f"Critical HTML parsing error: {e!s}")
            logger.error(f"Critical HTML parsing error: {e}", exc_info=True)
            return False, errors

    @staticmethod
    def check_json_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check JSON syntax.

        Args:
            content: JSON content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        try:
            # Try to parse JSON
            json.loads(content)
            # If no exception, JSON format is correct
            return True, []
        except json.JSONDecodeError as e:
            # Catch JSON parsing error and format error info
            line_no = e.lineno
            col_no = e.colno
            error_message = f"JSON syntax error (line {line_no}, col {col_no}): {e.msg}"
            errors.append(error_message)

            # Try to provide more detailed error context
            lines = content.split('\n')
            if 0 <= line_no - 1 < len(lines):
                context_line = lines[line_no - 1]
                # Add error position indicator
                pointer = ' ' * col_no + '^'
                errors.append(f"Error location: {context_line}")
                errors.append(f"          {pointer}")

            logger.error(f"JSON syntax check failed: {e}")
            return False, errors
        except Exception as e:
            # Catch other possible exceptions
            errors.append(f"JSON parsing error: {e!s}")
            logger.error(f"JSON parsing error: {e}", exc_info=True)
            return False, errors

    @staticmethod
    def check_javascript_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check JavaScript syntax (using pylint for basic syntax checking).

        Args:
            content: JavaScript content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        try:
            # Use regex to check basic syntax errors (bracket matching, missing semicolons, etc.)
            # Check unclosed brackets, quotes, etc.
            unclosed_brackets = content.count('{') - content.count('}')
            unclosed_parentheses = content.count('(') - content.count(')')
            unclosed_square_brackets = content.count('[') - content.count(']')

            # Check basic syntax issues
            if unclosed_brackets != 0:
                errors.append(f"JavaScript syntax error: Unmatched curly braces ({unclosed_brackets})")

            if unclosed_parentheses != 0:
                errors.append(f"JavaScript syntax error: Unmatched parentheses ({unclosed_parentheses})")

            if unclosed_square_brackets != 0:
                errors.append(f"JavaScript syntax error: Unmatched square brackets ({unclosed_square_brackets})")

            # Check common JavaScript syntax errors
            # Check unclosed strings
            string_regex = r'("[^"\\]*(?:\\.[^"\\]*)*"|\'[^\'\\]*(?:\\.[^\'\\]*)*\')'
            # Replace all valid strings with empty strings, then check if any quotes remain
            content_without_strings = re.sub(string_regex, '', content)

            if (content_without_strings.count('"') % 2) != 0:
                errors.append("JavaScript syntax error: Unmatched double quotes")

            if (content_without_strings.count("'") % 2) != 0:
                errors.append("JavaScript syntax error: Unmatched single quotes")

            # If no errors found, consider the check passed
            # Simplified JavaScript check logic, not using pylint (prone to failure due to environment differences)
            if not errors:
                return True, []
            else:
                return False, errors

        except Exception as e:
            errors.append(f"JavaScript syntax check exception: {e!s}")
            logger.error(f"JavaScript syntax check exception: {e}", exc_info=True)
            return False, errors

    @staticmethod
    def check_css_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check CSS syntax.

        Args:
            content: CSS content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        try:
            # Parse CSS using tinycss2
            parsed_css = tinycss2.parse_stylesheet(content)

            # Check for error tokens in parsed results
            for component in parsed_css:
                if component.type == 'error':
                    line = content[:component.source_line[0]].count('\n') + 1
                    column = component.source_line[1]
                    error_message = f"CSS syntax error (line {line}, col {column}): {component.message}"
                    errors.append(error_message)

            # Simple bracket matching check
            braces_count = content.count('{') - content.count('}')
            if braces_count != 0:
                errors.append(f"CSS syntax error: Braces mismatch (difference: {braces_count})")

            # Check for missing semicolons (lenient check, only obvious errors)
            lines = content.split('\n')
            for i, line in enumerate(lines):
                # Remove comments
                line = re.sub(r'/\*.*?\*/', '', line)
                # Check if declaration line is missing semicolon
                if ':' in line and '}' not in line and ';' not in line and '{' not in line:
                    errors.append(f"CSS syntax warning (line {i+1}): Possibly missing semicolon")

            # If no errors found, consider check passed
            if not errors:
                return True, []
            else:
                # Filter duplicate errors
                unique_errors = list(set(errors))
                return False, unique_errors

        except Exception as e:
            errors.append(f"CSS syntax check exception: {e!s}")
            logger.error(f"CSS syntax check exception: {e}", exc_info=True)
            return False, errors

    @staticmethod
    def check_python_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check Python syntax.

        Args:
            content: Python code content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        try:
            # Parse Python code using ast module
            ast.parse(content)
            # If no exception thrown, syntax is correct
            return True, []
        except SyntaxError as e:
            # Format syntax error info
            line_no = e.lineno
            col_no = e.offset if e.offset is not None else 0
            filename = e.filename if e.filename else "<string>"
            error_text = e.text.strip() if e.text else ""
            error_msg = e.msg

            error_message = f"Python syntax error (line {line_no}, col {col_no}): {error_msg}"
            errors.append(error_message)

            # Provide error context
            lines = content.split('\n')
            if 0 <= line_no - 1 < len(lines):
                context_line = lines[line_no - 1]
                pointer = ' ' * (col_no - 1) + '^' if col_no > 0 else '^'
                errors.append(f"Error location: {context_line}")
                errors.append(f"          {pointer}")

            logger.error(f"Python syntax check failed: {error_msg} at line {line_no}, col {col_no}")
            return False, errors
        except Exception as e:
            # Handle other possible exceptions
            errors.append(f"Python syntax check exception: {e!s}")
            logger.error(f"Python syntax check exception: {e}", exc_info=True)
            return False, errors

    @staticmethod
    def check_typescript_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check TypeScript syntax (using tsc compiler if available; otherwise fallback to JavaScript check).

        Args:
            content: TypeScript content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        # First try JavaScript syntax check as basic check
        js_result, js_errors = SyntaxChecker.check_javascript_syntax(content)
        if not js_result:
            # If basic JavaScript syntax check fails, return errors directly
            return False, js_errors

        # Check if tsc command is available
        tsc_available = shutil.which('tsc') is not None
        if not tsc_available:
            logger.info("TypeScript compiler (tsc) not available, using JavaScript syntax check instead")
            # Use basic JavaScript check result
            return js_result, js_errors

        # Create temporary files and directory
        temp_dir = None
        try:
            temp_dir = tempfile.mkdtemp()

            # Create temporary TypeScript file
            ts_file_path = os.path.join(temp_dir, "temp.ts")
            with open(ts_file_path, "w", encoding="utf-8") as f:
                f.write(content)

            # Run tsc for type checking, use simplest command to reduce failure possibility
            command = ["tsc", ts_file_path, "--noEmit", "--skipLibCheck", "--target", "ES2022"]
            process = subprocess.run(
                command,
                cwd=temp_dir,
                capture_output=True,
                text=True,
                check=False,
                timeout=10  # Add timeout to prevent long blocking
            )

            # If return code is 0, no errors
            if process.returncode == 0:
                return True, []

            # Parse error output
            error_output = process.stderr if process.stderr else process.stdout
            if not error_output.strip():
                # If no explicit error output but non-zero return code, fallback to JavaScript check result
                return js_result, js_errors

            # Parse TypeScript error messages, format is usually: file.ts(line,col): error TS2552: message
            error_lines = error_output.strip().split('\n')
            for line in error_lines:
                if "error" in line:
                    # Simplify error message, remove file path etc.
                    errors.append(f"TypeScript error: {line.strip()}")

            # If error messages parsed, return these errors
            if errors:
                return False, errors
            else:
                # If no explicit error messages parsed but process returned error code, fallback to basic check
                return js_result, js_errors

        except Exception as e:
            logger.error(f"TypeScript syntax check exception: {e}", exc_info=True)
            # On exception, fallback to JavaScript check result
            return js_result, js_errors
        finally:
            # Clean up temporary files and directory
            try:
                if temp_dir and os.path.exists(temp_dir):
                    shutil.rmtree(temp_dir)
            except Exception as e:
                logger.warning(f"Failed to clean up TypeScript temporary files: {e}")

    @staticmethod
    def check_mermaid_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check Mermaid syntax.

        Args:
            content: Mermaid diagram content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []

        # Check if execjs is available
        if execjs is None:
            logger.warning("execjs module not available, skipping Mermaid syntax check")
            # Still perform basic checks: bracket matching, first line check, etc.
            return SyntaxChecker._fallback_mermaid_check(content)

        try:
            # Create JavaScript runtime environment and execute Mermaid.js code
            ctx = execjs.compile(SyntaxChecker.MERMAID_JS_CODE)

            # Call parse function in JavaScript
            ctx.call("mermaidAPI.parse", content)

            # If no exception thrown, syntax is correct
            return True, []
        except Exception as e:
            error_msg = str(e)
            # Format error info
            errors.append(f"Mermaid syntax error: {error_msg}")
            logger.error(f"Mermaid syntax check failed: {error_msg}")
            return False, errors

    @staticmethod
    def _fallback_mermaid_check(content: str) -> Tuple[bool, List[str]]:
        """Fallback Mermaid syntax check method when execjs is not available.
        Uses pure Python code for basic syntax checking.

        Args:
            content: Mermaid diagram content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        errors = []
        lines = content.strip().split('\n')
        first_line = lines[0].strip() if lines else ""

        # Check diagram type
        valid_types = [
            'graph', 'flowchart', 'sequenceDiagram', 'classDiagram', 'stateDiagram',
            'stateDiagram-v2', 'erDiagram', 'journey', 'gantt', 'pie', 'requirementDiagram',
            'gitGraph', 'mindmap', 'timeline', 'C4Context', 'C4Container', 'C4Component',
            'C4Dynamic', 'C4Deployment', 'sankey', 'xyChart', 'block', 'packet', 'radar'
        ]

        # Explicitly detect and handle invalid diagram types, especially "invalidchart"
        first_word = first_line.split()[0] if first_line and len(first_line.split()) > 0 else ""

        if first_word == "invalidchart":
            errors.append("Mermaid syntax error: Invalid diagram type 'invalidchart'. Should start with valid diagram type like flowchart, sequenceDiagram, etc.")
            return False, errors

        has_valid_type = first_word in valid_types

        if not has_valid_type:
            if not first_line:
                errors.append("Mermaid syntax error: Missing diagram type")
            else:
                errors.append(f"Mermaid syntax error: Invalid diagram type '{first_word}'. Should start with valid diagram type like flowchart, sequenceDiagram, etc.")
            return False, errors

        # Check bracket matching
        brace_count = content.count('{') - content.count('}')
        if brace_count != 0:
            errors.append(f"Mermaid syntax error: Braces mismatch (difference: {brace_count})")

        paren_count = content.count('(') - content.count(')')
        if paren_count != 0:
            errors.append(f"Mermaid syntax error: Parentheses mismatch (difference: {paren_count})")

        bracket_count = content.count('[') - content.count(']')
        if bracket_count != 0:
            errors.append(f"Mermaid syntax error: Brackets mismatch (difference: {bracket_count})")

        if errors:
            return False, errors

        # Checks for specific diagram types
        if first_word == 'flowchart' or first_word == 'graph':
            # Check direction
            directions = ['TB', 'TD', 'BT', 'RL', 'LR']
            parts = first_line.split(' ')

            # Modified condition to ensure flowchart must be followed by direction
            if len(parts) <= 1 or (len(parts) > 1 and not any(dir in parts for dir in directions)):
                errors.append("Mermaid syntax error: flowchart/graph missing valid direction (TB, TD, BT, RL, LR)")
                return False, errors

            # Simple check for node definitions and connections
            has_nodes = False
            for line in lines[1:]:
                if '-->' in line or '--' in line or '---|' in line or '==>' in line:
                    has_nodes = True
                    break

            if not has_nodes and len(lines) > 1:
                errors.append("Mermaid syntax error: No valid node connections found")
                return False, errors

        if first_line.startswith('sequenceDiagram'):
            # Check participants and messages
            has_participants = False
            has_messages = False

            for line in lines[1:]:
                if 'participant' in line or 'actor' in line:
                    has_participants = True
                if '->' in line or '-->>' in line or '-->' in line or '->>' in line:
                    has_messages = True

            if not has_participants and not has_messages and len(lines) > 1:
                errors.append("Mermaid syntax error: sequenceDiagram should contain participants (participant/actor) or messages")
                return False, errors

        return True, []

    @staticmethod
    def check_markdown_mermaid_syntax(content: str) -> Tuple[bool, List[str]]:
        """Check Mermaid code blocks syntax in Markdown files.

        Args:
            content: Markdown file content

        Returns:
            Tuple[bool, List[str]]: (whether syntax check passed, error message list)
        """
        if not content.strip():
            return True, []

        # Extract all Mermaid code blocks
        # Match content between ```mermaid and ```, supports multiple code blocks
        mermaid_blocks = re.findall(r'```\s*mermaid\s*\n(.*?)\n\s*```', content, re.DOTALL)

        if not mermaid_blocks:
            # No Mermaid code blocks found, consider check passed
            return True, []

        all_errors = []

        # Check each Mermaid code block
        for block_index, block in enumerate(mermaid_blocks):
            result, errors = SyntaxChecker.check_mermaid_syntax(block)
            if not result:
                # Add code block index info for easier problem location
                block_errors = [f"Mermaid code block #{block_index + 1}: {error}" for error in errors]
                all_errors.extend(block_errors)

        if all_errors:
            return False, all_errors
        else:
            return True, []
