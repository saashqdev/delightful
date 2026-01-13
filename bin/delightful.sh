#!/bin/bash

# Detect system default language
detect_language() {
  # Default to English
  DEFAULT_LANG="en"

  # Get system language settings
  if [[ "$(uname -s)" == "Darwin" ]]; then
    # macOS system
    SYS_LANG=$(defaults read -g AppleLocale 2>/dev/null || echo "en_US")
  else
    # Linux and other systems
    SYS_LANG=$(echo $LANG || echo $LC_ALL || echo $LC_MESSAGES || echo "en_US.UTF-8")
  fi

  # If language code starts with zh_, set to Chinese, otherwise use English
  if [[ $SYS_LANG == zh_* ]]; then
    DEFAULT_LANG="zh"
  fi

  echo $DEFAULT_LANG
}

# Get system language
SYSTEM_LANG=$(detect_language)

# Bilingual prompt function
# Usage: bilingual "Chinese message" "English message"
bilingual() {
  if [ "$USER_LANG" = "zh" ]; then
    echo "$1"
  else
    echo "$2"
  fi
}

# Check if Be Delightful environment file exists
check_be_delightful_env() {
    if [ ! -f "config/.env_be_delightful" ]; then
        if [ -f "config/.env_be_delightful.example" ]; then
            bilingual "Error: config/.env_be_delightful file does not exist!" "Error: config/.env_be_delightful file does not exist!"
            bilingual "Please follow these steps:" "Please follow these steps:"
            bilingual "1. Copy the example configuration file: cp config/.env_be_delightful.example config/.env_be_delightful" "1. Copy the example configuration file: cp config/.env_be_delightful.example config/.env_be_delightful"
            bilingual "2. Edit the configuration file: vim config/.env_be_delightful (or use your preferred editor)" "2. Edit the configuration file: vim config/.env_be_delightful (or use your preferred editor)"
            bilingual "3. Configure all necessary environment variables" "3. Configure all necessary environment variables"
            bilingual "4. Run this script again" "4. Run this script again"
            return 1
        else
            bilingual "Error: Both config/.env_be_delightful and config/.env_be_delightful.example files do not exist!" "Error: Both config/.env_be_delightful and config/.env_be_delightful.example files do not exist!"
            bilingual "Please contact your system administrator for the correct configuration files." "Please contact your system administrator for the correct configuration files."
            return 1
        fi
    fi

    # Check whether config/config.yaml exists
    if [ ! -f "config/config.yaml" ]; then
        if [ -f "config/config.yaml.example" ]; then
            bilingual "Note: config/config.yaml file does not exist, copying from example file..." "Note: config/config.yaml file does not exist, copying from example file..."
            cp config/config.yaml.example config/config.yaml
            bilingual "Copied config/config.yaml.example to config/config.yaml" "Copied config/config.yaml.example to config/config.yaml"
        else
            bilingual "Error: Both config/config.yaml and config/config.yaml.example files do not exist!" "Error: Both config/config.yaml and config/config.yaml.example files do not exist!"
            bilingual "Please contact your system administrator for the correct configuration files." "Please contact your system administrator for the correct configuration files."
            return 1
        fi
    fi

    return 0
}

# Check if lock file exists - if it does, set default values and skip installation process
if [ -f "bin/delightful.lock" ]; then
    # Try to read the previously selected language from the lock file
    if [ -f "bin/user_lang" ]; then
        USER_LANG=$(cat bin/user_lang)
    else
        USER_LANG=$SYSTEM_LANG
    fi
    SKIP_LANGUAGE_SELECTION=true
    SKIP_INSTALLATION=true

    # If be-delightful config exists, set DELIGHTFUL_USE_BE_DELIGHTFUL automatically
    if [ -f "bin/use_be_delightful" ]; then
        # Use fixed profile parameters instead of reading from a file
        export DELIGHTFUL_USE_BE_DELIGHTFUL=" --profile delightful-gateway --profile sandbox-gateway"
        bilingual "Be Delightful configuration detected, Be Delightful related services will be started automatically" "Be Delightful configuration detected, Be Delightful related services will be started automatically"
    else
        export DELIGHTFUL_USE_BE_DELIGHTFUL=""
    fi
else
    SKIP_LANGUAGE_SELECTION=false
    SKIP_INSTALLATION=false
fi

