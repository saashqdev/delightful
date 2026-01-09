<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

class SubQuestionsTool
{
    public static string $name = 'batchSubQuestionsSearch';

    public static string $description = 'according tooriginalissue,splitminutemultiple childrenissue,useatbatchquantityinternetsearch';

    protected static array $parameters = [
        'type' => 'object',
        'properties' => [
            'subQuestions' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'according tooriginalissuesplitminuteoutcomeonechildissue',
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
