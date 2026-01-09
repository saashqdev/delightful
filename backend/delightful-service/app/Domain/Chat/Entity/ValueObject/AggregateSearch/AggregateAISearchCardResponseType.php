<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\AggregateSearch;

/**
 * responseorder:5 3 0 1 bybackrandom.
 */
class AggregateAISearchCardResponseType
{
    /**
     *associateissuesearchresult,includechildissue(search_keywords), webpagesearchresult(search), totalword count(total_words), matchword count(match_count), page count(page_count).
     */
    public const int SEARCH = 0;

    // LLM response
    public const int LLM_RESPONSE = 1;

    // thinkingguidegraph
    public const int MIND_MAP = 2;

    // associateissue
    public const int ASSOCIATE_QUESTIONS = 3;

    // event
    public const int EVENT = 4;

    // ping pong
    public const int PING_PONG = 5;

    // exceptiontermination
    public const int TERMINATE = 6;

    // PPT
    public const int PPT = 7;

    // searchdeepdegree
    public const int SEARCH_DEEP_LEVEL = 8;

    public static function getNameFromType(int $type): string
    {
        $typeNames = [
            self::SEARCH => 'searchresult',
            self::LLM_RESPONSE => 'LLMresponse',
            self::MIND_MAP => 'thinkingguidegraph',
            self::ASSOCIATE_QUESTIONS => 'associateissue',
            self::EVENT => 'event',
            self::PING_PONG => 'ping_pong',
            self::TERMINATE => 'exceptiontermination',
            self::PPT => 'PPT',
            self::SEARCH_DEEP_LEVEL => 'searchdeepdegree',
        ];
        return $typeNames[$type] ?? 'unknowntype';
    }
}
