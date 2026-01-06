## Quick Start
Supports Mac OS and Linux operating systems. Windows systems can run through docker-compose.

### 1. Clone the Project
```bash
git clone https://github.com/saashqdev/delightful/delightful.git
cd delightful
```

### 2. Configure Environment Variables
Configure Delightful environment variables. You must configure at least one large language model environment variable for proper functionality.
Copy the `.env.example` file to `.env` and modify the configuration as needed:
```bash
cp .env.example .env
```

### 3. Start the Service

```bash
# Start the service in foreground
./bin/delightful.sh start
```

### 4. Other Commands

```bash
# Display help information
./bin/delightful.sh help

# Start the service in foreground
./bin/delightful.sh start

# Start the service in background
./bin/delightful.sh daemon

# Stop the service
./bin/delightful.sh stop

# Restart the service
./bin/delightful.sh restart

# Check service status
./bin/delightful.sh status

# View service logs
./bin/delightful.sh logs
```

### 4. Access Services
- API Service: http://localhost:9501
- Web Application: http://localhost:8080
  - Account `13812345678`：Password `bedelightful.ai`
  - Account `13912345678`：Password `bedelightful.ai`
- RabbitMQ Management Interface: http://localhost:15672
  - Username: admin
  - Password: delightful123456
