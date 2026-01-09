<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageEditRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class QwenImageEditModel extends AbstractImageGenerate
{
    private QwenImageAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $apiKey = $serviceProviderConfig['api_key'];
        if (empty($apiKey)) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.api_call_failed');
        }

        $this->api = new QwenImageAPI($apiKey);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        // 通义千问不useAK/SKauthentication，此method为nullimplement
    }

    public function setSK(string $sk)
    {
        // 通义千问不useAK/SKauthentication，此method为nullimplement
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processQwenEditRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAI格式响应 - QwenEdit版本.
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
        if (! $imageGenerateRequest instanceof QwenImageEditRequest) {
            $this->logger->error('QwenEdit OpenAI格式生图：invalid的请求type', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据响应
        }

        // 3. synchandle图像edit（单图）
        try {
            $result = $this->callSyncEditAPI($imageGenerateRequest);
            $this->validateQwenEditResponse($result);

            // success：settingimage数据到响应object
            $this->addImageDataToResponseQwenEdit($response, $result, $imageGenerateRequest);
        } catch (Exception $e) {
            // fail：settingerrorinfo到响应object
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('QwenEdit OpenAI格式生图：请求fail', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        // 4. 记录final结果
        $this->logger->info('QwenEdit OpenAI格式生图：handlecomplete', [
            'successimage数' => count($response->getData()),
            '是否有error' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'qwen';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // 从原生结果中提取imageURL - 适配new响应格式 output.choices
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            $output = $result['output'];
            if (! empty($output['choices'])) {
                foreach ($output['choices'] as $choice) {
                    if (! empty($choice['message']['content'])) {
                        foreach ($choice['message']['content'] as $content) {
                            if (isset($content['image']) && ! empty($content['image'])) {
                                $imageUrls[$index] = $content['image'];
                                break 2; // 只取firstimageURL
                            }
                        }
                    }
                }
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generate图像的核心逻辑，return原生结果 - synccall.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof QwenImageEditRequest) {
            $this->logger->error('通义千问图像edit：invalid的请求type', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 校验必要parameter
        $this->validateEditRequest($imageGenerateRequest);

        $this->logger->info('通义千问图像edit：startedit', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'image_count' => count($imageGenerateRequest->getImageUrls()),
        ]);

        // 直接handle单个请求，图像edit只handle一张image
        try {
            $result = $this->callSyncEditAPI($imageGenerateRequest);
            $rawResults = [
                [
                    'success' => true,
                    'output' => $result['output'],
                ],
            ];
        } catch (Exception $e) {
            $this->logger->error('通义千问图像edit：fail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::from($e->getCode()) ?? ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }

        return $rawResults;
    }

    /**
     * 校验图像edit请求parameter.
     */
    private function validateEditRequest(QwenImageEditRequest $request): void
    {
        // check是否有输入图像
        if (empty($request->getImageUrls())) {
            $this->logger->error('通义千问图像edit：缺少输入图像');
            ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.reference_images_required');
        }
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    #[RateLimit(create: 4, consume: 1, capacity: 0, key: ImageGenerate::IMAGE_GENERATE_KEY_PREFIX . ImageGenerate::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::QwenImageEdit->value, waitTimeout: 60)]
    private function callSyncEditAPI(QwenImageEditRequest $request): array
    {
        try {
            $params = [
                'prompt' => $request->getPrompt(),
                'image_urls' => $request->getImageUrls(),
                'model' => $request->getModel(),
            ];

            $response = $this->api->submitEditTask($params);

            // check响应格式 - 适配newsync响应格式
            if (! isset($response['output']['choices'])) {
                $errorMsg = $response['message'] ?? '未知error';
                $this->logger->warning('通义千问图像edit：响应格式error', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, $errorMsg);
            }

            // check是否有图像数据
            $choices = $response['output']['choices'];
            if (empty($choices)) {
                $this->logger->error('通义千问图像edit：响应中缺少图像数据', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }

            $this->logger->info('通义千问图像edit：callsuccess', [
                'choices_count' => count($choices),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('通义千问图像edit：callexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * 为通义千问edit模式original数据添加水印 - 适配newchoices格式.
     */
    private function processQwenEditRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['output']['choices']) || ! is_array($result['output']['choices'])) {
                continue;
            }

            foreach ($result['output']['choices'] as $choiceIndex => &$choice) {
                if (! isset($choice['message']['content']) || ! is_array($choice['message']['content'])) {
                    continue;
                }

                foreach ($choice['message']['content'] as $contentIndex => &$content) {
                    if (! isset($content['image'])) {
                        continue;
                    }

                    try {
                        // handleURL格式的image
                        $content['image'] = $this->watermarkProcessor->addWatermarkToUrl($content['image'], $imageGenerateRequest);
                    } catch (Exception $e) {
                        // 水印handlefail时，记录error但不影响imagereturn
                        $this->logger->error('通义千问图像edit水印handlefail', [
                            'index' => $index,
                            'choiceIndex' => $choiceIndex,
                            'contentIndex' => $contentIndex,
                            'error' => $e->getMessage(),
                        ]);
                        // continuehandle下一张image，currentimage保持originalstatus
                    }
                }
            }
        }

        return $rawData;
    }

    /**
     * validate通义千问editAPI响应数据格式.
     */
    private function validateQwenEditResponse(array $result): void
    {
        if (empty($result['output']['choices']) || ! is_array($result['output']['choices'])) {
            throw new Exception('通义千问edit响应数据格式error：缺少choices数据');
        }

        $hasValidImage = false;
        foreach ($result['output']['choices'] as $choice) {
            if (! empty($choice['message']['content']) && is_array($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (! empty($content['image'])) {
                        $hasValidImage = true;
                        break 2;
                    }
                }
            }
        }

        if (! $hasValidImage) {
            throw new Exception('通义千问edit响应数据格式error：缺少图像数据');
        }
    }

    /**
     * 将通义千问editimage数据添加到OpenAI响应object中.
     */
    private function addImageDataToResponseQwenEdit(
        OpenAIFormatResponse $response,
        array $qwenResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // 从通义千问edit响应中提取数据
        if (empty($qwenResult['output']['choices']) || ! is_array($qwenResult['output']['choices'])) {
            return;
        }

        $currentData = $response->getData();
        $currentUsage = $response->getUsage() ?? new ImageUsage();

        foreach ($qwenResult['output']['choices'] as $choice) {
            if (empty($choice['message']['content']) || ! is_array($choice['message']['content'])) {
                continue;
            }

            foreach ($choice['message']['content'] as $content) {
                if (empty($content['image'])) {
                    continue;
                }

                // handle水印
                $processedUrl = $content['image'];
                try {
                    $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($content['image'], $imageGenerateRequest);
                } catch (Exception $e) {
                    $this->logger->error('QwenEdit添加image数据：水印handlefail', [
                        'error' => $e->getMessage(),
                        'url' => $content['image'],
                    ]);
                    // 水印handlefail时useoriginalURL
                }

                $currentData[] = [
                    'url' => $processedUrl,
                ];
            }
        }

        // 累计usageinfo - 通义千问edit的usage格式适配
        if (! empty($qwenResult['usage']) && is_array($qwenResult['usage'])) {
            $currentUsage->addGeneratedImages(1); // editgenerate1张image
            $currentUsage->promptTokens += $qwenResult['usage']['input_tokens'] ?? 0;
            $currentUsage->completionTokens += $qwenResult['usage']['output_tokens'] ?? 0;
            $currentUsage->totalTokens += $qwenResult['usage']['total_tokens'] ?? 0;
        }

        // 更新响应object
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
