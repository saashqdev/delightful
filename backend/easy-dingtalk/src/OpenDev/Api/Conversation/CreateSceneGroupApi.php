<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\Conversation;

use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

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
