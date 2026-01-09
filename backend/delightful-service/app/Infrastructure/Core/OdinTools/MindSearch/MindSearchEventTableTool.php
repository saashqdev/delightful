<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\OdinTools\MindSearch;

class MindSearchEventTableTool
{
    public static string $name = 'generateRelationEventsTable';

    public static string $description = 'according touser提问和searchresult，generate相关eventcolumn表';

    protected static array $parameters = [
        'type' => 'object',
        'properties' => [
            'relationEvents' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'event_name' => [
                            'type' => 'string',
                            'description' => 'associateevent名',
                        ],
                        'event_time' => [
                            'type' => 'string',
                            'description' => 'associateeventhair生time',
                        ],
                        'event_description' => [
                            'type' => 'string',
                            'description' => 'associateeventdescription',
                        ],
                        'related_citations' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'associateeventquote的searchresult',
                        ],
                    ],
                    'required' => ['event_name', 'event_time', 'event_description', 'related_citations'],
                ],
                'description' => '相关eventcolumn表',
            ],
        ],
        'additionalProperties' => false,
        'required' => ['relationEvents'],
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
