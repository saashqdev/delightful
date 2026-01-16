<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Test\Cases\Entity;

use DateTime;
use Delightful\TaskScheduler\Entity\TaskSchedulerCrontab;
use Delightful\TaskScheduler\Test\Cases\AbstractTestCase;

/**
 * @internal
 * @coversNothing
 */
class TaskSchedulerCrontabTest extends AbstractTestCase
{
    public function testListCycleDate()
    {
        $crontab = new TaskSchedulerCrontab();
        $crontab->setLastGenTime(new DateTime('2021-01-01'));
        $crontab->setCrontab('* * * * *');

        $list = $crontab->listCycleDate(new DateTime('2021-01-02'), 1);
        $this->assertCount(1, $list);
        $list = $crontab->listCycleDate(new DateTime('2021-01-02'), 1);
        $this->assertCount(1, $list);
        $this->assertEquals('2021-01-01 00:02:00', $list[0]->format('Y-m-d H:i:s'));
    }
}
