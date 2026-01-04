#!/bin/bash

# Set colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Get project root directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." &> /dev/null && pwd )"

# Enter project root directory
cd "$PROJECT_ROOT"

# Detect OS type
OS_TYPE="$(uname -s)"

echo -e "${GREEN}=== Sandbox Container Management Tool ===${NC}\n"

# Display options menu
echo -e "Please select an operation:"
echo -e "  1. Clean up all sandbox containers"
echo -e "  2. Clean up only exited sandbox containers"
echo -e "  3. Clean up exited sandbox containers older than specified days"
echo -e "  4. Only stop sandbox containers (without deleting)"
echo -e "  5. Cancel operation"
read -p "Please enter option [1-5]: " OPTION

# Set container filter based on option
case $OPTION in
    1)
        echo -e "\n${GREEN}=== Finding all sandbox containers to clean ===${NC}\n"
        FILTER_ARGS="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\""
        ACTION="clean"
        ;;
    2)
        echo -e "\n${GREEN}=== Finding exited sandbox containers to clean ===${NC}\n"
        FILTER_ARGS="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=exited\""
        ACTION="clean"
        ;;
    3)
        echo -e "\n${GREEN}=== Finding exited sandbox containers older than specified days to clean ===${NC}\n"
        # Get user input for number of days
        while true; do
            read -p "Please enter the number of days for containers to clean (must be a positive integer): " DAYS_FILTER
            if [[ "$DAYS_FILTER" =~ ^[0-9]+$ ]] && [ "$DAYS_FILTER" -gt 0 ]; then
                break
            else
                echo -e "${RED}Error: Please enter a valid positive integer for days${NC}"
            fi
        done
        
        echo -e "\n${GREEN}=== Finding sandbox containers exited more than ${DAYS_FILTER} days ago ===${NC}\n"
        
        # Base filter condition
        FILTER_ARGS="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=exited\""
        ACTION="clean_by_days"
        ;;
    4)
        echo -e "\n${GREEN}=== Finding sandbox containers to stop ===${NC}\n"
        FILTER_ARGS="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=running\""
        ACTION="stop"
        ;;
    5|*)
        echo -e "${YELLOW}Operation cancelled${NC}"
        exit 0
        ;;
esac

# Use container name prefixes "sandbox-agent-" and "sandbox-qdrant-" to identify sandbox containers to operate on
SANDBOX_CONTAINERS=$(eval "docker ps -a --format \"{{.ID}} {{.Names}} {{.Status}}\" $FILTER_ARGS")

# If filtering by days, further processing is needed
if [ "$ACTION" == "clean_by_days" ]; then
    # Calculate filter timestamp (current time minus user-specified days)
    # Use different date command based on operating system
    if [[ "$OS_TYPE" == "Darwin" ]]; then
        # macOS version
        FILTER_DATE=$(date -v-${DAYS_FILTER}d +%s)
    else
        # Linux version
        FILTER_DATE=$(date --date="${DAYS_FILTER} days ago" +%s)
    fi
    
    # Temporary storage for containers that meet the criteria
    FILTERED_CONTAINERS=""
    
    # Iterate through all exited containers and check exit time
    for CONTAINER_ID in $(echo "$SANDBOX_CONTAINERS" | awk '{print $1}'); do
        # Get container exit time
        FINISH_TIME=$(docker inspect --format='{{.State.FinishedAt}}' $CONTAINER_ID)
        
        # Use different timestamp conversion command based on operating system
        if [[ "$OS_TYPE" == "Darwin" ]]; then
            # macOS version, remove Z suffix and convert
            FINISH_TIMESTAMP=$(date -jf "%Y-%m-%dT%H:%M:%S" "${FINISH_TIME%.*}" +%s)
        else
            # Linux version
            FINISH_TIMESTAMP=$(date --date="${FINISH_TIME}" +%s)
        fi
        
        # If container exit time is earlier than filter time, add to list
        if [ $FINISH_TIMESTAMP -lt $FILTER_DATE ]; then
            CONTAINER_INFO=$(echo "$SANDBOX_CONTAINERS" | grep $CONTAINER_ID)
            if [ -z "$FILTERED_CONTAINERS" ]; then
                FILTERED_CONTAINERS="$CONTAINER_INFO"
            else
                FILTERED_CONTAINERS="$FILTERED_CONTAINERS\n$CONTAINER_INFO"
            fi
        fi
    done
    
    # Update container list to filtered list
    SANDBOX_CONTAINERS=$FILTERED_CONTAINERS
fi

if [ -z "$SANDBOX_CONTAINERS" ]; then
    echo -e "${YELLOW}No sandbox containers to operate on${NC}"
    exit 0
fi

# Count containers
COUNT=$(echo "$SANDBOX_CONTAINERS" | wc -l)
echo -e "${YELLOW}Found ${COUNT} sandbox container(s) to operate on:${NC}"

