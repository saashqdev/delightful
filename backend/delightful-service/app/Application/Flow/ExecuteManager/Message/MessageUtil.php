<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Message;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\Flow\ExecuteManager\Attachment\Event\ExternalAttachmentUploadEvent;
use App\Application\Flow\ExecuteManager\Attachment\ExternalAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessageV2;
use App\Domain\Chat\DTO\Message\ChatMessage\FilesMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\ChatAttachment;
use App\Domain\Chat\DTO\Message\ChatMessage\MarkdownMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\ValueObject\FileType;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessage;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessageType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\AsyncEvent\AsyncEventUtil;

class MessageUtil
{
    public static function getIMResponse(DelightfulFlowMessage $delightfulFlowMessage, ExecutionData $executionData, array $linkPaths = []): ?MessageInterface
    {
        switch ($delightfulFlowMessage->getType()) {
            case DelightfulFlowMessageType::Text:
            case DelightfulFlowMessageType::Markdown:
                $content = clone $delightfulFlowMessage->getContent()?->getValue();
                if (! $content) {
                    return null;
                }
                $content->getExpressionValue()?->setIsStringTemplate(true);
                $contentString = $content->getResult($executionData->getExpressionFieldData());
                if (is_numeric($contentString) || is_null($contentString) || is_bool($contentString)) {
                    $contentString = (string) $contentString;
                }
                if (! is_string($contentString)) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.message.content_error');
                }
                $contentString = trim($contentString);
                if ($delightfulFlowMessage->getType() === DelightfulFlowMessageType::Markdown) {
                    return new MarkdownMessage([
                        'content' => $contentString,
                    ]);
                }
                return new TextMessage([
                    'content' => $contentString,
                ]);
            case DelightfulFlowMessageType::Image:
                $chatAttachments = [];
                foreach ($linkPaths as $linkPath) {
                    if (! is_string($linkPath) || ! $attachment = $executionData->getAttachmentRecord($linkPath)) {
                        continue;
                    }
                    // todo batchquantityprocess
                    $chatFile = self::report2ChatFile($attachment, $executionData);
                    $chatAttachment = new ChatAttachment($chatFile->toArray());
                    $chatAttachment->setFileUrl($attachment->getUrl());
                    $chatAttachments[] = $chatAttachment;

                    if ($attachment instanceof ExternalAttachment) {
                        // asyncdownloadoutsidechainfileanduploadtothisservice oss
                        $imageUploadEvent = new ExternalAttachmentUploadEvent($attachment, $executionData->getDataIsolation()->getCurrentOrganizationCode());
                        AsyncEventUtil::dispatch($imageUploadEvent);
                    }
                }

                $message = new FilesMessage([]);
                $linkDesc = $delightfulFlowMessage->getLinkDesc()?->getValue()?->getResult($executionData->getExpressionFieldData());
                if (is_string($linkDesc) && $linkDesc !== '') {
                    // ifwithhavedescription,thatwhatshouldisrich textshapetype
                    $message = new TextMessage([]);
                    $message->setContent($linkDesc);
                }
                if (empty($chatAttachments)) {
                    return new TextMessage([
                        'content' => $linkDesc ?: 'sorry',
                    ]);
                }
                $message->setAttachments($chatAttachments);
                return $message;
            case DelightfulFlowMessageType::File:
                $chatAttachments = [];
                // thiswithindescriptionisusecomemarkfilename
                $linkDesc = $delightfulFlowMessage->getLinkDesc()?->getValue()?->getResult($executionData->getExpressionFieldData());
                foreach ($linkPaths as $linkPath) {
                    if (! is_string($linkPath) || ! $attachment = $executionData->getAttachmentRecord($linkPath)) {
                        continue;
                    }
                    // getfilename.if linkPaths only 1 ,andand linkDesc alsoisonlyone,thatwhatcandirectlyuse linkDesc asforfilename
                    if (count($linkPaths) === 1 && is_string($linkDesc) && $linkDesc !== '') {
                        $attachment->setName($linkDesc);
                    }
                    // downfind marker
                    if (is_array($linkDesc) && $fileName = $linkDesc[$attachment->getOriginAttachment()] ?? null) {
                        is_string($fileName) && $attachment->setName($fileName);
                    }

                    $chatFile = self::report2ChatFile($attachment, $executionData);
                    $chatAttachment = new ChatAttachment($chatFile->toArray());
                    $chatAttachment->setFileUrl($attachment->getUrl());
                    $chatAttachments[] = $chatAttachment;

                    if ($attachment instanceof ExternalAttachment) {
                        // asyncdownloadoutsidechainfileanduploadtothisservice oss
                        $imageUploadEvent = new ExternalAttachmentUploadEvent($attachment, $executionData->getDataIsolation()->getCurrentOrganizationCode());
                        AsyncEventUtil::dispatch($imageUploadEvent);
                    }
                }

                $message = new FilesMessage([]);
                $message->setAttachments($chatAttachments);
                return $message;
            case DelightfulFlowMessageType::AIMessage:
                $content = clone $delightfulFlowMessage->getContent()?->getForm();
                if (! $content) {
                    return null;
                }
                $contentString = $content->getKeyValue($executionData->getExpressionFieldData());
                // todo actualupnotimplement,bydownis伪code
                return new AggregateAISearchCardMessageV2([
                    'search' => $contentString['search'] ?? [],
                    'llm_response' => $contentString['llm_response'] ?? '',
                    'related_questions' => $contentString['related_questions'] ?? [],
                ]);
            default:
                return null;
        }
    }

    /**
     * up报file.
     */
    private static function report2ChatFile(AbstractAttachment $attachment, ExecutionData $executionData): DelightfulChatFileEntity
    {
        // thiswithinshouldisrelatedwhenat agent uploadfile
        $dataIsolation = ContactDataIsolation::create(
            $executionData->getDataIsolation()->getCurrentOrganizationCode(),
            $executionData->getAgentUserId() ?: $executionData->getDataIsolation()->getCurrentUserId()
        );

        $delightfulChatFileEntity = new DelightfulChatFileEntity();

        $delightfulChatFileEntity->setFileType(FileType::getTypeFromFileExtension($attachment->getExt()));
        $delightfulChatFileEntity->setFileSize($attachment->getSize());
        $delightfulChatFileEntity->setFileKey($attachment->getPath());
        $delightfulChatFileEntity->setFileName($attachment->getName());
        $delightfulChatFileEntity->setFileExtension($attachment->getExt());
        $delightfulChatFileEntity->setExternalUrl($attachment->getUrl());

        $chatFileDomainService = di(DelightfulChatFileDomainService::class);
        $chatFile = $chatFileDomainService->fileUpload([$delightfulChatFileEntity], $dataIsolation)[0] ?? null;
        if (! $chatFile) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.message.attachment_report_failed');
        }
        $attachment->setChatFileId($chatFile->getFileId());
        return $chatFile;
    }
}
