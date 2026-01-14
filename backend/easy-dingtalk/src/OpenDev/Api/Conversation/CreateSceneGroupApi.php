<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Api\Conversation;

use BeDelightful\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;

class CreateSceneGroupApi extends OpenDevApiAbstract
{
    public function getUri(): string
    {
        return '/topapi/im/chat/scenegroup/create';
    }

    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }
}
