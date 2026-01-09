# Go API Gateway Service

This is a high-performance API gateway service for Docker containers, implemented in Go language, which can securely manage environment variables and provide temporary access tokens.

Note: This gateway only supports replacing header content and URL domains, not body content.

## Features

- **High Performance**: Implemented in Go language with significant performance improvements compared to Python version
- **Authentication Service**: Generates temporary access tokens for containers
- **Environment Variable Protection**: Containers cannot directly access environment variable values, only use them indirectly through API proxy
- **Multi-Service Support**: Can simultaneously support multiple API services (such as OpenAI, DeepSeek, Delightful, etc.)
- **Environment Variable Name Routing**: Directly access corresponding services through environment variable names
- **API Proxy**: Automatically replaces environment variable references in requests
- **Multiple Environment Variable Reference Formats**: `env:VAR`, `${VAR}`, `$VAR`, `OPENAI_*`, etc.
- **Multi-Environment Deployment**: Supports independent deployment of three environments: test, pre-release (pre), and production

## Project Structure

```
delightful-gateway/
├── main.go            # Main program entry
├── .env               # Environment variable configuration file
├── README.md          # Project documentation
├── deploy.sh          # Multi-environment deployment script
├── docker-compose.yml # Docker orchestration configuration
├── Dockerfile         # Docker build file
├── config/            # Multi-environment configuration directory
│   ├── test/          # Test environment configuration
│   ├── pre/           # Pre-release environment configuration
│   └── prod/          # Production environment configuration
├── docs/              # Documentation directory
│   └── multi-environment-deployment.md # Multi-environment deployment detailed guide
├── tests/             # Unit test and functional test directory
│   ├── auth_test_client.go  # Authentication interface test client
│   ├── auth_key_test.go     # API Key verification test
│   └── test_api_key.go      # API Key functional test
└── test_client/       # Test client tools
    └── test_client.go # General test client
```

## Quick Start

### Prerequisites

- Go 1.18+ (for local builds)
- Docker & Docker Compose (for containerized deployment)

### Start with Script

```bash
# Make the service startup script executable
chmod +x start.sh

# Start the service
./start.sh
```

### Using Docker Compose

```bash
# Start the service
docker-compose up -d

# View logs
docker-compose logs -f
```

### Multi-Environment Deployment

The API gateway supports independent deployment of three environments: test, pre-release (pre), and production. Each environment uses different configurations and ports:

```bash
# First ensure the deployment script has execution permissions
chmod +x deploy.sh

# Start test environment (port 8001)
./deploy.sh test start

# Start pre-release environment (port 8002)
./deploy.sh pre start

# Start production environment (port 8003)
./deploy.sh prod start

# Start all environments simultaneously
./deploy.sh all start

# View specified environment logs
./deploy.sh test logs

# Check environment status
./deploy.sh pre status

# Stop specified environment
./deploy.sh prod stop

# Restart specified environment
./deploy.sh test restart
```

The deployment script will automatically create environment configuration directories and files. Each environment's configuration is stored in the `config/<environment>/.env` file and can be modified as needed.

Environment access ports:
- Test environment: http://localhost:8001
- Pre-release environment: http://localhost:8002
- Production environment: http://localhost:8003

For more detailed information about multi-environment deployment, please refer to [Multi-Environment Deployment Guide](docs/multi-environment-deployment.md).

## Configuration Guide

### Environment Variables

Configure the following environment variables in the `.env` file:

#### Target URL Whitelist Configuration

`DELIGHTFUL_GATEWAY_ALLOWED_TARGET_URLS` is used to configure whitelist rules for allowed target URLs.

**Format Description:**
- Use `|` to separate multiple rules
- Each rule format: `type:pattern@description` (`@description` is optional)
- Rule types:
  - `exact`: Exact URL match
  - `domain`: Domain and all subdomains
  - `prefix`: URL prefix match
  - `regex`: Regular expression match

**Examples:**
```bash
# Allow specific domains
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_URLS=domain:example.com@Example Service|domain:openai.com@OpenAI API

# Mixed rules
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_URLS=domain:xxxx.cn@Internal Service|prefix:https://api.github.com/@GitHub API
```

**Security Features:**
- Automatically blocks private IPs (127.x, 10.x, 192.168.x, etc.)
- Only allows http/https protocols
- Blocks common management ports (22, 23, 25, 3306, etc.)

