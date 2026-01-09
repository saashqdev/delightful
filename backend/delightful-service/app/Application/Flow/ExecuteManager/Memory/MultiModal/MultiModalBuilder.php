<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory\MultiModal;

use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AtomicNode\Tools\VisionTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Service\DelightfulFlowMultiModalLogDomainService;

class MultiModalBuilder
{
    public static function vision(ExecutionData $executionData, string $visionModel): ?DelightfulFlowMultiModalLogEntity
    {
        if (empty($executionData->getTriggerData()->getAttachments())) {
            return null;
        }
        if (empty($visionModel)) {
            return null;
        }

        $content = '';
        $messageContent = $executionData->getTriggerData()?->getMessageEntity()?->getContent();
        if ($messageContent instanceof TextContentInterface) {
            $content = $messageContent->getTextContent();
        }
        $content = trim($content);

        $attachments = $executionData->getTriggerData()->getAttachments();
        $imageUrls = [];
        foreach ($attachments as $attachment) {
            if ($attachment->isImage()) {
                $imageUrls[] = $attachment->getUrl();
            }
        }
        if (empty($imageUrls)) {
            return null;
        }

        // 调用tool提前识别
        $visionExecutionData = clone $executionData;
        $visionExecutionData->getTriggerData()->setParams([
            'model' => $visionModel,
            'intent' => $content,
            'image_urls' => $imageUrls,
        ]);

        $visionResult = VisionTool::execute($executionData);
        if (empty($visionResult['response'])) {
            return null;
        }

        $delightfulFlowMultiModalLogEntity = new DelightfulFlowMultiModalLogEntity();
        $delightfulFlowMultiModalLogEntity->setMessageId($executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $delightfulFlowMultiModalLogEntity->setAnalysisResult($visionResult['response']);
        $delightfulFlowMultiModalLogEntity->setType(1);
        $delightfulFlowMultiModalLogEntity->setModel($visionResult['model']);
        return di(DelightfulFlowMultiModalLogDomainService::class)->create($executionData->getDataIsolation(), $delightfulFlowMultiModalLogEntity);
    }
}
