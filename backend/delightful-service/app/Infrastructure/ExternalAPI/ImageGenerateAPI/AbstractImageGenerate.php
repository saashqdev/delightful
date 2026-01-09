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
 * imagegenerate统oneabstractcategory
 * integrationwatermarkprocessand钉钉alertfeature
 * 所haveimagegenerateProviderallshouldinheritthiscategory.
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
     * 统oneimagegenerate入口method
     * 先call子categoryimplementoriginalimagegenerate，again统oneaddwatermark.
     */
    final public function generateImage(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        $originalResponse = $this->generateImageInternal($imageGenerateRequest);

        return $this->applyWatermark($originalResponse, $imageGenerateRequest);
    }

    /**
     * implementinterfacerequire带watermarkoriginaldatamethod
     * each子categorymustaccording tofrom己dataformatimplementthismethod.
     */
    abstract public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array;

    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        return $this->generateImageOpenAIFormat($imageGenerateRequest);
    }

    /**
     * 子categoryimplementoriginalimagegeneratemethod
     * only负责calleachfromAPIgenerateimage，notuseclosecorewatermarkprocess.
     */
    abstract protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse;

    /**
     * getresponseobjectlock，useatandhairsecurityground操as OpenAIFormatResponse.
     * useRedisfrom旋lockimplementrow队etc待.
     *
     * @return string returnlockowner，useatreleaselock
     */
    protected function lockResponse(OpenAIFormatResponse $response): string
    {
        $lockKey = 'img_response_' . spl_object_id($response);
        $owner = bin2hex(random_bytes(8)); // 16位随机stringasforowner

        // spinLockwillfrom动etc待，untilgetsuccessortimeout（30second）
        if (! $this->redisLocker->spinLock($lockKey, $owner, 30)) {
            $this->logger->error('getgraphlikeresponseRedislocktimeout', [
                'lock_key' => $lockKey,
                'timeout' => 30,
            ]);
            throw new Exception('getgraphlikeresponselocktimeout，请稍backretry');
        }

        $this->logger->debug('Redislockgetsuccess', ['lock_key' => $lockKey, 'owner' => $owner]);
        return $owner;
    }

    /**
     * releaseresponseobjectlock.
     *
     * @param OpenAIFormatResponse $response responseobject
     * @param string $owner lockowner
     */
    protected function unlockResponse(OpenAIFormatResponse $response, string $owner): void
    {
        $lockKey = 'img_response_' . spl_object_id($response);

        try {
            $result = $this->redisLocker->release($lockKey, $owner);
            if (! $result) {
                $this->logger->warning('Redislockreleasefail，maybealreadybeotherenter程release', [
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
            // lockreleasefailnotimpactbusiness逻辑，butwantrecordlog
        }
    }

    /**
     * 统onewatermarkprocess逻辑
     * supportURLandbase64两typeformatimagewatermarkprocess.
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
                // watermarkprocessfailo clock，recorderrorbutnotimpactimagereturn
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
