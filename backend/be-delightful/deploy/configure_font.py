#!/usr/bin/env python3

import sys
import os
import subprocess
import matplotlib.pyplot as plt
import matplotlib.font_manager as fm

def create_font_config():
    """Create system default font configuration file, set WenQuanYi as preferred font"""

    font_config = """<?xml version="1.0"?>
<!DOCTYPE fontconfig SYSTEM "fonts.dtd">
<fontconfig>
  <alias>
    <family>sans-serif</family>
    <prefer>
      <family>WenQuanYi Zen Hei</family>
    </prefer>
  </alias>
  <alias>
    <family>serif</family>
    <prefer>
      <family>WenQuanYi Zen Hei</family>
    </prefer>
  </alias>
  <alias>
    <family>monospace</family>
    <prefer>
      <family>WenQuanYi Zen Hei Mono</family>
    </prefer>
  </alias>
</fontconfig>
"""

    # Check if directory exists, create if not
    os.makedirs("/etc/fonts", exist_ok=True)

    try:
        with open("/etc/fonts/local.conf", "w") as f:
            f.write(font_config)
        print("✓ Successfully created font configuration file: /etc/fonts/local.conf")

        # Update font cache
        subprocess.run(["fc-cache", "-fv"], check=True)
        print("✓ Successfully updated font cache")

        # Print system font configuration information
        print("\n========== System Font Configuration Information ==========")
        subprocess.run(["fc-match", "sans-serif"])
        subprocess.run(["fc-match", "serif"])
        subprocess.run(["fc-match", "monospace"])

        print("\n========== System Installed Fonts List ==========")
        result = subprocess.run(["fc-list"], capture_output=True, text=True)
        for line in result.stdout.splitlines():
            if "wenquanyi" in line.lower() or "wqy" in line.lower():
                print(line)
    except Exception as e:
        print(f"Failed to create font configuration file: {str(e)}")
        return False

    return True

def configure_matplotlib():
    """Create and configure matplotlib, set WenQuanYi as default font"""
    # Create configuration directory
    os.makedirs("/root/.config/matplotlib", exist_ok=True)

    # Configuration content
    config_content = """backend: Agg
font.family: sans-serif
font.sans-serif: WenQuanYi Zen Hei, DejaVu Sans, Arial, sans-serif
axes.unicode_minus: False
"""

    # Write configuration file
    try:
        with open("/root/.config/matplotlib/matplotlibrc", "w") as f:
            f.write(config_content)
        print("✓ Successfully created matplotlib configuration file")
    except Exception as e:
        print(f"Failed to create matplotlib configuration file: {str(e)}")
        return False

    return True

def check_matplotlib_font():
    """Check if matplotlib is currently using WenQuanYi font, if not exit image build"""

    # Create a test text to confirm the current font in use
    fig, ax = plt.subplots()
    test_text = ax.text(0.5, 0.5, 'Test text', ha='center', va='center')

    # Get font properties of text object
    font_properties = test_text.get_fontproperties()
    font_name = font_properties.get_name()

    # Close test figure
    plt.close(fig)

    print(f"Font currently used by matplotlib: {font_name}")

    # Check if it is WenQuanYi font
    if "wqy" in font_name.lower() or "wenquanyi" in font_name.lower():
        print("✓ Current font in use is WenQuanYi, meets requirements")
        return True
    else:
        print("✗ Current font in use is not WenQuanYi, exiting image build")
        sys.exit(1)  # Non-zero exit code will cause Docker build to fail

if __name__ == "__main__":
    print("Configuring system fonts...")
    create_font_config()

    print("\nConfiguring matplotlib...")
    configure_matplotlib()
