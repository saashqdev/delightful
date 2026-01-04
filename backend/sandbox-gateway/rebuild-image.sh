#!/bin/bash
# Build sandbox gateway service Docker image script

set -e

# Default parameters
DOCKERFILE="./Dockerfile"
NO_CACHE=false
BUILD_ARGS=()
USE_BUILDKIT=true

# Load environment variables from .env file
if [ -f .env ]; then
  source .env
fi

# Check if SANDBOX_GATEWAY_IMAGE environment variable exists
if [ -z "${SANDBOX_GATEWAY_IMAGE}" ]; then
  echo "Error: Environment variable SANDBOX_GATEWAY_IMAGE is not set"
  echo "Please set the SANDBOX_GATEWAY_IMAGE variable in the .env file"
  exit 1
fi

# Output usage help
usage() {
  echo "Usage: $0 [options]"
  echo "Options:"
  echo "  -t, --tag <image tag>     Specify image tag name, will override environment variable setting"
  echo "  -f, --file <file path>    Specify Dockerfile path (default: ./Dockerfile)"
  echo "  --no-cache                Do not use cache when building"
  echo "  --no-buildkit             Do not use BuildKit for building (BuildKit is enabled by default)"
  echo "  --build-arg <argument>    Build argument, format is KEY=VALUE"
  echo "  -h, --help                Display help information"
  exit 1
}

# Process command line arguments
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
  echo "Error: Dockerfile does not exist: $DOCKERFILE"
  exit 1
fi

# If image tag is not specified in command line arguments, use value from environment variable
if [ -z "$TAG" ]; then
  TAG="${SANDBOX_GATEWAY_IMAGE}"
  echo "Using image tag from environment variable: $TAG"
fi

echo "Starting to build sandbox gateway service Docker image: $TAG"
echo "Using Dockerfile: $DOCKERFILE"

# Set BuildKit environment variable
if [ "$USE_BUILDKIT" = true ]; then
  echo "Enable BuildKit to accelerate build and cache dependencies"
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

# Add build context path
BUILD_CMD="$BUILD_CMD ."

# Execute build
echo "Executing command: $BUILD_CMD"
if eval "$BUILD_CMD"; then
  echo "Image build successful: $TAG"
  echo -e "\nBuild complete! Image tag: $TAG"
else
  echo "Image build failed, error code: $?"
  exit 1
fi 