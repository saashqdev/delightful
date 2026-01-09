<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\AzureOpenAI;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\AzureOpenAIImageEditRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\AzureOpenAIImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;
use Hyperf\Retry\Annotation\Retry;

class AzureOpenAIImageGenerateModel extends AbstractImageGenerate
{
    private AzureOpenAIAPI $api;

    private array $configItem;

    public function __construct(array $serviceProviderConfig)
    {
        $this->configItem = $serviceProviderConfig;
        $baseUrl = $serviceProviderConfig['url'];
        $apiVersion = $serviceProviderConfig['api_version'];
        $this->api = new AzureOpenAIAPI($serviceProviderConfig['api_key'], $baseUrl, $apiVersion);
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof AzureOpenAIImageGenerateRequest) {
            $this->logger->error('Azure OpenAI图像generate：请求typeerror', [
                'expected' => AzureOpenAIImageGenerateRequest::class,
                'actual' => get_class($imageGenerateRequest),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        $this->validateRequest($imageGenerateRequest);

        // 无参考图像，use原有的generate逻辑
        $this->logger->info('Azure OpenAI图像generate：startcallgenerateAPI', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'size' => $imageGenerateRequest->getSize(),
            'quality' => $imageGenerateRequest->getQuality(),
            'n' => $imageGenerateRequest->getN(),
        ]);

        try {
            $requestData = [
                'prompt' => $imageGenerateRequest->getPrompt(),
                'size' => $imageGenerateRequest->getSize(),
                'quality' => $imageGenerateRequest->getQuality(),
                'n' => $imageGenerateRequest->getN(),
            ];

            $result = $this->api->generateImage($requestData);

            $this->logger->info('Azure OpenAI图像generate：APIcallsuccess', [
                'result_data_count' => isset($result['data']) ? count($result['data']) : 0,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAI图像generate：APIcallfail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    public function setAK(string $ak): void
    {
    }

    public function setSK(string $sk): void
    {
    }

    public function setApiKey(string $apiKey): void
    {
        $baseUrl = $this->config->getUrl();
        $apiVersion = $this->config->getApiVersion();
        $this->api = new AzureOpenAIAPI($apiKey, $baseUrl, $apiVersion);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processAzureOpenAIRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAI格式响应 - Azure OpenAI版本.
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
        if (! $imageGenerateRequest instanceof AzureOpenAIImageGenerateRequest) {
            $this->logger->error('Azure OpenAI OpenAI格式生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据响应
        }

        try {
            // 3. 图像generate（synchandle，Azure OpenAI API 支持 n parameter一次性generate多张image）
            if (! empty($imageGenerateRequest->getReferenceImages())) {
                $editModel = new AzureOpenAIImageEditModel($this->configItem);
                $editRequest = $this->convertToEditRequest($imageGenerateRequest);
                $result = $editModel->generateImageRaw($editRequest);
            } else {
                $result = $this->generateImageRaw($imageGenerateRequest);
            }

            $this->validateAzureOpenAIResponse($result);

            // 4. convert响应格式
            $this->addImageDataToResponseAzureOpenAI($response, $result, $imageGenerateRequest);

            $this->logger->info('Azure OpenAI OpenAI格式生图：handlecomplete', [
                '请求image数' => $imageGenerateRequest->getN(),
                'successimage数' => count($response->getData()),
            ]);
        } catch (Exception $e) {
            // settingerrorinfo到响应object
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('Azure OpenAI OpenAI格式生图：handlefail', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    public function getProviderName(): string
    {
        return 'azure_openai';
    }

    public function getConfigItem(): array
    {
        return $this->configItem;
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        try {
            if ($imageGenerateRequest instanceof AzureOpenAIImageGenerateRequest && ! empty($imageGenerateRequest->getReferenceImages())) {
                return $this->generateImageWithReference($imageGenerateRequest);
            }

            $result = $this->generateImageRaw($imageGenerateRequest);
            return $this->buildResponse($result);
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAI图像generate：图像generatefail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function buildResponse(array $result): ImageGenerateResponse
    {
        try {
            if (! isset($result['data'])) {
                $this->logger->error('Azure OpenAI图像generate：响应格式error - 缺少data字段', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, 'image_generate.response_format_error');
            }

            if (empty($result['data'])) {
                $this->logger->error('Azure OpenAI图像generate：响应数据为null', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, 'image_generate.no_image_generated');
            }

            $images = array_column($result['data'], 'b64_json');

            if (empty($images)) {
                $this->logger->error('Azure OpenAI图像generate：所有图像数据无效');
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.invalid_image_data');
            }

            // filter掉nullvalue
            $images = array_filter($images);

            if (empty($images)) {
                $this->logger->error('Azure OpenAI图像generate：filter后无有效图像数据');
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.no_valid_image_data');
            }

            return new ImageGenerateResponse(ImageGenerateType::BASE_64, $images);
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAI图像generate：build响应fail', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);

            if ($e instanceof BusinessException) {
                throw $e;
            }

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.response_build_failed');
        }
    }

    /**
     * 当有参考图像时，use图像edit模型generate图像.
     */
    private function generateImageWithReference(AzureOpenAIImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        try {
            $editModel = new AzureOpenAIImageEditModel($this->config);
            $editRequest = $this->convertToEditRequest($imageGenerateRequest);
            return $editModel->generateImage($editRequest);
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAI图像generate：参考图像generatefail', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 将图像generate请求convert为图像edit请求
     */
    private function convertToEditRequest(AzureOpenAIImageGenerateRequest $imageGenerateRequest): AzureOpenAIImageEditRequest
    {
        try {
            $editRequest = new AzureOpenAIImageEditRequest();
            $editRequest->setPrompt($imageGenerateRequest->getPrompt());
            $editRequest->setReferenceImages($imageGenerateRequest->getReferenceImages());
            $editRequest->setSize($imageGenerateRequest->getSize());
            $editRequest->setN($imageGenerateRequest->getN());
            // 图像edit不需要mask，所以setting为null
            $editRequest->setMaskUrl(null);

            return $editRequest;
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAI图像generate：请求格式convertfail', [
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.request_conversion_failed');
        }
    }

    private function validateRequest(AzureOpenAIImageGenerateRequest $request): void
    {
        if (empty($request->getPrompt())) {
            $this->logger->error('Azure OpenAI图像generate：缺少必要parameter - prompt');
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.prompt_required');
        }

        if ($request->getN() < 1 || $request->getN() > 10) {
            $this->logger->error('Azure OpenAI图像generate：generate数量超出范围', [
                'requested' => $request->getN(),
                'valid_range' => '1-10',
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_image_count');
        }
    }

    /**
     * 为Azure OpenAI原始数据添加水印.
     */
    private function processAzureOpenAIRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! isset($rawData['data']) || ! is_array($rawData['data'])) {
            return $rawData;
        }

        foreach ($rawData['data'] as $index => &$item) {
            if (! isset($item['b64_json'])) {
                continue;
            }

            try {
                // handlebase64格式的image
                $item['b64_json'] = $this->watermarkProcessor->addWatermarkToBase64($item['b64_json'], $imageGenerateRequest);
            } catch (Exception $e) {
                // 水印handlefail时，记录error但不影响imagereturn
                $this->logger->error('Azure OpenAIimage水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandle下一张image，当前image保持原始status
            }
        }

        return $rawData;
    }

    /**
     * validateAzure OpenAI API响应数据格式.
     */
    private function validateAzureOpenAIResponse(array $result): void
    {
        if (! isset($result['data'])) {
            throw new Exception('Azure OpenAI响应数据格式error：缺少data字段');
        }

        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('Azure OpenAI响应数据格式error：data字段为null或不是array');
        }

        $hasValidImage = false;
        foreach ($result['data'] as $item) {
            if (isset($item['b64_json']) && ! empty($item['b64_json'])) {
                $hasValidImage = true;
                break;
            }
        }

        if (! $hasValidImage) {
            throw new Exception('Azure OpenAI响应数据格式error：缺少有效的图像数据');
        }
    }

    /**
     * 将Azure OpenAIimage数据添加到OpenAI响应object中.
     */
    private function addImageDataToResponseAzureOpenAI(
        OpenAIFormatResponse $response,
        array $azureResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        if (! isset($azureResult['data']) || ! is_array($azureResult['data'])) {
            return;
        }

        $currentData = $response->getData();
        $currentUsage = $response->getUsage() ?? new ImageUsage();

        foreach ($azureResult['data'] as $item) {
            if (! isset($item['b64_json']) || empty($item['b64_json'])) {
                continue;
            }

            // handle水印（将base64convert为URL）
            $processedUrl = $item['b64_json'];
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToBase64($item['b64_json'], $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('Azure OpenAI添加image数据：水印handlefail', [
                    'error' => $e->getMessage(),
                ]);
                // 水印handlefail时use原始base64数据
            }

            // 只returnURL格式，与其他模型保持一致
            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo
            $currentUsage->addGeneratedImages(1);
        }

        // 如果Azure OpenAI响应containusageinfo，则use它
        if (! empty($azureResult['usage']) && is_array($azureResult['usage'])) {
            $usage = $azureResult['usage'];
            $currentUsage->promptTokens += $usage['input_tokens'] ?? 0;
            $currentUsage->completionTokens += $usage['output_tokens'] ?? 0;
            $currentUsage->totalTokens += $usage['total_tokens'] ?? 0;
        }

        // 更新响应object
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
