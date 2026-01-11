#!/bin/bash

# Set color output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting Go API Gateway Service...${NC}"

# Check if API gateway process is already running
check_running_process() {
    # Find running api-gateway process
    EXISTING_PID=$(pgrep -f "./api-gateway")

    if [ -n "$EXISTING_PID" ]; then
        echo -e "${RED}Detected API gateway service already running, PID: $EXISTING_PID${NC}"
        echo -e "${YELLOW}Terminating existing process...${NC}"

        # Try to terminate process gracefully
        kill $EXISTING_PID 2>/dev/null

        # Wait for process to terminate
        for i in {1..5}; do
            if ! ps -p $EXISTING_PID > /dev/null; then
                echo -e "${GREEN}Successfully terminated old process${NC}"
                return 0
            fi
            echo -e "${YELLOW}Waiting for process to terminate, attempt $i/5...${NC}"
            sleep 1
        done

        # If process still running, force terminate
        echo -e "${RED}Cannot terminate process gracefully, attempting force kill...${NC}"
        kill -9 $EXISTING_PID 2>/dev/null
        if ! ps -p $EXISTING_PID > /dev/null; then
            echo -e "${GREEN}Successfully force killed old process${NC}"
        else
            echo -e "${RED}Cannot terminate existing process, please manually kill PID: $EXISTING_PID${NC}"
            exit 1
        fi
    fi

    # Check if port 8000 is occupied
    PORT_PID=$(lsof -t -i:8000 2>/dev/null)
    if [ -n "$PORT_PID" ]; then
        echo -e "${RED}Detected port 8000 is already occupied, PID: $PORT_PID${NC}"
        echo -e "${YELLOW}Releasing port...${NC}"
        kill -9 $PORT_PID 2>/dev/null
        sleep 1
        if lsof -t -i:8000 >/dev/null 2>&1; then
            echo -e "${RED}Cannot release port 8000, please manually terminate the process occupying this port${NC}"
            exit 1
        else
            echo -e "${GREEN}Successfully released port 8000${NC}"
        fi
    fi
}

# Execute process check
check_running_process

# Check environment variable file
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    echo -e "${YELLOW}Creating sample .env file...${NC}"
    cat > .env << EOF
JWT_SECRET=your-secret-key-change-me
OPENAI_API_KEY=sk-xxxx
OPENAI_API_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4
DEFAULT_API_URL=https://api.default-service.com
API_GATEWAY_VERSION=1.0.0
EOF
    echo -e "${GREEN}Sample .env file created, please edit this file to set correct environment variables${NC}"
    echo -e "${YELLOW}Continuing to start service...${NC}"
fi

# Check Go dependencies and compile
echo -e "${YELLOW}Checking Go dependencies and compiling...${NC}"
go mod tidy
if ! go build -o api-gateway main.go; then
    echo -e "${RED}Compilation failed, please check error messages${NC}"
    exit 1
fi

# Check if executable file exists
if [ ! -f ./api-gateway ]; then
    echo -e "${RED}Compiled executable does not exist, compilation may have failed${NC}"
    exit 1
fi

# Display compilation success message
echo -e "${GREEN}Compilation successful!${NC}"

# Start API gateway service
echo -e "${YELLOW}Starting API gateway service...${NC}"
./api-gateway &
API_PID=$!

# Wait for API gateway service to start
echo -e "${YELLOW}Waiting for API gateway service to start...${NC}"
sleep 3

# Check if API gateway service is running normally
for i in {1..5}; do
    if curl -s http://localhost:8001/status > /dev/null; then
        echo -e "${GREEN}API gateway service started (PID: $API_PID)${NC}"
        echo -e "${GREEN}Service started${NC}"
        echo -e "${YELLOW}API gateway address: http://localhost:8001${NC}"

        echo -e "${BLUE}Example to get token:${NC}"
        echo -e "${BLUE}curl -X POST http://localhost:8001/auth -H \"X-USER-ID: your-user-id\"${NC}"
        echo -e "${BLUE}Note: Token request can only be initiated from localhost${NC}"
        echo -e "${BLUE}Tip: Token is permanently valid with no expiration, use Delightful-Authorization header${NC}"

        # Display Docker example
        echo -e "${BLUE}Docker container usage example:${NC}"
        echo -e "${BLUE}# 1. Get token on host machine${NC}"
        echo -e "${BLUE}TOKEN=\$(curl -s -X POST http://localhost:8001/auth -H \"X-USER-ID: your-user-id\" | jq -r '.token')${NC}"
        echo -e "${BLUE}# 2. Start container and inject token${NC}"
        echo -e "${BLUE}docker run -e API_TOKEN=\"\$TOKEN\" -e API_GATEWAY_URL=\"http://host.docker.internal:8001\" your-image${NC}"
        echo -e "${BLUE}# 3. Example of using token inside container${NC}"
        echo -e "${BLUE}curl -H \"Delightful-Authorization: Bearer \$API_TOKEN\" \$API_GATEWAY_URL/services${NC}"

        echo -e "${YELLOW}Press Ctrl+C to stop service${NC}"

        # Wait for user to press Ctrl+C
        wait
        exit 0
    else
        echo -e "${YELLOW}Waiting for service to start, attempt $i/5...${NC}"
        sleep 2
    fi
done

echo -e "${RED}API gateway service failed to start, please check logs${NC}"
kill $API_PID 2>/dev/null || true
exit 1
