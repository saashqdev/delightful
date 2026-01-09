<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AIImage\Tools;

use App\Application\Chat\Service\DelightfulChatImageConvertHighAppService;
use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Chat\DTO\ImageConvertHigh\Request\DelightfulChatImageConvertHighReqDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\ImageGenerate\ValueObject\ImageGenerateSourceEnum;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\Util\Context\RequestContext;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

use function di;

#[BuiltInToolDefine]
class ImageConvertHighBuiltInTool extends AbstractAIImageBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AIImage->getCode();
    }

    public function getName(): string
    {
        return 'image_convert_high';
    }

    public function getDescription(): string
    {
        return 'image转高清tool';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            if ($executionData->getExecutionType()->isDebug()) {
                // debug mode
                return ['image_convert_high: current not support debug model'];
            }
            $args = $executionData->getTriggerData()?->getParams();
            $searchKeyword = $args['user_prompt'] ?? '';
            $agentConversationId = $executionData->getOriginConversationId();
            $assistantAuthorization = $this->getAssistantAuthorization($executionData->getAgentUserId());

            $requestContext = new RequestContext();
            $requestContext->setUserAuthorization($assistantAuthorization);
            $requestContext->setOrganizationCode($assistantAuthorization->getOrganizationCode());

            $textMessage = new TextMessage([]);
            $textMessage->setContent($searchKeyword);
            $reqDto = (new DelightfulChatImageConvertHighReqDTO())
                ->setTopicId($executionData->getTopicId() ?? '')
                ->setConversationId($agentConversationId)
                ->setUserMessage($textMessage)
                ->setOriginImageUrl($executionData->getTriggerData()?->getAttachments()[0]->getUrl())
                ->setOriginImageId($executionData->getTriggerData()?->getAttachments()[0]->getChatFileId())
                ->setReferMessageId($executionData->getTriggerData()?->getSeqEntity()?->getSeqId())
                ->setSourceId($this->getCode())
                ->setSourceType(ImageGenerateSourceEnum::TOOL);
            $this->getDelightfulChatImageConvertHighAppService()->handleUserMessage($requestContext, $reqDto);
            return [];
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
        "user_prompt",
        "attachments"
    ],
    "properties": {
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
                        "title": "file大小",
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

    protected function getDelightfulChatImageConvertHighAppService(): DelightfulChatImageConvertHighAppService
    {
        return di(DelightfulChatImageConvertHighAppService::class);
    }
}
