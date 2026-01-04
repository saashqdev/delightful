<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Api\ChatBot;

use Dtyq\EasyDingTalk\Kernel\Constants\Host;
use Dtyq\EasyDingTalk\OpenDev\Api\OpenDevApiAbstract;
use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

/**
 * Batch send robot messages in one-on-one chat between user and robot.
 * @see https://open.dingtalk.com/document/orgapp/chatbots-send-one-on-one-chat-messages-in-batches
 */
class SendOneOnOneChatMessagesApi extends OpenDevApiAbstract
{
    public function getMethod(): RequestMethod
    {
        return RequestMethod::Post;
    }

    public function getUri(): string
    {
        return '/v1.0/robot/oToMessages/batchSend';
    }

    public function getHost(): string
    {
        return Host::API_DING_TALK;
    }
}
