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
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use Exception;
use Hyperf\Retry\Annotation\Retry;

class AzureOpenAIImageEditModel extends AbstractImageGenerate
{
    private AzureOpenAIAPI $api;

    private array $configItem;

    public function __construct(array $config)
    {
        $this->configItem = $config;
        $baseUrl = $config['url'];
        $apiVersion = $config['api_version'];
        $this->api = new AzureOpenAIAPI($config['api_key'], $baseUrl, $apiVersion);
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof AzureOpenAIImageEditRequest) {
            $this->logger->error('Azure OpenAIgraph像edit：requesttypeerror', [
                'expected' => AzureOpenAIImageEditRequest::class,
                'actual' => get_class($imageGenerateRequest),
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        $this->validateRequest($imageGenerateRequest);

        $this->logger->info('Azure OpenAIgraph像edit：startcallAPI', [
            'reference_images_count' => count($imageGenerateRequest->getReferenceImages()),
            'has_mask' => ! empty($imageGenerateRequest->getMaskUrl()),
            'prompt' => $imageGenerateRequest->getPrompt(),
            'size' => $imageGenerateRequest->getSize(),
            'n' => $imageGenerateRequest->getN(),
        ]);

        try {
            return $this->api->editImage(
                $imageGenerateRequest->getReferenceImages(),
                $imageGenerateRequest->getMaskUrl(),
                $imageGenerateRequest->getPrompt(),
                $imageGenerateRequest->getSize(),
                $imageGenerateRequest->getN()
            );
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAIgraph像edit：APIcallfail', [
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
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processAzureOpenAIEditRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraph像并returnOpenAIformatresponse - Azure OpenAIgraph像editversion.
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
        if (! $imageGenerateRequest instanceof AzureOpenAIImageEditRequest) {
            $this->logger->error('Azure OpenAIgraph像edit OpenAIformat生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        try {
            // 3. graph像edit（synchandle）
            $result = $this->generateImageRaw($imageGenerateRequest);
            $this->validateAzureOpenAIEditResponse($result);

            // 4. convertresponseformat
            $this->addImageDataToResponseAzureOpenAIEdit($response, $result, $imageGenerateRequest);

            $this->logger->info('Azure OpenAIgraph像edit OpenAIformat生graph：handlecomplete', [
                'successimage数' => count($response->getData()),
            ]);
        } catch (Exception $e) {
            // settingerrorinfotoresponseobject
            $response->setProviderErrorCode($e->getCode());
            $response->setProviderErrorMessage($e->getMessage());

            $this->logger->error('Azure OpenAIgraph像edit OpenAIformat生graph：handlefail', [
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
            $result = $this->generateImageRaw($imageGenerateRequest);
            $response = $this->buildResponse($result);

            $this->logger->info('Azure OpenAIgraph像edit：graph像generatesuccess', [
                'image_count' => count($response->getData()),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAIgraph像edit：graph像generatefail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function validateRequest(AzureOpenAIImageEditRequest $request): void
    {
        if (empty($request->getPrompt())) {
            $this->logger->error('Azure OpenAIgraph像edit：缺少必要parameter - prompt');
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.prompt_required');
        }

        if (empty($request->getReferenceImages())) {
            $this->logger->error('Azure OpenAIgraph像edit：缺少必要parameter - reference images');
            ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.reference_images_required');
        }

        if ($request->getN() < 1 || $request->getN() > 10) {
            $this->logger->error('Azure OpenAIgraph像edit：generatequantity超出range', [
                'requested' => $request->getN(),
                'valid_range' => '1-10',
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_image_count');
        }

        // validategraph像URLformat
        foreach ($request->getReferenceImages() as $index => $imageUrl) {
            if (empty($imageUrl) || ! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $this->logger->error('Azure OpenAIgraph像edit：invalid的参考graph像URL', [
                    'index' => $index,
                    'url' => $imageUrl,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_image_url');
            }
        }

        // validatemask URL（if提供）
        $maskUrl = $request->getMaskUrl();
        if (! empty($maskUrl) && ! filter_var($maskUrl, FILTER_VALIDATE_URL)) {
            $this->logger->error('Azure OpenAIgraph像edit：invalid的遮罩graph像URL', [
                'mask_url' => $maskUrl,
            ]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_mask_url');
        }
    }

    private function buildResponse(array $result): ImageGenerateResponse
    {
        try {
            if (! isset($result['data'])) {
                $this->logger->error('Azure OpenAIgraph像edit：responseformaterror - 缺少datafield', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, 'image_generate.response_format_error');
            }

            if (empty($result['data'])) {
                $this->logger->error('Azure OpenAIgraph像edit：responsedata为null', [
                    'response' => $result,
                ]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, 'image_generate.no_image_generated');
            }

            $images = [];
            foreach ($result['data'] as $index => $item) {
                if (! isset($item['b64_json'])) {
                    $this->logger->warning('Azure OpenAIgraph像edit：skipinvalid的graph像data', [
                        'index' => $index,
                        'item' => $item,
                    ]);
                    continue;
                }
                $images[] = $item['b64_json'];
            }

            if (empty($images)) {
                $this->logger->error('Azure OpenAIgraph像edit：所havegraph像datainvalid');
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA, 'image_generate.invalid_image_data');
            }

            $this->logger->info('Azure OpenAIgraph像edit：successbuildresponse', [
                'total_images' => count($images),
            ]);

            return new ImageGenerateResponse(ImageGenerateType::BASE_64, $images);
        } catch (Exception $e) {
            $this->logger->error('Azure OpenAIgraph像edit：buildresponsefail', [
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
     * 为Azure OpenAIedit模typeoriginaldataadd水印.
     */
    private function processAzureOpenAIEditRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! isset($rawData['data']) || ! is_array($rawData['data'])) {
            return $rawData;
        }

        foreach ($rawData['data'] as $index => &$item) {
            if (! isset($item['b64_json'])) {
                continue;
            }

            try {
                // handlebase64format的image
                $item['b64_json'] = $this->watermarkProcessor->addWatermarkToBase64($item['b64_json'], $imageGenerateRequest);
            } catch (Exception $e) {
                // 水印handlefailo clock，recorderrorbutnot影响imagereturn
                $this->logger->error('Azure OpenAIgraph像edit水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledown一张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }

    /**
     * validateAzure OpenAIgraph像editAPIresponsedataformat.
     */
    private function validateAzureOpenAIEditResponse(array $result): void
    {
        if (! isset($result['data'])) {
            throw new Exception('Azure OpenAIgraph像editresponsedataformaterror：缺少datafield');
        }

        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('Azure OpenAIgraph像editresponsedataformaterror：datafield为nullornot是array');
        }

        $hasValidImage = false;
        foreach ($result['data'] as $item) {
            if (isset($item['b64_json']) && ! empty($item['b64_json'])) {
                $hasValidImage = true;
                break;
            }
        }

        if (! $hasValidImage) {
            throw new Exception('Azure OpenAIgraph像editresponsedataformaterror：缺少valid的graph像data');
        }
    }

    /**
     * 将Azure OpenAIgraph像editresultaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseAzureOpenAIEdit(
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
                $this->logger->error('Azure OpenAIgraph像editaddimagedata：水印handlefail', [
                    'error' => $e->getMessage(),
                ]);
                // 水印handlefailo clockuseoriginalbase64data
            }

            // 只returnURLformat，与其他model保持一致
            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo
            $currentUsage->addGeneratedImages(1);
        }

        // ifAzure OpenAIresponsecontainusageinfo，thenuse它
        if (! empty($azureResult['usage']) && is_array($azureResult['usage'])) {
            $usage = $azureResult['usage'];
            $currentUsage->promptTokens += $usage['input_tokens'] ?? 0;
            $currentUsage->completionTokens += $usage['output_tokens'] ?? 0;
            $currentUsage->totalTokens += $usage['total_tokens'] ?? 0;
        }

        // updateresponseobject
        $response->setData($currentData);
        $response->setUsage($currentUsage);
    }
}
