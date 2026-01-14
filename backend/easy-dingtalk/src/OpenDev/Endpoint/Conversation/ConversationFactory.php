<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\Conversation;

use BeDelightful\EasyDingTalk\OpenDev\Result\Conversation\CreateGroupResult;
use BeDelightful\EasyDingTalk\OpenDev\Result\Conversation\CreateSceneGroupResult;

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
