<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace HyperfTest\Extensions\Runners\SlowestTests;

/**
 * Console output channel.
 */
final class ConsoleChannel extends Channel
{
    protected function printResults(): void
    {
        $tests = $this->testsToPrint();

        if (empty($tests)) {
            return;
        }

        echo "\n";
        echo "Slowest tests:\n";

        foreach ($tests as $test => $time) {
            printf("  %s: %s ms\n", $test, $time);
        }
    }
}
