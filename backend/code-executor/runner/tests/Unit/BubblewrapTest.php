<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */

namespace Delightful\CodeRunnerBwrap\Tests\Unit;

use Delightful\CodeRunnerBwrap\Bubblewrap;
use Delightful\CodeRunnerBwrap\Tests\TestCase;
use Hyperf\Support\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @internal
 * @coversNothing
 */
class BubblewrapTest extends TestCase
{
    protected Bubblewrap $bubblewrap;

    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = \Mockery::mock(Filesystem::class);
        $this->bubblewrap = new Bubblewrap($this->filesystem);
    }

    public function testRunThrowsExceptionWhenCodeIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The code cannot be empty.');

        $this->bubblewrap->run([]);
    }

    public function testRunCreatesSandboxAndExecutesCode()
    {
        // Mock filesystem operations
        $this->filesystem->shouldReceive('makeDirectory')->times(4);
        $this->filesystem->shouldReceive('put')->times(3);
        $this->filesystem->shouldReceive('exists')->once()->andReturn(true);
        $this->filesystem->shouldReceive('get')->once()->andReturn('template %{code}%');
        $this->filesystem->shouldReceive('put')->once();
        $this->filesystem->shouldReceive('deleteDirectory')->once();

        // Mock Process class
        $processMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $processMock->shouldReceive('setTimeout')->once();
        $processMock->shouldReceive('setInput')->once();
        $processMock->shouldReceive('run')->once();
        $processMock->shouldReceive('isSuccessful')->once()->andReturn(true);
        $processMock->shouldReceive('getOutput')->once()->andReturn(json_encode(['result' => 'success']));

        $result = $this->bubblewrap->run([
            'code' => 'return "test";',
            'language' => 'php',
            'timeout' => 5,
        ]);

        $this->assertEquals(['result' => 'success', 'duration' => 0], $result);
    }

    public function testRunHandlesExecutionFailure()
    {
        // Mock filesystem operations
        $this->filesystem->shouldReceive('makeDirectory')->times(4);
        $this->filesystem->shouldReceive('put')->times(3);
        $this->filesystem->shouldReceive('exists')->once()->andReturn(true);
        $this->filesystem->shouldReceive('get')->once()->andReturn('template %{code}%');
        $this->filesystem->shouldReceive('put')->once();
        $this->filesystem->shouldReceive('deleteDirectory')->once();

        // Mock Process class
        $processMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $processMock->shouldReceive('setTimeout')->once();
        $processMock->shouldReceive('setInput')->once();
        $processMock->shouldReceive('run')->once();
        $processMock->shouldReceive('isSuccessful')->once()->andReturn(false);
        $processMock->shouldReceive('getErrorOutput')->once()->andReturn('Error executing code');
        $processMock->shouldReceive('getExitCode')->once()->andReturn(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error executing code');

        $this->bubblewrap->run([
            'code' => 'invalid code;',
            'language' => 'php',
        ]);
    }

    public function testRunHandlesEmptyOutput()
    {
        // Mock filesystem operations
        $this->filesystem->shouldReceive('makeDirectory')->times(4);
        $this->filesystem->shouldReceive('put')->times(3);
        $this->filesystem->shouldReceive('exists')->once()->andReturn(true);
        $this->filesystem->shouldReceive('get')->once()->andReturn('template %{code}%');
        $this->filesystem->shouldReceive('put')->once();
        $this->filesystem->shouldReceive('deleteDirectory')->once();

        // Mock Process class
        $processMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $processMock->shouldReceive('setTimeout')->once();
        $processMock->shouldReceive('setInput')->once();
        $processMock->shouldReceive('run')->once();
        $processMock->shouldReceive('isSuccessful')->once()->andReturn(true);
        $processMock->shouldReceive('getOutput')->once()->andReturn('');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No result was obtained');

        $this->bubblewrap->run([
            'code' => 'return null;',
            'language' => 'php',
        ]);
    }

    public function testRunThrowsExceptionWhenTemplateNotFound()
    {
        // Mock filesystem operations
        $this->filesystem->shouldReceive('makeDirectory')->times(4);
        $this->filesystem->shouldReceive('put')->times(2);
        $this->filesystem->shouldReceive('exists')->once()->andReturn(false);
        $this->filesystem->shouldReceive('deleteDirectory')->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template file not found');

        $this->bubblewrap->run([
            'code' => 'return "test";',
            'language' => 'unknown',
        ]);
    }
}
