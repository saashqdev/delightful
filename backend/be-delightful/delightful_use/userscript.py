# delightful_use/userscript.py
import dataclasses
from pathlib import Path
from typing import List, Optional


@dataclasses.dataclass(frozen=True)
class Userscript:
    """
    Encapsulates information about a Tampermonkey script (Userscript).

    Uses frozen=True to make instances immutable, increasing reliability.
    """
    name: str                  # Script name (@name)
    file_path: Path            # Script source file path
    content: str               # Script JS code content

    # Optional metadata fields, providing default values
    version: Optional[str] = None          # Version (@version)
    description: Optional[str] = None      # Description (@description)
    match_patterns: List[str] = dataclasses.field(default_factory=list)  # List of matched URL patterns (@match)
    exclude_patterns: List[str] = dataclasses.field(default_factory=list) # List of excluded URL patterns (@exclude)
    run_at: str = "document-end"          # Injection timing (@run-at), default is document-end

    def __post_init__(self):
        # Validation logic can be added here, such as checking if name and content are empty
        if not self.name:
            raise ValueError("Userscript name cannot be empty.")
        if not self.content:
            raise ValueError("Userscript content cannot be empty.")
        if not self.file_path:
            raise ValueError("Userscript file_path cannot be empty.")
