<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * conversation的messagetype.
 */
enum ConversationType: int
{
    // 与ai的conversation(private chat)
    case Ai = 0;

    // 与人category的conversation(private chat)
    case User = 1;

    // group chat
    case Group = 2;

    // systemmessage
    case System = 3;

    // 云document
    case CloudDocument = 4;

    // multidimensional table
    case MultidimensionalTable = 5;

    // 话题
    case Topic = 6;

    // applicationmessage
    case App = 7;

    /**
     * 将枚举typeconvert.
     */
    public static function getCaseFromName(string $typeName): ?ConversationType
    {
        foreach (self::cases() as $conversationType) {
            if ($conversationType->name === $typeName) {
                return $conversationType;
            }
        }
        return null;
    }
}
