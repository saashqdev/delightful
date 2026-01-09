<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\Loader;

use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Connector\Component\ComponentFactory;
use Connector\Component\Structure\StructureType;
use Hyperf\Codec\Json;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class ExcelLoaderNodeRunnerTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $node = $this->createNode();
        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'file_name' => 'yougood.xlsx',
            'file_url' => 'https://example.tos-cn-beijing.volces.com/DELIGHTFUL/test/yougood.xlsx',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    private function createNode(): Node
    {
        $node = Node::generateTemplate(NodeType::ExcelLoader, json_decode(
            <<<'JSON'
{
    "files": {
        "id": "component-6698c07a6d49b",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "array",
            "key": "root",
            "sort": 0,
            "title": "filecolumntable",
            "description": "",
            "required": null,
            "value": null,
            "items": {
                "type": "object",
                "key": "file",
                "sort": 0,
                "title": "file",
                "description": "",
                "required": [
                    "file_name",
                    "file_url"
                ],
                "value": null,
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
                        "items": null,
                        "properties": null
                    },
                    "file_url": {
                        "type": "string",
                        "key": "content",
                        "sort": 1,
                        "title": "fileground址",
                        "description": "",
                        "required": null,
                        "value": null,
                        "items": null,
                        "properties": null
                    }
                }
            },
            "properties": {
                "0": {
                    "type": "object",
                    "key": "file",
                    "sort": 0,
                    "title": "file",
                    "description": "",
                    "required": [
                        "file_name",
                        "file_url"
                    ],
                    "value": null,
                    "items": null,
                    "properties": {
                        "file_name": {
                            "type": "string",
                            "key": "file_name",
                            "sort": 0,
                            "title": "filename",
                            "description": "",
                            "required": null,
                            "value": {
                                "type": "const",
                                "const_value": [
                                    {
                                        "type": "fields",
                                        "value": "9527.file_name",
                                        "name": "name",
                                        "args": null
                                    }
                                ],
                                "expression_value": null
                            },
                            "items": null,
                            "properties": null
                        },
                        "file_url": {
                            "type": "string",
                            "key": "content",
                            "sort": 1,
                            "title": "fileground址",
                            "description": "",
                            "required": null,
                            "value": {
                                "type": "const",
                                "const_value": [
                                    {
                                        "type": "fields",
                                        "value": "9527.file_url",
                                        "name": "name",
                                        "args": null
                                    }
                                ],
                                "expression_value": null
                            },
                            "items": null,
                            "properties": null
                        }
                    }
                }
            }
        }
    }
}
JSON,
            true
        ));
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form, Json::decode(
            <<<'JSON'
    {
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": "rootsectionpoint",
        "description": "",
        "items": null,
        "value": null,
        "required": [
            "files_spreadsheet"
        ],
        "properties": {
            "files_spreadsheet": {
                "type": "array",
                "key": "files_spreadsheet",
                "sort": 1,
                "title": "tablefile",
                "description": "",
                "items": {
                    "type": "object",
                    "key": "files_spreadsheet",
                    "sort": 0,
                    "title": "file",
                    "description": "",
                    "required": [
                        "file_name",
                        "file_url",
                        "file_extension",
                        "sheet"
                    ],
                    "value": null,
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
                            "items": null,
                            "properties": null
                        },
                        "file_url": {
                            "type": "string",
                            "key": "content",
                            "sort": 1,
                            "title": "fileground址",
                            "description": "",
                            "required": null,
                            "value": null,
                            "items": null,
                            "properties": null
                        },
                        "file_extension": {
                            "type": "string",
                            "key": "file_extension",
                            "sort": 2,
                            "title": "fileextension名",
                            "description": "",
                            "required": null,
                            "value": null,
                            "items": null,
                            "properties": null
                        },
                        "sheets": {
                            "type": "array",
                            "key": "sheets",
                            "sort": 3,
                            "title": "worktable",
                            "description": "",
                            "required": null,
                            "value": null,
                            "items": {
                                "type": "object",
                                "key": "",
                                "sort": 0,
                                "title": "worktable",
                                "description": "",
                                "required": null,
                                "value": null,
                                "items": null,
                                "properties": {
                                    "sheet_name": {
                                        "type": "string",
                                        "key": "sheet_name",
                                        "sort": 0,
                                        "title": "worktablename",
                                        "description": "",
                                        "required": null,
                                        "value": null,
                                        "items": null,
                                        "properties": null
                                    },
                                    "rows": {
                                        "type": "array",
                                        "key": "rows",
                                        "sort": 1,
                                        "title": "line",
                                        "description": "",
                                        "required": null,
                                        "value": null,
                                        "items": {
                                            "type": "object",
                                            "key": "",
                                            "sort": 0,
                                            "title": "line",
                                            "description": "",
                                            "required": null,
                                            "value": null,
                                            "items": null,
                                            "properties": {
                                                "row_index": {
                                                    "type": "string",
                                                    "key": "row_index",
                                                    "sort": 0,
                                                    "title": "lineindex",
                                                    "description": "",
                                                    "required": null,
                                                    "value": null,
                                                    "items": null,
                                                    "properties": null
                                                },
                                                "cells": {
                                                    "type": "array",
                                                    "key": "cells",
                                                    "sort": 0,
                                                    "title": "singleyuan格",
                                                    "description": "",
                                                    "required": null,
                                                    "value": null,
                                                    "items": {
                                                        "type": "object",
                                                        "key": "",
                                                        "sort": 0,
                                                        "title": "column",
                                                        "description": "",
                                                        "required": null,
                                                        "value": null,
                                                        "items": null,
                                                        "properties": {
                                                            "value": {
                                                                "type": "string",
                                                                "key": "value",
                                                                "sort": 0,
                                                                "title": "value",
                                                                "description": "",
                                                                "required": null,
                                                                "value": null,
                                                                "items": null,
                                                                "properties": null
                                                            },
                                                            "column_index": {
                                                                "type": "string",
                                                                "key": "column_index",
                                                                "sort": 1,
                                                                "title": "columnindex",
                                                                "description": "",
                                                                "required": null,
                                                                "value": null,
                                                                "items": null,
                                                                "properties": null
                                                            }
                                                        }
                                                    },
                                                    "properties": null
                                                }
                                            }
                                        },
                                        "properties": null
                                    }
                                }
                            },
                            "properties": null
                        }
                    }
                },
                "properties": null,
                "required": null,
                "value": null
            }
        }
    }
JSON
        )));
        $node->setOutput($output);
        return $node;
    }
}