# Check and update the SANDBOX_NETWORK parameter
check_sandbox_network() {
    if [ -f "config/.env_sandbox_gateway" ]; then
        CURRENT_NETWORK=$(grep "^SANDBOX_NETWORK=" config/.env_sandbox_gateway | cut -d'=' -f2)
        if [ "$CURRENT_NETWORK" != "delightful-sandbox-network" ]; then
            bilingual "Detected SANDBOX_NETWORK value is not delightful-sandbox-network, updating..." "Detected SANDBOX_NETWORK value is not delightful-sandbox-network, updating..."
            if [ "$(uname -s)" == "Darwin" ]; then
                # macOS version
                sed -i '' "s/^SANDBOX_NETWORK=.*/SANDBOX_NETWORK=delightful-sandbox-network/" config/.env_sandbox_gateway
            else
                # Linux version
                sed -i "s/^SANDBOX_NETWORK=.*/SANDBOX_NETWORK=delightful-sandbox-network/" config/.env_sandbox_gateway
            fi
            bilingual "Updated SANDBOX_NETWORK value to delightful-sandbox-network" "Updated SANDBOX_NETWORK value to delightful-sandbox-network"
        fi
    fi
}

# Let the user choose the language only if not skipped
if [ "$SKIP_LANGUAGE_SELECTION" = "false" ]; then
  choose_language() {
    echo "Please select your preferred language:"
    echo "1. English"
    echo "2. Chinese"
    read -p "Enter your choice [1/2] (default: $SYSTEM_LANG): " LANG_CHOICE

    if [ -z "$LANG_CHOICE" ]; then
      USER_LANG=$SYSTEM_LANG
    elif [ "$LANG_CHOICE" = "1" ]; then
      USER_LANG="en"
    elif [ "$LANG_CHOICE" = "2" ]; then
      USER_LANG="zh"
    else
    echo "Invalid choice, using system detected language: $SYSTEM_LANG"
      USER_LANG=$SYSTEM_LANG
    fi

    echo "Selected language: $([ "$USER_LANG" = "en" ] && echo "English" || echo "Chinese")"
    echo ""

    # Save the user-selected language to a file
    echo "$USER_LANG" > bin/user_lang
  }

  # Run language selection
  choose_language
else
    bilingual "Using previously selected language: $([ "$USER_LANG" = "en" ] && echo "English" || echo "Chinese")" "Using previously selected language: $([ "$USER_LANG" = "en" ] && echo "English" || echo "Chinese")"
fi

# Check if lock file exists - if it does, set default values and skip installation process
if [ "$SKIP_INSTALLATION" = "true" ]; then
    bilingual "Detected delightful.lock file, skipping installation configuration..." "Detected delightful.lock file, skipping installation configuration..."

    # Set default values for required variables
    if [ -f ".env_be_delightful" ]; then
        export DELIGHTFUL_USE_BE_DELIGHTFUL=""
    else
        export DELIGHTFUL_USE_BE_DELIGHTFUL=""
    fi
fi

