<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\GPT;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\GPT4oModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\Context\CoContext;
use Exception;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class GPT4oModel extends AbstractImageGenerate
{
    // most大round询count
    private const MAX_POLL_ATTEMPTS = 60;

    // round询between隔（second）
    private const POLL_INTERVAL = 5;

    protected GPTAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $this->api = new GPTAPI($serviceProviderConfig['api_key']);
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

        return $this->processGPT4oRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generategraphlikeandreturnOpenAIformatresponse - GPT4oversion.
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
        if (! $imageGenerateRequest instanceof GPT4oModelRequest) {
            $this->logger->error('GPT4o OpenAIformat生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnulldataresponse
        }

        // 3. andhairhandle - 直接操asresponseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittaskandround询result
                    $jobId = $this->requestImageGeneration($imageGenerateRequest);
                    $result = $this->pollTaskResultForRaw($jobId);

                    $this->validateGPT4oResponse($result);

                    // success：settingimagedatatoresponseobject
                    $this->addImageDataToResponseGPT4o($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfotoresponseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('GPT4o OpenAIformat生graph：singlerequestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('GPT4o OpenAIformat生graph：andhairhandlecomplete', [
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
        return 'gpt-image';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // fromnativeresultmiddleextractimageURL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            if (! empty($result['imageUrl'])) {
                $imageUrls[$index] = $result['imageUrl'];
            }
        }

        // checkwhetherat leasthaveone张imagegeneratesuccess
        if (empty($imageUrls)) {
            $this->logger->error('GPT4o文生graph：所haveimagegenerate均fail', ['rawResults' => $rawResults]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE);
        }

        // 按索引sortresult
        ksort($imageUrls);
        $imageUrls = array_values($imageUrls);

        $this->logger->info('GPT4o文生graph：generateend', [
            'totalImages' => count($imageUrls),
            'requestedImages' => $imageGenerateRequest->getGenerateNum(),
        ]);

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    protected function getAlertPrefix(): string
    {
        return 'GPT4o API';
    }

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
     * requestgenerateimageandreturntaskID.
     */
    #[RateLimit(create: 20, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::TTAPIGPT4o->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function requestImageGeneration(GPT4oModelRequest $imageGenerateRequest): string
    {
        $prompt = $imageGenerateRequest->getPrompt();
        $referImages = $imageGenerateRequest->getReferImages();

        // recordrequeststart
        $this->logger->info('GPT4o文生graph：start生graph', [
            'prompt' => $prompt,
            'referImages' => $referImages,
        ]);

        try {
            $result = $this->api->submitGPT4oTask($prompt, $referImages);

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->warning('GPT4o文生graph：generaterequestfail', ['message' => $result['message'] ?? '未知error']);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $result['message']);
            }

            if (empty($result['data']['jobId'])) {
                $this->logger->error('GPT4o文生graph：缺少taskID', ['response' => $result]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }
            $taskId = $result['data']['jobId'];
            $this->logger->info('GPT4o文生graph：submittasksuccess', [
                'taskId' => $taskId,
            ]);
            return $taskId;
        } catch (Exception $e) {
            $this->logger->warning('GPT4o文生graph：callimagegenerateinterfacefail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    /**
     * round询taskresult.
     * @throws Exception
     */
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function pollTaskResult(string $jobId): array
    {
        $attempts = 0;
        while ($attempts < self::MAX_POLL_ATTEMPTS) {
            try {
                $result = $this->api->getGPT4oTaskResult($jobId);

                if ($result['status'] === 'FAILED') {
                    throw new Exception($result['message'] ?? 'taskexecutefail');
                }

                if ($result['status'] === 'SUCCESS' && ! empty($result['data']['imageUrl'])) {
                    return $result['data'];
                }

                // iftaskalsoinconductmiddle，etc待backcontinueround询
                if ($result['status'] === 'ON_QUEUE') {
                    $this->logger->info('GPT4o文生graph：taskhandlemiddle', [
                        'jobId' => $jobId,
                        'attempt' => $attempts + 1,
                    ]);
                    sleep(self::POLL_INTERVAL);
                    ++$attempts;
                    continue;
                }

                throw new Exception('未知taskstatus：' . $result['status']);
            } catch (Exception $e) {
                $this->logger->error('GPT4o文生graph：round询taskfail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        throw new Exception('taskround询timeout');
    }

    /**
     * round询taskresult，returnnativedataformat.
     */
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function pollTaskResultForRaw(string $jobId): array
    {
        $attempts = 0;
        while ($attempts < self::MAX_POLL_ATTEMPTS) {
            try {
                $result = $this->api->getGPT4oTaskResult($jobId);

                if ($result['status'] === 'FAILED') {
                    throw new Exception($result['message'] ?? 'taskexecutefail');
                }

                if ($result['status'] === 'SUCCESS' && ! empty($result['data']['imageUrl'])) {
                    return $result['data'];
                }

                // iftaskalsoinconductmiddle，etc待backcontinueround询
                if ($result['status'] === 'ON_QUEUE') {
                    $this->logger->info('GPT4o文生graph：taskhandlemiddle', [
                        'jobId' => $jobId,
                        'attempt' => $attempts + 1,
                    ]);
                    sleep(self::POLL_INTERVAL);
                    ++$attempts;
                    continue;
                }

                throw new Exception('未知taskstatus：' . $result['status']);
            } catch (Exception $e) {
                $this->logger->error('GPT4o文生graph：round询taskfail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        throw new Exception('taskround询timeout');
    }

    /**
     * generategraphlike核core逻辑，returnnativeresult.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof GPT4oModelRequest) {
            $this->logger->error('GPT4o文生graph：invalidrequesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        $count = $imageGenerateRequest->getGenerateNum();
        $rawResults = [];
        $errors = [];

        // use Parallel andlinehandle
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();
        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    $jobId = $this->requestImageGeneration($imageGenerateRequest);
                    $result = $this->pollTaskResultForRaw($jobId);
                    return [
                        'success' => true,
                        'data' => $result,
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('GPT4o文生graph：imagegeneratefail', [
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

        // get所haveandlinetaskresult
        $results = $parallel->wait();

        // handleresult，保持nativeformat
        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result['data'];
            } else {
                $errors[] = $result['error'] ?? '未知error';
            }
        }

        // checkwhetherat leasthaveone张imagegeneratesuccess
        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('GPT4o文生graph：所haveimagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        // 按索引sortresult
        ksort($rawResults);
        return array_values($rawResults);
    }

    /**
     * forGPT4ooriginaldataaddwatermark.
     */
    private function processGPT4oRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['imageUrl'])) {
                continue;
            }

            try {
                // handleimageURL
                $result['imageUrl'] = $this->watermarkProcessor->addWatermarkToUrl($result['imageUrl'], $imageGenerateRequest);
            } catch (Exception $e) {
                // watermarkhandlefailo clock，recorderrorbutnot影响imagereturn
                $this->logger->error('GPT4oimagewatermarkhandlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandledownone张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }

    /**
     * validateGPT4o APIround询responsedataformat.
     */
    private function validateGPT4oResponse(array $result): void
    {
        if (empty($result['imageUrl'])) {
            throw new Exception('GPT4oresponsedataformaterror：缺少imageUrlfield');
        }
    }

    /**
     * willGPT4oimagedataaddtoOpenAIresponseobjectmiddle.
     */
    private function addImageDataToResponseGPT4o(
        OpenAIFormatResponse $response,
        array $gpt4oResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensureandhairsecurity
        $lockOwner = $this->lockResponse($response);
        try {
            // fromGPT4oround询resultmiddleextractimageURL
            if (empty($gpt4oResult['imageUrl'])) {
                return;
            }

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            $imageUrl = $gpt4oResult['imageUrl'];

            // handlewatermark
            $processedUrl = $imageUrl;
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('GPT4oaddimagedata：watermarkhandlefail', [
                    'error' => $e->getMessage(),
                    'url' => $imageUrl,
                ]);
                // watermarkhandlefailo clockuseoriginalURL
            }

            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo - GPT4onothave详细tokenstatistics
            $currentUsage->addGeneratedImages(1);

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelockone定willberelease
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
