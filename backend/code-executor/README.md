# Code Executor

An isolated environment system supporting multi-language code execution. It can safely execute code through different runtime environments (such as Alibaba Cloud Function Compute, local processes, etc.).

## Key Features

- **Multi-language Support**: Currently supports programming languages such as PHP, Python, etc.
- **Multiple Runtime Environments**: Supports runtimes like Alibaba Cloud Function Compute
- **Security Isolation**: Executes code in isolated environments to ensure system security
- **High Extensibility**: Easy to add new language support and runtime environments
- **Clean API**: Simple and intuitive interface design

## Installation

Install via Composer:

```bash
composer require dtyq/code-executor
```

## Quick Start

### Direct Usage

```php
<?php

use Dtyq\CodeExecutor\Executor\Aliyun\AliyunExecutor;
use Dtyq\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient;
use Dtyq\CodeExecutor\ExecutionRequest;
use Dtyq\CodeExecutor\Language;

// Alibaba Cloud configuration
$config = [
    'access_key' => 'your-access-key-id',
    'secret_key' => 'your-access-key-secret',
    'region' => 'cn-hangzhou',
    'endpoint' => 'cn-hangzhou.fc.aliyuncs.com',
];

// Create Alibaba Cloud runtime client
$runtimeClient = new AliyunRuntimeClient($config);

// Create executor
$executor = new AliyunExecutor($runtimeClient);

// Initialize execution environment
$executor->initialize();

// Create execution request
$request = new ExecutionRequest(
    Language::PHP,
    '<?php 
        $a = 10;
        $b = 20;
        $sum = $a + $b;
        echo "Calculation result: {$a} + {$b} = {$sum}";
        return ["sum" => $sum, "a" => $a, "b" => $b];
    ',
    [],  // Parameters
    30   // Timeout (seconds)
);

// Execute code
$result = $executor->execute($request);

// Output results
echo "Output: " . $result->getOutput() . PHP_EOL;
echo "Execution time: " . $result->getDuration() . "ms" . PHP_EOL;
echo "Return result: " . json_encode($result->getResult(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
```

### Using in Hyperf

Publish configuration file:

```bash
php bin/hyperf.php vendor:publish dtyq/code-executor
```

Add environment variables to `.env` file:

```
CODE_EXECUTOR=aliyun
CODE_EXECUTOR_ALIYUN_ACCESS_KEY=
CODE_EXECUTOR_ALIYUN_SECRET_KEY=
CODE_EXECUTOR_ALIYUN_REGION=cn-shenzhen
CODE_EXECUTOR_ALIYUN_ENDPOINT=
CODE_EXECUTOR_ALIYUN_FUNCTION_NAME=
```

## Detailed Documentation

### Core Components

- **Executor**: Main component responsible for code execution
- **RuntimeClient**: Interface for communicating with specific execution environments
- **ExecutionRequest**: Encapsulates code execution request information
- **ExecutionResult**: Encapsulates code execution result information

### Supported Languages

Currently supported programming languages:

- PHP
- Python

More language support can be easily added through extensions.

### Supported Runtime Environments

Currently supported runtime environments:

- Alibaba Cloud Function Compute

### Configuration Options

#### Alibaba Cloud Function Compute Configuration

```php
$config = [
    'access_key' => 'your-access-key-id',    // Alibaba Cloud AccessKey ID
    'secret_key' => 'your-access-key-secret', // Alibaba Cloud AccessKey Secret
    'region' => 'cn-hangzhou',               // Region ID
    'endpoint' => 'cn-hangzhou.fc.aliyuncs.com', // Service endpoint
    'function' => [
        'name' => 'test-code-runner',       // Function name
        // You can override default configuration here
        'code_package_path' => __DIR__ . '/../runner',
    ],
];  
```

## Examples

More usage examples can be found in the `examples` directory:

- `examples/aliyun_executor_example.php` - Complete example for Alibaba Cloud Function Compute executor
- `examples/aliyun_executor_config.example.php` - Configuration example file

Running examples:

```bash
# Copy configuration example
cp examples/aliyun_executor_config.example.php examples/aliyun_executor_config.php

# Edit configuration file
vim examples/aliyun_executor_config.php

# Run example
php examples/aliyun_executor_example.php
```

## Extension Development

### Adding New Language Support

1. Add new language type to the `Language` enum
2. Implement corresponding language support logic in the runtime client

### Adding New Runtime Environments

1. Implement the `RuntimeClient` interface
2. Create corresponding `Executor` implementation class

## Notes

1. Using Alibaba Cloud Function Compute service requires a valid Alibaba Cloud account and correct configuration
2. Code execution may incur costs, please pay attention to resource usage control
3. It is recommended to verify in a test environment before using in production
4. The `runner` directory contains the source code of the `dtyq/code-runner-bwrap` project, which serves as the runtime environment in Alibaba Cloud Function Compute service. Since this component has not been officially open-sourced yet, it is currently embedded directly in this project to ensure functional completeness. After the component is officially open-sourced, only the `runner/bootstrap` file needs to be retained, and the rest can be introduced through dependencies

## License

Apache License 2.0