#### Allowed Private IP Configuration

`DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP` is used in multi-node deployment scenarios to allow certain private IPs to pass validation.

**Format Description:**
- Supports multiple separators: comma (`,`), semicolon (`;`), newline (`\n`), space (` `)
- Supports single IP addresses and CIDR format
- Supports IPv4 and IPv6

**Use Cases:**
- Single-node deployment: Allow specific private network services
- Multi-node deployment: Allow IP ranges of nodes within the cluster

**Examples:**

```bash
# Single-node deployment - Allow specific IPs and subnets
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP=192.168.1.1,10.0.0.0/8

# Multi-node deployment - Comma separated (recommended)
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP=10.0.1.0/24,10.0.2.0/24,10.0.3.0/24,192.168.1.0/24

# Multi-node deployment - Newline separated (suitable for config files)
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP="10.0.1.0/24
10.0.2.0/24
10.0.3.0/24
192.168.1.0/24"

# Mixed format
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP=192.168.1.1,10.0.0.0/8;172.16.0.0/12 192.168.2.0/24

# Support IPv6
DELIGHTFUL_GATEWAY_ALLOWED_TARGET_IP=10.0.0.0/8,2001:db8::/32,::1
```

**Notes:**
- If this variable is not configured, all private IPs will be blocked
- Configured IPs will be validated against the whitelist before private IP checking
- System automatically deduplicates to avoid repeated IP/CIDR rules
- In debug mode, detailed loading logs and statistics will be displayed

### Other Environment Variables

```
# General configuration
JWT_SECRET=your-secret-key-change-me
API_GATEWAY_VERSION=1.0.0
DEFAULT_API_URL=https://api.default-service.com
DELIGHTFUL_GATEWAY_API_KEY=your-gateway-api-key-here

# OpenAI service configuration
OPENAI_API_KEY=sk-xxxx
OPENAI_API_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4

# Delightful service configuration
DELIGHTFUL_API_KEY=xxx
DELIGHTFUL_API_BASE_URL=https://api.delightful.com/v1
DELIGHTFUL_MODEL=gpt-4o-global

# DeepSeek service configuration
DEEPSEEK_API_KEY=xxxxx
DEEPSEEK_API_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-coder

# Azure OpenAI service configuration
AZURE_OPENAI_EMBEDDING_API_KEY=xxxx
AZURE_OPENAI_EMBEDDING_ENDPOINT=https://example.openai.azure.com/
AZURE_OPENAI_EMBEDDING_MODEL=text-embedding-3-large
AZURE_OPENAI_EMBEDDING_DEPLOYMENT=example-text-embedding
AZURE_OPENAI_EMBEDDING_API_VERSION=2023-05-15
```

**Important:** `DELIGHTFUL_GATEWAY_API_KEY` is a critical security credential, used only for authentication at the `/auth` endpoint. This API key is only required when obtaining tokens; all other requests after obtaining a token use the acquired token for authentication and do not require this API key again.

### Container Environment Variables

In containers, you can use the same environment variable names, but without actual values. For example, in the container's `.env` file:

```
OPENAI_API_KEY="OPENAI_API_KEY"
OPENAI_API_BASE_URL="OPENAI_API_BASE_URL"
OPENAI_MODEL="OPENAI_MODEL"

DELIGHTFUL_API_KEY="DELIGHTFUL_API_KEY"
DELIGHTFUL_API_BASE_URL="DELIGHTFUL_API_BASE_URL"
DELIGHTFUL_MODEL="DELIGHTFUL_MODEL"
```

## API Usage Guide

### 1. Obtain Temporary Token

**Important Notes:**
1. Requests to obtain temporary tokens **can only** be initiated from the host machine locally (localhost/127.0.0.1). Containers cannot directly obtain tokens. This is designed for security considerations.
2. When obtaining a token, you **must** provide a valid `X-Gateway-API-Key` request header whose value must match the `DELIGHTFUL_GATEWAY_API_KEY` in environment variables.

```bash
curl -X POST http://localhost:8000/auth \
  -H "delightful-user-id: your-user-id" \
  -H "delightful-organization-code: your-organization-code" \
  -H "X-Gateway-API-Key: your-gateway-api-key-here"
```

Response example:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "header": "Delightful-Authorization",
  "example": "Delightful-Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