# Only run installation steps if not skipped
if [ "$SKIP_INSTALLATION" = "false" ]; then
    # Check if Docker is installed
    if ! command -v docker &> /dev/null; then
        bilingual "Error: Docker is not installed." "Error: Docker is not installed."
        bilingual "Please install Docker first:" "Please install Docker first:"
        if [ "$(uname -s)" == "Darwin" ]; then
            bilingual "1. Visit https://docs.docker.com/desktop/install/mac-install/" "1. Visit https://docs.docker.com/desktop/install/mac-install/"
            bilingual "2. Download and install Docker Desktop for Mac" "2. Download and install Docker Desktop for Mac"
        elif [ "$(uname -s)" == "Linux" ]; then
            bilingual "1. Visit https://docs.docker.com/engine/install/" "1. Visit https://docs.docker.com/engine/install/"
            bilingual "2. Follow the installation instructions for your Linux distribution" "2. Follow the installation instructions for your Linux distribution"
        else
            bilingual "Please visit https://docs.docker.com/get-docker/ for installation instructions" "Please visit https://docs.docker.com/get-docker/ for installation instructions"
        fi
        exit 1
    fi

    # Check if Docker is running
    if ! docker info &> /dev/null; then
        bilingual "Error: Docker is not running." "Error: Docker is not running."
        bilingual "Please start Docker and try again." "Please start Docker and try again."
        if [ "$(uname -s)" == "Darwin" ]; then
            bilingual "1. Open Docker Desktop" "1. Open Docker Desktop"
            bilingual "2. Wait for Docker to start" "2. Wait for Docker to start"
        elif [ "$(uname -s)" == "Linux" ]; then
            bilingual "1. Start Docker service: sudo systemctl start docker" "1. Start Docker service: sudo systemctl start docker"
        fi
        exit 1
    fi

    # Ensure the delightful-sandbox-network exists; create it if missing
    if ! docker network inspect delightful-sandbox-network &> /dev/null; then
        bilingual "Network delightful-sandbox-network does not exist, creating..." "Network delightful-sandbox-network does not exist, creating..."
        docker network create delightful-sandbox-network
        bilingual "Network delightful-sandbox-network has been created." "Network delightful-sandbox-network has been created."
    else
        bilingual "Network delightful-sandbox-network already exists, skipping creation." "Network delightful-sandbox-network already exists, skipping creation."
    fi

    # Check if docker compose is installed
    if ! command -v docker compose &> /dev/null; then
        bilingual "Error: docker compose is not installed." "Error: docker compose is not installed."
        bilingual "Please install docker compose first:" "Please install docker compose first:"
        if [ "$(uname -s)" == "Darwin" ]; then
            bilingual "1. Docker Desktop for Mac includes docker compose by default" "1. Docker Desktop for Mac includes docker compose by default"
            bilingual "2. If you're using an older version, visit https://docs.docker.com/compose/install/" "2. If you're using an older version, visit https://docs.docker.com/compose/install/"
        elif [ "$(uname -s)" == "Linux" ]; then
            bilingual "1. Visit https://docs.docker.com/compose/install/" "1. Visit https://docs.docker.com/compose/install/"
            bilingual "2. Follow the installation instructions for your Linux distribution" "2. Follow the installation instructions for your Linux distribution"
            bilingual "   For example, on Ubuntu/Debian:" "   For example, on Ubuntu/Debian:"
            echo "   sudo apt-get update"
            echo "   sudo apt-get install docker compose-plugin"
        else
            bilingual "Please visit https://docs.docker.com/compose/install/ for installation instructions" "Please visit https://docs.docker.com/compose/install/ for installation instructions"
        fi
        exit 1
    fi

    # Detect system architecture
    ARCH=$(uname -m)
    case $ARCH in
        x86_64)
            export PLATFORM=linux/amd64
            ;;
        aarch64|arm64|armv7l)
            export PLATFORM=linux/arm64
            ;;
        *)
            bilingual "Unsupported architecture: $ARCH" "Unsupported architecture: $ARCH"
            exit 1
            ;;
    esac

    # Do not set PLATFORM if using macOS arm64
    if [ "$(uname -s)" == "Darwin" ] && [ "$ARCH" == "arm64" ]; then
        export PLATFORM=""
    fi

    bilingual "Detected architecture: $ARCH, using platform: $PLATFORM" "Detected architecture: $ARCH, using platform: $PLATFORM"

    # Create .env file if it doesn't exist
    if [ ! -f ".env" ]; then
        cp .env.example .env
    fi

    # Modify PLATFORM variable in .env
    if [ -n "$PLATFORM" ]; then
        if [ "$(uname -s)" == "Darwin" ]; then
            # macOS version
            sed -i '' "s/^PLATFORM=.*/PLATFORM=$PLATFORM/" .env
        else
            # Linux version
            sed -i "s/^PLATFORM=.*/PLATFORM=$PLATFORM/" .env
        fi
    else
        # If PLATFORM is empty, set it to an empty string
        if [ "$(uname -s)" == "Darwin" ]; then
            sed -i '' "s/^PLATFORM=.*/PLATFORM=/" .env
        else
            sed -i "s/^PLATFORM=.*/PLATFORM=/" .env
        fi
    fi

    # Ask if Be Delightful service should be installed
    ask_be_delightful() {
        bilingual "Do you want to install Be Delightful service?" "Do you want to install Be Delightful service?"
        bilingual "1. Yes, install Be Delightful service" "1. Yes, install Be Delightful service"
        bilingual "2. No, don't install Be Delightful service" "2. No, don't install Be Delightful service"
        read -p "$(bilingual "Please enter option number [1/2]: " "Please enter option number [1/2]: ")" BE_DELIGHTFUL_OPTION

        if [ "$BE_DELIGHTFUL_OPTION" = "1" ]; then
            bilingual "You have chosen to install Be Delightful service." "You have chosen to install Be Delightful service."

            # Check if .env_be_delightful exists
            if ! check_be_delightful_env; then
                exit 1
            fi

            # Check if other gateway configuration files exist
            if [ ! -f "config/.env_delightful_gateway" ]; then
                bilingual "Error: config/.env_delightful_gateway file does not exist!" "Error: config/.env_delightful_gateway file does not exist!"
                bilingual "Please ensure the Delightful Gateway configuration file exists." "Please ensure the Delightful Gateway configuration file exists."
                exit 1
            fi

            if [ ! -f "config/.env_sandbox_gateway" ]; then
                bilingual "Error: config/.env_sandbox_gateway file does not exist!" "Error: config/.env_sandbox_gateway file does not exist!"
                bilingual "Please ensure the Sandbox Gateway configuration file exists." "Please ensure the Sandbox Gateway configuration file exists."
                exit 1
            fi

            # Add profiles for be-delightful, delightful-gateway and sandbox-gateway
            export DELIGHTFUL_USE_BE_DELIGHTFUL=" --profile delightful-gateway --profile sandbox-gateway"
            # Record the be-delightful configuration for automatic loading next start
            echo "$DELIGHTFUL_USE_BE_DELIGHTFUL" > bin/use_be_delightful
            bilingual "Be Delightful, Delightful Gateway and Sandbox Gateway services will be started." "Be Delightful, Delightful Gateway and Sandbox Gateway services will be started."
            bilingual "Your choice has been recorded, Be Delightful related services will be loaded automatically next time." "Your choice has been recorded, Be Delightful related services will be loaded automatically next time."
        else
            bilingual "You have chosen not to install Be Delightful service." "You have chosen not to install Be Delightful service."
            export DELIGHTFUL_USE_BE_DELIGHTFUL=""
            # Remove any previous be-delightful configuration file if present
            if [ -f "bin/use_be_delightful" ]; then
                rm bin/use_be_delightful
            fi
        fi
    }

    # Detect public IP and update environment variables
    detect_public_ip() {
        # Ask user about deployment method
        bilingual "Please select your deployment method:" "Please select your deployment method:"
        bilingual "1. Local deployment" "1. Local deployment"
        bilingual "2. Remote server deployment" "2. Remote server deployment"
        read -p "$(bilingual "Please enter option number [1/2]: " "Please enter option number [1/2]: ")" DEPLOYMENT_TYPE

        # If user chooses local deployment, do not update IP
        if [ "$DEPLOYMENT_TYPE" = "1" ]; then
            bilingual "Local deployment selected, keeping default settings." "Local deployment selected, keeping default settings."
            return 0
        elif [ "$DEPLOYMENT_TYPE" != "2" ]; then
            bilingual "Invalid option, using local deployment by default." "Invalid option, using local deployment by default."
            return 0
        fi

        # Check the docker.sock location
        bilingual "Detecting docker.sock location..." "Detecting docker.sock location..."

        # Try several possible docker.sock locations
        DOCKER_SOCK_PATHS=(
            "/var/run/docker.sock"
            "/run/user/$(id -u)/docker.sock"
            "$HOME/.local/share/docker/docker.sock"
            "$(docker info --format '{{.DockerRootDir}}/docker.sock' 2>/dev/null)"
        )

        DOCKER_SOCK_PATH=""
        for path in "${DOCKER_SOCK_PATHS[@]}"; do
            if [ -S "$path" ]; then
                DOCKER_SOCK_PATH="$path"
                break
            fi
        done

        # If predefined paths do not exist, try a global search with find
        if [ -z "$DOCKER_SOCK_PATH" ]; then
            bilingual "Searching for docker.sock file globally..." "Searching for docker.sock file globally..."
            FOUND_SOCK=$(find / -name "docker.sock" 2>/dev/null | head -n 1)
            if [ -n "$FOUND_SOCK" ] && [ -S "$FOUND_SOCK" ]; then
                DOCKER_SOCK_PATH="$FOUND_SOCK"
                bilingual "Found docker.sock through global search: $DOCKER_SOCK_PATH" "Found docker.sock through global search: $DOCKER_SOCK_PATH"
            else
                # If find cannot locate it, try docker info to obtain the path
                DOCKER_SOCK_PATH=$(docker info --format '{{.DockerRootDir}}/docker.sock' 2>/dev/null)
                if [ -z "$DOCKER_SOCK_PATH" ] || [ ! -S "$DOCKER_SOCK_PATH" ]; then
                    DOCKER_SOCK_PATH="/var/run/docker.sock"
                fi
            fi
        fi

        if [ "$DOCKER_SOCK_PATH" != "/var/run/docker.sock" ]; then
            bilingual "Detected non-standard docker.sock location: $DOCKER_SOCK_PATH" "Detected non-standard docker.sock location: $DOCKER_SOCK_PATH"
            bilingual "Updating SANDBOX_DOCKER_RUNTIME configuration..." "Updating SANDBOX_DOCKER_RUNTIME configuration..."

            if [ "$(uname -s)" == "Darwin" ]; then
                # macOS version
                sed -i '' "s|^SANDBOX_DOCKER_RUNTIME=.*|SANDBOX_DOCKER_RUNTIME=$DOCKER_SOCK_PATH|" .env
            else
                # Linux version
                sed -i "s|^SANDBOX_DOCKER_RUNTIME=.*|SANDBOX_DOCKER_RUNTIME=$DOCKER_SOCK_PATH|" .env
            fi

            bilingual "Updated SANDBOX_DOCKER_RUNTIME to: $DOCKER_SOCK_PATH" "Updated SANDBOX_DOCKER_RUNTIME to: $DOCKER_SOCK_PATH"
        else
            bilingual "Using default docker.sock location: $DOCKER_SOCK_PATH" "Using default docker.sock location: $DOCKER_SOCK_PATH"
        fi

        # Ask if domain name is needed
        bilingual "Do you need to use a domain name for access?" "Do you need to use a domain name for access?"
        read -p "$(bilingual "Please enter [y/n]: " "Please enter [y/n]: ")" USE_DOMAIN

        if [[ "$USE_DOMAIN" =~ ^[Yy]$ ]]; then
            read -p "$(bilingual "Please enter domain address (without http/https prefix): " "Please enter domain address (without http/https prefix): ")" DOMAIN_ADDRESS

            if [ -n "$DOMAIN_ADDRESS" ]; then
                bilingual "Updating environment variables with domain: $DOMAIN_ADDRESS..." "Updating environment variables with domain: $DOMAIN_ADDRESS..."

                # Update DELIGHTFUL_SOCKET_BASE_URL and DELIGHTFUL_SERVICE_BASE_URL
                if [ "$(uname -s)" == "Darwin" ]; then
                    # macOS version
                    sed -i '' "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$DOMAIN_ADDRESS|" .env
                    sed -i '' "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$DOMAIN_ADDRESS|" .env
                    # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                    sed -i '' "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$DOMAIN_ADDRESS/files|" .env
                    sed -i '' "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$DOMAIN_ADDRESS|" .env
                else
                    # Linux version
                    sed -i "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$DOMAIN_ADDRESS|" .env
                    sed -i "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$DOMAIN_ADDRESS|" .env
                    # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                    sed -i "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$DOMAIN_ADDRESS/files|" .env
                    sed -i "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$DOMAIN_ADDRESS|" .env
                fi

                bilingual "Environment variables updated:" "Environment variables updated:"
                echo "DELIGHTFUL_SOCKET_BASE_URL=ws://$DOMAIN_ADDRESS"
                echo "DELIGHTFUL_SERVICE_BASE_URL=http://$DOMAIN_ADDRESS"
                echo "FILE_LOCAL_READ_HOST=http://$DOMAIN_ADDRESS/files"
                echo "FILE_LOCAL_WRITE_HOST=http://$DOMAIN_ADDRESS"

                # Update the domain in the Caddyfile
                bilingual "Updating Caddyfile configuration..." "Updating Caddyfile configuration..."

                # Check whether Caddyfile exists
                if [ -f "bin/caddy/Caddyfile" ]; then
                    # Insert the domain at the top of the Caddyfile
                    if [ "$(uname -s)" == "Darwin" ]; then
                        # macOS version
                        sed -i '' "s|^# File service\n:80 {|# File service\n$DOMAIN_ADDRESS:80 {|" bin/caddy/Caddyfile
                    else
                        # Linux version
                        sed -i "s|^# File service\n:80 {|# File service\n$DOMAIN_ADDRESS:80 {|" bin/caddy/Caddyfile
                    fi
                    bilingual "Updated Caddyfile configuration with domain: $DOMAIN_ADDRESS" "Updated Caddyfile configuration with domain: $DOMAIN_ADDRESS"
                else
                    bilingual "Caddyfile not found, skipping update" "Caddyfile not found, skipping update"
                fi

                return 0
            else
                bilingual "Domain is empty, continuing with public IP configuration." "Domain is empty, continuing with public IP configuration."
            fi
        else
            bilingual "Not using domain, continuing with public IP configuration." "Not using domain, continuing with public IP configuration."
        fi

        bilingual "Detecting public IP..." "Detecting public IP..."

        # Try multiple methods to get public IP
        PUBLIC_IP=""

        # Method 1: Using ipinfo.io
        if [ -z "$PUBLIC_IP" ]; then
            PUBLIC_IP=$(curl -s https://ipinfo.io/ip 2>/dev/null)
            if [ -z "$PUBLIC_IP" ] || [[ $PUBLIC_IP == *"html"* ]]; then
                PUBLIC_IP=""
            fi
        fi

        # Method 2: Using ip.sb
        if [ -z "$PUBLIC_IP" ]; then
            PUBLIC_IP=$(curl -s https://api.ip.sb/ip 2>/dev/null)
            if [ -z "$PUBLIC_IP" ] || [[ $PUBLIC_IP == *"html"* ]]; then
                PUBLIC_IP=""
            fi
        fi

        # Method 3: Using ipify
        if [ -z "$PUBLIC_IP" ]; then
            PUBLIC_IP=$(curl -s https://api.ipify.org 2>/dev/null)
            if [ -z "$PUBLIC_IP" ] || [[ $PUBLIC_IP == *"html"* ]]; then
                PUBLIC_IP=""
            fi
        fi

        # Method 4: Using checkip.amazonaws.com
        if [ -z "$PUBLIC_IP" ]; then
            PUBLIC_IP=$(curl -s https://checkip.amazonaws.com 2>/dev/null)
            if [ -z "$PUBLIC_IP" ] || [[ $PUBLIC_IP == *"html"* ]]; then
                PUBLIC_IP=""
            fi
        fi

        # If successfully obtained public IP, ask user whether to use this IP
        if [ -n "$PUBLIC_IP" ]; then
            bilingual "Detected public IP: $PUBLIC_IP" "Detected public IP: $PUBLIC_IP"
            bilingual "Do you want to use this IP for configuration?" "Do you want to use this IP for configuration?"
            read -p "$(bilingual "Please enter [y/n]: " "Please enter [y/n]: ")" USE_DETECTED_IP

            if [[ "$USE_DETECTED_IP" =~ ^[Yy]$ ]]; then
                bilingual "Updating environment variables..." "Updating environment variables..."

                # Update DELIGHTFUL_SOCKET_BASE_URL and DELIGHTFUL_SERVICE_BASE_URL
                if [ "$(uname -s)" == "Darwin" ]; then
                    # macOS version
                    sed -i '' "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$PUBLIC_IP|" .env
                    sed -i '' "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$PUBLIC_IP|" .env
                    # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                    sed -i '' "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$PUBLIC_IP/files|" .env
                    sed -i '' "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$PUBLIC_IP|" .env
                else
                    # Linux version
                    sed -i "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$PUBLIC_IP|" .env
                    sed -i "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$PUBLIC_IP|" .env
                    # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                    sed -i "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$PUBLIC_IP/files|" .env
                    sed -i "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$PUBLIC_IP|" .env
                fi

                bilingual "Environment variables updated:" "Environment variables updated:"
                echo "DELIGHTFUL_SOCKET_BASE_URL=ws://$PUBLIC_IP"
                echo "DELIGHTFUL_SERVICE_BASE_URL=http://$PUBLIC_IP"
                echo "FILE_LOCAL_READ_HOST=http://$PUBLIC_IP/files"
                echo "FILE_LOCAL_WRITE_HOST=http://$PUBLIC_IP"

                # Update the IP in the Caddyfile
                bilingual "Updating Caddyfile configuration..." "Updating Caddyfile configuration..."

                # Check whether Caddyfile exists
                if [ -f "bin/caddy/Caddyfile" ]; then
                    # Insert the public IP at the top of the Caddyfile
                    if [ "$(uname -s)" == "Darwin" ]; then
                        # macOS version
                        sed -i '' "s|^# File service\n:80 {|# File service\n$PUBLIC_IP:80 {|" bin/caddy/Caddyfile
                    else
                        # Linux version
                        sed -i "s|^# File service\n:80 {|# File service\n$PUBLIC_IP:80 {|" bin/caddy/Caddyfile
                    fi
                    bilingual "Updated Caddyfile configuration with public IP: $PUBLIC_IP" "Updated Caddyfile configuration with public IP: $PUBLIC_IP"
                else
                    bilingual "Caddyfile not found, skipping update" "Caddyfile not found, skipping update"
                fi
            else
                bilingual "Keeping default settings." "Keeping default settings."
            fi
        else
            bilingual "Failed to detect public IP." "Failed to detect public IP."
            bilingual "Do you want to manually enter an IP address?" "Do you want to manually enter an IP address?"
            read -p "$(bilingual "Please enter [y/n]: " "Please enter [y/n]: ")" MANUAL_IP

            if [[ "$MANUAL_IP" =~ ^[Yy]$ ]]; then
                read -p "$(bilingual "Please enter IP address: " "Please enter IP address: ")" MANUAL_IP_ADDRESS

                if [ -n "$MANUAL_IP_ADDRESS" ]; then
                    bilingual "Updating environment variables with IP: $MANUAL_IP_ADDRESS..." "Updating environment variables with IP: $MANUAL_IP_ADDRESS..."

                    # Update DELIGHTFUL_SOCKET_BASE_URL and DELIGHTFUL_SERVICE_BASE_URL
                    if [ "$(uname -s)" == "Darwin" ]; then
                        # macOS version
                        sed -i '' "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$MANUAL_IP_ADDRESS|" .env
                        sed -i '' "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$MANUAL_IP_ADDRESS|" .env
                        # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                        sed -i '' "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$MANUAL_IP_ADDRESS/files|" .env
                        sed -i '' "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$MANUAL_IP_ADDRESS|" .env
                    else
                        # Linux version
                        sed -i "s|^DELIGHTFUL_SOCKET_BASE_URL=ws://localhost:9502|DELIGHTFUL_SOCKET_BASE_URL=ws://$MANUAL_IP_ADDRESS|" .env
                        sed -i "s|^DELIGHTFUL_SERVICE_BASE_URL=http://localhost|DELIGHTFUL_SERVICE_BASE_URL=http://$MANUAL_IP_ADDRESS|" .env
                        # Update FILE_LOCAL_READ_HOST and FILE_LOCAL_WRITE_HOST
                        sed -i "s|^FILE_LOCAL_READ_HOST=http://127.0.0.1/files|FILE_LOCAL_READ_HOST=http://$MANUAL_IP_ADDRESS/files|" .env
                        sed -i "s|^FILE_LOCAL_WRITE_HOST=http://127.0.0.1|FILE_LOCAL_WRITE_HOST=http://$MANUAL_IP_ADDRESS|" .env
                    fi

                    bilingual "Environment variables updated:" "Environment variables updated:"
                    echo "DELIGHTFUL_SOCKET_BASE_URL=ws://$MANUAL_IP_ADDRESS"
                    echo "DELIGHTFUL_SERVICE_BASE_URL=http://$MANUAL_IP_ADDRESS"
                    echo "FILE_LOCAL_READ_HOST=http://$MANUAL_IP_ADDRESS/files"
                    echo "FILE_LOCAL_WRITE_HOST=http://$MANUAL_IP_ADDRESS"

                    # Update the manually entered IP in the Caddyfile
                    bilingual "Updating Caddyfile configuration..." "Updating Caddyfile configuration..."

                    # Check whether Caddyfile exists
                    if [ -f "bin/caddy/Caddyfile" ]; then
                        # Insert the manually entered IP at the top of the Caddyfile
                        if [ "$(uname -s)" == "Darwin" ]; then
                            # macOS version
                            sed -i '' "s|^# File service\n:80 {|# File service\n$MANUAL_IP_ADDRESS:80 {|" bin/caddy/Caddyfile
                        else
                            # Linux version
                            sed -i "s|^# File service\n:80 {|# File service\n$MANUAL_IP_ADDRESS:80 {|" bin/caddy/Caddyfile
                        fi
                        bilingual "Updated Caddyfile configuration with manually entered IP: $MANUAL_IP_ADDRESS" "Updated Caddyfile configuration with manually entered IP: $MANUAL_IP_ADDRESS"
                    else
                        bilingual "Caddyfile not found, skipping update" "Caddyfile not found, skipping update"
                    fi
                else
                    bilingual "IP address is empty, keeping default settings." "IP address is empty, keeping default settings."
                fi
            else
                bilingual "Keeping default settings." "Keeping default settings."
            fi
        fi
    fi
}

# Only run these if not skipped
if [ "$SKIP_INSTALLATION" = "false" ]; then
    detect_public_ip

    # Ask if Be Delightful service should be installed
    ask_be_delightful

    # Create lock file to skip installation next time
    touch bin/delightful.lock
    bilingual "Created delightful.lock file, next startup will skip installation configuration." "Created delightful.lock file, next startup will skip installation configuration."
fi

# Show help information
show_help() {
    bilingual "Usage: $0 [command]" "Usage: $0 [command]"
    echo ""
    bilingual "Commands:" "Commands:"
    bilingual "  start             Start services in foreground" "  start             Start services in foreground"
    bilingual "  stop              Stop all services" "  stop              Stop all services"
    bilingual "  daemon            Start services in background" "  daemon            Start services in background"
    bilingual "  restart           Restart all services" "  restart           Restart all services"
    bilingual "  status            Show services status" "  status            Show services status"
    bilingual "  logs              Show services logs" "  logs              Show services logs"
    bilingual "  be-delightful       Start only Be Delightful service (foreground)" "  be-delightful       Start only Be Delightful service (foreground)"
    bilingual "  be-delightful-daemon Start only Be Delightful service (background)" "  be-delightful-daemon Start only Be Delightful service (background)"
    echo ""
    bilingual "If no command is provided, 'start' will be used by default." "If no command is provided, 'start' will be used by default."
}

# Start services
start_services() {
    # Check and update the SANDBOX_NETWORK parameter
    check_sandbox_network

    bilingual "Starting services in foreground..." "Starting services in foreground..."
    if [ -f "bin/use_be_delightful" ]; then
        # Start directly with the profile parameters
        docker compose  --profile be-delightful --profile  delightful-gateway --profile sandbox-gateway up
    else
        docker compose up
    fi
}

# Stop services
stop_services() {
    bilingual "Stopping services..." "Stopping services..."
    if [ -f "bin/use_be_delightful" ]; then
        docker compose --profile be-delightful --profile  delightful-gateway --profile sandbox-gateway down
    else
        docker compose down
    fi
}

# Start services in background
start_daemon() {
    # Check and update the SANDBOX_NETWORK parameter
    check_sandbox_network

    bilingual "Starting services in background..." "Starting services in background..."
    if [ -f "bin/use_be_delightful" ]; then
        docker compose --profile be-delightful --profile  delightful-gateway --profile sandbox-gateway up -d
    else
        docker compose up -d
    fi
}

# Restart services
restart_services() {
    # Check and update the SANDBOX_NETWORK parameter
    check_sandbox_network

    bilingual "Restarting services..." "Restarting services..."
    if [ -f "bin/use_be_delightful" ]; then
        docker compose --profile be-delightful --profile  delightful-gateway --profile sandbox-gateway restart
    else
        docker compose restart
    fi
}

# Show services status
show_status() {
    bilingual "Services status:" "Services status:"
    docker compose $DELIGHTFUL_USE_BE_DELIGHTFUL ps
}

# Show services logs
show_logs() {
    bilingual "Showing services logs:" "Showing services logs:"
    docker compose $DELIGHTFUL_USE_BE_DELIGHTFUL logs -f
}

# Start only Be Delightful service
start_be_delightful() {
    # Check and update the SANDBOX_NETWORK parameter
    check_sandbox_network

    # Check if .env_be_delightful exists
    if ! check_be_delightful_env; then
        exit 1
    fi

    # Check if other gateway configuration files exist
    if [ ! -f "config/.env_delightful_gateway" ]; then
        bilingual "Error: config/.env_delightful_gateway file does not exist!" "Error: config/.env_delightful_gateway file does not exist!"
        bilingual "Please ensure the Delightful Gateway configuration file exists." "Please ensure the Delightful Gateway configuration file exists."
        exit 1
    fi

    if [ ! -f "config/.env_sandbox_gateway" ]; then
        bilingual "Error: config/.env_sandbox_gateway file does not exist!" "Error: config/.env_sandbox_gateway file does not exist!"
        bilingual "Please ensure the Sandbox Gateway configuration file exists." "Please ensure the Sandbox Gateway configuration file exists."
        exit 1
    fi

    bilingual "Starting Be Delightful service and Gateway services in foreground..." "Starting Be Delightful service and Gateway services in foreground..."
    docker compose  --profile delightful-gateway --profile sandbox-gateway up
}

# Start only Be Delightful service in background
start_be_delightful_daemon() {
    # Check and update SANDBOX_NETWORK parameter
    check_sandbox_network

    # Check if .env_be_delightful exists
    if ! check_be_delightful_env; then
        exit 1
    fi

    # Check if other gateway configuration files exist
    if [ ! -f "config/.env_delightful_gateway" ]; then
        bilingual "Error: config/.env_delightful_gateway file does not exist!" "Error: config/.env_delightful_gateway file does not exist!"
        bilingual "Please ensure the Delightful Gateway configuration file exists." "Please ensure the Delightful Gateway configuration file exists."
        exit 1
    fi

    if [ ! -f "config/.env_sandbox_gateway" ]; then
        bilingual "Error: config/.env_sandbox_gateway file does not exist!" "Error: config/.env_sandbox_gateway file does not exist!"
        bilingual "Please ensure the Sandbox Gateway configuration file exists." "Please ensure the Sandbox Gateway configuration file exists."
        exit 1
    fi

    bilingual "Starting Be Delightful service and Gateway services in background..." "Starting Be Delightful service and Gateway services in background..."
    docker compose  --profile delightful-gateway --profile sandbox-gateway up -d
}

# Handle command line arguments
case "$1" in
    start)
        start_services
        ;;
    stop)
        stop_services
        ;;
    daemon)
        start_daemon
        ;;
    restart)
        restart_services
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    be-delightful)
        start_be_delightful
        ;;
    be-delightful-daemon)
        start_be_delightful_daemon
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        if [ -z "$1" ]; then
            start_services
        else
            bilingual "Unknown command: $1" "Unknown command: $1"
            show_help
            exit 1
        fi
        ;;
esac
