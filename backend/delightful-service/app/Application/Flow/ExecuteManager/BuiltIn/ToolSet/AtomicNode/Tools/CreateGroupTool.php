<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AtomicNode\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\Expression\Value;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class CreateGroupTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return 'create_group';
    }

    public function getDescription(): string
    {
        return 'create一个具有指定人员的group chat天';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();

            $node = Node::generateTemplate(NodeType::CreateGroup, [
                'group_name' => ComponentFactory::fastCreate([
                    'type' => StructureType::Value,
                    'structure' => Value::buildConst($params['group_name'] ?? ''),
                ]),
                'group_owner' => ComponentFactory::fastCreate([
                    'type' => StructureType::Value,
                    'structure' => Value::buildConst($params['group_owner'] ?? ''),
                ]),
                'group_members' => ComponentFactory::fastCreate([
                    'type' => StructureType::Value,
                    'structure' => Value::buildConst($params['group_members'] ?? []),
                ]),
                'group_type' => $params['group_type'] ?? 0,
                'include_current_user' => true,
                'include_current_assistant' => true,
                'assistant_opening_speech' => ComponentFactory::fastCreate([
                    'type' => StructureType::Value,
                    'structure' => Value::buildConst($params['opening_speech'] ?? ''),
                ]),
            ], 'latest');

            $runner = NodeRunnerFactory::make($node);
            $vertexResult = new VertexResult();
            $runner->execute($vertexResult, clone $executionData);
            $result = $vertexResult->getResult();
            return ['success' => true, 'result' => $result];
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
    "required": [
        "group_name",
        "group_owner",
        "group_members",
        "group_type"
    ],
    "value": null,
    "encryption": false,
    "encryption_value": null,
    "items": null,
    "properties": {
        "group_name": {
            "type": "string",
            "key": "group_name",
            "title": "群name",
            "description": "群name",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "group_owner": {
            "type": "object",
            "key": "group_owner",
            "title": "群主",
            "description": "群主",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": {
                "id": {
                    "type": "string",
                    "key": "id",
                    "title": "user ID",
                    "description": "user ID",
                    "required": null,
                    "value": null,
                    "encryption": false,
                    "encryption_value": null,
                    "items": null,
                    "properties": null
                },
                "name": {
                    "type": "string",
                    "key": "name",
                    "title": "username",
                    "description": "username",
                    "required": null,
                    "value": null,
                    "encryption": false,
                    "encryption_value": null,
                    "items": null,
                    "properties": null
                }
            }
        },
        "group_members": {
            "type": "array",
            "key": "group_members",
            "title": "群成员",
            "description": "群成员",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "object",
                "key": "group_member",
                "sort": 0,
                "title": "群成员",
                "description": "",
                "required": null,
                "value": null,
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": {
                    "id": {
                        "type": "string",
                        "key": "id",
                        "title": "user ID",
                        "description": "user ID",
                        "required": null,
                        "value": null,
                        "encryption": false,
                        "encryption_value": null,
                        "items": null,
                        "properties": null
                    },
                    "name": {
                        "type": "string",
                        "key": "name",
                        "title": "username",
                        "description": "username",
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
        "group_type": {
            "type": "number",
            "key": "group_type",
            "title": "群type",
            "description": "群type。1 内部群；2 培训群；3 会议群；4 项目群；5 工单群；6 外部群；",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "opening_speech": {
            "type": "string",
            "key": "opening_speech",
            "title": "开场白",
            "description": "已当前助理的身份发送一次group chat的开场白。默认不传该value，除非指定需要发送开场白。",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
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
