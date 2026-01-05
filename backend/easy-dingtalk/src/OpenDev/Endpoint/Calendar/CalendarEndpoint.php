<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\Calendar;

use Delightful\EasyDingTalk\Kernel\Exceptions\BadRequestException;
use Delightful\EasyDingTalk\OpenDev\Api\Calendar\CreateEventApi;
use Delightful\EasyDingTalk\OpenDev\Endpoint\OpenDevEndpoint;
use Delightful\EasyDingTalk\OpenDev\Parameter\Calendar\CreateEventParameter;
use Delightful\EasyDingTalk\OpenDev\Result\Calendar\CreateEventResult;
use GuzzleHttp\RequestOptions;

class CalendarEndpoint extends OpenDevEndpoint
{
    public function createEvent(CreateEventParameter $parameter): CreateEventResult
    {
        $parameter->validate();

        $api = new CreateEventApi();
        $api->setPathParams([
            'userId' => $parameter->getUserId(),
            'calendarId' => $parameter->getCalendarId(),
        ]);
        $api->setOptions([
            RequestOptions::HEADERS => [
                'x-acs-dingtalk-access-token' => $parameter->getAccessToken(),
                'x-client-token' => $parameter->getRequestId(),
            ],
            RequestOptions::JSON => $parameter->toBody(),
        ]);
        $response = $this->send($api);
        $data = json_decode($response->getBody()->getContents(), true);
        if (! is_array($data)) {
            throw new BadRequestException('Invalid response content');
        }
        return CalendarFactory::createCreateEventResult($data);
    }
}
