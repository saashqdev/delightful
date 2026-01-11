# Be Delightful Module

## Introduction

Be Delightful Module is an extension package based on the Hyperf framework, designed as an enhanced extension module specifically for delightful-service. This module adopts Domain-Driven Design (DDD) architecture, providing a clear layered structure and rich functional components for applications.

Be Delightful Module needs to be used together with delightful-service. Its core function is to establish an information transmission channel between users and the Super Maggie AI agent by taking over delightful-service message events. This design allows users to interact seamlessly with the agent, thereby obtaining a more intelligent service experience.

As a bridging module, Be Delightful Module not only handles message delivery but also converts data formats, coordinates event processes, and provides necessary context information to ensure the agent can accurately understand user intent and provide appropriate responses.

## Features

- Built on Hyperf 3.1, perfectly adapted to existing delightful-service architecture
- Follows Domain-Driven Design (DDD) architecture with clear code organization and easy maintenance
- Provides resource sharing functionality, supporting cross-module resource access
- Serves as message channel connecting users with Super Maggie AI agent
- Supports event listening and processing, real-time response to user requests
- Provides workspace management, supporting multi-topic and multi-task processing
- Implements file management system, supporting agent file operations
- PSR-compliant code organization ensuring code quality

## System Architecture

As an extension of delightful-service, Be Delightful Module plays the following role in the overall system:

```
User Request → delightful-service → Be Delightful Module → Super Maggie AI Agent
                 ↑                 |
                 └─────────────────┘
              Response Return
```

The module integrates with delightful-service through the following methods:

1. Listen to message events from delightful-service
2. Process and transform message formats
3. Forward messages to Super Maggie AI agent
4. Receive and process agent responses
5. Return processing results to delightful-service

## Installation

Install via Composer:

```bash
composer require delightful/be-delightful-module
```

## Basic Usage

### Configuration

The module provides a `ConfigProvider` for registering related services and features. Configure in the `config/autoload` directory of your Hyperf application:

```php
<?php

return [
    // Load ConfigProvider
    \Delightful\BeDelightful\ConfigProvider::class,
];
```

### Integration with delightful-service

To integrate Be Delightful Module with delightful-service, you need to take over dependencies in delightful-service:

```php
[
    'dependencies_priority' => [
        // Agent execution event
        AgentExecuteInterface::class => BeAgentMessageSubscriberV2::class,
        BeAgentMessageInterface::class => BeAgentMessage::class,
    ]
]
```

### Domain Layer Usage

The module is designed based on DDD architecture and contains the following main layers:

- Domain Layer: Contains business logic and entities, such as core functions like message processing and workspace management
- Application Layer: Coordinates domain objects to complete complex business scenarios, such as message delivery processes
- Infrastructure Layer: Provides technical support, including data storage, external service calls, etc.
- Interfaces Layer: Handles external requests and responses, provides API interfaces

## Development

### Directory Structure

```
src/
├── Application/      # Application layer, handles business processes
│   ├── Share/        # Resource sharing services
│   └── BeAgent/   # Super agent services
├── Domain/           # Domain layer, contains core business logic
│   ├── Share/        # Resource sharing domain models
│   └── BeAgent/   # Super agent domain models
├── Infrastructure/   # Infrastructure layer, provides technical implementation
│   ├── ExternalAPI/  # External API calls
│   └── Utils/        # Utility classes
├── Interfaces/       # Interfaces layer, handles external interactions
│   ├── Share/        # Resource sharing interfaces
│   └── BeAgent/   # Super agent interfaces
├── Listener/         # Event listeners
└── ConfigProvider.php # Configuration provider
```

### Commands

This extension package provides a series of useful commands:

```bash
# Code style fixes
composer fix

# Static code analysis
composer analyse

# Run tests
composer test

# Start Hyperf service
composer start
```

## Message Flow

The basic flow for Be Delightful Module to process messages is as follows:

1. User sends message in delightful-service
2. delightful-service triggers message event
3. Be Delightful Module listens to the event and extracts message content
4. Message is converted to a format understandable by Super Maggie AI agent
5. Message is sent to Super Maggie AI agent
6. Agent processes the message and generates a response
7. Be Delightful Module receives the response and converts the format
8. Response is passed back to delightful-service through events
9. User receives the agent's response

## Testing

Run tests:

```bash
composer test
```

## Contributing Guidelines

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## Related Resources

- [Hyperf Official Documentation](https://hyperf.wiki)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Domain-Driven Design Reference](https://www.domainlanguage.com/ddd/)
- [Delightful Service Documentation](https://docs.delightful.com/delightful-service/)

## Authors

- **delightful team** - [team@delightful.ai](mailto:team@delightful.ai)

## License

This project uses a private license - see internal team documentation for details.

## Project Status

This module is under active development as an enhancement component of delightful-service, continuously providing upgrades to intelligent interaction capabilities. We welcome feedback and suggestions from team members to jointly improve this critical module.