Temporary tokens are now **permanently valid** with no expiration time limit. You only need to obtain the token once and can use it long-term. When running containers, the token obtained from the host machine should be injected into the container via environment variables when starting the container. Please note to use the `Delightful-Authorization` header instead of the standard `Authorization` header to send requests.

### 2. Query Available Environment Variables

```bash
# Get all allowed environment variable names
curl  http://host.docker.internal:8000/env \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN"

# Query whether specific environment variables are available
curl "http://host.docker.internal:8000/env?vars=OPENAI_API_KEY,OPENAI_MODEL" \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN"
```

Response example:
```json
{
  "available_vars": ["OPENAI_API_KEY", "OPENAI_MODEL", "OPENAI_API_BASE_URL", "DELIGHTFUL_API_KEY", "DELIGHTFUL_MODEL", "API_GATEWAY_VERSION"],
  "message": "Direct access to environment variable values is not allowed, please use these variables through API proxy requests"
}
```

### 3. Query Available Services

```bash
curl http://localhost:8000/services \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN"
```

Response example:
```json
{
  "available_services": [
    {
      "name": "OPENAI",
      "base_url": "api.openai.com",
      "default_model": "gpt-4"
    },
    {
      "name": "DELIGHTFUL",
      "base_url": "api.delightful.com",
      "default_model": "gpt-4o-global"
    },
    {
      "name": "DEEPSEEK",
      "base_url": "api.deepseek.com",
      "default_model": "deepseek-coder"
    }
  ],
  "message": "These services can be used through API proxy requests, using format: /{service}/path or using env: reference"
}
```

### 4. Use API Proxy and Replace Environment Variables

There are multiple ways to call different services:

#### Method 1: Direct Access Using Environment Variable Names (Recommended)

```bash
# Access directly through environment variable names
curl -X POST http://host.docker.internal:8000/OPENAI_API_BASE_URL/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-4",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'

# Can also directly use environment variable names as values (when string matches exactly)
curl -X POST http://host.docker.internal:8000/OPENAI_API_BASE_URL/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "OPENAI_MODEL",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'

# Using Delightful service
curl -X POST http://host.docker.internal:8000/DELIGHTFUL_API_BASE_URL/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "DELIGHTFUL_MODEL",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'
```

#### Method 2: Access Through Service Name

```bash
# Call OpenAI service
curl -X POST http://host.docker.internal:8000/openai/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "env:OPENAI_MODEL",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'

# Call Delightful service
curl -X POST http://host.docker.internal:8000/delightful/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "env:DELIGHTFUL_MODEL",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'
```

#### Method 3: Specify Service Using Query Parameters

```bash
curl -X POST "http://host.docker.internal:8000/v1/chat/completions?service=deepseek" \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "env:DEEPSEEK_MODEL",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'
```

#### Method 4: Use Environment Variable References

```bash
curl -X POST http://host.docker.internal:8000/v1/chat/completions \
  -H "Delightful-Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "env:OPENAI_MODEL",
    "api_base": "${OPENAI_API_BASE_URL}",
    "messages": [
      {"role": "user", "content": "Hello!"}
    ]
  }'
```

## Docker Container Integration

In Docker containers, due to security restrictions, temporary tokens cannot be obtained directly. Please follow these steps to obtain tokens on the host machine and inject them into containers:

### 1. Obtain Temporary Token on Host Machine

#### Single Environment Mode

```bash
# Execute on host machine
USER_ID="your-user-id"
GATEWAY_API_KEY="your-gateway-api-key"

# Obtain temporary token (can only be executed locally)
TOKEN=$(curl -s -X POST "http://localhost:8000/auth" \
  -H "X-USER-ID: $USER_ID" \
  -H "X-Gateway-API-Key: $GATEWAY_API_KEY" | jq -r '.token')

echo "Obtained token: $TOKEN"
```

#### Multi-Environment Mode

```bash
# Execute on host machine - Specify environment (test, pre, prod)
ENV="test"  # Optional values: test, pre, prod
USER_ID="your-user-id"

# Select port and API key based on environment
case $ENV in
  test)
    PORT=8001
    GATEWAY_API_KEY="test-gateway-api-key"
    ;;
  pre)
    PORT=8002
    GATEWAY_API_KEY="pre-gateway-api-key"
    ;;
  prod)
    PORT=8003
    GATEWAY_API_KEY="prod-gateway-api-key"
    ;;
esac

# Obtain temporary token for specified environment
TOKEN=$(curl -s -X POST "http://localhost:$PORT/auth" \
  -H "X-USER-ID: $USER_ID" \
  -H "X-Gateway-API-Key: $GATEWAY_API_KEY" | jq -r '.token')

echo "Obtained token for $ENV environment: $TOKEN"
```

