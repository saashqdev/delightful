<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace HyperfTest\Extensions\Runners\SlowestTests;

abstract class Channel
{
    /**
     * Stores slow tests.
     */
    protected array $tests = [];

    /**
     * Maximum number of tests to collect.
     */
    protected ?int $rows;

    /**
     * Collect tests whose duration exceeds min milliseconds.
     */
    protected ?int $min;

    public function __construct(?int $rows = null, ?int $min = 200)
    {
        $this->rows = $rows;
        $this->min = $min;
    }

    public function addTest(string $test, float $time): void
    {
        $time = $this->timeToMiliseconds($time);

        if ($time <= $this->min) {
            return;
        }

        $this->tests[$test] = $time;
    }

    public function finishTests(): void
    {
        $this->sortTestsBySpeed();
        $this->printResults();
    }

    protected function timeToMiliseconds(float $time): int
    {
        return (int) ($time * 1000);
    }

    protected function sortTestsBySpeed(): void
    {
        arsort($this->tests);
    }

    abstract protected function printResults(): void;

    protected function testsToPrint(): array
    {
        if ($this->rows === null) {
            return $this->tests;
        }

        return array_slice($this->tests, 0, $this->rows, true);
    }

    protected function getClassName(): string
    {
        return get_class($this);
    }
}
