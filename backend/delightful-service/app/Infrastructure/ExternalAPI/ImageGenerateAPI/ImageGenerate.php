<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI;

use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;

interface ImageGenerate
{
    // retrycount
    public const GENERATE_RETRY_COUNT = 3;

    // retrytime
    public const GENERATE_RETRY_TIME = 1000;

    public const IMAGE_GENERATE_KEY_PREFIX = 'text2image:';

    public const IMAGE_GENERATE_SUBMIT_KEY_PREFIX = 'submit:';

    public const IMAGE_GENERATE_POLL_KEY_PREFIX = 'poll:';

    /**
     * generategraphlikeandreturnstandardformatresponse.
     */
    public function generateImage(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse;

    /**
     * generategraphlikeandreturnthethird-partynativeformatdata.
     */
    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array;

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array;

    /**
     * generategraphlikeandreturnOpenAIformatresponse.
     */
    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse;

    public function setAK(string $ak);

    public function setSK(string $sk);

    public function setApiKey(string $apiKey);

    public function getProviderName(): string;
}
