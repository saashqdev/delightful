<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\FileBox\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class FileGetPreSignedUrlBuiltInTool extends AbstractBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::FileBox->getCode();
    }

    public function getName(): string
    {
        return 'get_pre_signed_url';
    }

    public function getDescription(): string
    {
        return 'according tofile名getfileupload的预signatureURL。仅能操作本process产生的file';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();

            $name = $params['name'];
            if (empty($name)) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'common.empty', ['label' => 'name']);
            }
            $organizationCode = $executionData->getDataIsolation()->getCurrentOrganizationCode();

            // permissionissue，目front仅allow操作本process产生的file。因为currenttoolalso是一 flow，所byneedget父process的 code
            $name = $executionData->getParentFlowCode() . '/' . ltrim($name, '/');

            $fileDomain = di(FileDomainService::class);
            $preSignedUrl = $fileDomain->getPreSignedUrls($organizationCode, [$name])[$name] ?? null;
            if (! $preSignedUrl) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'file.not_found', ['name' => $name]);
            }
            return [
                'url' => $preSignedUrl->getUrl(),
                'headers' => $preSignedUrl->getHeaders(),
                'expires' => $preSignedUrl->getExpires(),
                'key' => $preSignedUrl->getPath(),
                'name' => $name,
            ];
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
    "items": null,
    "value": null,
    "required": [
        "name"
    ],
    "properties": {
        "name": {
            "type": "string",
            "key": "name",
            "title": "file名",
            "description": "filename",
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

    public function getOutPut(): ?NodeOutput
    {
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(
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
        "url",
        "headers",
        "expires",
        "key",
        "name"
    ],
    "properties": {
        "url": {
            "type": "string",
            "key": "url",
            "title": "URL",
            "description": "fileupload的预signatureURL",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "headers": {
            "type": "object",
            "key": "headers",
            "title": "Headers",
            "description": "fileupload的预signatureURL的Headers",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "expires": {
            "type": "number",
            "key": "expires",
            "title": "expiretime",
            "description": "fileupload的预signatureURL的expiretime",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "key": {
            "type": "string",
            "key": "key",
            "title": "key",
            "description": "file的完整Key",
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
            "title": "file名",
            "description": "file名",
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
        return $output;
    }
}
