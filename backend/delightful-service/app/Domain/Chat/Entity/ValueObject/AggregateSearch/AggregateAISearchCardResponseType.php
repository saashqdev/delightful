<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AggregateSearch;

/**
 * response顺序：5 3 0 1 by后随意.
 */
class AggregateAISearchCardResponseType
{
    /**
     *associateissue的searchresult，include子issue(search_keywords), 网页searchresult(search), 总词数(total_words), 匹配词数(match_count), 页数(page_count).
     */
    public const int SEARCH = 0;

    // LLM response
    public const int LLM_RESPONSE = 1;

    // 思维导图
    public const int MIND_MAP = 2;

    // associateissue
    public const int ASSOCIATE_QUESTIONS = 3;

    // event
    public const int EVENT = 4;

    // ping pong
    public const int PING_PONG = 5;

    // exception终止
    public const int TERMINATE = 6;

    // PPT
    public const int PPT = 7;

    // search深度
    public const int SEARCH_DEEP_LEVEL = 8;

    public static function getNameFromType(int $type): string
    {
        $typeNames = [
            self::SEARCH => 'searchresult',
            self::LLM_RESPONSE => 'LLMresponse',
            self::MIND_MAP => '思维导图',
            self::ASSOCIATE_QUESTIONS => 'associateissue',
            self::EVENT => 'event',
            self::PING_PONG => 'ping_pong',
            self::TERMINATE => 'exception终止',
            self::PPT => 'PPT',
            self::SEARCH_DEEP_LEVEL => 'search深度',
        ];
        return $typeNames[$type] ?? '未知type';
    }
}
