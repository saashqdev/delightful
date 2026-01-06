<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */
// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Delightful\CodeExecutor\Exception\ExecuteException;
use Delightful\CodeExecutor\ExecutionRequest;
use Delightful\CodeExecutor\Executor\Aliyun\AliyunExecutor;
use Delightful\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient;
use Delightful\CodeExecutor\Language;

// Check if configuration file exists
$configFile = __DIR__ . '/aliyun_executor_config.php';
if (! file_exists($configFile)) {
    echo "Configuration file does not exist. Please create aliyun_executor_config.php based on aliyun_executor_config.example.php and fill in your Alibaba Cloud configuration\n";
    exit(1);
}

// Load configuration
$config = require $configFile;

try {
    echo "=== Alibaba Cloud Code Executor Example ===\n\n";

    // Create runtime client
    echo "Creating runtime client...\n";
    $runtimeClient = new AliyunRuntimeClient($config);

    // Create executor
    echo "Creating executor...\n";
    $executor = new AliyunExecutor($runtimeClient);

    // Initialize executor (prepare runtime environment)
    echo "Initializing execution environment...\n";
    $executor->initialize();
    echo "Execution environment initialized successfully\n\n";

    // Prepare PHP code to execute
    $phpCode = <<<'EOD'
<?php
function add($a, $b) {
    return $a + $b;
}

$a = $args['a'] ?? 5;
$b = $args['b'] ?? 3;

$result = add($a, $b);

echo "Calculation result: $a + $b = $result\n";

return [
    'sum' => $result,
    'a' => $a,
    'b' => $b,
    'timestamp' => time()
];
EOD;

    // Create execution request, can pass parameters
    $request = new ExecutionRequest(
        Language::PHP,       // Execution language
        $phpCode,            // Code to execute
        ['a' => 10, 'b' => 7], // Parameters passed to code
        60                   // Timeout in seconds
    );

    // Execute code
    echo "Executing code...\n";
    echo json_encode($request, JSON_PRETTY_PRINT);
    $startTime = microtime(true);
    $result = $executor->execute($request);
    $endTime = microtime(true);

    // Output results
    echo "\nExecution completed!\n";
    echo "------------------------------\n";
    echo "Execution output:\n{$result->getOutput()}\n";
    echo "Execution time: {$result->getDuration()}ms\n";
    echo 'Actual time: ' . round(($endTime - $startTime) * 1000, 2) . "ms\n";
    echo 'Output content: ' . $result->getOutput() . "\n";
    echo "Execution result:\n" . json_encode($result->getResult(), JSON_PRETTY_PRINT) . "\n";
    echo "------------------------------\n";

    // Performance test with multiple executions (optional)
    echo "\nPerform performance test? (y/n): ";
    $input = trim(fgets(STDIN));
    if (strtolower($input) === 'y') {
        $count = 10; // Number of executions
        echo "\nRunning {$count} performance tests...\n";

        $totalTime = 0;
        for ($i = 1; $i <= $count; ++$i) {
            $startTime = microtime(true);
            $executor->execute($request);
            $endTime = microtime(true);
            $time = ($endTime - $startTime) * 1000;
            $totalTime += $time;
            echo "Execution #{$i} time: " . round($time, 2) . "ms\n";
            sleep(1);
        }

        echo "\nAverage execution time for {$count} runs: " . round($totalTime / $count, 2) . "ms\n";
    }
} catch (ExecuteException $e) {
    echo "\nExecution error: ({$e->getCode()}) {$e->getMessage()}\n";
    if (method_exists($e, 'getOutput')) {
        echo "Error output: {$e->getOutput()}\n";
    }
} catch (Exception $e) {
    echo "\nSystem error: ({$e->getCode()}) {$e->getMessage()}\n";
}

echo "\n=== Example execution completed ===\n";
