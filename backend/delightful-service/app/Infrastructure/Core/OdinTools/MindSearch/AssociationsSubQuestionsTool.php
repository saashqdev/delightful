<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

/**
 * 批量generateassociate问题的子问题，then批量互联网search.
 */
class AssociationsSubQuestionsTool
{
    public static string $name = 'associationsSubQuestionsSearch';

    public static string $description = '将每个associate问题拆分为多个子问题，then批量互联网search';

    protected static array $parameters = [
        'type' => 'object',
        'properties' => [
            'association' => [
                'type' => 'string',
                'description' => 'associate问题',
            ],
            'subQuestions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'associate问题的多个子问题',
            ],
        ],
        'required' => ['association', 'subQuestions'],
    ];

    public function toArray(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => self::$name,
                'description' => self::$description,
                'parameters' => self::$parameters,
            ],
        ];
    }
}
