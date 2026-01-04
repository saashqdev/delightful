# Code Executor Usage Examples

This directory contains usage examples for the code executor, helping you understand how to integrate and use code execution functionality in real projects.

## Example Files

- `aliyun_executor_config.example.php` - Configuration example file, copy to `aliyun_executor_config.php` and fill in your actual configuration before use
- `aliyun_executor_example.php` - Usage example for Alibaba Cloud Function Compute code executor

## Usage Steps

### 1. Prepare Configuration File

```bash
# Copy configuration example file
cp aliyun_executor_config.example.php aliyun_executor_config.php

# Edit configuration file, fill in your Alibaba Cloud account information
vim aliyun_executor_config.php
```

### 2. Run Example

```bash
# Run Alibaba Cloud code executor example
php aliyun_executor_example.php
```

## Example Output Description

After running the example, you will see output similar to the following:

```
=== Alibaba Cloud Code Executor Example ===

Creating runtime client...
Creating executor...
Initializing execution environment...
Execution environment initialized successfully

Executing code...

Execution completed!
------------------------------
Execution output:
Calculation result: 10 + 7 = 17

Execution time: 123ms
Actual time: 1034.56ms
Execution result:
{
    "sum": 17,
    "a": 10,
    "b": 7,
    "timestamp": 1679876543
}
------------------------------

Perform performance test? (y/n): 
```

## Notes

1. Using Alibaba Cloud Function Compute service requires a valid Alibaba Cloud account and correct configuration
2. Code execution may incur costs, please pay attention to resource usage control
3. It is recommended to verify in a test environment before using in production

## Troubleshooting

If you encounter problems, please check:

1. Whether the information in the configuration file is correct
2. Whether the Alibaba Cloud account has sufficient permissions
3. Whether the Function Compute service has been activated
4. Whether the network connection is normal