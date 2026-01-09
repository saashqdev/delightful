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
    // 最大轮询次数
    private const MAX_POLL_ATTEMPTS = 60;

    // 轮询间隔（秒）
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
     * generate图像并returnOpenAIformatresponse - GPT4oversion.
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
            $this->logger->error('GPT4o OpenAIformat生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
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
                    // submittask并轮询result
                    $jobId = $this->requestImageGeneration($imageGenerateRequest);
                    $result = $this->pollTaskResultForRaw($jobId);

                    $this->validateGPT4oResponse($result);

                    // success：settingimagedata到responseobject
                    $this->addImageDataToResponseGPT4o($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfo到responseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('GPT4o OpenAIformat生图：单个requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. recordfinalresult
        $this->logger->info('GPT4o OpenAIformat生图：并发handlecomplete', [
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
        return 'gpt-image';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // 从原生result中提取imageURL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            if (! empty($result['imageUrl'])) {
                $imageUrls[$index] = $result['imageUrl'];
            }
        }

        // check是否at least有一张imagegeneratesuccess
        if (empty($imageUrls)) {
            $this->logger->error('GPT4o文生图：所有imagegenerate均fail', ['rawResults' => $rawResults]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE);
        }

        // 按索引sortresult
        ksort($imageUrls);
        $imageUrls = array_values($imageUrls);

        $this->logger->info('GPT4o文生图：generateend', [
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
                throw new Exception('check余额fail: ' . ($result['message'] ?? '未知error'));
            }

            return (float) $result['data']['balance'];
        } catch (Exception $e) {
            throw new Exception('check余额fail: ' . $e->getMessage());
        }
    }

    /**
     * requestgenerateimage并returntaskID.
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
        $this->logger->info('GPT4o文生图：start生图', [
            'prompt' => $prompt,
            'referImages' => $referImages,
        ]);

        try {
            $result = $this->api->submitGPT4oTask($prompt, $referImages);

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->warning('GPT4o文生图：generaterequestfail', ['message' => $result['message'] ?? '未知error']);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $result['message']);
            }

            if (empty($result['data']['jobId'])) {
                $this->logger->error('GPT4o文生图：缺少taskID', ['response' => $result]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }
            $taskId = $result['data']['jobId'];
            $this->logger->info('GPT4o文生图：submittasksuccess', [
                'taskId' => $taskId,
            ]);
            return $taskId;
        } catch (Exception $e) {
            $this->logger->warning('GPT4o文生图：callimagegenerateinterfacefail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    /**
     * 轮询taskresult.
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

                // 如果task还在进行中，等待后continue轮询
                if ($result['status'] === 'ON_QUEUE') {
                    $this->logger->info('GPT4o文生图：taskhandle中', [
                        'jobId' => $jobId,
                        'attempt' => $attempts + 1,
                    ]);
                    sleep(self::POLL_INTERVAL);
                    ++$attempts;
                    continue;
                }

                throw new Exception('未知的taskstatus：' . $result['status']);
            } catch (Exception $e) {
                $this->logger->error('GPT4o文生图：轮询taskfail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        throw new Exception('task轮询timeout');
    }

    /**
     * 轮询taskresult，return原生dataformat.
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

                // 如果task还在进行中，等待后continue轮询
                if ($result['status'] === 'ON_QUEUE') {
                    $this->logger->info('GPT4o文生图：taskhandle中', [
                        'jobId' => $jobId,
                        'attempt' => $attempts + 1,
                    ]);
                    sleep(self::POLL_INTERVAL);
                    ++$attempts;
                    continue;
                }

                throw new Exception('未知的taskstatus：' . $result['status']);
            } catch (Exception $e) {
                $this->logger->error('GPT4o文生图：轮询taskfail', [
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        throw new Exception('task轮询timeout');
    }

    /**
     * generate图像的核心逻辑，return原生result.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof GPT4oModelRequest) {
            $this->logger->error('GPT4o文生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        $count = $imageGenerateRequest->getGenerateNum();
        $rawResults = [];
        $errors = [];

        // use Parallel 并行handle
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
                    $this->logger->error('GPT4o文生图：imagegeneratefail', [
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

        // get所有并行task的result
        $results = $parallel->wait();

        // handleresult，保持原生format
        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result['data'];
            } else {
                $errors[] = $result['error'] ?? '未知error';
            }
        }

        // check是否at least有一张imagegeneratesuccess
        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('GPT4o文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        // 按索引sortresult
        ksort($rawResults);
        return array_values($rawResults);
    }

    /**
     * 为GPT4ooriginaldata添加水印.
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
                // 水印handlefail时，recorderror但不影响imagereturn
                $this->logger->error('GPT4oimage水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandle下一张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }

    /**
     * validateGPT4o API轮询responsedataformat.
     */
    private function validateGPT4oResponse(array $result): void
    {
        if (empty($result['imageUrl'])) {
            throw new Exception('GPT4oresponsedataformaterror：缺少imageUrl字段');
        }
    }

    /**
     * 将GPT4oimagedata添加到OpenAIresponseobject中.
     */
    private function addImageDataToResponseGPT4o(
        OpenAIFormatResponse $response,
        array $gpt4oResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并发安全
        $lockOwner = $this->lockResponse($response);
        try {
            // 从GPT4o轮询result中提取imageURL
            if (empty($gpt4oResult['imageUrl'])) {
                return;
            }

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            $imageUrl = $gpt4oResult['imageUrl'];

            // handle水印
            $processedUrl = $imageUrl;
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('GPT4o添加imagedata：水印handlefail', [
                    'error' => $e->getMessage(),
                    'url' => $imageUrl,
                ]);
                // 水印handlefail时useoriginalURL
            }

            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo - GPT4o没有详细的tokenstatistics
            $currentUsage->addGeneratedImages(1);

            // updateresponseobject
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // ensurelock一定will被释放
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
