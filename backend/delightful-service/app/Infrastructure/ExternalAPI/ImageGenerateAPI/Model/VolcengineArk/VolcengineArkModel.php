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

        // ifnothaveconfigurationURL，usedefault端point
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
        // VolcengineArk notuseAK/SK，这within为nullimplement
    }

    public function setSK(string $sk)
    {
        // VolcengineArk notuseAK/SK，这within为nullimplement
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
     * generategraph像并returnOpenAIformatresponse - V2一body化version.
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
        if (! $imageGenerateRequest instanceof VolcengineArkRequest) {
            $this->logger->error('VolcengineArk OpenAIformat生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
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
                    $result = $this->requestImageGenerationV2($imageGenerateRequest);
                    $this->validateVolcengineArkResponse($result);

                    // success：settingimagedatatoresponseobject
                    $this->addImageDataToResponse($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('VolcengineArk OpenAIformat生graph：单requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('VolcengineArk OpenAIformat生graph：并hairhandlecomplete', [
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
        return 'volcengine_ark';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // fromnativeresultmiddle提取imageURL
        $imageData = [];
        foreach ($rawResults as $index => $result) {
            // check嵌set的data结构：result['data']['data'][0]['url']
            if (! empty($result['data']['data']) && ! empty($result['data']['data'][0]['url'])) {
                $imageData[$index] = $result['data']['data'][0]['url'];
            }
        }

        if (empty($imageData)) {
            $this->logger->error('VolcengineArk文生graph：所haveimagegenerate均fail', ['rawResults' => $rawResults]);
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
        // VolcengineArk API 目frontnothavebalancequeryinterface，returndefaultvalue
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

        // ifsetting了groupgraphfeatureoption，then添加 sequential_image_generation_options
        $sequentialOptions = $imageGenerateRequest->getSequentialImageGenerationOptions();
        if (! empty($sequentialOptions)) {
            $payload['sequential_image_generation_options'] = $sequentialOptions;
        }

        // ifhave参考graph像，then添加imagefield（support多张image）
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
            $this->logger->warning('VolcengineArkimagegenerate：callimagegenerateinterfacefail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * V2version：纯粹的APIcall，nothandleexception.
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

        // ifsetting了groupgraphfeatureoption，then添加 sequential_image_generation_options
        $sequentialOptions = $imageGenerateRequest->getSequentialImageGenerationOptions();
        if (! empty($sequentialOptions)) {
            $payload['sequential_image_generation_options'] = $sequentialOptions;
        }

        // ifhave参考graph像，then添加imagefield（support多张image）
        if (! empty($referImages)) {
            if (count($referImages) === 1) {
                $payload['image'] = $referImages[0];
            } else {
                $payload['image'] = $referImages;
            }
        }

        // 直接callAPI，exception自然toup抛
        return $this->api->generateImage($payload);
    }

    /**
     * validate火山方舟APIresponsedataformat.
     */
    private function validateVolcengineArkResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data']) || empty($result['data'][0]['url'])) {
            throw new Exception('火山方舟responsedataformaterror');
        }
    }

    /**
     * 将火山方舟imagedata添加toOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponse(
        OpenAIFormatResponse $response,
        array $volcengineResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并hairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // from火山方舟responsemiddle提取data
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
                        $this->logger->error('VolcengineArk添加imagedata：水印handlefail', [
                            'error' => $e->getMessage(),
                            'url' => $item['url'],
                        ]);
                        // 水印handlefailo clockuseoriginalURL
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

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelock一定willberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }

    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof VolcengineArkRequest) {
            $this->logger->error('VolcengineArk文生graph：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // VolcengineArk APIeachtime只能generate一张graph，pass并haircallimplement多graphgenerate
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
                    $this->logger->error('VolcengineArk文生graph：imagegeneratefail', [
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
            $this->logger->error('VolcengineArk文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        ksort($rawResults);
        return array_values($rawResults);
    }

    /**
     * 为火山engineArkoriginaldata添加水印.
     */
    private function processVolcengineArkRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['data']['data']) || empty($result['data']['data'])) {
                continue;
            }

            try {
                // VolcengineArk returnis URL format，useURL水印handle
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
