#!/bin/bash

# Define colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Reset to default color

# Help information
function show_help {
  echo -e "${YELLOW}API Gateway Multi-Environment Deployment Script${NC}"
  echo "Usage: $0 [environment] [operation]"
  echo ""
  echo "Environment options:"
  echo "  test       - Test environment (port 8001)"
  echo "  pre        - Pre-release environment (port 8002)"
  echo "  prod       - Production environment (port 8003)"
  echo ""
  echo "Operation options:"
  echo "  start      - Start specified environment"
  echo "  stop       - Stop specified environment"
  echo "  restart    - Restart specified environment"
  echo "  logs       - View logs for specified environment"
  echo "  status     - View status of specified environment"
  echo "  cleanup    - Clean up Redis permission issues"
  echo "  all        - Operate on all environments"
  echo ""
  echo "Examples:"
  echo "    $0 test start    - Start test environment"
  echo "    $0 all start     - Start all environments"
  echo "    $0 prod logs     - View production environment logs"
  echo "    $0 test cleanup  - Clean up Redis permission issues"
}

# Parameter validation
if [ $# -lt 2 ]; then
  show_help
  exit 1
fi

# Environment parameter validation
if [ "$1" != "test" ] && [ "$1" != "pre" ] && [ "$1" != "prod" ] && [ "$1" != "all" ]; then
  show_help
  exit 1
fi

# Operation parameter validation
if [ "$2" != "start" ] && [ "$2" != "stop" ] && [ "$2" != "restart" ] && [ "$2" != "logs" ] && [ "$2" != "status" ] && [ "$2" != "cleanup" ]; then
  show_help
  exit 1
fi

# Check if config directory exists, create if not
function check_config_dir {
  local env=$1
  if [ ! -d "config/$env" ]; then
    echo -e "${YELLOW}Config directory config/$env does not exist, creating...${NC}"
    mkdir -p config/$env

    # If no environment config file exists, copy from example file
    if [ ! -f "config/$env/.env" ]; then
      if [ -f ".env_example" ]; then
        cp .env_example config/$env/.env
        echo -e "${YELLOW}Created config/$env/.env config file from example, please modify according to environment requirements${NC}"
      else
        echo -e "${RED}Warning: Example config file not found, please manually create config/$env/.env file${NC}"
        touch config/$env/.env
      fi
    fi
  fi
}

# Check and create external network
function ensure_network_exists {
  if ! docker network ls | grep -q delightful-sandbox-network; then
    echo -e "${YELLOW}Creating external network delightful-sandbox-network...${NC}"
    docker network create delightful-sandbox-network
  else
    echo -e "${GREEN}External network delightful-sandbox-network already exists${NC}"
  fi
}

# Fix Redis data directory permissions
function fix_redis_permissions {
  if [ -d "redis_data" ]; then
    echo -e "${YELLOW}Checking and fixing Redis data directory permissions...${NC}"
    # Try to set correct user group using sudo
    sudo chown -R 999:999 redis_data 2>/dev/null || {
      echo -e "${YELLOW}Cannot use sudo, using chmod to set permissions...${NC}"
      chmod -R 777 redis_data
    }
  fi
}

# Clean up Redis permission issues
function cleanup_redis_permissions {
  echo -e "${YELLOW}Cleaning up Redis permission issues...${NC}"

  # Stop Redis container
  if docker ps | grep -q "api-gateway-redis"; then
    echo -e "${YELLOW}Stopping Redis container...${NC}"
    docker stop api-gateway-redis
  fi

  # Remove Redis container
  if docker ps -a | grep -q "api-gateway-redis"; then
    echo -e "${YELLOW}Removing Redis container...${NC}"
    docker rm api-gateway-redis
  fi

  # Fix data directory permissions
  if [ -d "redis_data" ]; then
    echo -e "${YELLOW}Fixing data directory permissions...${NC}"
    sudo chown -R $(id -u):$(id -g) redis_data 2>/dev/null || chmod -R 755 redis_data
  fi

  echo -e "${GREEN}Redis permission issues cleaned up${NC}"
}

# Start specified environment
function start_env {
  local env=$1
  local port
  local api_key
  local jwt_secret
  local debug
  local redis_port
  local redis_db

  # Check config directory
  check_config_dir $env

  # Ensure external network exists
  ensure_network_exists

  # Fix Redis permission issues
  fix_redis_permissions

  echo -e "${GREEN}Starting $env environment...${NC}"

  case $env in
    test)
      port=8001
      redis_port=28001
      redis_db=0
      api_key="test-gateway-api-key"
      jwt_secret="test-jwt-secret-key"
      debug="true"
      ;;
    pre)
      port=8002
      redis_port=28001  # Use the same Redis port
      redis_db=1
      api_key="pre-gateway-api-key"
      jwt_secret="pre-jwt-secret-key"
      debug="true"
      ;;
    prod)
      port=8003
      redis_port=28001  # Use the same Redis port
      redis_db=2
      api_key="prod-gateway-api-key"
      jwt_secret="prod-jwt-secret-key"
      debug="false"
      ;;
  esac

  # Ensure Redis data directory exists
  if [ ! -d "redis_data" ]; then
    echo -e "${YELLOW}Creating Redis data directory...${NC}"
    mkdir -p redis_data
  fi

  # Check if Redis container already exists
  if docker ps -a | grep -q "api-gateway-redis"; then
    echo -e "${YELLOW}Redis container api-gateway-redis already exists, skipping creation...${NC}"
    # If Redis container exists but not running, start it
    if ! docker ps | grep -q "api-gateway-redis"; then
      echo -e "${YELLOW}Starting existing Redis container...${NC}"
      docker start api-gateway-redis
    fi
  else
    echo -e "${YELLOW}Creating Redis container...${NC}"
    # Start Redis container directly, not through docker-compose
    docker run -d --name api-gateway-redis --network delightful-sandbox-network -p ${redis_port}:6379 -v $(pwd)/redis_data:/data public.ecr.aws/docker/library/redis:alpine redis-server --appendonly yes
  fi

  # Get Redis container IP address
  local redis_ip=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' api-gateway-redis)
  echo -e "${YELLOW}Redis container IP address: ${redis_ip}${NC}"

  # Create temporary docker-compose override file
  cat > docker-compose.override.yml <<EOF
