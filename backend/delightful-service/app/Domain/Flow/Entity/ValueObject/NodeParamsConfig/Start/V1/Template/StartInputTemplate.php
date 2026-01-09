<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\V1\Template;

use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

class StartInputTemplate
{
    public static function getChatMessageInputKeys(): array
    {
        return [
            'conversation_id',
            'topic_id',
            'message_content',
            'message_type',
            'message_time',
            'organization_code',
            'files',
            'user',
            'bot_key',
        ];
    }

    public static function getChatMessageInputTemplateComponent(): Component
    {
        $formJson = <<<'JSON'
{
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": "root节点",
        "description": "",
        "items": null,
        "value": null,
        "required": [
            "conversation_id",
            "topic_id",
            "message_content",
            "message_type",
            "message_time",
            "organization_code",
            "user",
            "bot_key"
        ],
        "properties": {
            "conversation_id": {
                "type": "string",
                "key": "conversation_id",
                "title": "conversation ID",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "topic_id": {
                "type": "string",
                "key": "topic_id",
                "title": "话题 ID",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "message_content": {
                "type": "string",
                "key": "message_content",
                "title": "messagecontent",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "message_type": {
                "type": "string",
                "key": "message_type",
                "title": "messagetype",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "message_time": {
                "type": "string",
                "key": "message_time",
                "title": "send时间",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "organization_code": {
                "type": "string",
                "key": "organization_code",
                "title": "organization编码",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "files": {
                "type": "array",
                "key": "files",
                "title": "file列表",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": {
                    "type": "object",
                    "key": "files",
                    "title": "file",
                    "description": "",
                    "required": [
                        "name",
                        "url",
                        "extension",
                        "size"
                    ],
                    "value": null,
                    "encryption": false,
                    "encryption_value": null,
                    "items": null,
                    "properties": {
                        "name": {
                            "type": "string",
                            "key": "name",
                            "title": "file名称",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "url": {
                            "type": "string",
                            "key": "url",
                            "title": "file链接",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "extension": {
                            "type": "string",
                            "key": "extension",
                            "title": "fileextension名",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "size": {
                            "type": "number",
                            "key": "size",
                            "title": "filesize",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        }
                    }
                },
                "properties": null
            },
            "user": {
                "type": "object",
                "key": "user",
                "title": "user",
                "description": "",
                "items": null,
                "required": [
                    "id",
                    "nickname",
                    "real_name",
                    "position",
                    "phone_number",
                    "work_number"
                ],
                "properties": {
                    "id": {
                        "type": "string",
                        "key": "id",
                        "title": "user ID",
                        "description": "",
                        "items": null,
                        "properties": null,
                        "required": null,
                        "value": null
                    },
                    "nickname": {
                        "type": "string",
                        "key": "nickname",
                        "title": "user昵称",
                        "description": "",
                        "items": null,
                        "properties": null,
                        "required": null,
                        "value": null
                    },
                    "real_name": {
                        "type": "string",
                        "key": "real_name",
                        "title": "true实姓名",
                        "description": "",
                        "items": null,
                        "properties": null,
                        "required": null,
                        "value": null
                    },
                    "position": {
                        "type": "string",
                        "key": "position",
                        "title": "岗位",
                        "description": "",
                        "items": null,
                        "properties": null,
                        "required": null,
                        "encryption": false,
                        "encryption_value": null,
                        "value": null
                    },
                    "work_number": {
                        "type": "string",
                        "key": "work_number",
                        "title": "工号",
                        "description": "",
                        "items": null,
                        "properties": null,
                        "required": null,
                        "encryption": false,
                        "encryption_value": null,
                        "value": null
                    },
                    "departments": {
                        "type": "array",
                        "key": "departments",
                        "title": "department",
                        "description": "desc",
                        "required": [],
                        "encryption": false,
                        "encryption_value": null,
                        "items": {
                            "type": "object",
                            "key": "departments",
                            "sort": 0,
                            "title": "department",
                            "description": "desc",
                            "required": [
                                "id",
                                "name",
                                "path"
                            ],
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": {
                                "id": {
                                    "type": "string",
                                    "title": "department ID",
                                    "description": "",
                                    "key": "id",
                                    "items": null,
                                    "properties": null,
                                    "required": null,
                                    "encryption": false,
                                    "encryption_value": null,
                                    "value": null
                                },
                                "name": {
                                    "type": "string",
                                    "title": "department名称",
                                    "description": "",
                                    "key": "name",
                                    "items": null,
                                    "properties": null,
                                    "required": null,
                                    "encryption": false,
                                    "encryption_value": null,
                                    "value": null
                                },
                                "path": {
                                    "type": "string",
                                    "title": "department路径",
                                    "description": "",
                                    "key": "path",
                                    "items": null,
                                    "properties": null,
                                    "required": null,
                                    "encryption": false,
                                    "encryption_value": null,
                                    "value": null
                                }
                            },
                            "value": null
                        },
                        "properties": null,
                        "value": null
                    }
                },
                "value": null
            },
            "bot_key": {
                "type": "string",
                "key": "bot_key",
                "title": "第third-partychat机器人编码",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            }
        }
    }
JSON;
        return ComponentFactory::generateTemplate(StructureType::Form, json_decode($formJson, true));
    }
}
