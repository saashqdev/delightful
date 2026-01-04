<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Endpoint\Conversation;

use Dtyq\EasyDingTalk\OpenDev\Result\Conversation\CreateGroupResult;
use Dtyq\EasyDingTalk\OpenDev\Result\Conversation\CreateSceneGroupResult;

class ConversationFactory
{
    /**
     * Create scene group result object from raw data
     *
     * @param array $rawData Raw response data
     */
    public static function createSceneGroupResultByRawData(array $rawData): CreateSceneGroupResult
    {
        return new CreateSceneGroupResult($rawData);
    }

    /**
     * Create group result object from raw data
     *
     * @param array $rawData Raw response data
     */
    public static function createGroupResultByRawData(array $rawData): CreateGroupResult
    {
        return new CreateGroupResult($rawData);
    }
}
