#!/usr/bin/env bash
set -e

if (( "$#" != 2 ))
then
    echo "Usage: $0 <package_name> <version>"
    echo "Example: $0 api-response 1.0.0"
    exit 1
fi

NOW=$(date +%s)
COMPOSE_NAME=$1
VERSION=$2

# Always prepend with "v"
if [[ $VERSION != v*  ]]
then
    VERSION="v$VERSION"
fi

# Get the absolute path to the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the absolute path to the frontend directory
SERVICE_DIR="$(cd "${SCRIPT_DIR}/../frontend" && pwd)"
# Get the absolute path to the repository root
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Load environment variables
if [ -f "${ROOT_DIR}/.env" ]; then
    export $(grep -v '^#' "${ROOT_DIR}/.env" | xargs)
fi

echo ""
echo ""
echo "Cloning ${COMPOSE_NAME}";
TMP_DIR="/tmp/delightful-split"
# Use the env variable for Git repo URL, default to GitHub
if [ -z "${GIT_REPO_URL}" ]; then
    # Use default value if env var is not set
    GIT_REPO_URL="git@github.com:delightful"
fi
REMOTE_URL="${GIT_REPO_URL}/${COMPOSE_NAME}.git"

rm -rf $TMP_DIR;
mkdir $TMP_DIR;

(
    cd $TMP_DIR;
    git clone $REMOTE_URL;
    echo "git clone ${REMOTE_URL} success";
    ls -l;
    cd ${COMPOSE_NAME};

    # Get the default branch name
    DEFAULT_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5);
    git checkout $DEFAULT_BRANCH;

    # Back up the original Dockerfile
    # if [ -f Dockerfile ]; then
    #     mv Dockerfile Dockerfile.bak
    # fi

    # Copy component files from the frontend directory
    echo "${SERVICE_DIR}/${COMPOSE_NAME}"
    cp -a "${SERVICE_DIR}/${COMPOSE_NAME}"/* .
    # cp -a "${SERVICE_DIR}/${COMPOSE_NAME}"/.gitignore .


    # Add and commit the changes
    git add .
    git commit -m "chore: update service files for version ${VERSION}"

    if [[ $(git log --pretty="%d" -n 1 | grep tag --count) -eq 0 ]]; then
        echo "Releasing ${COMPOSE_NAME}"
        git tag $VERSION
        git push origin $DEFAULT_BRANCH
        git push origin --tags
    fi
)

TIME=$(echo "$(date +%s) - $NOW" | bc)

printf "Execution time: %f seconds" $TIME