### 2. Inject Token When Starting Container

#### Single Environment Mode

```bash
# Start container with obtained token
docker run -e API_TOKEN="$TOKEN" \
  -e API_GATEWAY_URL="http://host.docker.internal:8000" \
  your-image
```

#### Multi-Environment Mode

```bash
# Start container with obtained token, specify environment
docker run -e API_TOKEN="$TOKEN" \
  -e API_GATEWAY_URL="http://host.docker.internal:$PORT" \
  -e API_GATEWAY_ENV="$ENV" \
  your-image
```

### 3. Use Injected Token Inside Container

```bash
# Applications inside container can get token from environment variables
TOKEN=$API_TOKEN
GATEWAY_URL=$API_GATEWAY_URL

# Query available services
curl -s "$GATEWAY_URL/services" \
  -H "Delightful-Authorization: Bearer $TOKEN"
```

### 4. Configure Multi-Environment Using Docker Compose

You can configure application containers in docker-compose.yml file to connect to API gateway for specific environments:

```yaml
version: '3'

services:
  your-app:
    image: your-app-image
    environment:
      - API_TOKEN=${API_TOKEN}
      - API_GATEWAY_URL=http://host.docker.internal:${PORT:-8000}
      - API_GATEWAY_ENV=${ENV:-dev}
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

Then start containers using environment variables:

```bash
# Start application container with injected environment variables
ENV=test PORT=8001 API_TOKEN=$TOKEN docker-compose up -d
```

## Security Features

1. **Environment Variable Protection**: Containers cannot directly access host machine environment variable values, can only use them indirectly through API proxy requests
2. **Environment Variable Replacement**: API gateway automatically replaces environment variable references in requests, containers don't need to know actual values
3. **Custom Authentication Header**: Uses Delightful-Authorization header to avoid conflicts with other services' Authorization
4. **Multi-Service Isolation**: API keys for each service are managed by the gateway and not exposed to containers
5. **Temporary Tokens**: All requests require valid authentication tokens with time limits
6. **Container Isolation**: Each container uses independent tokens, cannot access other containers' tokens
7. **Gateway API Key**: Obtaining tokens requires providing a valid gateway API key (`X-Gateway-API-Key`), adding an extra security layer

## Performance Comparison

Compared to the Python version, the Go version of the API gateway has the following performance advantages:

1. **Lower Memory Usage**: Go version typically uses less memory than Python version
2. **Higher Concurrency Handling**: Go's concurrency model enables more efficient handling of large numbers of requests
3. **Faster Startup Time**: Go compiles to a single executable file with faster startup speed
4. **Lower Latency**: Request processing latency is significantly reduced

## Build Instructions

If manual building is needed:

```bash
# Get dependencies
go mod tidy

# Build executable
go build -o api-gateway
```

## Security Recommendations

1. Change `JWT_SECRET` in production environment
2. Add HTTPS proxy layer when needed
3. Restrict containers allowed to access
4. Regularly rotate API keys

## Environment Variable Replacement Feature

The API gateway provides powerful environment variable replacement functionality, allowing replacement of environment variable references in different locations:

1. **Request Body Replacement** - Replaces environment variable references in the following formats in JSON request body:
   - Exact environment variable name match: `"model": "OPENAI_MODEL"`
   - `env:` prefix: `"model": "env:OPENAI_MODEL"`
   - `${VAR}` format: `"url": "https://example.com/${SERVICE_URL}"`
   - `$VAR` format: `"key": "$OPENAI_API_KEY"`

2. **Request Header Replacement** - Replaces environment variable references in custom request headers

3. **URL Path Replacement** - Uses environment variables as URL path prefix: `/OPENAI_API_BASE_URL/v1/chat/completions`

This allows containers to safely use environment variables without knowing actual values. The API gateway automatically detects and replaces environment variable references in requests, with all replacements completed on the proxy side, ensuring sensitive information is not exposed to containers.
