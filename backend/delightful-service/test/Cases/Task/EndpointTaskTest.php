<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Task;

use HyperfTest\Cases\BaseTest;
use BeDelightful\DelightfulApiPremium\HighAvailability\Task\EndpointStatisticsAggregateTask;

/**
 * @internal
 */
class EndpointTaskTest extends BaseTest
{
    public function testEndpointStatisticsAggregateTask()
    {
        make(EndpointStatisticsAggregateTask::class)->execute();
    }
}
