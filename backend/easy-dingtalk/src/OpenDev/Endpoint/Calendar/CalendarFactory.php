<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\Calendar;

use BeDelightful\EasyDingTalk\OpenDev\Result\Calendar\CreateEventResult;

class CalendarFactory
{
    public static function createCreateEventResult(array $rawData): CreateEventResult
    {
        return new CreateEventResult($rawData);
    }
}
