<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\Message;

use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Send work notification message using template.
 * @doc https://open.dingtalk.com/document/isvapp/work-notification-templating-send-notification-interface
 */
class SendCorpConversationByTemplateApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/topapi/message/corpconversation/sendbytemplate';
    }
}
