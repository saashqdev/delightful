<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Delightful\CodeExecutor\Tests\Unit\Executor\Aliyun;

use AlibabaCloud\SDK\FC\V20230330\Models\InvokeFunctionResponse;
use Dtyq\CodeExecutor\Enums\StatusCode;
use Dtyq\CodeExecutor\Exception\ExecuteException;
use Dtyq\CodeExecutor\Exception\ExecuteFailedException;
use Dtyq\CodeExecutor\ExecutionRequest;
use Dtyq\CodeExecutor\ExecutionResult;
use Dtyq\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient;
use Dtyq\CodeExecutor\Executor\Aliyun\FC;
use Dtyq\CodeExecutor\Language;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 * @covers \Dtyq\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient
 */
class AliyunRuntimeClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $mockFcClient;

    private array $config;

    protected function setUp(): void
    {
        $this->mockFcClient = \Mockery::mock(FC::class);

        $this->config = [
            'access_key' => 'test_access_key',
            'secret_key' => 'test_secret_key',
            'region' => 'cn-test',
            'endpoint' => 'test.endpoint.com',
            'function' => [
                'name' => 'test-function-name',
                'cpu' => 0.25,
                'disk_size' => 512,
                'memory_size' => 512,
                'instance_concurrency' => 1,
                'runtime' => 'custom.debian10',
                'timeout' => 60,
            ],
        ];
    }

    public function testConstructorWithInvalidConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AliyunRuntimeClient([]);
    }

    public function testGetSupportedLanguages(): void
    {
        // Use reflection to initialize client but skip initializeClient method
        $clientReflection = new \ReflectionClass(AliyunRuntimeClient::class);
        $client = $clientReflection->newInstanceWithoutConstructor();

        $configProperty = $clientReflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($client, $this->config);

        $fcClientProperty = $clientReflection->getProperty('fcClient');
        $fcClientProperty->setAccessible(true);
        $fcClientProperty->setValue($client, $this->mockFcClient);

        $supportLanguagesProperty = $clientReflection->getProperty('supportLanguages');
        $supportLanguagesProperty->setAccessible(true);
        $supportLanguagesProperty->setValue($client, [Language::PHP]);

        $this->assertEquals([Language::PHP], $client->getSupportedLanguages());
    }

    public function testInvoke(): void
    {
        // Create response data that matches parseExecutionResult function expectations
        $responseBody = json_encode([
            'code' => StatusCode::OK->value,
            'data' => [
                'output' => 'test output',
                'duration' => 100,
                'result' => ['value' => 'test result'],
            ],
        ]);

        $mockedStream = \Mockery::mock(StreamInterface::class);
        $mockedStream->shouldReceive('__toString')
            ->andReturn($responseBody);

        $invokeFunctionResponse = new InvokeFunctionResponse([]);
        $invokeFunctionResponse->body = $mockedStream;
        $invokeFunctionResponse->statusCode = 200;

        // Prepare request parameters
        $functionName = 'test-function-name';

        // Mock call
        $this->mockFcClient->shouldReceive('invokeFunction')
            ->once()
            ->with($functionName, \Mockery::any())
            ->andReturn($invokeFunctionResponse);

        // Use reflection to create client and inject Mock and config
        $clientReflection = new \ReflectionClass(AliyunRuntimeClient::class);
        $client = $clientReflection->newInstanceWithoutConstructor();

        $configProperty = $clientReflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($client, $this->config);

        $fcClientProperty = $clientReflection->getProperty('fcClient');
        $fcClientProperty->setAccessible(true);
        $fcClientProperty->setValue($client, $this->mockFcClient);

        $supportLanguagesProperty = $clientReflection->getProperty('supportLanguages');
        $supportLanguagesProperty->setAccessible(true);
        $supportLanguagesProperty->setValue($client, [Language::PHP]);

        // Create execution request
        $request = new ExecutionRequest(Language::PHP, '<?php echo "Hello World"; ?>');

        // Execute test
        $result = $client->invoke($request);

        // Verify results
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEquals('test output', $result->getOutput());
        $this->assertEquals(100, $result->getDuration());
        $this->assertEquals(['value' => 'test result'], $result->getResult());
    }

    public function testInvokeInvalidResponse(): void
    {
        // Mock direct ExecuteFailedException case
        $mockedStream = \Mockery::mock(StreamInterface::class);
        $mockedStream->shouldReceive('__toString')
            ->andReturn('');  // Empty response will trigger error

        $invokeFunctionResponse = new InvokeFunctionResponse([]);
        $invokeFunctionResponse->body = $mockedStream;
        $invokeFunctionResponse->statusCode = 200;

        // Prepare request parameters
        $functionName = 'test-function-name';

        // Mock call
        $this->mockFcClient->shouldReceive('invokeFunction')
            ->once()
            ->with($functionName, \Mockery::any())
            ->andReturn($invokeFunctionResponse);

        // Use reflection to create client and inject Mock and config
        $clientReflection = new \ReflectionClass(AliyunRuntimeClient::class);
        $client = $clientReflection->newInstanceWithoutConstructor();

        $configProperty = $clientReflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($client, $this->config);

        $fcClientProperty = $clientReflection->getProperty('fcClient');
        $fcClientProperty->setAccessible(true);
        $fcClientProperty->setValue($client, $this->mockFcClient);

        $supportLanguagesProperty = $clientReflection->getProperty('supportLanguages');
        $supportLanguagesProperty->setAccessible(true);
        $supportLanguagesProperty->setValue($client, [Language::PHP]);

        // Create execution request
        $request = new ExecutionRequest(Language::PHP, '<?php echo "Hello World"; ?>');

        $this->expectException(ExecuteFailedException::class);
        // Adjust exception message matching, use more lenient pattern
        $this->expectExceptionMessageMatches('/Failed to retrieve the result/');

        // Execute test
        $client->invoke($request);
    }

    public function testInvokeInvalidResponseCode(): void
    {
        // Create response with non-OK status code
        $responseBody = json_encode([
            'code' => StatusCode::ERROR->value,
            'message' => 'An error occurred in function execution',
        ]);

        $mockedStream = \Mockery::mock(StreamInterface::class);
        $mockedStream->shouldReceive('__toString')
            ->andReturn($responseBody);

        $invokeFunctionResponse = new InvokeFunctionResponse([]);
        $invokeFunctionResponse->body = $mockedStream;
        $invokeFunctionResponse->statusCode = 200;

        // Prepare request parameters
        $functionName = 'test-function-name';

        // Mock call
        $this->mockFcClient->shouldReceive('invokeFunction')
            ->once()
            ->with($functionName, \Mockery::any())
            ->andReturn($invokeFunctionResponse);

        // Use reflection to create client and inject Mock and config
        $clientReflection = new \ReflectionClass(AliyunRuntimeClient::class);
        $client = $clientReflection->newInstanceWithoutConstructor();

        $configProperty = $clientReflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($client, $this->config);

        $fcClientProperty = $clientReflection->getProperty('fcClient');
        $fcClientProperty->setAccessible(true);
        $fcClientProperty->setValue($client, $this->mockFcClient);

        $supportLanguagesProperty = $clientReflection->getProperty('supportLanguages');
        $supportLanguagesProperty->setAccessible(true);
        $supportLanguagesProperty->setValue($client, [Language::PHP]);

        // Create execution request
        $request = new ExecutionRequest(Language::PHP, '<?php echo "Hello World"; ?>');

        // According to parseExecutionResult function implementation, non-OK status code will throw ExecuteException
        $this->expectException(ExecuteException::class);
        $this->expectExceptionMessage('An error occurred in function execution');

        // Execute test
        $client->invoke($request);
    }
}
