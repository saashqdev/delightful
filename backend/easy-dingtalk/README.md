# Easy DingTalk

<p align="center">
  <a href="https://packagist.org/packages/dtyq/easy-dingtalk"><img src="https://img.shields.io/packagist/v/dtyq/easy-dingtalk.svg" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/dtyq/easy-dingtalk"><img src="https://img.shields.io/packagist/dt/dtyq/easy-dingtalk.svg" alt="Total Downloads"></a>
  <a href="https://github.com/lihq1403/easy-dingtalk/actions"><img src="https://github.com/lihq1403/easy-dingtalk/workflows/CI/badge.svg" alt="Build Status"></a>
</p>

Easy DingTalk is an easy-to-use SDK for the DingTalk Open Platform, supporting PHP 8.1+. It provides flexible interfaces to interact with the DingTalk Open Platform, allowing developers to build DingTalk applications with ease.

## Features

- Supports PHP 8.1+
- Developed based on PSR standards
- Supports Hyperf framework integration
- Flexible request assembly mechanism
- Comprehensive unit tests
- Supports major DingTalk Open Platform interfaces

## Installation

```bash
composer require dtyq/easy-dingtalk -vvv
```

## Quick Start

### Basic Usage

```php
use Dtyq\EasyDingTalk\OpenDevFactory;

$factory = new OpenDevFactory([
    'app_key' => 'your_app_key',
    'app_secret' => 'your_app_secret',
]);

// Get access token
$accessToken = $factory->getAccessToken();

// Use other interfaces...
```

### Hyperf Integration

Add to `config/autoload/dependencies.php`:

```php
return [
    Dtyq\EasyDingTalk\OpenDevFactory::class => function (ContainerInterface $container) {
        return new Dtyq\EasyDingTalk\OpenDevFactory([
            'app_key' => config('dingtalk.app_key'),
            'app_secret' => config('dingtalk.app_secret'),
        ]);
    },
];
```

## Development

### Run Tests

```bash
composer test
```

### Code Style Check

```bash
composer cs-fix
```

### Static Analysis

```bash
composer analyse
```

## Contributing

Pull Requests and Issues are welcome.

## License

MIT