services:
  delightful-gateway:
    environment:
      - REDIS_HOST=${redis_ip}
      - REDIS_PORT=6379
      - REDIS_DB=${redis_db}
  # No longer manage redis container through docker-compose
  delightful-redis:
    profiles: ["disabled"]
EOF

  ENV=$env PORT=$port DELIGHTFUL_GATEWAY_PORT=$port DELIGHTFUL_GATEWAY_API_KEY=$api_key JWT_SECRET=$jwt_secret DELIGHTFUL_GATEWAY_DEBUG=$debug \
    docker compose -p delightful-gateway-$env up -d --build

  # Remove temporary file
  rm docker-compose.override.yml

  echo -e "${GREEN}$env environment started, access URL: http://localhost:$port${NC}"
  echo -e "${GREEN}Redis info: IP=${redis_ip}, Port=6379, DB=${redis_db}${NC}"
}

# Stop specified environment
function stop_env {
  local env=$1
  echo -e "${YELLOW}Stopping $env environment...${NC}"
  docker compose -p delightful-gateway-$env down

  # If Redis container has stopped, fix data directory permissions
  if docker ps -a | grep -q "api-gateway-redis" && ! docker ps | grep -q "api-gateway-redis"; then
    echo -e "${YELLOW}Fixing Redis data directory permissions...${NC}"
    sudo chown -R $(id -u):$(id -g) redis_data 2>/dev/null || chmod -R 755 redis_data
  fi

  echo -e "${YELLOW}$env environment stopped${NC}"
}

# View logs for specified environment
function view_logs {
  local env=$1
  echo -e "${GREEN}Viewing $env environment logs...${NC}"
  docker compose -p delightful-gateway-$env logs -f
}

# View status of specified environment
function check_status {
  local env=$1
  echo -e "${GREEN}$env environment status:${NC}"
  docker compose -p delightful-gateway-$env ps
}

# Process operation
function process_operation {
  local env=$1
  local operation=$2

  case $operation in
    start)
      start_env $env
      ;;
    stop)
      stop_env $env
      ;;
    restart)
      stop_env $env
      start_env $env
      ;;
    logs)
      view_logs $env
      ;;
    status)
      check_status $env
      ;;
    cleanup)
      cleanup_redis_permissions
      ;;
  esac
}

# Process all environments
function process_all_envs {
  local operation=$1

  for env in test pre prod; do
    process_operation $env $operation
  done
}

# Main flow
if [ "$1" == "all" ]; then
  process_all_envs $2
else
  process_operation $1 $2
fi




