# Sandbox Gateway Service

The sandbox gateway service provides HTTP and WebSocket interfaces that allow clients to create and manage sandbox Docker containers and communicate with containers through WebSocket connections.

## Features

- Create isolated sandbox Docker containers
- Separate sandbox creation and connection processes
- Provide RESTful API to manage sandbox lifecycle
- Execute commands in containers through WebSocket interface
- Automatically clean up idle containers

## Prerequisites

- Docker installed
- Python 3.8+
- Sandbox container image `sandbox-websocket-image` built

## Quick Start

Use the provided startup script to start the service:

```bash
./start.sh
```

By default, the service will start on port 8003. If you need to specify a different port, you can pass it as an argument:

```bash
./start.sh 8080
```

## API Reference

### HTTP API

| Endpoint | Method | Description |
|------|------|------|
| `/sandboxes` | POST | Create new sandbox container |
| `/sandboxes` | GET | Get list of all sandbox containers |
| `/sandboxes/{sandbox_id}` | GET | Get information about specified sandbox |
| `/sandboxes/{sandbox_id}` | DELETE | Delete specified sandbox container |

#### Create Sandbox

**Request:**
```
POST /sandboxes
```

**Response:**
```json
{
  "sandbox_id": "abcd1234",
  "status": "created",
  "message": "Sandbox container created successfully"
}
```

#### Get Sandbox List

**Request:**
```
GET /sandboxes
```

**Response:**
```json
[
  {
    "sandbox_id": "abcd1234",
    "status": "idle",
    "created_at": 1648371234.567,
    "ip_address": "172.17.0.2"
  },
  {
    "sandbox_id": "efgh5678",
    "status": "connected",
    "created_at": 1648371345.678,
    "ip_address": "172.17.0.3"
  }
]
```

#### Get Sandbox Information

**Request:**
```
GET /sandboxes/{sandbox_id}
```

**Response:**
```json
{
  "sandbox_id": "abcd1234",
  "status": "idle",
  "created_at": 1648371234.567,
  "ip_address": "172.17.0.2"
}
```

#### Delete Sandbox

**Request:**
```
DELETE /sandboxes/{sandbox_id}
```

**Response:**
```json
{
  "message": "Sandbox abcd1234 successfully deleted"
}
```

### WebSocket API

| Endpoint | Description |
|------|------|
| `/ws/{sandbox_id}` | Connect to specified sandbox's WebSocket |

#### Connect to Sandbox

WebSocket connection URL format:
```
ws://localhost:8003/ws/{sandbox_id}
```

After connecting to this endpoint, the service will:
1. Connect to the specified sandbox container
2. Establish bidirectional communication between client and container
3. Container will not be automatically destroyed when connection is disconnected; it will become idle

## Message Format

### Send Command

```json
{
  "command": "ls -la",
  "request_id": "optional-unique-id"
}
```

If `request_id` is not provided, the service will automatically generate one.

### Receive Response

```json
{
  "request_id": "same-as-request",
  "command": "ls -la",
  "success": true,
  "output": "command output",
  "error": "error message if any",
  "returncode": 0,
  "timestamp": "2023-03-27T12:34:56.789"
}
```

## Usage Flow

1. Create sandbox through HTTP interface:
   ```
   POST /sandboxes
   ```

2. Get sandbox ID from response

3. Establish WebSocket connection using sandbox ID:
   ```
   ws://localhost:8003/ws/{sandbox_id}
   ```

4. Send commands and receive results through WebSocket

5. After use, you can delete the sandbox:
   ```
   DELETE /sandboxes/{sandbox_id}
   ```

## Security Considerations

- Containers run in Docker's default bridge network
- This service is for development and testing environments only; not recommended for direct use in production
- Containers will be automatically cleaned up after one hour of being idle
- Sandbox ID should be kept secure; anyone who knows the sandbox ID can access the container through the WebSocket interface