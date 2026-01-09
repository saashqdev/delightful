<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Midjourney;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\MidjourneyModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;

class MidjourneyModel extends AbstractImageGenerate
{
    // most大retrycount
    protected const MAX_RETRIES = 20;

    // retrybetween隔（second）
    protected const RETRY_INTERVAL = 10;

    protected MidjourneyAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $this->api = new MidjourneyAPI($serviceProviderConfig['api_key']);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        // TODO: Implement setAK() method.
    }

    public function setSK(string $sk)
    {
        // TODO: Implement setSK() method.
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processMidjourneyRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraph像并returnOpenAIformatresponse - Midjourneyversion.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        // 1. 预先createresponseobject
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof MidjourneyModelRequest) {
            $this->logger->error('Midjourney OpenAIformat生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. synchandle（Midjourney采useround询机制）
        try {
            $result = $this->generateImageRawInternal($imageGenerateRequest);
            $this->validateMidjourneyResponse($result);

            // success：settingimagedatatoresponseobject
            $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
        } catch (Exception $e) {
            // fail：settingerrorinfotoresponseobject
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('Midjourney OpenAIformat生graph：requestfail', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        // 4. recordfinalresult
        $this->logger->info('Midjourney OpenAIformat生graph：handlecomplete', [
            'successimage数' => count($response->getData()),
            'whetherhaveerror' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'midjourney';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResult = $this->generateImageRawInternal($imageGenerateRequest);

        // fromnativeresultmiddle提取imageURL
        if (! empty($rawResult['data']['images']) && is_array($rawResult['data']['images'])) {
            return new ImageGenerateResponse(ImageGenerateType::URL, $rawResult['data']['images']);
        }

        // ifnothave images array，尝试use cdnImage
        if (! empty($rawResult['data']['cdnImage'])) {
            return new ImageGenerateResponse(ImageGenerateType::URL, [$rawResult['data']['cdnImage']]);
        }

        $this->logger->error('MJ文生graph：未gettoimageURL', [
            'rawResult' => $rawResult,
        ]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
    }

    /**
     * round询taskresult并returnnativedata.
     * @throws Exception
     */
    protected function pollTaskResultForRaw(string $jobId): array
    {
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $result = $this->api->getTaskResult($jobId);

                if (! isset($result['status'])) {
                    $this->logger->error('MJ文生graph：round询responseformaterror', [
                        'jobId' => $jobId,
                        'response' => $result,
                    ]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $this->logger->info('MJ文生graph：round询status', [
                    'jobId' => $jobId,
                    'status' => $result['status'],
                    'retryCount' => $retryCount,
                ]);

                if ($result['status'] === 'SUCCESS') {
                    // 直接return完整的nativedata
                    return $result;
                }

                if ($result['status'] === 'FAILED') {
                    $this->logger->error('MJ文生graph：taskexecutefail', [
                        'jobId' => $jobId,
                        'message' => $result['message'] ?? '未知error',
                    ]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
                }

                // if是其他status（如 PENDING_QUEUE or ON_QUEUE），continueetc待
                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('MJ文生graph：round询taskresultfail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                    'retryCount' => $retryCount,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::POLLING_FAILED);
            }
        }

        $this->logger->error('MJ文生graph：taskexecutetimeout', [
            'jobId' => $jobId,
            'maxRetries' => self::MAX_RETRIES,
            'totalTime' => self::MAX_RETRIES * self::RETRY_INTERVAL,
        ]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    protected function submitAsyncTask(string $prompt, string $mode = 'fast'): string
    {
        try {
            $result = $this->api->submitTask($prompt, $mode);

            if (! isset($result['status'])) {
                $this->logger->error('MJ文生graph：responseformaterror', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->error('MJ文生graph：submitfail', [
                    'message' => $result['message'] ?? '未知error',
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
            }

            if (empty($result['data']['jobId'])) {
                $this->logger->error('MJ文生graph：缺少taskID', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }

            $jobId = $result['data']['jobId'];
            $this->logger->info('MJ文生graph：submittasksuccess', [
                'jobId' => $jobId,
            ]);
            return $jobId;
        } catch (Exception $e) {
            $this->logger->error('MJ文生graph：submittaskexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    /**
     * check Prompt whetherlegal.
     * @throws Exception
     */
    protected function checkPrompt(string $prompt): void
    {
        try {
            $result = $this->api->checkPrompt($prompt);

            if (! isset($result['status'])) {
                $this->logger->error('MJ文生graph：Prompt校验responseformaterror', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->warning('MJ文生graph：Prompt校验fail', [
                    'message' => $result['message'] ?? '未知error',
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::INVALID_PROMPT);
            }

            $this->logger->info('MJ文生graph：Prompt校验complete');
        } catch (Exception $e) {
            $this->logger->error('MJ文生graph：Prompt校验requestfail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::PROMPT_CHECK_FAILED);
        }
    }

    /**
     * checkaccountbalance.
     * @return float balance
     * @throws Exception
     */
    protected function checkBalance(): float
    {
        try {
            $result = $this->api->getAccountInfo();

            if ($result['status'] !== 'SUCCESS') {
                throw new Exception('checkbalancefail: ' . ($result['message'] ?? '未知error'));
            }

            return (float) $result['data']['balance'];
        } catch (Exception $e) {
            throw new Exception('checkbalancefail: ' . $e->getMessage());
        }
    }

    /**
     * getalertmessagefront缀
     */
    protected function getAlertPrefix(): string
    {
        return 'TT API';
    }

    /**
     * generategraph像的核core逻辑，returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof MidjourneyModelRequest) {
            $this->logger->error('MJ文生graph：invalid的requesttype', [
                'class' => get_class($imageGenerateRequest),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // build prompt
        $prompt = $imageGenerateRequest->getPrompt();
        if ($imageGenerateRequest->getRatio()) {
            $prompt .= ' --ar ' . $imageGenerateRequest->getRatio();
        }
        if ($imageGenerateRequest->getNegativePrompt()) {
            $prompt .= ' --no ' . $imageGenerateRequest->getNegativePrompt();
        }

        $prompt .= ' --v 7.0';

        // recordrequeststart
        $this->logger->info('MJ文生graph：start生graph', [
            'prompt' => $prompt,
            'ratio' => $imageGenerateRequest->getRatio(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'mode' => $imageGenerateRequest->getModel(),
        ]);

        try {
            $this->checkPrompt($prompt);

            $jobId = $this->submitAsyncTask($prompt, $imageGenerateRequest->getModel());

            $rawResult = $this->pollTaskResultForRaw($jobId);

            $this->logger->info('MJ文生graph：generateend', [
                'jobId' => $jobId,
            ]);

            return $rawResult;
        } catch (Exception $e) {
            $this->logger->error('MJ文生graph：fail', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * 为Midjourneyoriginaldata添加水印.
     */
    private function processMidjourneyRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! isset($rawData['data'])) {
            return $rawData;
        }

        try {
            // handle images array
            if (! empty($rawData['data']['images']) && is_array($rawData['data']['images'])) {
                foreach ($rawData['data']['images'] as $index => &$imageUrl) {
                    $imageUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                }
                unset($imageUrl);
            }

            // handle单 cdnImage
            if (! empty($rawData['data']['cdnImage'])) {
                $rawData['data']['cdnImage'] = $this->watermarkProcessor->addWatermarkToUrl($rawData['data']['cdnImage'], $imageGenerateRequest);
            }
        } catch (Exception $e) {
            // 水印handlefailo clock，recorderrorbutnot影响imagereturn
            $this->logger->error('Midjourneyimage水印handlefail', [
                'error' => $e->getMessage(),
            ]);
            // returnoriginaldata
        }

        return $rawData;
    }

    /**
     * validateMidjourney APIresponsedataformat（仅checkimagesfield）.
     */
    private function validateMidjourneyResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('Midjourneyresponsedataformaterror：缺少datafield');
        }

        if (empty($result['data']['images']) || ! is_array($result['data']['images'])) {
            throw new Exception('Midjourneyresponsedataformaterror：缺少imagesfieldorimagesnot是array');
        }

        if (count($result['data']['images']) === 0) {
            throw new Exception('Midjourneyresponsedataformaterror：imagesarray为null');
        }
    }

    /**
     * 将Midjourneyimagedata添加toOpenAIresponseobjectmiddle（仅handleimagesfield）.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $midjourneyResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // fromMidjourneyresponsemiddle提取data.imagesfield
        if (empty($midjourneyResult['data']['images']) || ! is_array($midjourneyResult['data']['images'])) {
            return;
        }

        $currentData = $response->getData();
        $currentUsage = $response->getUsage() ?? new ImageUsage();

        // 仅handle images arraymiddle的URL
        foreach ($midjourneyResult['data']['images'] as $imageUrl) {
            if (! empty($imageUrl)) {
                // handle水印
                $processedUrl = $imageUrl;
                try {
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                } catch (Exception $e) {
                    $this->logger->error('Midjourney添加imagedata：水印handlefail', [
                        'error' => $e->getMessage(),
                        'url' => $imageUrl,
                    ]);
                    // 水印handlefailo clockuseoriginalURL
                }

                $currentData[] = [
                    'url' => $processedUrl,
                ];
            }
        }

        // 累计usageinfo
        $imageCount = count($midjourneyResult['data']['images']);
        $currentUsage->addGeneratedImages($imageCount);

        // updateresponseobject
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
