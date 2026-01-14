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
use BeDelightful\FlowExprEngine\ComponentFactory;
use BeDelightful\FlowExprEngine\Structure\Expression\Value;
use BeDelightful\FlowExprEngine\Structure\StructureType;

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
        return 'createonewithhavefingersetpersonmembergroup chatday';
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
    "title": "rootsectionpoint",
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
            "title": "groupname",
            "description": "groupname",
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
            "title": "group owner",
            "description": "group owner",
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
            "title": "groupmember",
            "description": "groupmember",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "object",
                "key": "group_member",
                "sort": 0,
                "title": "groupmember",
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
            "title": "grouptype",
            "description": "grouptype.1 insidedepartment group;2 training group;3 willdiscussion group;4 projectgroup;5 workersinglegroup;6 outsidedepartment group;",
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
            "title": "openfield",
            "description": "alreadycurrentassistantbodysharesendonetimegroup chatopenfield.defaultnotpassthevalue,unlessfingersetneedsendopenfield.",
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
