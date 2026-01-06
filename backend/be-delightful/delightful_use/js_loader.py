"""
JavaScript loading management module.

Handles loading, dependency parsing, and execution of JavaScript code to provide JS functionality for browser pages.
"""

import glob
import logging
import os
import re
from pathlib import Path
from typing import Dict, List, Optional, Set

from playwright.async_api import Page

# Configure logger
logger = logging.getLogger(__name__)


class JSLoader:
    """JavaScript loader responsible for loading and managing JS code."""

    def __init__(self, page: Page):
        """Initialize the JS loader.

        Args:
            page: Playwright page object
        """
        self.page = page
        self._js_code = {}    # Store code for each module
        self._js_dir = Path(__file__).parent / "js"  # JS file directory
        self._loading_modules = set()  # Modules currently loading, used to detect circular dependencies

        # Ensure JS directory exists
        os.makedirs(self._js_dir, exist_ok=True)

    async def _parse_dependencies(self, js_code: str) -> List[str]:
        """Parse dependency declarations from JS code.

        Looks for comments like // @depends: module1, module2

        Args:
            js_code: JavaScript code

        Returns:
            List of dependency module names
        """
        dependencies = []
        # Regex to match dependency declaration comment
        pattern = r'//\s*@depends:\s*([\w\s,]+)'
        matches = re.search(pattern, js_code)

        if matches:
            # Parse and trim dependency names
            deps_str = matches.group(1)
            for dep in deps_str.split(','):
                dep_name = dep.strip()
                if dep_name:
                    dependencies.append(dep_name)

        return dependencies

    async def load_module(self, module_name: str, force_reload: bool = False) -> bool:
        """Load a JavaScript module; by default load only if missing.
        Supports automatically loading dependency modules.

        Args:
            module_name: Module name, matching filename under js/ (without .js extension)
            force_reload: Force reload even if already loaded; defaults to False

        Returns:
            bool: Whether the module loaded successfully
        """
        try:
            # Detect circular dependency
            if module_name in self._loading_modules:
                logger.error(f"Circular dependency detected: {module_name}")
                return False

            # Check if module already loaded unless forcing reload
            if not force_reload:
                check_exists_script = f"""() => {{
                    return window.DelightfulUse && window.DelightfulUse['{module_name}'];
                }}"""

                module_exists = await self.page.evaluate(check_exists_script)

                if module_exists:
                    logger.debug(f"JavaScript module {module_name} already exists, skipping load")
                    return True

            # Load JavaScript code from file
            js_path = self._js_dir / f"{module_name}.js"
            if not js_path.exists():
                logger.error(f"JavaScript module file not found: {js_path}")
                raise FileNotFoundError(f"JavaScript module file not found: {js_path}")

            js_code = js_path.read_text(encoding="utf-8")
            self._js_code[module_name] = js_code

            # Parse dependencies
            dependencies = await self._parse_dependencies(js_code)
            if dependencies:
                logger.debug(f"Module {module_name} depends on: {dependencies}")

                # Mark module as loading
                self._loading_modules.add(module_name)

                # Load dependencies first
                for dep in dependencies:
                    dep_loaded = await self.load_module(dep)
                    if not dep_loaded:
                        logger.error(f"Failed to load dependency {dep}; cannot load {module_name}")
                        self._loading_modules.remove(module_name)
                        return False

                # Dependencies done, remove loading flag
                self._loading_modules.remove(module_name)

            # Execute code directly in page via evaluate to bypass CSP
            load_script = f"""
            () => {{
                try {{
                    window.SuperDelightful = window.SuperDelightful || {{
                        'version': '0.0.1',
                    }};
                    window.DelightfulUse = window.DelightfulUse || {{}};
                    // Execute module code
                    (function() {{
                        {js_code}
                    }})();
                    window.DelightfulUse['{module_name}'] = true;
                    return true;
                }} catch (error) {{
                    console.error('Error executing module code:', error);
                    return {{
                        error: error.toString(),
                        stack: error.stack
                    }};
                }}
            }}
            """

            eval_result = await self.page.evaluate(load_script)

            # Check result
            if eval_result == True:
                logger.debug(f"JavaScript module {module_name} loaded")
                return True
            else:
                logger.error(f"JavaScript module {module_name} failed to load: {eval_result.get('error')}")
                raise Exception(f"Load failed: {eval_result.get('error')}")

        except Exception as e:
            logger.error(f"Failed to load JavaScript module {module_name}: {e}")
            if module_name in self._loading_modules:
                self._loading_modules.remove(module_name)
            raise

    async def scan_and_load_all_modules(self) -> Dict[str, bool]:
        """Scan all JS files under the js directory and load them.

        Returns:
            Dict of load results keyed by module name with success status
        """
        results = {}

        try:
            # Scan all JS files under directory
            js_files = glob.glob(str(self._js_dir / "*.js"))

            for js_file in js_files:
                # Extract module name (filename without .js extension)
                module_name = Path(js_file).stem

                try:
                    # Load module
                    await self.load_module(module_name)
                    results[module_name] = True
                except Exception as e:
                    logger.error(f"Failed to auto-load module {module_name}: {e}")
                    results[module_name] = False

            return results
        except Exception as e:
            logger.error(f"Scanning and loading JS modules failed: {e}")
            return results
