<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loader;

use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\NodeParamsConfig;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;
use Hyperf\Codec\Json;

class ExcelLoaderNodeParamsConfig extends NodeParamsConfig
{
    private Component $files;

    public function getFiles(): Component
    {
        return $this->files;
    }

    public function validate(): array
    {
        $params = $this->node->getParams();

        $files = ComponentFactory::fastCreate($params['files'] ?? [], lazy: true);
        if (! $files?->isForm()) {
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'flow.component.format_error', ['label' => 'files']);
        }
        $this->files = $files;

        return [
            'files' => $this->files->toArray(),
        ];
    }

    public function generateTemplate(): void
    {
        $this->node->setParams([
            'files' => ComponentFactory::generateTemplate(StructureType::Form, json_decode(
                <<<'JSON'
{
    "type": "array",
    "key": "root",
    "sort": 0,
    "title": "filecolumn表",
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
    "properties": null
}
JSON,
                true
            ))->toArray(),
        ]);

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
                            "title": "work表",
                            "description": "",
                            "required": null,
                            "value": null,
                            "items": {
                                "type": "object",
                                "key": "",
                                "sort": 0,
                                "title": "work表",
                                "description": "",
                                "required": null,
                                "value": null,
                                "items": null,
                                "properties": {
                                    "sheet_name": {
                                        "type": "string",
                                        "key": "sheet_name",
                                        "sort": 0,
                                        "title": "work表name",
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
                                                    "title": "line索引",
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
                                                                "title": "column索引",
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
        $this->node->setOutput($output);
    }
}
