<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AIImage\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class AIImageBuiltInTool extends AbstractAIImageBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AIImage->getCode();
    }

    public function getName(): string
    {
        return 'ai_image';
    }

    public function getDescription(): string
    {
        return '文生图tool';
    }

    public function getCallback(): ?Closure
    {
        // 可接受parameter指定任意 model，default是火山。
        return function (ExecutionData $executionData) {
            $args = $executionData->getTriggerData()?->getParams();
            $model = $args['model'] ?? ImageGenerateModelType::Volcengine->value;
            $this->executeCallback($executionData, $model);
        };
    }

    public function getInput(): ?NodeInput
    {
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(
            <<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "root节点",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "user_prompt"
    ],
    "properties": {
        "model": {
            "type": "string",
            "key": "model",
            "title": "所use的文生图模型",
            "description": "可选：Volcengine,Midjourney,Flux1-Schnell,defaultVolcengine,TTAPI-GPT4o",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "radio": {
            "type": "string",
            "key": "radio",
            "title": "generateimage的比例",
            "description": "可选：\"1:1\",\"2:3\",\"4:3\",\"9:16\",\"16:9\",default\"1:1\"",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "user_prompt": {
            "type": "string",
            "key": "user_prompt",
            "title": "userprompt词",
            "description": "userprompt词",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "reference_image_ids": {
            "type": "array",
            "key": "reference_image_ids",
            "title": "quote的imageid列表",
            "description": "quote的imageid列表",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "string",
                "key": "reference_image_id",
                "sort": 0,
                "title": "reference_image_id",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "properties": null
        },
        "attachments": {
            "type": "array",
            "key": "attachments",
            "title": "attachmentarray",
            "description": "传入file列表array",
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
                        "title": "file名称",
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
                        "title": "file地址",
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
                        "title": "file后缀",
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
        }
    }
}
JSON,
            true
        )));
        return $input;
    }
}
