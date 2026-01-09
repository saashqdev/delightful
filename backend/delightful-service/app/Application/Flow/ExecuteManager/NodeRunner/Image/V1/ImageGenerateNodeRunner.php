<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Image\V1;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Image\V1\ImageGenerateNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;

#[FlowNodeDefine(
    type: NodeType::ImageGenerate->value,
    code: NodeType::ImageGenerate->name,
    name: 'graph像generate',
    paramsConfig: ImageGenerateNodeParamsConfig::class,
    version: 'v1',
    singleDebug: false,
    needInput: false,
    needOutput: true,
)]
class ImageGenerateNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var ImageGenerateNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $userPrompt = $paramsConfig->getUserPrompt()->getValue()->getResult($executionData->getExpressionFieldData());
        $vertexResult->addDebugLog('user_prompt', $userPrompt);

        $wide = $paramsConfig->getWidth()?->getValue()?->getResult($executionData->getExpressionFieldData()) ?? '512';
        $vertexResult->addDebugLog('width', $wide);

        $height = $paramsConfig->getHeight()?->getValue()?->getResult($executionData->getExpressionFieldData()) ?? '512';
        $vertexResult->addDebugLog('height', $height);

        $negativePrompt = $paramsConfig->getNegativePrompt()?->getValue()?->getResult($executionData->getExpressionFieldData()) ?? '';
        $vertexResult->addDebugLog('negative_prompt', $negativePrompt);

        $referenceImages = $paramsConfig->getReferenceImages()?->getValue()?->getResult($executionData->getExpressionFieldData()) ?? [];
        $vertexResult->addDebugLog('reference_images', $referenceImages);

        $defaultRatio = '1:1';
        $ratio = $paramsConfig->getRatio()?->getValue()?->getResult($executionData->getExpressionFieldData())[0]['id'] ?? $defaultRatio;

        $vertexResult->addDebugLog('ratio', $ratio);

        $useSr = $paramsConfig->getUseSr();
        $vertexResult->addDebugLog('useSr', $negativePrompt);

        $modelId = $paramsConfig->getModelId();
        $vertexResult->addDebugLog('model_id', $modelId);

        $data = [
            'model_id' => $modelId,
            'height' => $height,
            'width' => $wide,
            'user_prompt' => $userPrompt,
            'negative_prompt' => $negativePrompt,
            'ratio' => $ratio,
            'use_sr' => $useSr,
            'reference_images' => $referenceImages,
            'generate_num' => 1,
        ];
        $flowDataIsolation = $executionData->getDataIsolation();
        $delightfulUserAuthorization = new DelightfulUserAuthorization();
        $delightfulUserAuthorization->setOrganizationCode($flowDataIsolation->getCurrentOrganizationCode());
        $delightfulUserAuthorization->setId($flowDataIsolation->getCurrentUserId());
        $images = $this->llmAppService->imageGenerate($delightfulUserAuthorization, '', $modelId, $data);

        // 这within可能是 url、base64，均recordtoprocessexecuteattachmentmiddle（此o clockwillconductuploadto云service端）。uploadfailed的filewill直接skip
        $attachments = $this->recordFlowExecutionAttachments($executionData, $images, true);
        $vertexResult->addDebugLog('attachments', array_map(fn (AbstractAttachment $attachment) => $attachment->toArray(), $attachments));
        $result = [
            'image_urls' => array_map(fn ($attachment) => $attachment->getUrl(), $attachments),
        ];
        $vertexResult->setResult($result);
        $executionData->saveNodeContext($this->node->getNodeId(), $result);
    }
}
