# delightful_use/userscript_manager.py
import asyncio
import fnmatch # Import fnmatch for URL pattern matching
import logging
import re # Import re module
from pathlib import Path
from typing import List, Optional, Dict, Any # Import Dict, Any

import aiofiles # For async file reading
from delightful_use.userscript import Userscript

logger = logging.getLogger(__name__)

# Define the path to the delightful_monkey directory relative to this file
DELIGHTFUL_MONKEY_DIR = Path(__file__).resolve().parent / "delightful_monkey"

class UserscriptManager:
    """
    Manage loading, parsing, caching and matching of Userscripts (user scripts).

    Adopts module-level singleton pattern.
    """
    _instance: Optional['UserscriptManager'] = None
    _lock = asyncio.Lock()  # Class lock for protecting singleton instantiation process

    # Regular expression for parsing Userscript metadata block
    _METADATA_BLOCK_RE = re.compile(r"// ==UserScript==\s*(.*?)\s*// ==/UserScript==", re.DOTALL)
    _METADATA_LINE_RE = re.compile(r"// @(\S+)\s+(.*)")

    def __init__(self, userscript_dir: Path):
        """
        Private constructor.

        Args:
            userscript_dir: Directory containing userscript .js files.
        """
        if not userscript_dir.exists() or not userscript_dir.is_dir():
            logger.warning(f"Userscript directory does not exist or is not a directory: {userscript_dir}, will not load any scripts.")
            # Allow directory to not exist, just don't load scripts
            self._userscript_dir = None # Mark directory as invalid
        else:
            self._userscript_dir = userscript_dir
        self._scripts: List[Userscript] = [] # Cache parsed scripts
        self._load_lock = asyncio.Lock() # Async lock for protecting load process
        self._initialized = False # Add initialization flag

    @classmethod
    async def get_instance(cls) -> 'UserscriptManager':
        """
        Get singleton instance of UserscriptManager.

        If instance doesn't exist, create it asynchronously.
        Use class lock to ensure thread/task safety.
        """
        if cls._instance is None:
            async with cls._lock:
                # Double-checked locking to prevent multiple coroutines creating instance simultaneously
                if cls._instance is None:
                    # Assume DELIGHTFUL_MONKEY_DIR is a Path object defined in app.paths
                    instance = cls(DELIGHTFUL_MONKEY_DIR)
                    # Start loading scripts immediately after instance creation
                    # Note: Here we don't directly await load_scripts in constructor or get_instance
                    # Rather let the caller decide when to load, or load when PageRegistry initializes
                    cls._instance = instance
        return cls._instance

    async def _parse_script_file(self, file_path: Path) -> Optional[Userscript]:
        """
        Parse a single userscript file.

        Args:
            file_path: Path to the .js file.

        Returns:
            Returns Userscript object if parsing succeeds, otherwise returns None.
        """
        try:
            async with aiofiles.open(file_path, mode='r', encoding='utf-8') as f:
                content = await f.read()

            metadata_match = self._METADATA_BLOCK_RE.search(content)
            if not metadata_match:
                logger.warning(f"Script file missing metadata block: {file_path}")
                return None

            metadata_content = metadata_match.group(1)
            metadata: Dict[str, Any] = {
                "match_patterns": [],
                "exclude_patterns": [],
                "run_at": "document-end" # Default value
            }
            has_name = False

            for line in metadata_content.strip().splitlines():
                line = line.strip()
                match = self._METADATA_LINE_RE.match(line)
                if match:
                    key, value = match.groups()
                    key = key.lower() # Unified lowercase processing
                    value = value.strip()

                    if key == "match":
                        metadata["match_patterns"].append(value)
                    elif key == "exclude":
                        metadata["exclude_patterns"].append(value)
                    elif key == "name":
                        metadata[key] = value
                        has_name = True
                    elif key in ["version", "description", "run-at"]:
                        # Record only the first appearing tag (for non-list types)
                        if key not in metadata or key in ["run-at"]: # run-at use last one
                             metadata[key] = value
                    # Can add processing for other metadata tags (e.g. @grant, @require) as needed
                    else:
                        logger.debug(f"Unhandled metadata tag found in {file_path}: @{key}")

            if not has_name:
                logger.warning(f"Script file missing required @name tag: {file_path}")
                return None

            # Extract script body content (all content after metadata block)
            script_body = content[metadata_match.end():].strip()
            if not script_body:
                 logger.warning(f"Script file missing actual execution content: {file_path}")
                 # Allow no script body? Or return None? Temporarily allow.
                 # return None

            return Userscript(
                name=metadata.get("name"),
                file_path=file_path,
                content=script_body,
                version=metadata.get("version"),
                description=metadata.get("description"),
                match_patterns=metadata.get("match_patterns", []),
                exclude_patterns=metadata.get("exclude_patterns", []),
                run_at=metadata.get("run_at", "document-end"),
            )

        except OSError as e:
            logger.error(f"Failed to read script file: {file_path}, Error: {e}")
            return None
        except ValueError as e: # Userscript's __post_init__ might throw ValueError
            logger.error(f"Failed to create Userscript object ({file_path}): {e}")
            return None
        except Exception as e:
            logger.error(f"Unexpected error when parsing script file: {file_path}, Error: {e}")
            return None

    async def load_scripts(self):
        """
        Asynchronously scan script directory, parse all .js files and cache results.

        Use lock to ensure only one load operation at a time.
        """
        # If directory is invalid, return directly
        if self._userscript_dir is None:
            logger.info("Userscript directory is invalid, skip script loading.")
            self._initialized = True # Mark as initialized (even if no load)
            return

        # Prevent duplicate initialization or concurrent loading
        async with self._load_lock:
            if self._initialized:
                logger.debug("Userscripts already loaded, skip.")
                return

            logger.info(f"Start loading userscripts from {self._userscript_dir}...")
            loaded_scripts: List[Userscript] = []
            tasks = []

            # Use pathlib's rglob to find all .js files
            try:
                 script_files = [f for f in self._userscript_dir.rglob("*.js") if f.is_file()]
            except Exception as e:
                 logger.error(f"Failed to scan userscript directory: {self._userscript_dir}, Error: {e}")
                 script_files = [] # Do not load if error

            for file_path in script_files:
                 # Create a parsing task for each file
                 tasks.append(asyncio.create_task(self._parse_script_file(file_path)))

            if tasks:
                 results = await asyncio.gather(*tasks)
                 for script in results:
                     if script:
                         loaded_scripts.append(script)
                 logger.info(f"Successfully loaded {len(loaded_scripts)} userscripts.")
            else:
                 logger.info("No userscript files found in the specified directory.")


            self._scripts = loaded_scripts # Update cache
            self._initialized = True # Mark initialization complete

    async def reload_scripts(self):
         """Force reload all scripts"""
         async with self._load_lock: # Acquire lock
             self._initialized = False # Reset initialization flag
             self._scripts.clear() # Clear cache
             logger.info("Force reload userscripts...")
         await self.load_scripts() # Reload

    def get_matching_scripts(self, url: str, run_at: str = "document-end") -> List[Userscript]:
        """
        Find matching userscripts based on URL and injection timing.

        Args:
            url: URL of the current page.
            run_at: Expected script injection timing (e.g. "document-end", "document-start").

        Returns:
            List of matching Userscript objects.
        """
        if not self._initialized:
            logger.warning("Userscript manager not yet initialized, cannot get matching scripts. Please call load_scripts() first.")
            # Or trigger a loading here? Depends on design decision
            # await self.load_scripts() # If auto-loading is needed
            return []

        if not url: # If URL is empty or None, don't match
            return []

        matching_scripts: List[Userscript] = []
        for script in self._scripts:
            # 1. Check if injection timing matches
            if script.run_at != run_at:
                continue

            # 2. Check if URL matches @match rule
            is_matched = False
            if not script.match_patterns: # If no @match rule, don't match any page by default
                logger.debug(f"Script '{script.name}' has no @match rule, skip URL: {url}")
                continue
            for pattern in script.match_patterns:
                # Use fnmatch for simple wildcard matching
                # Note: This may not fully cover all complex matching rules in Tampermonkey
                # But can handle common * wildcard
                if fnmatch.fnmatch(url, pattern):
                    is_matched = True
                    break # Matching any one @match rule is enough

            if not is_matched:
                continue # If no @match rule matched, skip this script

            # 3. Check if URL matches @exclude rule
            is_excluded = False
            for pattern in script.exclude_patterns:
                if fnmatch.fnmatch(url, pattern):
                    is_excluded = True
                    break # Matching any one @exclude rule is enough

            if is_excluded:
                continue # If matched exclude rule, skip this script

            # 4. If pass all checks, add to result list
            matching_scripts.append(script)
            logger.debug(f"URL '{url}' matches script '{script.name}' (run_at={run_at})")

        return matching_scripts

# Can optionally create singleton instance here if async retrieval is not needed
# But async retrieval provides better flexibility, especially if initialization involves async operations
# _userscript_manager_instance = UserscriptManager(DELIGHTFUL_MONKEY_DIR)
# def get_userscript_manager():
#     return _userscript_manager_instance
