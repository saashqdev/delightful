<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

class SubQuestionsTool
{
    public static string $name = 'batchSubQuestionsSearch';

    public static string $description = 'according tooriginalissue，拆minute多子issue，useatbatchquantity互联网search';

    protected static array $parameters = [
        'type' => 'object',
        'properties' => [
            'subQuestions' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'according tooriginalissue拆minuteoutcomeone子issue',
            ],
        ],
        'additionalProperties' => false,
        'required' => ['subQuestions'],
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
