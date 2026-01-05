<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Conversation;

use Delightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Delightful\SdkBase\Kernel\Constant\RequestMethod;

class CreateGroupApi extends OpenDevApiAbstract
{
    public function getUri(): string
    {
        return '/chat/create';
    }

    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }
}
