# Delightful Service

## Project Overview
Delightful Service is a high-performance PHP microservice built on the Hyperf framework and powered by the Swow coroutine engine for high concurrency. It integrates AI search, chat, file handling, access control, and other modules to provide a comprehensive service solution.

## Features
- **AI search**: Integrates Google and other search-engine APIs for intelligent search capabilities.
- **Chat system**: Supports real-time communication and session management.
- **File handling**: Upload, download, and manage files.
- **Workflow management**: Configure and run workflows.
- **Assistant features**: Extensible assistant functionality.

## Requirements
- PHP >= 8.3
- Swow extension
- Redis extension
- PDO extension
- Other extensions: bcmath, curl, fileinfo, openssl, xlswriter, zlib, etc.
- Composer

## Installation & Setup
### 1. Clone the project
```bash
git clone https://github.com/saashqdev/delightful/delightful.git
cd delightful-service
```

### 2. Install dependencies
```bash
composer install
```

### 3. Environment configuration
Copy the sample env file and adjust as needed:
```bash
cp .env.example .env
```

### Database migrations
```bash
php bin/hyperf.php migrate
```

## Running the Application
### Start the frontend
```bash
cd static/web && npm install && npm run dev
```

### Start the backend
```bash
php bin/hyperf.php start
```

Or use the helper script:
```bash
sh start.sh
```

## Development Guide
### Project structure
- app/  application code
  - Application/  application layer
  - Domain/  domain layer
  - Infrastructure/  infrastructure layer
  - Interfaces/  interface layer
  - ErrorCode/  error code definitions
  - Listener/  event listeners
- config/  configuration files
- migrations/  database migrations
- test/  tests
- bin/  executable scripts
- static/  static assets

### Code quality
Code style (PHP-CS-Fixer):
```bash
composer fix
```

Static analysis (PHPStan):
```bash
composer analyse
```

### Unit tests
```bash
vendor/bin/phpunit
# or
composer test
```

## Docker
Build the image with the provided Dockerfile:
```bash
docker build -t delightful-service .
```

## Contributing
1. Fork the repo.
2. Create a feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes (`git commit -m 'Add some amazing feature'`).
4. Push the branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request.

## License
This project is licensed under the MIT License. See the LICENSE file for details.
