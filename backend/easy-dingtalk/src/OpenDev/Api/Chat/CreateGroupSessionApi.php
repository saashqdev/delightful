<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\Chat;

use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Create group chat session.
 * @see https://open.dingtalk.com/document/orgapp/create-group-session
 */
class CreateGroupSessionApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/chat/create';
    }
}
