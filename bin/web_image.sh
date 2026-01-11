#!/usr/bin/env bash

### Build the web image locally
set -e

# determine swoole version to build.
TASK=${1}
TAG=${2}
CHECK=${!#}


export WEB_IMAGE="delightful/delightful-web"
export REGISTRY="ghcr.io"

# Get the absolute path to the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the absolute path to the web directory
SERVICE_DIR="$(cd "${SCRIPT_DIR}/../frontend/delightful-web" && pwd)"

function publish() {
    echo "Publishing "$TAG" ..."
    # Push origin image
    docker push ${REGISTRY}"/"${WEB_IMAGE}":"${TAG}

    echo -e "\n"
}

# Check and install buildx
check_and_install_buildx() {
    if ! docker buildx version > /dev/null 2>&1; then
        echo "Docker Buildx not detected, attempting to install..."

        # Detect the operating system and install
        if [[ "$OSTYPE" == "linux-gnu"* ]]; then
            echo "Linux system detected, installing Buildx..."
            # Linux installation method
            mkdir -p ~/.docker/cli-plugins/
            BUILDX_URL="https://github.com/docker/buildx/releases/download/v0.10.4/buildx-v0.10.4.linux-amd64"
            if ! curl -sSL "$BUILDX_URL" -o ~/.docker/cli-plugins/docker-buildx; then
                echo "Failed to download Buildx, please check your network connection or install manually: https://docs.docker.com/go/buildx/"
                exit 1
            fi
            chmod +x ~/.docker/cli-plugins/docker-buildx
        elif [[ "$OSTYPE" == "darwin"* ]]; then
            echo "macOS system detected, recommending Homebrew installation..."
            read -p "Do you want to install Docker Buildx using Homebrew? (y/n) " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                if ! command -v brew &> /dev/null; then
                    echo "Homebrew not detected, please install Homebrew first: https://brew.sh/"
                    exit 1
                fi
                brew install docker-buildx
            else
                echo "Please install Docker Buildx manually: https://docs.docker.com/go/buildx/"
                exit 1
            fi
        else
            echo "Unsupported operating system, please install Docker Buildx manually: https://docs.docker.com/go/buildx/"
            exit 1
        fi

        # Verify installation
        if docker buildx version > /dev/null 2>&1; then
            echo "Docker Buildx installed successfully: $(docker buildx version | head -n 1)"
        else
            echo "Docker Buildx installation failed, please install manually: https://docs.docker.com/go/buildx/"
            exit 1
        fi
    else
        echo "Docker Buildx is already installed: $(docker buildx version | head -n 1)"
    fi
}

# build base image
if [[ ${TASK} == "build" ]]; then
    # Check and install buildx
    check_and_install_buildx


    # Enable BuildKit
    export DOCKER_BUILDKIT=1

    echo "Building image: ${REGISTRY}/${WEB_IMAGE}:${TAG}"
    docker build -t ${REGISTRY}"/"${WEB_IMAGE}":"${TAG} -f ./frontend/delightful-web/Dockerfile.web ./frontend/delightful-web
fi

if [[ ${TASK} == "publish" ]]; then
    # Push base image
    publish $TAG
fi
