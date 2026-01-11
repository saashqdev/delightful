#!/usr/bin/env bash
set -e
set -x

# Get path info (hide command output to avoid showing paths)
set +x  # Temporarily disable command echo
# Get the absolute path to the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the absolute path to the service directory
SERVICE_DIR="$(cd "${SCRIPT_DIR}/../backend/delightful-service" && pwd)"
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
    GIT_REPO_URL="git@github.com:delightful"
fi
REMOTE_URL="${GIT_REPO_URL}/delightful-service.git"

# Check whether this is a GitHub repo; otherwise treat it as GitLab
IS_GITHUB=false
if [[ $REMOTE_URL == *"github"* ]]; then
    IS_GITHUB=true
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Get version number or branch name
if (( "$#" == 1 )); then
    VERSION=$1
    # Always prepend with "v"
    if [[ $VERSION != v*  ]]; then
        VERSION="v$VERSION"
    fi
    USE_BRANCH=false
    TARGET_BRANCH=$CURRENT_BRANCH
else
    if [[ $IS_GITHUB == false ]]; then
        # If not GitHub and no version provided, use current branch
        echo "No version provided, using current branch: ${CURRENT_BRANCH}"
        USE_BRANCH=true
        TARGET_BRANCH=$CURRENT_BRANCH
    else
        echo "Tag has to be provided"
        exit 1
    fi
fi

NOW=$(date +%s)

# Add a confirmation step to avoid accidental publishing
echo "Preparing to publish to remote repository: ${REMOTE_URL}"
if [[ $IS_GITHUB == true ]]; then
    echo "🔔 Note: Publishing code to GitHub repository"
    echo "🔔 Using version: ${VERSION}"
else
    echo "🔔 Note: Publishing code to GitLab repository"
    if [[ $USE_BRANCH == true ]]; then
        echo "🔔 Using branch: ${CURRENT_BRANCH}"
    else
        echo "🔔 Using version: ${VERSION}"
    fi
fi

read -p "Do you want to continue? (y/n): " confirm
if [[ $confirm != "y" && $confirm != "Y" ]]; then
    echo "Publishing cancelled"
    exit 0
fi

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$TARGET_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
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
remote delightful-service $REMOTE_URL

# Split the subtree and push
echo "Splitting and pushing..."
split "backend/delightful-service" delightful-service

# Tag and push the tag
if [[ $USE_BRANCH == false ]]; then
    echo "Tagging and pushing tag..."
    git fetch delightful-service || true
    git tag -a $VERSION -m "Release $VERSION" $CURRENT_BRANCH
    git push delightful-service $VERSION
fi

TIME=$(echo "$(date +%s) - $NOW" | bc)

printf "Execution time: %f seconds" $TIME