# Display all containers to be operated on
echo -e "${YELLOW}Container list to be operated on:${NC}"
if [ "$OPTION" -eq 2 ] || [ "$OPTION" -eq 3 ]; then
    # For exited containers, display ID more explicitly
    echo -e "${YELLOW}Container ID\tContainer Name\tStatus${NC}"
    echo -e "$SANDBOX_CONTAINERS" | awk '{print $1 "\t" $2 "\t" substr($0, index($0,$3))}'
else
    # Keep original format
    echo -e "$SANDBOX_CONTAINERS" | awk '{print "  - " $2 " (" $1 ") - Status: " substr($0, index($0,$3))}'
fi
echo ""

# Request user confirmation
if [ "$ACTION" == "clean" ] || [ "$ACTION" == "clean_by_days" ]; then
    if [ "$OPTION" -eq 3 ]; then
        read -p "Do you want to stop and delete these containers that exited more than ${DAYS_FILTER} days ago? (y/N): " CONFIRM
    else
        read -p "Do you want to stop and delete these containers? (y/N): " CONFIRM
    fi
else
    read -p "Do you want to stop these containers? (y/N): " CONFIRM
fi

if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Operation cancelled${NC}"
    exit 0
fi

if [ "$ACTION" == "clean" ] || [ "$ACTION" == "clean_by_days" ]; then
    echo -e "\n${GREEN}=== Starting cleanup of sandbox containers ===${NC}"
else
    echo -e "\n${GREEN}=== Starting to stop sandbox containers ===${NC}"
fi

# Process all sandbox containers
for CONTAINER_INFO in $(echo -e "$SANDBOX_CONTAINERS" | awk '{print $1";"$2}'); do
    CONTAINER_ID=$(echo $CONTAINER_INFO | cut -d';' -f1)
    CONTAINER_NAME=$(echo $CONTAINER_INFO | cut -d';' -f2)
    
    if [ "$ACTION" == "clean" ] || [ "$ACTION" == "clean_by_days" ]; then
        echo -e "${YELLOW}Stopping and deleting container: ${CONTAINER_NAME} (${CONTAINER_ID})${NC}"
        
        # Try to stop container
        if docker stop $CONTAINER_ID > /dev/null 2>&1; then
            echo -e "  - Container stopped"
        else
            echo -e "${RED}  - Failed to stop container${NC}"
        fi
        
        # Try to remove container
        if docker rm $CONTAINER_ID > /dev/null 2>&1; then
            echo -e "  - Container deleted"
        else
            echo -e "${RED}  - Failed to delete container${NC}"
        fi
    else
        echo -e "${YELLOW}Stopping container: ${CONTAINER_NAME} (${CONTAINER_ID})${NC}"
        
        # Try to stop container
        if docker stop $CONTAINER_ID > /dev/null 2>&1; then
            echo -e "  - Container stopped"
        else
            echo -e "${RED}  - Failed to stop container${NC}"
        fi
    fi
done

if [ "$ACTION" == "clean" ] || [ "$ACTION" == "clean_by_days" ]; then
    echo -e "\n${GREEN}=== Sandbox container cleanup completed ===${NC}"

    # Display status after completion
    if [ "$OPTION" -eq 1 ]; then
        # Check all containers
        FILTER_CHECK="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\""
    elif [ "$OPTION" -eq 3 ]; then
        # No check here, as containers filtered by days may have all been deleted
        echo -e "${GREEN}Cleanup of sandbox containers exited more than ${DAYS_FILTER} days ago completed${NC}"
        exit 0
    else
        # Check exited containers
        FILTER_CHECK="--filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=exited\""
    fi

    if [ "$OPTION" -ne 3 ]; then
        REMAINING=$(eval "docker ps -a --format \"{{.ID}}\" $FILTER_CHECK" | wc -l)
        if [ "$REMAINING" -eq 0 ]; then
            echo -e "${GREEN}All sandbox containers that needed cleanup have been successfully cleared${NC}"
        else
            echo -e "${RED}Warning: ${REMAINING} sandbox container(s) could not be cleared${NC}"
            if [ "$OPTION" -eq 1 ]; then
                echo -e "${YELLOW}You can try running again with force option:${NC}"
                echo -e "  docker rm -f \$(docker ps -a --filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" -q)"
            else
                echo -e "${YELLOW}You can try running again with force option:${NC}"
                echo -e "  docker rm -f \$(docker ps -a --filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=exited\" -q)"
            fi
        fi
    fi
else
    echo -e "\n${GREEN}=== Sandbox container stop completed ===${NC}"
    
    # Check if any containers failed to stop
    STILL_RUNNING=$(eval "docker ps --format \"{{.ID}}\" --filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" --filter \"status=running\"" | wc -l)
    if [ "$STILL_RUNNING" -eq 0 ]; then
        echo -e "${GREEN}All sandbox containers have been successfully stopped${NC}"
    else
        echo -e "${RED}Warning: ${STILL_RUNNING} sandbox container(s) are still running${NC}"
        echo -e "${YELLOW}You can try using force option to stop:${NC}"
        echo -e "  docker stop \$(docker ps --filter \"name=sandbox-agent-\" --filter \"name=sandbox-qdrant-\" -q)"
    fi
fi 