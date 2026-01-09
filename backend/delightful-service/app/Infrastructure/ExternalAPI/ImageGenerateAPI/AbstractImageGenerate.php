<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI;

use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\ImageGenerate\ImageWatermarkProcessor;
use App\Infrastructure\Util\Locker\RedisLocker;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;

/**
 * 图片生成统一抽象类
 * 集成水印处理和钉钉告警功能
 * 所有图片生成Provider都应该继承此类.
 */
abstract class AbstractImageGenerate implements ImageGenerate
{
    #[Inject]
    protected LoggerInterface $logger;

    #[Inject]
    protected ImageWatermarkProcessor $watermarkProcessor;

    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected RedisLocker $redisLocker;

    /**
     * 统一的图片生成入口method
     * 先call子类实现的原始图片生成，再统一添加水印.
     */
    final public function generateImage(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $originalResponse = $this->generateImageInternal($imageGenerateRequest);

        return $this->applyWatermark($originalResponse, $imageGenerateRequest);
    }

    /**
     * 实现接口要求的带水印原始数据method
     * 各子类必须根据自己的数据格式实现此method.
     */
    abstract public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array;

    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        return $this->generateImageOpenAIFormat($imageGenerateRequest);
    }

    /**
     * 子类实现的原始图片生成method
     * 只负责call各自API生成图片，不用关心水印处理.
     */
    abstract protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse;

    /**
     * get响应object的锁，用于并发安全地操作 OpenAIFormatResponse.
     * 使用Redis自旋锁实现排队等待.
     *
     * @return string return锁的owner，用于释放锁
     */
    protected function lockResponse(OpenAIFormatResponse $response): string
    {
        $lockKey = 'img_response_' . spl_object_id($response);
        $owner = bin2hex(random_bytes(8)); // 16位随机string作为owner

        // spinLock会自动等待，直到getsuccess或超时（30秒）
        if (! $this->redisLocker->spinLock($lockKey, $owner, 30)) {
            $this->logger->error('get图像响应Redis锁超时', [
                'lock_key' => $lockKey,
                'timeout' => 30,
            ]);
            throw new Exception('get图像响应锁超时，请稍后重试');
        }

        $this->logger->debug('Redis锁getsuccess', ['lock_key' => $lockKey, 'owner' => $owner]);
        return $owner;
    }

    /**
     * 释放响应object的锁.
     *
     * @param OpenAIFormatResponse $response 响应object
     * @param string $owner 锁的owner
     */
    protected function unlockResponse(OpenAIFormatResponse $response, string $owner): void
    {
        $lockKey = 'img_response_' . spl_object_id($response);

        try {
            $result = $this->redisLocker->release($lockKey, $owner);
            if (! $result) {
                $this->logger->warning('Redis锁释放fail，可能已被其他进程释放', [
                    'lock_key' => $lockKey,
                    'owner' => $owner,
                ]);
            } else {
                $this->logger->debug('Redis锁释放success', ['lock_key' => $lockKey, 'owner' => $owner]);
            }
        } catch (Exception $e) {
            $this->logger->error('Redis锁释放exception', [
                'lock_key' => $lockKey,
                'owner' => $owner,
                'error' => $e->getMessage(),
            ]);
            // 锁释放fail不影响业务逻辑，但要recordlog
        }
    }

    /**
     * 统一的水印处理逻辑
     * 支持URL和base64两种格式的图片水印处理.
     */
    private function applyWatermark(ImageGenerateResponse $response, ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $data = $response->getData();
        $processedData = [];

        foreach ($data as $index => $imageData) {
            try {
                if ($response->getImageGenerateType()->isBase64()) {
                    // 处理base64格式图片
                    $processedData[$index] = $this->watermarkProcessor->addWatermarkToBase64($imageData, $imageGenerateRequest);
                } else {
                    // 处理URL格式图片
                    $processedData[$index] = $this->watermarkProcessor->addWatermarkToUrl($imageData, $imageGenerateRequest);
                }
            } catch (Exception $e) {
                // 水印处理fail时，recorderror但不影响图片return
                $this->logger->error('图片水印处理fail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'imageType' => $response->getImageGenerateType()->value,
                ]);
                // return原始图片
                $processedData[$index] = $imageData;
            }
        }

        return new ImageGenerateResponse($response->getImageGenerateType(), $processedData);
    }
}
