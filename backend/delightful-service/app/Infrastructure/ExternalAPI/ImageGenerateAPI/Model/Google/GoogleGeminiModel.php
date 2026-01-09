<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Google;

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
use GuzzleHttp\Client;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\Retry\Annotation\Retry;

class GoogleGeminiModel extends AbstractImageGenerate
{
    protected GoogleGeminiAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $apiUrl = $serviceProviderConfig['url'];

        if (empty($apiUrl)) {
            throw new Exception('Google Gemini API URL configuration缺失');
        }

        $this->api = new GoogleGeminiAPI($serviceProviderConfig['api_key'], $apiUrl, $serviceProviderConfig['model_version']);
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        return $this->generateImageRawInternal($imageGenerateRequest);
    }

    public function setAK(string $ak)
    {
        // Google Gemini 不needAK
    }

    public function setSK(string $sk)
    {
        // Google Gemini 不needSK
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setAccessToken($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);
        return $this->processGoogleGeminiRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAIformatresponse - Google Geminiversion.
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
        if (! $imageGenerateRequest instanceof GoogleGeminiRequest) {
            $this->logger->error('GoogleGemini OpenAIformat生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. 并发handle - 直接操作responseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGeneration($imageGenerateRequest);
                    $this->validateGoogleGeminiResponse($result);

                    // success：settingimagedata到responseobject
                    $this->addImageDataToResponseGemini($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfo到responseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('GoogleGemini OpenAIformat生图：单个requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('GoogleGemini OpenAIformat生图：并发handlecomplete', [
            '总request数' => $count,
            'successimage数' => count($response->getData()),
            '是否有error' => $response->hasError(),
            'error码' => $response->getProviderErrorCode(),
            'errormessage' => $response->getProviderErrorMessage(),
        ]);

        return $response;
    }

    public function getProviderName(): string
    {
        return 'google_gemini';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        $imageData = [];
        foreach ($rawResults as $index => $result) {
            if (! empty($result['imageData'])) {
                $imageData[$index] = $result['imageData'];
            }
        }

        if (empty($imageData)) {
            $this->logger->error('Google Gemini文生图：所有imagegenerate均fail', ['rawResults' => $rawResults]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE);
        }

        ksort($imageData);
        $imageData = array_values($imageData);

        return new ImageGenerateResponse(ImageGenerateType::BASE_64, $imageData);
    }

    protected function getAlertPrefix(): string
    {
        return 'Google Gemini API';
    }

    protected function checkBalance(): float
    {
        // Google Gemini API 目前没有余额queryinterface，returndefaultvalue
        return 999.0;
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function requestImageGeneration(GoogleGeminiRequest $imageGenerateRequest): array
    {
        $prompt = $imageGenerateRequest->getPrompt();
        $modelId = $imageGenerateRequest->getModel();
        $referImages = $imageGenerateRequest->getReferImages();

        // 如果request中指定了model，则动态setting
        if (! empty($modelId)) {
            $this->api->setModelId($modelId);
        }

        try {
            // 如果有参考图像，则execute图像edit
            if (! empty($referImages)) {
                // 取第一张参考图像进行edit
                $referImage = $referImages[0];
                $result = $this->processImageEdit($referImage, $prompt);
            } else {
                $result = $this->api->generateImageFromText($prompt, [
                    'temperature' => $imageGenerateRequest->getTemperature(),
                    'candidateCount' => $imageGenerateRequest->getCandidateCount(),
                    'maxOutputTokens' => $imageGenerateRequest->getMaxOutputTokens(),
                ]);
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->warning('Google Geminiimagegenerate：callimagegenerateinterfacefail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    private function processImageEdit(string $referImageUrl, string $instructions): array
    {
        // 直接handleURL图像
        $imageBase64 = $this->downloadImageAsBase64($referImageUrl);
        $mimeType = $this->detectMimeTypeFromUrl($referImageUrl);

        return $this->api->editBase64Image($imageBase64, $mimeType, $instructions);
    }

    private function downloadImageAsBase64(string $url): string
    {
        try {
            $client = new Client(['timeout' => 30]);
            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("无法download图像，HTTPstatus码: {$response->getStatusCode()}");
            }

            $imageContent = $response->getBody()->getContents();
            if (empty($imageContent)) {
                throw new Exception('download的图像content为null');
            }

            return base64_encode($imageContent);
        } catch (Exception $e) {
            $this->logger->error('Google Gemini图生图：图像downloadfail', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("download图像fail: {$e->getMessage()}");
        }
    }

    private function detectMimeTypeFromUrl(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg'
        };
    }

    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof GoogleGeminiRequest) {
            $this->logger->error('Google Gemini文生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // Google Gemini API每次只能generate一张图，pass并发callimplement多图generate
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
                    $imageData = $this->extractImageDataFromResponse($result);

                    return [
                        'success' => true,
                        'data' => ['imageData' => $imageData],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('Google Gemini文生图：imagegeneratefail', [
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
                $rawResults[$result['index']] = $result['data'];
            } else {
                $errors[] = $result['error'] ?? '未知error';
            }
        }

        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('Google Gemini文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        ksort($rawResults);
        return array_values($rawResults);
    }

    private function extractImageDataFromResponse(array $result): string
    {
        if (! isset($result['candidates']) || ! is_array($result['candidates'])) {
            throw new Exception('response中缺少candidatesfield');
        }

        foreach ($result['candidates'] as $candidate) {
            if (! isset($candidate['content']['parts'])) {
                continue;
            }

            foreach ($candidate['content']['parts'] as $part) {
                if (isset($part['inlineData']['data'])) {
                    return $part['inlineData']['data'];
                }
            }
        }

        throw new Exception('response中未找到imagedata');
    }

    private function processGoogleGeminiRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['imageData'])) {
                continue;
            }

            try {
                $result['imageData'] = $this->watermarkProcessor->addWatermarkToBase64($result['imageData'], $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('Google Geminiimage水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rawData;
    }

    /**
     * validateGoogle Gemini APIresponsedataformat.
     */
    private function validateGoogleGeminiResponse(array $result): void
    {
        if (! isset($result['candidates']) || ! is_array($result['candidates'])) {
            throw new Exception('Google Geminiresponsedataformaterror：缺少candidatesfield');
        }

        $hasValidImage = false;
        foreach ($result['candidates'] as $candidate) {
            if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['inlineData']['data']) && ! empty($part['inlineData']['data'])) {
                        $hasValidImage = true;
                        break 2;
                    }
                }
            }
        }

        if (! $hasValidImage) {
            throw new Exception('Google Geminiresponsedataformaterror：缺少图像data');
        }
    }

    /**
     * 将Google Geminiimagedata添加到OpenAIresponseobject中（convert为URLformat）.
     */
    private function addImageDataToResponseGemini(
        OpenAIFormatResponse $response,
        array $geminiResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并发security
        $lockOwner = $this->lockResponse($response);
        try {
            // use现有method提取图像data
            $imageBase64 = $this->extractImageDataFromResponse($geminiResult);

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // 水印handle（will将base64convert为URL）
            $processedUrl = $imageBase64;
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToBase64($imageBase64, $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('GoogleGemini添加imagedata：水印handlefail', [
                    'error' => $e->getMessage(),
                ]);
                // 水印handlefail时useoriginalbase64data（但这通常不should发生）
            }

            // 只returnURLformat，与其他model保持一致
            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo - 从usageMetadata中提取
            if (! empty($geminiResult['usageMetadata']) && is_array($geminiResult['usageMetadata'])) {
                $usageMetadata = $geminiResult['usageMetadata'];
                $currentUsage->addGeneratedImages(1);
                $currentUsage->promptTokens += $usageMetadata['promptTokenCount'] ?? 0;
                $currentUsage->completionTokens += $usageMetadata['candidatesTokenCount'] ?? 0;
                $currentUsage->totalTokens += $usageMetadata['totalTokenCount'] ?? 0;
            } else {
                // 如果没有usageinfo，default增加1张image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelock一定will被释放
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
