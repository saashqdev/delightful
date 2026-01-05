#!/usr/bin/env bash
set -e
set -x

if (( "$#" != 1 ))
then
    echo "Usage: $0 <composer_name>"
    echo "Example: $0 api-response"
    exit 1
fi

NOW=$(date +%s)
COMPOSE_NAME=$1
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Get path info (hide command output to avoid showing paths)
set +x  # Temporarily disable command echo
# Get the absolute path to the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the absolute path to the backend directory
SERVICE_DIR="$(cd "${SCRIPT_DIR}/../backend" && pwd)"
# Get the absolute path to the repository root
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
set -x  # Re-enable command echo

# Load environment variables (quiet)
set +x  # Temporarily disable command echo
if [ -f "${ROOT_DIR}/.env" ]; then
    echo "Loading environment variables..."
    source "${ROOT_DIR}/.env"
fi
set -x  # Re-enable command echo

# Use the env variable for Git repo URL, default to GitHub
if [ -z "${GIT_REPO_URL}" ]; then
    # Use default value if env var is not set
    GIT_REPO_URL="git@github.com:dtyq"
fi
REMOTE_URL="${GIT_REPO_URL}/${COMPOSE_NAME}.git"

# Add a confirmation step to avoid accidental publishing
echo "Preparing to publish component to remote repository: ${COMPOSE_NAME} -> ${REMOTE_URL}"
if [[ $REMOTE_URL == *"github"* ]]; then
    echo "🔔 Note: Publishing code to GitHub repository"
elif [[ $REMOTE_URL == *"gitlab"* ]]; then
    echo "🔔 Note: Publishing code to GitLab repository"
fi

read -p "Do you want to continue? (y/n): " confirm
if [[ $confirm != "y" && $confirm != "Y" ]]; then
    echo "Publishing cancelled"
    exit 0
fi

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    # Check whether the remote already exists
    if git remote | grep -q "^$1$"; then
        CURRENT_URL=$(git remote get-url $1)
        if [ "$CURRENT_URL" != "$2" ]; then
            echo "⚠️ Warning: Remote '$1' exists but points to a different URL"
            echo "Current URL: $CURRENT_URL"
            echo "Expected URL: $2"
            read -p "Do you want to update the remote URL? (y/n): " update_remote
            if [[ $update_remote == "y" || $update_remote == "Y" ]]; then
                echo "Updating remote URL..."
                git remote set-url $1 $2
            else
                echo "❌ Operation cancelled: Remote URL mismatch"
                exit 1
            fi
        fi
    else
        git remote add $1 $2
    fi
}

# Handle git pull more robustly
echo "Checking remote branch status..."
if git ls-remote --heads origin $CURRENT_BRANCH | grep -q $CURRENT_BRANCH; then
    echo "Remote branch exists, pulling now..."
    git pull origin $CURRENT_BRANCH
else
    echo "Remote branch does not exist, skipping pull operation"
fi

# Initialize remote connection
echo "Initializing remote connection..."
remote $COMPOSE_NAME $REMOTE_URL

# Split the subtree and push
echo "Splitting and pushing..."
split "backend/$COMPOSE_NAME" $COMPOSE_NAME

TIME=$(echo "$(date +%s) - $NOW" | bc)

printf "Execution time: %f seconds" $TIME
