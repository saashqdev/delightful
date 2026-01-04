#!/bin/bash

# Set colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Get script directory and project root directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." &> /dev/null && pwd )"

echo -e "${GREEN}=== Starting Sandbox Gateway Service ===${NC}\n"

# Enter project root directory
cd "$PROJECT_ROOT"

# Check and create virtual environment
if [ ! -d ".venv" ]; then
    echo -e "${YELLOW}Creating virtual environment...${NC}"
    python -m venv .venv
fi

# Activate virtual environment
source .venv/bin/activate

# Install dependencies
echo -e "${YELLOW}Installing dependencies...${NC}"
python -m pip install -r requirements.txt

# Optional port parameter
PORT=${1:-8003}

# Start sandbox gateway
echo -e "${YELLOW}Starting sandbox gateway service, listening on port: ${PORT}...${NC}"
python main.py $PORT 