#!/usr/bin/env python3

import sys
import os
import subprocess
import matplotlib.pyplot as plt
import matplotlib.font_manager as fm

def check_matplotlib_font():
    """Check if matplotlib is currently using WenQuanYi font. If not, exit the image build"""

    # Create test text to confirm current font in use
    fig, ax = plt.subplots()
    test_text = ax.text(0.5, 0.5, 'test text', ha='center', va='center')

    # Get text object font properties
    font_properties = test_text.get_fontproperties()
    font_name = font_properties.get_name()

    # Close test figure
    plt.close(fig)

    print(f"Current font used by matplotlib: {font_name}")

    # Check if it is WenQuanYi font
    if "wqy" in font_name.lower() or "wenquanyi" in font_name.lower():
        print("✓ The font currently in use is WenQuanYi, which meets requirements")
        return True
    else:
        print("✗ The font currently in use is not WenQuanYi, exiting image build")
        sys.exit(1)  # Non-zero exit code will cause Docker build to fail

if __name__ == "__main__":
    print("\nChecking matplotlib font configuration...")
    check_matplotlib_font()
