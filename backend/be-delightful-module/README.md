# Be Delightful Module

## Introduction

Be Delightful Module is an extension package based on the Hyperf framework, designed as an enhanced extension module specifically for delightful-service. This module adopts Domain-Driven Design (DDD) architecture, providing a clear layered structure and rich functional components for applications.

Be Delightful Module needs to be used in conjunction with delightful-service. Its core functionality is to establish an information transmission channel between users and the Super Delightful AI agent by taking over delightful-service message events. This design enables users to seamlessly interact with the agent, thereby obtaining a more intelligent service experience.

As a bridging module, Be Delightful Module not only handles message delivery but also handles data format conversion, coordinates event flows, and provides necessary context information to ensure that the agent can accurately understand user intentions and provide appropriate responses.

## Features

- Built on Hyperf 3.1, perfectly adapted to existing delightful-service architecture
- Follows Domain-Driven Design (DDD) architecture with clear code organization and easy maintenance
- Provides resource sharing functionality, supporting cross-module resource access
- Acts as a message channel, connecting users with the Super Delightful AI agent
- Supports event listening and handling, responding to user requests in real-time
- Provides workspace management, supporting multi-topic and multi-task processing
- Implements file management system, supporting agent file operations
- PSR-compliant code organization, ensuring code quality

## System Architecture

Be Delightful Module, as an extension of delightful-service, plays the following role in the system:

```
User Request → delightful-service → Be Delightful Module → Super Delightful AI Agent
                 ↑                 |
                 └─────────────────┘
              Response Return
```

This module integrates with delightful-service through the following methods:

1. Listen to delightful-service message events
2. Process and transform message formats
3. Deliver messages to the Super Delightful AI agent
4. Receive and process agent responses
5. Return processing results to delightful-service

## Installation

Install via Composer:

```bash
composer require dtyq/be-delightful-module
```

## Basic Usage

### Configuration

The module provides `ConfigProvider` for registering related services and features. Configure in the Hyperf application's `config/autoload` directory:

```php
<?php

return [
    // Load ConfigProvider
    \Dtyq\BeDelightful\ConfigProvider::class,
];
```

### Integration with delightful-service

To integrate Be Delightful Module with delightful-service, you need to override dependencies in delightful-service:

```php
[
    'dependencies_priority' => [
        // Assistant execution event
        AgentExecuteInterface::class => BeAgentMessageSubscriberV2::class,
        BeAgentMessageInterface::class => BeAgentMessage::class,
    ]
]
```

### Domain Layer Usage

The module is designed based on DDD architecture and includes the following main layers:

- Domain Layer: Contains business logic and entities, such as message processing, workspace management, and other core functions
- Application Layer: Coordinates domain objects to complete complex business scenarios, such as message delivery processes
- Infrastructure Layer: Provides technical support, including data storage, external service calls, etc.
- Interfaces Layer: Handles external requests and responses, providing API interfaces

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
├── Interfaces/       # Interface layer, handles external interactions
│   ├── Share/        # Resource sharing interfaces
│   └── BeAgent/   # Super agent interfaces
├── Listener/         # Event listeners
└── ConfigProvider.php # Configuration provider
```

### Commands

This extension package provides a series of useful commands:

```bash
# Code style fix
composer fix

# Static code analysis
composer analyse

# Execute tests
composer test

# Start Hyperf service
composer start
```

## Message Flow

The basic flow for Be Delightful Module to process messages is as follows:

1. User sends a message in delightful-service
2. delightful-service triggers a message event
3. Be Delightful Module listens to the event and extracts message content
4. Message is converted to a format understandable by the Super Delightful AI agent
5. Message is sent to the Super Delightful AI agent
6. Agent processes the message and generates a response
7. Be Delightful Module receives the response and converts the format
8. Response is passed back to delightful-service through events
9. User receives the agent's response

## Testing

Execute tests:

```bash
composer test
```

## Contribution Guidelines

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## Related Resources

- [Hyperf Official Documentation](https://hyperf.wiki)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Domain-Driven Design Reference](https://www.domainlanguage.com/ddd/)
- [Delightful Service Documentation](https://docs.dtyq.com/delightful-service/)

## Authors

- **dtyq team** - [team@dtyq.com](mailto:team@dtyq.com)

## License

This project uses a private license - please refer to the internal team documentation for details.

## Project Status

This module is under active development. As an enhancement component of delightful-service, it continuously provides upgrades to intelligent interaction capabilities. We welcome feedback and suggestions from team members to jointly improve this key module.