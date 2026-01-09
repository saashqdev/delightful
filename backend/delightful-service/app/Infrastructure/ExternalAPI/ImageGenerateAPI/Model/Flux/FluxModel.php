<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Flux;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\FluxModelRequest;
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

class FluxModel extends AbstractImageGenerate
{
    protected const MAX_RETRIES = 20;

    protected const RETRY_INTERVAL = 10;

    protected FluxAPI $api;

    public function __construct(array $serviceProviderConfig)
    {
        $this->api = new FluxAPI($serviceProviderConfig['api_key']);
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

        return $this->processFluxRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAIformatresponse - Fluxversion.
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
        if (! $imageGenerateRequest instanceof FluxModelRequest) {
            $this->logger->error('Flux OpenAIformat生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据response
        }

        // 3. 并发handle - 直接操作responseobject
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // submittask并轮询结果
                    $jobId = $this->requestImageGeneration($imageGenerateRequest);
                    $result = $this->pollTaskResultForRaw($jobId);

                    $this->validateFluxResponse($result);

                    // success：settingimage数据到responseobject
                    $this->addImageDataToResponseFlux($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：settingerrorinfo到responseobject（只settingfirsterror）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Flux OpenAIformat生图：单个requestfail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. 记录final结果
        $this->logger->info('Flux OpenAIformat生图：并发handlecomplete', [
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
        return 'flux';
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $rawResults = $this->generateImageRawInternal($imageGenerateRequest);

        // 从原生结果中提取imageURL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            if (! empty($result['data']['imageUrl'])) {
                $imageUrls[$index] = $result['data']['imageUrl'];
            }
        }

        // check是否at least有一张imagegeneratesuccess
        if (empty($imageUrls)) {
            $this->logger->error('Flux文生图：所有imagegenerate均fail', ['rawResults' => $rawResults]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE);
        }

        // 按索引sort结果
        ksort($imageUrls);
        $imageUrls = array_values($imageUrls);

        $this->logger->info('Flux文生图：generateend', [
            'totalImages' => count($imageUrls),
            'requestedImages' => $imageGenerateRequest->getGenerateNum(),
        ]);

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * requestgenerateimage并returntaskID.
     */
    #[RateLimit(create: 20, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::Flux->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function requestImageGeneration(FluxModelRequest $imageGenerateRequest): string
    {
        $prompt = $imageGenerateRequest->getPrompt();
        $size = $imageGenerateRequest->getWidth() . 'x' . $imageGenerateRequest->getHeight();
        $mode = $imageGenerateRequest->getModel();
        // 记录requeststart
        $this->logger->info('Flux文生图：start生图', [
            'prompt' => $prompt,
            'size' => $size,
            'mode' => $mode,
        ]);

        try {
            $result = $this->api->submitTask($prompt, $size, $mode);

            if ($result['status'] !== 'SUCCESS') {
                $this->logger->warning('Flux文生图：generaterequestfail', ['message' => $result['message'] ?? '未知error']);
                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $result['message']);
            }

            if (empty($result['data']['jobId'])) {
                $this->logger->error('Flux文生图：缺少taskID', ['response' => $result]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
            }
            $taskId = $result['data']['jobId'];
            $this->logger->info('Flux文生图：submittasksuccess', [
                'taskId' => $taskId,
            ]);
            return $taskId;
        } catch (Exception $e) {
            $this->logger->warning('Flux文生图：callimagegenerate接口fail', ['error' => $e->getMessage()]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }
    }

    /**
     * 轮询task结果.
     */
    #[RateLimit(create: 40, consume: 1, capacity: 40, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::Flux->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function pollTaskResult(string $jobId): ImageGenerateResponse
    {
        $rawResult = $this->pollTaskResultForRaw($jobId);

        if (! empty($rawResult['data']['imageUrl'])) {
            return new ImageGenerateResponse(ImageGenerateType::URL, [$rawResult['data']['imageUrl']]);
        }

        $this->logger->error('Flux文生图：未get到imageURL', ['response' => $rawResult]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
    }

    /**
     * 轮询task结果并return原生数据.
     */
    #[RateLimit(create: 40, consume: 1, capacity: 40, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::Flux->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    protected function pollTaskResultForRaw(string $jobId): array
    {
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $result = $this->api->getTaskResult($jobId);

                if ($result['status'] === 'SUCCESS') {
                    // 直接return完整的原生数据
                    return $result;
                }

                if ($result['status'] === 'FAILED') {
                    $this->logger->error('Flux文生图：taskexecutefail', ['message' => $result['message'] ?? '未知error']);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $result['message']);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->warning('Flux文生图：轮询task结果fail', ['error' => $e->getMessage(), 'jobId' => $jobId]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::POLLING_FAILED);
            }
        }

        $this->logger->error('Flux文生图：taskexecutetimeout', ['jobId' => $jobId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * checkaccount余额.
     * @return float 余额
     * @throws Exception
     */
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
     * getalertmessage前缀
     */
    protected function getAlertPrefix(): string
    {
        return 'TT API';
    }

    /**
     * generate图像的核心逻辑，return原生结果.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof FluxModelRequest) {
            $this->logger->error('Flux文生图：invalid的requesttype', ['class' => get_class($imageGenerateRequest)]);
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
                    $this->logger->error('Flux文生图：imagegeneratefail', [
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

        // get所有并行task的结果
        $results = $parallel->wait();

        // handle结果，保持原生format
        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result['data'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // check是否at least有一张imagegeneratesuccess
        if (empty($rawResults)) {
            $errorMessage = implode('; ', $errors);
            $this->logger->error('Flux文生图：所有imagegenerate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::NO_VALID_IMAGE, $errorMessage);
        }

        // 按索引sort结果
        ksort($rawResults);
        return array_values($rawResults);
    }

    /**
     * 为Fluxoriginal数据添加水印.
     */
    private function processFluxRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['data']['imageUrl'])) {
                continue;
            }

            try {
                // handleimageURL
                $result['data']['imageUrl'] = $this->watermarkProcessor->addWatermarkToUrl($result['data']['imageUrl'], $imageGenerateRequest);
            } catch (Exception $e) {
                // 水印handlefail时，记录error但不影响imagereturn
                $this->logger->error('Fluximage水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandle下一张image，currentimage保持originalstatus
            }
        }

        return $rawData;
    }

    /**
     * validateFlux APIresponse数据format.
     */
    private function validateFluxResponse(array $result): void
    {
        if (empty($result['data']) || ! is_array($result['data'])) {
            throw new Exception('Fluxresponse数据formaterror：缺少data字段');
        }

        if (empty($result['data']['imageUrl'])) {
            throw new Exception('Fluxresponse数据formaterror：缺少imageUrl字段');
        }
    }

    /**
     * 将Fluximage数据添加到OpenAIresponseobject中.
     */
    private function addImageDataToResponseFlux(
        OpenAIFormatResponse $response,
        array $fluxResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // useRedislockensure并发安全
        $lockOwner = $this->lockResponse($response);
        try {
            // 从Fluxresponse中提取数据
            if (empty($fluxResult['data']['imageUrl'])) {
                return;
            }

            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            $imageUrl = $fluxResult['data']['imageUrl'];

            // handle水印
            $processedUrl = $imageUrl;
            try {
                $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($imageUrl, $imageGenerateRequest);
            } catch (Exception $e) {
                $this->logger->error('Flux添加image数据：水印handlefail', [
                    'error' => $e->getMessage(),
                    'url' => $imageUrl,
                ]);
                // 水印handlefail时useoriginalURL
            }

            $currentData[] = [
                'url' => $processedUrl,
            ];

            // 累计usageinfo
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
