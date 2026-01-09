<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

/**
 * 批quantitygenerateassociateissue的子issue，then批quantity互联网search.
 */
class AssociationsSubQuestionsTool
{
    public static string $name = 'associationsSubQuestionsSearch';

    public static string $description = '将eachassociateissue拆minute为多子issue，then批quantity互联网search';

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
                'description' => 'associateissue的多子issue',
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
