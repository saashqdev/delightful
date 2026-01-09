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
            throw new Exception('Google Gemini API URL configurationmissing');
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
     * generategraphlikeandreturnOpenAIformatresponse - Google Geminiversion.
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
            $this->logger->error('GoogleGemini OpenAIformat生graph:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. andhairhandle - directly操asresponseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $result = $this->requestImageGeneration($imageGenerateRequest);
                    $this->validateGoogleGeminiResponse($result);

                    // success:settingimagedatatoresponseobject
                    $this->addImageDataToResponseGemini($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail:settingerrorinfotoresponseobject(onlysettingfirsterror)
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('GoogleGemini OpenAIformat生graph:singlerequestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('GoogleGemini OpenAIformat生graph:andhairhandlecomplete', [
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
            $this->logger->error('Google Gemini文生graph:所haveimagegenerate均fail', ['rawResults' => $rawResults]);
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
        // Google Gemini API 目frontnothavebalancequeryinterface,returndefaultvalue
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

        // ifrequestmiddlefinger定model,then动statesetting
        if (! empty($modelId)) {
            $this->api->setModelId($modelId);
        }

        try {
            // ifhavereferencegraphlike,thenexecutegraphlikeedit
            if (! empty($referImages)) {
                // 取theone张referencegraphlikeconductedit
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
            $this->logger->warning('Google Geminiimagegenerate:callimagegenerateinterfacefail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    private function processImageEdit(string $referImageUrl, string $instructions): array
    {
        // directlyhandleURLgraphlike
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
                throw new Exception("no法downloadgraphlike,HTTPstatus码: {$response->getStatusCode()}");
            }

            $imageContent = $response->getBody()->getContents();
            if (empty($imageContent)) {
                throw new Exception('downloadgraphlikecontentfornull');
            }

            return base64_encode($imageContent);
        } catch (Exception $e) {
            $this->logger->error('Google Geminigraph生graph:graphlikedownloadfail', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("downloadgraphlikefail: {$e->getMessage()}");
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
            $this->logger->error('Google Gemini文生graph:invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // Google Gemini APIeachtimeonlycangenerateone张graph,passandhaircallimplement多graphgenerate
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
                    $this->logger->error('Google Gemini文生graph:imagegeneratefail', [
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
                $errors[] = $result['error'] ?? 'unknownerror';
            }
        }

        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('Google Gemini文生graph:所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        ksort($rawResults);
        return array_values($rawResults);
    }

    private function extractImageDataFromResponse(array $result): string
    {
        if (! isset($result['candidates']) || ! is_array($result['candidates'])) {
            throw new Exception('responsemiddlemissingcandidatesfield');
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

        throw new Exception('responsemiddlenot找toimagedata');
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
                $this->logger->error('Google Geminiimagewatermarkhandlefail', [
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
            throw new Exception('Google Geminiresponsedataformaterror:missingcandidatesfield');
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
            throw new Exception('Google Geminiresponsedataformaterror:missinggraphlikedata');
        }
    }

    /**
     * willGoogle GeminiimagedataaddtoOpenAIresponseobjectmiddle(convertforURLformat).
     */
    private function addImageDataToResponseGemini(
        OpenAIFormatResponse $response,
        array $geminiResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensureandhairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // use现havemethodextractgraphlikedata
            $imageBase64 = $this->extractImageDataFromResponse($geminiResult);

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // watermarkhandle(willwillbase64convertforURL)
            $processedUrl = $imageBase64;
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToBase64($imageBase64, $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('GoogleGeminiaddimagedata:watermarkhandlefail', [
                    'error' => $e->getMessage(),
                ]);
                // watermarkhandlefailo clockuseoriginalbase64data(butthisusuallynotshouldhair生)
            }

            // onlyreturnURLformat,andothermodelmaintainone致
            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo - fromusageMetadatamiddleextract
            if (! empty($geminiResult['usageMetadata']) && is_array($geminiResult['usageMetadata'])) {
                $usageMetadata = $geminiResult['usageMetadata'];
                $currentUsage->addGeneratedImages(1);
                $currentUsage->promptTokens += $usageMetadata['promptTokenCount'] ?? 0;
                $currentUsage->completionTokens += $usageMetadata['candidatesTokenCount'] ?? 0;
                $currentUsage->totalTokens += $usageMetadata['totalTokenCount'] ?? 0;
            } else {
                // ifnothaveusageinfo,defaultincrease1张image
                $currentUsage->addGeneratedImages(1);
            }

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelockone定willberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
