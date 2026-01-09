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
    // 最大重试次数
    protected const MAX_RETRIES = 20;

    // 重试间隔（秒）
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
     * generate图像并returnOpenAI格式响应 - Midjourney版本.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        // 1. 预先create响应object
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof MidjourneyModelRequest) {
            $this->logger->error('Midjourney OpenAI格式生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据响应
        }

        // 3. synchandle（Midjourney采用轮询机制）
        try {
            $result = $this->generateImageRawInternal($imageGenerateRequest);
            $this->validateMidjourneyResponse($result);

            // success：settingimage数据到响应object
            $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
        } catch (Exception $e) {
            // fail：settingerrorinfo到响应object
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('Midjourney OpenAI格式生图：请求fail', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        // 4. 记录最终结果
        $this->logger->info('Midjourney OpenAI格式生图：handlecomplete', [
            'successimage数' => count($response->getData()),
            '是否有error' => $response->hasError(),
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

        // 从原生结果中提取imageURL
        if (! empty($rawResult['data']['images']) && is_array($rawResult['data']['images'])) {
            return new ImageGenerateResponse(ImageGenerateType::URL, $rawResult['data']['images']);
        }

        // 如果没有 images array，尝试use cdnImage
        if (! empty($rawResult['data']['cdnImage'])) {
            return new ImageGenerateResponse(ImageGenerateType::URL, [$rawResult['data']['cdnImage']]);
        }

        $this->logger->error('MJ文生图：未获取到imageURL', [
            'rawResult' => $rawResult,
        ]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
    }

    /**
     * 轮询task结果并return原生数据.
     * @throws Exception
     */
    protected function pollTaskResultForRaw(string $jobId): array
    {
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $result = $this->api->getTaskResult($jobId);

                if (! isset($result['status'])) {
                    $this->logger->error('MJ文生图：轮询响应格式error', [
                        'jobId' => $jobId,
                        'response' => $result,
                    ]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $this->logger->info('MJ文生图：轮询status', [
                    'jobId' => $jobId,
                    'status' => $result['status'],
                    'retryCount' => $retryCount,
                ]);

                if ($result['status'] === 'SUCCESS') {
                    // 直接return完整的原生数据
                    return $result;
                }

                if ($result['status'] === 'FAILED') {
                    $this->logger->error('MJ文生图：taskexecutefail', [
                        'jobId' => $jobId,
                        'message' => $result['message'] ?? '未知error',
                    ]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
                }

                // 如果是其他status（如 PENDING_QUEUE 或 ON_QUEUE），continue等待
                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('MJ文生图：轮询task结果fail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                    'retryCount' => $retryCount,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::POLLING_FAILED);
            }
        }

        $this->logger->error('MJ文生图：taskexecute超时', [
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
                $this->logger->error('MJ文生图：响应格式error', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->error('MJ文生图：submitfail', [
                    'message' => $result['message'] ?? '未知error',
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
            }

            if (empty($result['data']['jobId'])) {
                $this->logger->error('MJ文生图：缺少taskID', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }

            $jobId = $result['data']['jobId'];
            $this->logger->info('MJ文生图：submittasksuccess', [
                'jobId' => $jobId,
            ]);
            return $jobId;
        } catch (Exception $e) {
            $this->logger->error('MJ文生图：submittaskexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    /**
     * 检查 Prompt 是否合法.
     * @throws Exception
     */
    protected function checkPrompt(string $prompt): void
    {
        try {
            $result = $this->api->checkPrompt($prompt);

            if (! isset($result['status'])) {
                $this->logger->error('MJ文生图：Prompt校验响应格式error', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
            }

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->warning('MJ文生图：Prompt校验fail', [
                    'message' => $result['message'] ?? '未知error',
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::INVALID_PROMPT);
            }

            $this->logger->info('MJ文生图：Prompt校验complete');
        } catch (Exception $e) {
            $this->logger->error('MJ文生图：Prompt校验请求fail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::PROMPT_CHECK_FAILED);
        }
    }

    /**
     * 检查account余额.
     * @return float 余额
     * @throws Exception
     */
    protected function checkBalance(): float
    {
        try {
            $result = $this->api->getAccountInfo();

            if ($result['status'] !== 'SUCCESS') {
                throw new Exception('检查余额fail: ' . ($result['message'] ?? '未知error'));
            }

            return (float) $result['data']['balance'];
        } catch (Exception $e) {
            throw new Exception('检查余额fail: ' . $e->getMessage());
        }
    }

    /**
     * 获取alertmessage前缀
     */
    protected function getAlertPrefix(): string
    {
        return 'TT API';
    }

    /**
     * generate图像的核心逻辑，return原生结果.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof MidjourneyModelRequest) {
            $this->logger->error('MJ文生图：无效的请求type', [
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

        // 记录请求start
        $this->logger->info('MJ文生图：start生图', [
            'prompt' => $prompt,
            'ratio' => $imageGenerateRequest->getRatio(),
            'negativePrompt' => $imageGenerateRequest->getNegativePrompt(),
            'mode' => $imageGenerateRequest->getModel(),
        ]);

        try {
            $this->checkPrompt($prompt);

            $jobId = $this->submitAsyncTask($prompt, $imageGenerateRequest->getModel());

            $rawResult = $this->pollTaskResultForRaw($jobId);

            $this->logger->info('MJ文生图：generateend', [
                'jobId' => $jobId,
            ]);

            return $rawResult;
        } catch (Exception $e) {
            $this->logger->error('MJ文生图：fail', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * 为Midjourney原始数据添加水印.
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

            // handle单个 cdnImage
            if (! empty($rawData['data']['cdnImage'])) {
                $rawData['data']['cdnImage'] = $this->watermarkProcessor->addWatermarkToUrl($rawData['data']['cdnImage'], $imageGenerateRequest);
            }
        } catch (Exception $e) {
            // 水印handlefail时，记录error但不影响imagereturn
            $this->logger->error('Midjourneyimage水印handlefail', [
                'error' => $e->getMessage(),
            ]);
            // return原始数据
        }

        return $rawData;
    }

    /**
     * validateMidjourney API响应数据格式（仅检查images字段）.
     */
    private function validateMidjourneyResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('Midjourney响应数据格式error：缺少data字段');
        }

        if (empty($result['data']['images']) || ! is_array($result['data']['images'])) {
            throw new Exception('Midjourney响应数据格式error：缺少images字段或images不是array');
        }

        if (count($result['data']['images']) === 0) {
            throw new Exception('Midjourney响应数据格式error：imagesarray为null');
        }
    }

    /**
     * 将Midjourneyimage数据添加到OpenAI响应object中（仅handleimages字段）.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $midjourneyResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // 从Midjourney响应中提取data.images字段
        if (empty($midjourneyResult['data']['images']) || ! is_array($midjourneyResult['data']['images'])) {
            return;
        }

        $currentData = $response->getData();
        $currentUsage = $response->getUsage() ?? new ImageUsage();

        // 仅handle images array中的URL
        foreach ($midjourneyResult['data']['images'] as $imageUrl) {
            if (! empty($imageUrl)) {
                // handle水印
                $processedUrl = $imageUrl;
                try {
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
                } catch (Exception $e) {
                    $this->logger->error('Midjourney添加image数据：水印handlefail', [
                        'error' => $e->getMessage(),
                        'url' => $imageUrl,
                    ]);
                    // 水印handlefail时use原始URL
                }

                $currentData[] = [
                    'url' => $processedUrl,
                ];
            }
        }

        // 累计usageinfo
        $imageCount = count($midjourneyResult['data']['images']);
        $currentUsage->addGeneratedImages($imageCount);

        // 更新响应object
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
