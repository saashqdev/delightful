<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace HyperfTest\Extensions\Runners\SlowestTests;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

class TestFinishedSubscriber implements FinishedSubscriber
{
    private Channel $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function notify(Finished $event): void
    {
        $test = $event->test();

        // Only record test methods, not test classes
        if (! $test->isTestMethod()) {
            return;
        }

        $testName = $test->name();

        // Compatible with PHPUnit 10.5+ method to get duration
        $duration = 0;

        // Try to use the durationSincePrevious method
        if (method_exists($event->telemetryInfo(), 'durationSincePrevious')) {
            $duration = $event->telemetryInfo()->durationSincePrevious()->asFloat();
        }
        // If it doesn't exist, use alternative (fixed value)
        else {
            $duration = 0.5; // Default to 0.5 seconds as test time
        }

        $this->channel->addTest($testName, $duration);
    }
}
