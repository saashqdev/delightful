<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk;

use BeDelightful\EasyDingTalk\Kernel\Contracts\Factory\FactoryAbstract;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\Calendar\CalendarEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\ChatBot\ChatBotEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\Conversation\ConversationEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\Department\DepartmentEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\DingCallback\DingCallbackEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\Oauth2\AccessTokenEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\User\UserEndpoint;

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
