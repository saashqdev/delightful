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
 * imagegenerate统一抽象类
 * integration水印process和钉钉alertfeature
 * 所有imagegenerateProvider都shouldinherit此类.
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
     * 统一的imagegenerate入口method
     * 先call子类implement的originalimagegenerate，再统一添加水印.
     */
    final public function generateImage(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $originalResponse = $this->generateImageInternal($imageGenerateRequest);

        return $this->applyWatermark($originalResponse, $imageGenerateRequest);
    }

    /**
     * implementinterface要求的带水印originaldatamethod
     * 各子类mustaccording to自己的dataformatimplement此method.
     */
    abstract public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array;

    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        return $this->generateImageOpenAIFormat($imageGenerateRequest);
    }

    /**
     * 子类implement的originalimagegeneratemethod
     * 只负责call各自APIgenerateimage，不用关心水印process.
     */
    abstract protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse;

    /**
     * getresponseobject的lock，用于并发security地操作 OpenAIFormatResponse.
     * useRedis自旋lockimplement排队等待.
     *
     * @return string returnlock的owner，用于释放lock
     */
    protected function lockResponse(OpenAIFormatResponse $response): string
    {
        $lockKey = 'img_response_' . spl_object_id($response);
        $owner = bin2hex(random_bytes(8)); // 16位随机string作为owner

        // spinLockwill自动等待，直到getsuccess或timeout（30秒）
        if (! $this->redisLocker->spinLock($lockKey, $owner, 30)) {
            $this->logger->error('get图像responseRedislocktimeout', [
                'lock_key' => $lockKey,
                'timeout' => 30,
            ]);
            throw new Exception('get图像responselocktimeout，请稍后retry');
        }

        $this->logger->debug('Redislockgetsuccess', ['lock_key' => $lockKey, 'owner' => $owner]);
        return $owner;
    }

    /**
     * 释放responseobject的lock.
     *
     * @param OpenAIFormatResponse $response responseobject
     * @param string $owner lock的owner
     */
    protected function unlockResponse(OpenAIFormatResponse $response, string $owner): void
    {
        $lockKey = 'img_response_' . spl_object_id($response);

        try {
            $result = $this->redisLocker->release($lockKey, $owner);
            if (! $result) {
                $this->logger->warning('Redislock释放fail，可能已被其他进程释放', [
                    'lock_key' => $lockKey,
                    'owner' => $owner,
                ]);
            } else {
                $this->logger->debug('Redislock释放success', ['lock_key' => $lockKey, 'owner' => $owner]);
            }
        } catch (Exception $e) {
            $this->logger->error('Redislock释放exception', [
                'lock_key' => $lockKey,
                'owner' => $owner,
                'error' => $e->getMessage(),
            ]);
            // lock释放fail不影响业务逻辑，但要recordlog
        }
    }

    /**
     * 统一的水印process逻辑
     * supportURL和base64两种format的image水印process.
     */
    private function applyWatermark(ImageGenerateResponse $response, ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $data = $response->getData();
        $processedData = [];

        foreach ($data as $index => $imageData) {
            try {
                if ($response->getImageGenerateType()->isBase64()) {
                    // processbase64formatimage
                    $processedData[$index] = $this->watermarkProcessor->addWatermarkToBase64($imageData, $imageGenerateRequest);
                } else {
                    // processURLformatimage
                    $processedData[$index] = $this->watermarkProcessor->addWatermarkToUrl($imageData, $imageGenerateRequest);
                }
            } catch (Exception $e) {
                // 水印processfail时，recorderror但不影响imagereturn
                $this->logger->error('image水印processfail', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'imageType' => $response->getImageGenerateType()->value,
                ]);
                // returnoriginalimage
                $processedData[$index] = $imageData;
            }
        }

        return new ImageGenerateResponse($response->getImageGenerateType(), $processedData);
    }
}
