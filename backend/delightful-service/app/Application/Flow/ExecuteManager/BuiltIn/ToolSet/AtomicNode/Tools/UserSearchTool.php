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
class UserSearchTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return 'user_search';
    }

    public function getDescription(): string
    {
        return 'usersearch.notallowsearchalldepartmentpersonmember,onesetiswithhavefingersetfiltervalue';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();

            $filters = [];
            foreach ($params['filters'] ?? [] as $filter) {
                if (empty($filter['left']) || empty($filter['operator']) || empty($filter['right'])) {
                    continue;
                }
                $filters[] = [
                    'left' => $filter['left'],
                    'operator' => $filter['operator'],
                    'right' => ComponentFactory::fastCreate([
                        'type' => StructureType::Value,
                        'structure' => Value::buildConst($filter['right']),
                    ]),
                ];
            }

            $node = Node::generateTemplate(NodeType::UserSearch, [
                'filter_type' => $params['filter_type'] ?? 'all',
                'filters' => $filters,
            ], 'latest');

            $runner = NodeRunnerFactory::make($node);
            $vertexResult = new VertexResult();
            $runner->execute($vertexResult, clone $executionData);
            return $vertexResult->getResult();
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
    "title": "root",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "filter_type",
        "filters"
    ],
    "properties": {
        "filter_type": {
            "type": "string",
            "key": "filter_type",
            "title": "filtertype",
            "description": "filtertype.supportfiltertypehave:all,any.minutealternativetable  haveitemitem,anyitemitem.defaultis all",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "filters": {
            "type": "array",
            "key": "filters",
            "title": "filteritemitem",
            "description": "filteritemitem",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "object",
                "key": "filters",
                "title": "filters",
                "description": "",
                "required": [
                    "left",
                    "operator",
                    "right"
                ],
                "value": null,
                "properties": {
                    "left": {
                        "type": "string",
                        "key": "field",
                        "title": "filterfield",
                        "description": "filterfield.optionalenumhave:username,work_number,position,position,department_name,group_name.minutealternativetable  username,userworkernumber,userpost,userhandmachinenumber,departmentname,group chatname",
                        "required": null,
                        "value": null,
                        "encryption": false,
                        "encryption_value": null,
                        "items": null,
                        "properties": null
                    },
                    "operator": {
                        "type": "string",
                        "key": "operator",
                        "title": "filter符",
                        "description": "filter符.optionalenumhave:equals,no_equals,contains,no_contains.minutealternativetable equal,notequal,contain,notcontain",
                        "required": null,
                        "value": null,
                        "encryption": false,
                        "encryption_value": null,
                        "items": null,
                        "properties": null
                    },
                    "right": {
                        "type": "string",
                        "key": "value",
                        "title": "filtervalue",
                        "description": "filtervalue",
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
