<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\FileBox\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionType;
use App\Application\Flow\ExecuteManager\Memory\MemoryQuery;
use App\Application\Flow\ExecuteManager\Memory\Persistence\ChatMemory;
use App\Domain\Chat\DTO\Message\ChatMessage\AbstractAttachmentMessage;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use Closure;
use DateTime;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class FileListBuiltInTool extends AbstractBuiltInTool
{
    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            if ($executionData->getExecutionType() !== ExecutionType::IMChat) {
                return ['files' => []];
            }
            $params = $executionData->getTriggerData()->getParams();
            $chatFiles = $this->getChatFilesByConversationIdAndTopicId(
                conversationId: $executionData->getOriginConversationId(),
                topicId: $executionData->getTopicIdString(),
                limit: (int) ($params['limit'] ?? 10),
                order: $params['order'] ?? 'desc',
                startTime: $params['start_time'] ?? '',
                endTime: $params['end_time'] ?? ''
            );
            $chatFilesMaps = [];
            $filePaths = [];
            foreach ($chatFiles as $chatFile) {
                $filePaths[] = $chatFile->getFileKey();
                $chatFilesMaps[$chatFile->getFileKey()] = $chatFile;
            }
            $fileDomainService = di(FileDomainService::class);
            $fileLinks = $fileDomainService->getLinks(
                $executionData->getDataIsolation()->getCurrentOrganizationCode(),
                $filePaths
            );
            $attachments = [];
            foreach ($fileLinks as $fileLink) {
                $chatFile = $chatFilesMaps[$fileLink->getPath()] ?? null;
                $attachments[] = [
                    'file_name' => $chatFile->getFileName(),
                    'file_url' => $fileLink->getUrl(),
                    'file_ext' => $chatFile->getFileExtension(),
                    'file_size' => $chatFile->getFileSize(),
                ];
            }
            return [
                'files' => $attachments,
            ];
        };
    }

    public function getToolSetCode(): string
    {
        return BuiltInToolSet::FileBox->getCode();
    }

    public function getName(): string
    {
        return 'file_list';
    }

    public function getDescription(): string
    {
        return '列出user当前session产生的file';
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
    ],
    "properties": {
        "limit": {
            "type": "number",
            "key": "limit",
            "sort": 0,
            "title": "query数量",
            "description": "query数量 默认 10",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "sort": {
            "type": "string",
            "key": "sort",
            "sort": 1,
            "title": "sort",
            "description": "sort规则。asc 升序;desc 降序。默认 desc",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "start_time": {
            "type": "string",
            "key": "start_time",
            "sort": 2,
            "title": "开始time",
            "description": "time范围search的开始time。格式示例：Y-m-d H:i:s",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "end_time": {
            "type": "string",
            "key": "end_time",
            "sort": 3,
            "title": "结束time",
            "description": "time范围search的结束time。格式示例：Y-m-d H:i:s",
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
    "title": "root节点",
    "description": "",
    "items": null,
    "value": null,
    "required": [
    ],
    "properties": {
         "files": {
            "type": "array",
            "key": "root",
            "sort": 5,
            "title": "filelist",
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
        return $output;
    }

    /**
     * @return array<DelightfulChatFileEntity>
     */
    private function getChatFilesByConversationIdAndTopicId(
        string $conversationId,
        string $topicId,
        int $limit = 10,
        string $order = 'desc',
        string $startTime = '',
        string $endTime = ''
    ): array {
        $memoryQuery = new MemoryQuery(ExecutionType::IMChat, $conversationId, $conversationId, $topicId, 1000);
        if (! empty($startTime) && strtotime($startTime) !== false) {
            $memoryQuery->setStartTime(new DateTime($startTime));
        }
        if (! empty($endTime) && strtotime($endTime) !== false) {
            $memoryQuery->setEndTime(new DateTime($endTime));
        }
        // 只getfile
        $memoryQuery->setRangMessageTypes([
            ChatMessageType::Text,
            ChatMessageType::RichText,
            ChatMessageType::Markdown,
            ChatMessageType::Files,
            ChatMessageType::Image,
            ChatMessageType::Voice,
            ChatMessageType::Video,
        ]);

        $attachmentIds = [];
        $messages = di(ChatMemory::class)->getImChatMessages($memoryQuery);
        foreach ($messages as $message) {
            $messageContent = $message->getContent();
            if ($messageContent instanceof AbstractAttachmentMessage) {
                $attachmentIds = array_merge($attachmentIds, $messageContent->getAttachmentIds());
            }
        }

        // sort+数量
        $delightfulChatFileDomainService = di(DelightfulChatFileDomainService::class);
        return $delightfulChatFileDomainService->getFileEntitiesByFileIds($attachmentIds, $order, $limit);
    }
}
