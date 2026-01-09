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
 * imagegenerate统一抽象category
 * integrationwatermarkprocess和钉钉alertfeature
 * 所haveimagegenerateProviderallshouldinherit此category.
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
     * 先call子categoryimplement的originalimagegenerate，again统一addwatermark.
     */
    final public function generateImage(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $originalResponse = $this->generateImageInternal($imageGenerateRequest);

        return $this->applyWatermark($originalResponse, $imageGenerateRequest);
    }

    /**
     * implementinterface要求的带watermarkoriginaldatamethod
     * each子categorymustaccording to自己的dataformatimplement此method.
     */
    abstract public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array;

    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        return $this->generateImageOpenAIFormat($imageGenerateRequest);
    }

    /**
     * 子categoryimplement的originalimagegeneratemethod
     * 只负责calleach自APIgenerateimage，notuse关corewatermarkprocess.
     */
    abstract protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse;

    /**
     * getresponseobject的lock，useat并hairsecurityground操作 OpenAIFormatResponse.
     * useRedis自旋lockimplementrow队etc待.
     *
     * @return string returnlock的owner，useatreleaselock
     */
    protected function lockResponse(OpenAIFormatResponse $response): string
    {
        $lockKey = 'img_response_' . spl_object_id($response);
        $owner = bin2hex(random_bytes(8)); // 16位随机string作为owner

        // spinLockwill自动etc待，untilgetsuccessortimeout（30second）
        if (! $this->redisLocker->spinLock($lockKey, $owner, 30)) {
            $this->logger->error('getgraph像responseRedislocktimeout', [
                'lock_key' => $lockKey,
                'timeout' => 30,
            ]);
            throw new Exception('getgraph像responselocktimeout，请稍backretry');
        }

        $this->logger->debug('Redislockgetsuccess', ['lock_key' => $lockKey, 'owner' => $owner]);
        return $owner;
    }

    /**
     * releaseresponseobject的lock.
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
                $this->logger->warning('Redislockreleasefail，可能已be其他进程release', [
                    'lock_key' => $lockKey,
                    'owner' => $owner,
                ]);
            } else {
                $this->logger->debug('Redislockreleasesuccess', ['lock_key' => $lockKey, 'owner' => $owner]);
            }
        } catch (Exception $e) {
            $this->logger->error('Redislockreleaseexception', [
                'lock_key' => $lockKey,
                'owner' => $owner,
                'error' => $e->getMessage(),
            ]);
            // lockreleasefailnot影响业务逻辑，but要recordlog
        }
    }

    /**
     * 统一的watermarkprocess逻辑
     * supportURL和base64两typeformat的imagewatermarkprocess.
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
                // watermarkprocessfailo clock，recorderrorbutnot影响imagereturn
                $this->logger->error('imagewatermarkprocessfail', [
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
