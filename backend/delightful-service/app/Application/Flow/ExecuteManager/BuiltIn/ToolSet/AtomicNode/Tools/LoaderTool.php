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
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class LoaderTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return 'document_parse';
    }

    public function getDescription(): string
    {
        return 'documentparse.useatparseremotedocumentmiddlecontent,extractdocumentmiddlecontentoutput.';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();

            $node = Node::generateTemplate(NodeType::Loader, [], 'latest');
            /** @var null|Component $fileComponent */
            $fileComponent = ComponentFactory::fastCreate($node->getParams()['files'] ?? []);
            if (! $fileComponent?->isForm()) {
                return null;
            }
            $fileComponent->getForm()->appendConstValue($params['files'] ?? []);
            $node->setParams([
                'files' => $fileComponent,
            ]);

            $runner = NodeRunnerFactory::make($node);
            $vertexResult = new VertexResult();
            $runner->execute($vertexResult, clone $executionData);
            return $vertexResult->getResult();
        };
    }

    public function getInput(): ?NodeInput
    {
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(<<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "rootsectionpoint",
    "description": "",
    "required": [
        "files"
    ],
    "value": null,
    "encryption": false,
    "encryption_value": null,
    "items": null,
    "properties": {
        "files": {
            "type": "array",
            "key": "files",
            "title": "file",
            "description": "",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": {
                "type": "object",
                "key": "file",
                "sort": 0,
                "title": "file",
                "description": "",
                "required": [
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
                        "title": "filename",
                        "description": "filename.nothaveo clockcanuse url middlefile名",
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
                        "title": "fileground址.remotefileground址",
                        "description": "fileground址.remotefileground址",
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
JSON, true)));
        return $input;
    }
}
