<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\VolcengineArk;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\Context\CoContext;
use Exception;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\Retry\Annotation\Retry;

class VolcengineArkModel extends AbstractImageGenerate
{
    protected VolcengineArkAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $apiUrl = $serviceProviderConfig['url'];
        $apiKey = $serviceProviderConfig['api_key'];

        if (empty($apiKey)) {
            throw new Exception('VolcengineArk API Key configuration缺失');
        }

        // 如果没有configurationURL，use默认端点
        if (empty($apiUrl)) {
            $this->api = new VolcengineArkAPI($apiKey);
        } else {
            $this->api = new VolcengineArkAPI($apiKey, $apiUrl);
        }
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        // VolcengineArk 不useAK/SK，这里为nullimplement
    }

    public function setSK(string $sk)
    {
        // VolcengineArk 不useAK/SK，这里为nullimplement
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processVolcengineArkRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAI格式响应 - V2一体化版本.
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
        if (! $imageGenerateRequest instanceof VolcengineArkRequest) {
            $this->logger->error('VolcengineArk OpenAI格式生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据响应
        }

        // 3. 并发handle - 直接操作响应object
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGenerationV2($imageGenerateRequest);
                    $this->validateVolcengineArkResponse($result);

                    // success：settingimage数据到响应object
                    $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfo到响应object（只setting第一个error）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('VolcengineArk OpenAI格式生图：单个请求fail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. 记录最终结果
        $this->logger->info('VolcengineArk OpenAI格式生图：并发handlecomplete', [
            '总请求数' => $count,
            'successimage数' => count($response->getData()),
            '是否有error' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'volcengine_ark';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // 从原生结果中提取imageURL
        $imageData = [];
        foreach ($rawResults as $index => $result) {
            // 检查嵌套的数据结构：result['data']['data'][0]['url']
            if (! empty($result['data']['data']) && ! empty($result['data']['data'][0]['url'])) {
                $imageData[$index] = $result['data']['data'][0]['url'];
            }
        }

        if (empty($imageData)) {
            $this->logger->error('VolcengineArk文生图：所有imagegenerate均fail', ['rawResults' => $rawResults]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE);
        }

        ksort($imageData);
        $imageData = array_values($imageData);

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageData);
    }

    protected function getAlertPrefix(): string
    {
        return 'VolcengineArk API';
    }

    protected function checkBalance(): float
    {
        // VolcengineArk API 目前没有余额query接口，return默认value
        return 999.0;
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function requestImageGeneration(VolcengineArkRequest $imageGenerateRequest): array
    {
        $prompt = $imageGenerateRequest->getPrompt();
        $referImages = $imageGenerateRequest->getReferImages();

        // buildAPI payload
        $payload = [
            'model' => $imageGenerateRequest->getModel(),
            'prompt' => $prompt,
            'size' => $imageGenerateRequest->getSize(),
            'response_format' => $imageGenerateRequest->getResponseFormat(),
            'watermark' => $imageGenerateRequest->getWatermark(),
            'sequential_image_generation' => $imageGenerateRequest->getSequentialImageGeneration(),
            'stream' => $imageGenerateRequest->getStream(),
        ];

        // 如果setting了组图功能选项，则添加 sequential_image_generation_options
        $sequentialOptions = $imageGenerateRequest->getSequentialImageGenerationOptions();
        if (! empty($sequentialOptions)) {
            $payload['sequential_image_generation_options'] = $sequentialOptions;
        }

        // 如果有参考图像，则添加image字段（支持多张image）
        if (! empty($referImages)) {
            if (count($referImages) === 1) {
                $payload['image'] = $referImages[0];
            } else {
                $payload['image'] = $referImages;
            }
        }
        try {
            return $this->api->generateImage($payload);
        } catch (Exception $e) {
            $this->logger->warning('VolcengineArkimagegenerate：callimagegenerate接口fail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * V2版本：纯粹的APIcall，不handleexception.
     */
    protected function requestImageGenerationV2(VolcengineArkRequest $imageGenerateRequest): array
    {
        $prompt = $imageGenerateRequest->getPrompt();
        $referImages = $imageGenerateRequest->getReferImages();

        // buildAPI payload
        $payload = [
            'model' => $imageGenerateRequest->getModel(),
            'prompt' => $prompt,
            'size' => $imageGenerateRequest->getSize(),
            'response_format' => $imageGenerateRequest->getResponseFormat(),
            'watermark' => $imageGenerateRequest->getWatermark(),
            'sequential_image_generation' => $imageGenerateRequest->getSequentialImageGeneration(),
            'stream' => $imageGenerateRequest->getStream(),
        ];

        // 如果setting了组图功能选项，则添加 sequential_image_generation_options
        $sequentialOptions = $imageGenerateRequest->getSequentialImageGenerationOptions();
        if (! empty($sequentialOptions)) {
            $payload['sequential_image_generation_options'] = $sequentialOptions;
        }

        // 如果有参考图像，则添加image字段（支持多张image）
        if (! empty($referImages)) {
            if (count($referImages) === 1) {
                $payload['image'] = $referImages[0];
            } else {
                $payload['image'] = $referImages;
            }
        }

        // 直接callAPI，exception自然向上抛
        return $this->api->generateImage($payload);
    }

    /**
     * validate火山方舟API响应数据格式.
     */
    private function validateVolcengineArkResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data']) || empty($result['data'][0]['url'])) {
            throw new Exception('火山方舟响应数据格式error');
        }
    }

    /**
     * 将火山方舟image数据添加到OpenAI响应object中.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $volcengineResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedis锁确保并发安全
        $lockOwner = $this->lockResponse($response);
        try {
            // 从火山方舟响应中提取数据
            if (empty($volcengineResult['data']) || ! is_array($volcengineResult['data'])) {
                return;
            }

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            foreach ($volcengineResult['data'] as $item) {
                if (! empty($item['url'])) {
                    // handle水印
                    $processedUrl = $item['url'];
                    try {
                        $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($item['url'], $imageGenerateRequest);
                    } catch (Exception $e) {
                        $this->logger->error('VolcengineArk添加image数据：水印handlefail', [
                            'error' => $e->getMessage(),
                            'url' => $item['url'],
                        ]);
                        // 水印handlefail时use原始URL
                    }

                    $currentData[] = [
                        'url' => $processedUrl,
                        'size' => $item['size'] ?? null,
                    ];
                }
            }

            // 累计usageinfo
            if (! empty($volcengineResult['usage']) && is_array($volcengineResult['usage'])) {
                $currentUsage->addGeneratedImages($volcengineResult['usage']['generated_images'] ?? 0);
                $currentUsage->completionTokens += $volcengineResult['usage']['output_tokens'] ?? 0;
                $currentUsage->totalTokens += $volcengineResult['usage']['total_tokens'] ?? 0;
            }

            // 更新响应object
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // 确保锁一定会被释放
            $this->unlockResponse($response, $lockOwner);
        }
    }

    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof VolcengineArkRequest) {
            $this->logger->error('VolcengineArk文生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // VolcengineArk API每次只能generate一张图，通过并发callimplement多图generate
        $count = $imageGenerateRequest->getGenerateNum();
        $rawResults = [];
        $errors = [];

        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGeneration($imageGenerateRequest);

                    return [
                        'success' => true,
                        'data' => $result,
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('VolcengineArk文生图：imagegeneratefail', [
                        'error' => $e->getMessage(),
                        'index' => $i,
                    ]);
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'index' => $i,
                    ];
                }
            });
        }

        $results = $parallel->wait();

        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result;
            } else {
                $errors[] = $result['error'] ?? '未知error';
            }
        }

        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('VolcengineArk文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        ksort($rawResults);
        return array_values($rawResults);
    }

    /**
     * 为火山引擎Ark原始数据添加水印.
     */
    private function processVolcengineArkRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['data']['data']) || empty($result['data']['data'])) {
                continue;
            }

            try {
                // VolcengineArk return的是 URL 格式，useURL水印handle
                foreach ($result['data']['data'] as $i => &$item) {
                    if (isset($item['url'])) {
                        $item['url'] = $this->watermarkProcessor->addWatermarkToUrl($item['url'], $imageGenerateRequest);
                    }
                }
                unset($item);
            } catch (Exception $e) {
                $this->logger->error('VolcengineArkimage水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rawData;
    }
}
