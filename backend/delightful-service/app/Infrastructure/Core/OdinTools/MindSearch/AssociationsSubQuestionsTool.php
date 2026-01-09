<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

/**
 * 批量generateassociateissue的子issue，then批量互联网search.
 */
class AssociationsSubQuestionsTool
{
    public static string $name = 'associationsSubQuestionsSearch';

    public static string $description = '将每个associateissue拆分为多个子issue，then批量互联网search';

    protected static array $parameters = [
        'type' => 'object',
        'properties' => [
            'association' => [
                'type' => 'string',
                'description' => 'associateissue',
            ],
            'subQuestions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'associateissue的多个子issue',
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
