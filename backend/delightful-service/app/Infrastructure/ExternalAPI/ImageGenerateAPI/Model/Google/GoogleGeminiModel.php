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
        // Google Gemini notneedAK
    }

    public function setSK(string $sk)
    {
        // Google Gemini notneedSK
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
     * generategraph像并returnOpenAIformatresponse - Google Geminiversion.
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
            $this->logger->error('GoogleGemini OpenAIformat生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. 并hairhandle - 直接操作responseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGeneration($imageGenerateRequest);
                    $this->validateGoogleGeminiResponse($result);

                    // success：settingimagedatatoresponseobject
                    $this->addImageDataToResponseGemini($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('GoogleGemini OpenAIformat生graph：单requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('GoogleGemini OpenAIformat生graph：并hairhandlecomplete', [
            '总request数' => $count,
            'successimage数' => count($response->getData()),
            'whetherhaveerror' => $response->hasError(),
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
            $this->logger->error('Google Gemini文生graph：所haveimagegenerate均fail', ['rawResults' => $rawResults]);
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
        // Google Gemini API 目frontnothavebalancequeryinterface，returndefaultvalue
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

        // ifrequestmiddlefinger定了model，then动statesetting
        if (! empty($modelId)) {
            $this->api->setModelId($modelId);
        }

        try {
            // ifhave参考graph像，thenexecutegraph像edit
            if (! empty($referImages)) {
                // 取the一张参考graph像conductedit
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
        // 直接handleURLgraph像
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
                throw new Exception("无法downloadgraph像，HTTPstatus码: {$response->getStatusCode()}");
            }

            $imageContent = $response->getBody()->getContents();
            if (empty($imageContent)) {
                throw new Exception('download的graph像content为null');
            }

            return base64_encode($imageContent);
        } catch (Exception $e) {
            $this->logger->error('Google Geminigraph生graph：graph像downloadfail', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("downloadgraph像fail: {$e->getMessage()}");
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
            $this->logger->error('Google Gemini文生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // Google Gemini APIeachtime只能generate一张graph，pass并haircallimplement多graphgenerate
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
                    $this->logger->error('Google Gemini文生graph：imagegeneratefail', [
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
            $this->logger->error('Google Gemini文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        ksort($rawResults);
        return array_values($rawResults);
    }

    private function extractImageDataFromResponse(array $result): string
    {
        if (! isset($result['candidates']) || ! is_array($result['candidates'])) {
            throw new Exception('responsemiddle缺少candidatesfield');
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

        throw new Exception('responsemiddle未找toimagedata');
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
            throw new Exception('Google Geminiresponsedataformaterror：缺少graph像data');
        }
    }

    /**
     * 将Google Geminiimagedata添加toOpenAIresponseobjectmiddle（convert为URLformat）.
     */
    private function addImageDataToResponseGemini(
        OpenAIFormatResponse $response,
        array $geminiResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并hairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // use现havemethod提取graph像data
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
                // 水印handlefailo clockuseoriginalbase64data（but这usuallynotshouldhair生）
            }

            // 只returnURLformat，与其他model保持一致
            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo - fromusageMetadatamiddle提取
            if (! empty($geminiResult['usageMetadata']) && is_array($geminiResult['usageMetadata'])) {
                $usageMetadata = $geminiResult['usageMetadata'];
                $currentUsage->addGeneratedImages(1);
                $currentUsage->promptTokens += $usageMetadata['promptTokenCount'] ?? 0;
                $currentUsage->completionTokens += $usageMetadata['candidatesTokenCount'] ?? 0;
                $currentUsage->totalTokens += $usageMetadata['totalTokenCount'] ?? 0;
            } else {
                // ifnothaveusageinfo，default增加1张image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelock一定willberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
