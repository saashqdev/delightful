<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateModelType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\ImageGenerateType;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageUsage;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\Context\CoContext;
use Exception;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Coroutine;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Retry\Annotation\Retry;

class QwenImageModel extends AbstractImageGenerate
{
    // 最大轮询重试次数
    private const MAX_RETRY_COUNT = 30;

    // 轮询重试间隔（秒）
    private const RETRY_INTERVAL = 2;

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
        // 通义千问不使用AK/SK认证，此method为nullimplement
    }

    public function setSK(string $sk)
    {
        // 通义千问不使用AK/SK认证，此method为nullimplement
    }

    public function setApiKey(string $apiKey)
    {
        $this->api->setApiKey($apiKey);
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $rawData = $this->generateImageRaw($imageGenerateRequest);

        return $this->processQwenRawDataWithWatermark($rawData, $imageGenerateRequest);
    }

    /**
     * generate图像并returnOpenAI格式响应 - Qwen版本.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        // 1. 预先创建响应object
        $response = new OpenAIFormatResponse([
            'created' => time(),
            'provider' => $this->getProviderName(),
            'data' => [],
        ]);

        // 2. parametervalidate
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('Qwen OpenAI格式生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            return $response; // returnnull数据响应
        }

        // 3. 并发handle - 直接操作响应object
        $count = $imageGenerateRequest->getGenerateNum();
        $parallel = new Parallel();
        $fromCoroutineId = Coroutine::id();

        for ($i = 0; $i < $count; ++$i) {
            $parallel->add(function () use ($imageGenerateRequest, $response, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // 提交任务并轮询结果
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    $this->validateQwenResponse($result);

                    // success：设置图片数据到响应object
                    $this->addImageDataToResponseQwen($response, $result, $imageGenerateRequest);
                } catch (Exception $e) {
                    // fail：设置error信息到响应object（只设置第一个error）
                    if (! $response->hasError()) {
                        $response->setProviderErrorCode($e->getCode());
                        $response->setProviderErrorMessage($e->getMessage());
                    }

                    $this->logger->error('Qwen OpenAI格式生图：单个请求fail', [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                    ]);
                }
            });
        }

        $parallel->wait();

        // 4. 记录最终结果
        $this->logger->info('Qwen OpenAI格式生图：并发handlecomplete', [
            '总请求数' => $count,
            'success图片数' => count($response->getData()),
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

        // 从原生结果中提取图片URL
        $imageUrls = [];
        foreach ($rawResults as $index => $result) {
            $output = $result['output'];
            if (! empty($output['results'])) {
                foreach ($output['results'] as $resultItem) {
                    if (! empty($resultItem['url'])) {
                        $imageUrls[$index] = $resultItem['url'];
                        break; // 只取第一个图片URL
                    }
                }
            }
        }

        return new ImageGenerateResponse(ImageGenerateType::URL, $imageUrls);
    }

    /**
     * generate图像的核心逻辑，return原生结果.
     */
    private function generateImageRawInternal(ImageGenerateRequest $imageGenerateRequest): array
    {
        if (! $imageGenerateRequest instanceof QwenImageModelRequest) {
            $this->logger->error('通义千问文生图：无效的请求type', ['class' => get_class($imageGenerateRequest)]);
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR);
        }

        // 其他文生图是 x ，阿里是 * ，保持上游一致，最终传入还是 *
        $size = $imageGenerateRequest->getWidth() . 'x' . $imageGenerateRequest->getHeight();

        // 校验图片尺寸
        $this->validateImageSize($size, $imageGenerateRequest->getModel());

        $count = $imageGenerateRequest->getGenerateNum();

        $this->logger->info('通义千问文生图：start生图', [
            'prompt' => $imageGenerateRequest->getPrompt(),
            'size' => $size,
            'count' => $count,
        ]);

        // 使用 Parallel 并行handle
        $parallel = new Parallel();
        for ($i = 0; $i < $count; ++$i) {
            $fromCoroutineId = Coroutine::id();
            $parallel->add(function () use ($imageGenerateRequest, $i, $fromCoroutineId) {
                CoContext::copy($fromCoroutineId);
                try {
                    // 提交任务（带重试）
                    $taskId = $this->submitAsyncTask($imageGenerateRequest);
                    // 轮询结果（带重试）
                    $result = $this->pollTaskResult($taskId, $imageGenerateRequest);

                    return [
                        'success' => true,
                        'output' => $result['output'],
                        'index' => $i,
                    ];
                } catch (Exception $e) {
                    $this->logger->error('通义千问文生图：fail', [
                        'error' => $e->getMessage(),
                        'index' => $i,
                    ]);
                    return [
                        'success' => false,
                        'error_code' => $e->getCode(),
                        'error_msg' => $e->getMessage(),
                        'index' => $i,
                    ];
                }
            });
        }

        // 获取所有并行任务的结果
        $results = $parallel->wait();
        $rawResults = [];
        $errors = [];

        // handle结果，保持原生格式
        foreach ($results as $result) {
            if ($result['success']) {
                $rawResults[$result['index']] = $result;
            } else {
                $errors[] = [
                    'code' => $result['error_code'] ?? ImageGenerateErrorCode::GENERAL_ERROR->value,
                    'message' => $result['error_msg'] ?? '',
                ];
            }
        }

        if (empty($rawResults)) {
            // 优先使用具体的error码，如果都是通用error则使用 NO_VALID_IMAGE
            $finalErrorCode = ImageGenerateErrorCode::NO_VALID_IMAGE;
            $finalErrorMsg = '';

            foreach ($errors as $error) {
                if ($error['code'] !== ImageGenerateErrorCode::GENERAL_ERROR->value) {
                    $finalErrorCode = ImageGenerateErrorCode::from($error['code']);
                    $finalErrorMsg = $error['message'];
                    break;
                }
            }

            // 如果没有找到具体errormessage，使用第一个errormessage
            if (empty($finalErrorMsg) && ! empty($errors[0]['message'])) {
                $finalErrorMsg = $errors[0]['message'];
            }

            $this->logger->error('通义千问文生图：所有图片generate均fail', ['errors' => $errors]);
            ExceptionBuilder::throw($finalErrorCode, $finalErrorMsg);
        }

        // 按索引sort结果
        ksort($rawResults);
        $rawResults = array_values($rawResults);

        $this->logger->info('通义千问文生图：generateend', [
            '图片数量' => $count,
        ]);

        return $rawResults;
    }

    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    #[RateLimit(create: 4, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_SUBMIT_KEY_PREFIX . ImageGenerateModelType::QwenImage->value, waitTimeout: 60)]
    private function submitAsyncTask(QwenImageModelRequest $request): string
    {
        $prompt = $request->getPrompt();

        try {
            $params = [
                'prompt' => $prompt,
                'size' => $request->getWidth() . '*' . $request->getHeight(),
                'n' => 1, // 通义千问每次只能generate1张图片
                'model' => $request->getModel(),
                'watermark' => false, // 关闭API水印，使用统一PHP水印
                'prompt_extend' => $request->isPromptExtend(),
            ];

            $response = $this->api->submitTask($params);

            // 检查响应格式
            if (! isset($response['output']['task_id'])) {
                $errorMsg = $response['message'] ?? '未知error';
                $this->logger->warning('通义千问文生图：响应中缺少任务ID', ['response' => $response]);
                ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR, $errorMsg);
            }

            $taskId = $response['output']['task_id'];

            $this->logger->info('通义千问文生图：提交任务success', [
                'taskId' => $taskId,
            ]);

            return $taskId;
        } catch (Exception $e) {
            $this->logger->error('通义千问文生图：任务提交exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    #[RateLimit(create: 18, consume: 1, capacity: 0, key: self::IMAGE_GENERATE_KEY_PREFIX . self::IMAGE_GENERATE_POLL_KEY_PREFIX . ImageGenerateModelType::QwenImage->value, waitTimeout: 60)]
    #[Retry(
        maxAttempts: self::GENERATE_RETRY_COUNT,
        base: self::GENERATE_RETRY_TIME
    )]
    private function pollTaskResult(string $taskId, QwenImageModelRequest $imageGenerateRequest): array
    {
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRY_COUNT) {
            try {
                $response = $this->api->getTaskResult($taskId);

                // 检查响应格式
                if (! isset($response['output'])) {
                    $this->logger->warning('通义千问文生图：query任务响应格式error', ['response' => $response]);
                    ExceptionBuilder::throw(ImageGenerateErrorCode::RESPONSE_FORMAT_ERROR);
                }

                $output = $response['output'];
                $taskStatus = $output['task_status'] ?? '';

                $this->logger->info('通义千问文生图：任务status', [
                    'taskId' => $taskId,
                    'status' => $taskStatus,
                ]);

                switch ($taskStatus) {
                    case 'SUCCEEDED':
                        if (! empty($output['results'])) {
                            return $response;
                        }
                        $this->logger->error('通义千问文生图：任务complete但缺少图片数据', ['response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::MISSING_IMAGE_DATA);
                        // no break
                    case 'PENDING':
                    case 'RUNNING':
                        break;
                    case 'FAILED':
                        $errorMsg = $output['message'] ?? '任务executefail';
                        $this->logger->error('通义千问文生图：任务executefail', ['taskId' => $taskId, 'error' => $errorMsg]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $errorMsg);
                        // no break
                    default:
                        $this->logger->error('通义千问文生图：未知的任务status', ['status' => $taskStatus, 'response' => $response]);
                        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT_WITH_REASON);
                }

                ++$retryCount;
                sleep(self::RETRY_INTERVAL);
            } catch (Exception $e) {
                $this->logger->error('通义千问文生图：query任务exception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'taskId' => $taskId,
                ]);

                ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
            }
        }

        $this->logger->error('通义千问文生图：任务query超时', ['taskId' => $taskId]);
        ExceptionBuilder::throw(ImageGenerateErrorCode::TASK_TIMEOUT);
    }

    /**
     * 校验图片尺寸是否match通义千问模型的规格
     */
    private function validateImageSize(string $size, string $model): void
    {
        switch ($model) {
            case 'qwen-image':
                $this->validateQwenImageSize($size);
                break;
            case 'wan2.2-t2i-flash':
                $this->validateWan22FlashSize($size);
                break;
            default:
                // 其他模型暂不校验
                break;
        }
    }

    /**
     * 校验qwen-image模型的固定尺寸列表.
     */
    private function validateQwenImageSize(string $size): void
    {
        // qwen-image支持的固定尺寸列表
        $supportedSizes = [
            '1664x928',   // 16:9
            '1472x1140',  // 4:3
            '1328x1328',  // 1:1 (默认)
            '1140x1472',  // 3:4
            '928x1664',   // 9:16
        ];

        if (! in_array($size, $supportedSizes, true)) {
            $this->logger->error('通义千问文生图：qwen-image不支持的图片尺寸', [
                'requested_size' => $size,
                'supported_sizes' => $supportedSizes,
                'model' => 'qwen-image',
            ]);

            ExceptionBuilder::throw(
                ImageGenerateErrorCode::UNSUPPORTED_IMAGE_SIZE,
                'image_generate.unsupported_image_size',
                [
                    'size' => $size,
                    'supported_sizes' => implode('、', $supportedSizes),
                ]
            );
        }
    }

    /**
     * 校验wan2.2-t2i-flash模型的区间尺寸.
     */
    private function validateWan22FlashSize(string $size): void
    {
        $dimensions = explode('x', $size);
        if (count($dimensions) !== 2) {
            $this->logger->error('通义千问文生图：wan2.2-t2i-flash尺寸格式error', [
                'requested_size' => $size,
                'model' => 'wan2.2-t2i-flash',
            ]);

            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, 'image_generate.invalid_size_format');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        // wan2.2-t2i-flash支持512-1440像素区间
        $minSize = 512;
        $maxSize = 1440;

        if ($width < $minSize || $width > $maxSize || $height < $minSize || $height > $maxSize) {
            $this->logger->error('通义千问文生图：wan2.2-t2i-flash尺寸超出支持范围', [
                'requested_size' => $size,
                'width' => $width,
                'height' => $height,
                'min_size' => $minSize,
                'max_size' => $maxSize,
                'model' => 'wan2.2-t2i-flash',
            ]);

            ExceptionBuilder::throw(
                ImageGenerateErrorCode::UNSUPPORTED_IMAGE_SIZE_RANGE,
                'image_generate.unsupported_image_size_range',
                [
                    'size' => $size,
                    'min_size' => $minSize,
                    'max_size' => $maxSize,
                ]
            );
        }
    }

    /**
     * 为通义千问原始数据添加水印.
     */
    private function processQwenRawDataWithWatermark(array $rawData, ImageGenerateRequest $imageGenerateRequest): array
    {
        foreach ($rawData as $index => &$result) {
            if (! isset($result['output']['results'])) {
                continue;
            }

            try {
                // handle results array中的图片URL
                foreach ($result['output']['results'] as $i => &$resultItem) {
                    if (! empty($resultItem['url'])) {
                        $resultItem['url'] = $this->watermarkProcessor->addWatermarkToUrl($resultItem['url'], $imageGenerateRequest);
                    }
                }
                unset($resultItem);
            } catch (Exception $e) {
                // 水印handlefail时，记录error但不影响图片return
                $this->logger->error('通义千问图片水印handlefail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // continuehandle下一张图片，当前图片保持原始status
            }
        }

        return $rawData;
    }

    /**
     * validate通义千问API响应数据格式.
     */
    private function validateQwenResponse(array $result): void
    {
        if (empty($result['output']) || ! is_array($result['output'])) {
            throw new Exception('通义千问响应数据格式error：缺少output字段');
        }

        $output = $result['output'];
        if (empty($output['results']) || ! is_array($output['results'])) {
            throw new Exception('通义千问响应数据格式error：缺少results字段');
        }

        // 检查第一个结果是否有URL
        if (empty($output['results'][0]['url'])) {
            throw new Exception('通义千问响应数据格式error：缺少图片URL');
        }
    }

    /**
     * 将通义千问图片数据添加到OpenAI响应object中.
     */
    private function addImageDataToResponseQwen(
        OpenAIFormatResponse $response,
        array $qwenResult,
        ImageGenerateRequest $imageGenerateRequest
    ): void {
        // 使用Redis锁确保并发安全
        $lockOwner = $this->lockResponse($response);
        try {
            // 从通义千问响应中提取数据
            if (empty($qwenResult['output']['results']) || ! is_array($qwenResult['output']['results'])) {
                return;
            }

            $results = $qwenResult['output']['results'];
            $currentData = $response->getData();
            $currentUsage = $response->getUsage() ?? new ImageUsage();

            // handle results array中的第一个图片URL
            foreach ($results as $resultItem) {
                if (! empty($resultItem['url'])) {
                    try {
                        // handle水印
                        $processedUrl = $this->watermarkProcessor->addWatermarkToUrl($resultItem['url'], $imageGenerateRequest);
                        $currentData[] = [
                            'url' => $processedUrl,
                        ];
                    } catch (Exception $e) {
                        $this->logger->error('Qwen添加图片数据：URL水印handlefail', [
                            'error' => $e->getMessage(),
                            'url' => $resultItem['url'],
                        ]);
                        // 水印handlefail时使用原始URL
                        $currentData[] = [
                            'url' => $resultItem['url'],
                        ];
                    }
                    break; // 只取第一个图片
                }
            }

            // 累计usage信息
            if (! empty($qwenResult['usage']) && is_array($qwenResult['usage'])) {
                $currentUsage->addGeneratedImages($qwenResult['usage']['image_count'] ?? 1);
            // 通义千问没有token信息，保持默认value
            } else {
                // 如果没有usage信息，默认增加1张图片
                $currentUsage->addGeneratedImages(1);
            }

            // 更新响应object
            $response->setData($currentData);
            $response->setUsage($currentUsage);
        } finally {
            // 确保锁一定会被释放
            $this->unlockResponse($response, $lockOwner);
        }
    }
}
