<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */

namespace Delightful\CodeExecutor\Tests\Unit\Executor\Aliyun;

use BeDelightful\CodeExecutor\Exception\ExecuteFailedException;
use BeDelightful\CodeExecutor\Exception\InvalidArgumentException;
use BeDelightful\CodeExecutor\ExecutionRequest;
use BeDelightful\CodeExecutor\ExecutionResult;
use BeDelightful\CodeExecutor\Executor\Aliyun\AliyunExecutor;
use BeDelightful\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient;
use BeDelightful\CodeExecutor\Language;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \Delightful\CodeExecutor\Executor\Aliyun\AliyunExecutor
 */
class AliyunExecutorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AliyunRuntimeClient|MockInterface
     */
    private $mockRuntimeClient;

    /**
     * @var AliyunExecutor
     */
    private $executor;

    protected function setUp(): void
    {
        $this->mockRuntimeClient = \Mockery::mock(AliyunRuntimeClient::class);
        $this->executor = new AliyunExecutor($this->mockRuntimeClient);
    }

    public function testExecute(): void
    {
        // Prepare test data
        $request = new ExecutionRequest(Language::PHP, '<?php echo "test"; ?>');
        $expectedResult = new ExecutionResult('test output', 100, ['value' => 'result']);

        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('getSupportedLanguages')
            ->once()
            ->andReturn([Language::PHP]);

        $this->mockRuntimeClient->shouldReceive('invoke')
            ->once()
            ->with($request)
            ->andReturn($expectedResult);

        // Execute test method
        $result = $this->executor->execute($request);

        // Verify results
        $this->assertSame($expectedResult, $result);
    }

    public function testExecuteUnsupportedLanguage(): void
    {
        // Prepare test data
        $request = new ExecutionRequest(Language::PYTHON, 'print("test")');

        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('getSupportedLanguages')
            ->once()
            ->andReturn([Language::PHP]);

        // Expect exception to be thrown
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Language python is not supported by this executor');

        // Execute test method
        $this->executor->execute($request);
    }

    public function testExecuteRuntimeException(): void
    {
        // Prepare test data
        $request = new ExecutionRequest(Language::PHP, '<?php echo "test"; ?>');
        $exception = new \RuntimeException('Runtime error', 500);

        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('getSupportedLanguages')
            ->once()
            ->andReturn([Language::PHP]);

        $this->mockRuntimeClient->shouldReceive('invoke')
            ->once()
            ->with($request)
            ->andThrow($exception);

        // Expect exception to be thrown
        $this->expectException(ExecuteFailedException::class);
        $this->expectExceptionMessage('Failed to execute code: Runtime error');

        // Execute test method
        $this->executor->execute($request);
    }

    public function testExecuteExecutionException(): void
    {
        // Prepare test data
        $request = new ExecutionRequest(Language::PHP, '<?php echo "test"; ?>');
        $exception = new ExecuteFailedException('Execution error', 400);

        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('getSupportedLanguages')
            ->once()
            ->andReturn([Language::PHP]);

        $this->mockRuntimeClient->shouldReceive('invoke')
            ->once()
            ->with($request)
            ->andThrow($exception);

        // Expect original exception to be thrown
        $this->expectException(ExecuteFailedException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Execution error');

        // Execute test method
        $this->executor->execute($request);
    }

    public function testGetSupportedLanguages(): void
    {
        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('getSupportedLanguages')
            ->once()
            ->andReturn([Language::PHP]);

        // Execute test method
        $languages = $this->executor->getSupportedLanguages();

        // Verify results
        $this->assertEquals([Language::PHP], $languages);
    }

    public function testInitialize(): void
    {
        // Set Mock expected behavior
        $this->mockRuntimeClient->shouldReceive('initialize')
            ->once();

        // Execute test method
        $this->executor->initialize();
    }
}
