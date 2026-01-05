<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk;

use Delightful\EasyDingTalk\Kernel\Contracts\Factory\FactoryAbstract;
use Delightful\EasyDingTalk\OpenDev\Endpoint\Calendar\CalendarEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\ChatBot\ChatBotEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\Conversation\ConversationEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\Department\DepartmentEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\DingCallback\DingCallbackEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\Oauth2\AccessTokenEndpoint;
use Delightful\EasyDingTalk\OpenDev\Endpoint\User\UserEndpoint;

/**
 * @property AccessTokenEndpoint $accessTokenEndpoint
 * @property DingCallbackEndpoint $dingCallbackEndpoint
 * @property DepartmentEndpoint $departmentEndpoint
 * @property UserEndpoint $userEndpoint
 * @property CalendarEndpoint $calendarEndpoint
 * @property ChatBotEndpoint $chatBotEndpoint
 * @property ConversationEndpoint $conversationEndpoint
 */
class OpenDevFactory extends FactoryAbstract
{
    protected function getEndpoints(): array
    {
        return [
            AccessTokenEndpoint::class,
            DingCallbackEndpoint::class,
            DepartmentEndpoint::class,
            UserEndpoint::class,
            CalendarEndpoint::class,
            ChatBotEndpoint::class,
            ConversationEndpoint::class,
        ];
    }
}
