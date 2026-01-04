#!/bin/bash
# Docker image build script

set -e

# Default parameters
DOCKERFILE="./Dockerfile"
NO_CACHE=false
BUILD_ARGS=()
USE_BUILDKIT=true

# Output usage help
usage() {
  echo "Usage: $0 [options]"
  echo "Options:"
  echo "  -t, --tag <image tag>       Specify image tag name, e.g., myapp:1.0.0"
  echo "  -f, --file <file path>      Specify Dockerfile path (default: ./Dockerfile)"
  echo "  --no-cache                  Do not use cache when building"
  echo "  --no-buildkit               Do not use BuildKit for building (BuildKit enabled by default)"
  echo "  --build-arg <argument>      Build argument in KEY=VALUE format"
  echo "  -h, --help                  Show help message"
  exit 1
}

# Handle command line arguments
while [[ $# -gt 0 ]]; do
  case "$1" in
    -t|--tag)
      TAG="$2"
      shift 2
      ;;
    -f|--file)
      DOCKERFILE="$2"
      shift 2
      ;;
    --no-cache)
      NO_CACHE=true
      shift
      ;;
    --no-buildkit)
      USE_BUILDKIT=false
      shift
      ;;
    --build-arg)
      BUILD_ARGS+=("$2")
      shift 2
      ;;
    -h|--help)
      usage
      ;;
    *)
      echo "Error: Unknown option $1"
      usage
      ;;
  esac
done

# Check if Dockerfile exists
if [ ! -f "$DOCKERFILE" ]; then
  echo "Error: Dockerfile not found: $DOCKERFILE"
  exit 1
fi

# If image tag not specified, generate default tag
if [ -z "$TAG" ]; then
  # Get current project directory name
  PROJECT_NAME=$(basename "$(pwd)")
  # Use latest as default tag
  TAG="${PROJECT_NAME}:latest"
fi

echo "Starting Docker image build: $TAG"
echo "Using Dockerfile: $DOCKERFILE"

# Set BuildKit environment variable
if [ "$USE_BUILDKIT" = true ]; then
  echo "Enabling BuildKit to accelerate build and cache dependencies"
  export DOCKER_BUILDKIT=1
else
  echo "BuildKit not enabled"
  unset DOCKER_BUILDKIT
fi

# Build Docker command
BUILD_CMD="docker build -t $TAG -f $DOCKERFILE --progress=plain"

# Add --no-cache option (if specified)
if [ "$NO_CACHE" = true ]; then
  BUILD_CMD="$BUILD_CMD --no-cache"
fi

# Add build arguments (if any)
for arg in "${BUILD_ARGS[@]}"; do
  BUILD_CMD="$BUILD_CMD --build-arg $arg"
done

# Get current Git commit ID
GIT_COMMIT_ID=$(git rev-parse HEAD)
echo "Git Commit ID: $GIT_COMMIT_ID"

# Add Git Commit ID to build arguments
BUILD_CMD="$BUILD_CMD --build-arg GIT_COMMIT_ID=$GIT_COMMIT_ID"

# Add build context path
BUILD_CMD="$BUILD_CMD ."

# Execute build
echo "Executing command: $BUILD_CMD"
if eval "$BUILD_CMD"; then
  echo "Image build succeeded: $TAG"
  echo -e "\nBuild completed! Image tag: $TAG"
  echo -e "\nYou can run the image with:"
  echo "docker run -it --rm $TAG"
else
  echo "Image build failed, exit code: $?"
  exit 1
fi 