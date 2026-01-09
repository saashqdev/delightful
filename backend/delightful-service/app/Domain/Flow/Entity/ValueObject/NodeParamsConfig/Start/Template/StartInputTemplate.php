<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Template;

use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

class StartInputTemplate
{
    public static function getChatMessageInputTemplateComponent(): Component
    {
        $formJson = <<<'JSON'
{
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": "rootsectionpoint",
        "description": "",
        "items": null,
        "value": null,
        "required": [
            "user_id",
            "nickname",
            "chat_time",
            "message_type",
            "content",
            "organization_code",
            "conversation_id",
            "topic_id"
        ],
        "properties": {
            "user_id": {
                "type": "string",
                "key": "user_id",
                "sort": 0,
                "title": " userID",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "nickname": {
                "type": "string",
                "key": "nickname",
                "sort": 1,
                "title": " usernickname",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "chat_time": {
                "type": "string",
                "key": "chat_time",
                "sort":  2,
                "title": "sendtime",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "message_type": {
                "type": "string",
                "key": "message_type",
                "sort": 3,
                "title": "messagetype",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "message_content": {
                "type": "string",
                "key": "message_content",
                "sort": 4,
                "title": "messagecontent",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
             "files": {
                "type": "array",
                "key": "root",
                "sort": 5,
                "title": "filecolumntable",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": {
                    "type": "object",
                    "key": "files",
                    "sort": 0,
                    "title": "file",
                    "description": "",
                    "required": [
                        "file_name",
                        "file_url"
                    ],
                    "value": null,
                    "encryption": false,
                    "encryption_value": null,
                    "items": null,
                    "properties": {
                        "file_name": {
                            "type": "string",
                            "key": "file_name",
                            "sort": 0,
                            "title": "filename",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "file_url": {
                            "type": "string",
                            "key": "file_url",
                            "sort": 1,
                            "title": "filegroundaddress",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "file_ext": {
                            "type": "string",
                            "key": "file_ext",
                            "sort": 2,
                            "title": "filebacksuffix",
                            "description": "",
                            "required": null,
                            "value": null,
                            "encryption": false,
                            "encryption_value": null,
                            "items": null,
                            "properties": null
                        },
                        "file_size": {
                            "type": "number",
                            "key": "file_size",
                            "sort": 3,
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
            "organization_code": {
                "type": "string",
                "key": "organization_code",
                "sort": 6,
                "title": "organizationencoding",
                "description": "",
                "items": null,
                "properties": null,
                "required": null,
                "value": null
            },
            "conversation_id": {
                "type": "string",
                "key": "conversation_id",
                "sort": 7,
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
                "sort": 8,
                "title": "topic ID",
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
