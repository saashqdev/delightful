<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace OpenDev\Provider\Calendar;

use DateTime;
use DateTimeZone;
use Delightful\EasyDingTalk\OpenDev\Parameter\Calendar\CreateEventParameter;
use Delightful\EasyDingTalk\Test\OpenDev\OpenDevEndpointBaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class CalendarEndpointTestEndpoint extends OpenDevEndpointBaseTestCase
{
    public function testCreateEvent()
    {
        $openDev = $this->createOpenDevFactory('first');
        $param = new CreateEventParameter($openDev->accessTokenEndpoint->get());
        $param->setUserId('xxx');
        $param->setSummary('Test Calendar Event');
        $param->setStart(new DateTime('2024-09-02 10:00:00', new DateTimeZone('Asia/Shanghai')));
        $param->setEnd(new DateTime('2024-09-02 11:00:00', new DateTimeZone('Asia/Shanghai')));
        $param->setIsAllDay(false);
        $result = $openDev->calendarEndpoint->createEvent($param);
        $this->assertIsString($result->getId());
    }
}
