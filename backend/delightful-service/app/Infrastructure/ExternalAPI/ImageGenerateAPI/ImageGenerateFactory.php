<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI;

use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\AzureOpenAI\AzureOpenAIImageEditModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\AzureOpenAI\AzureOpenAIImageGenerateModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Flux\FluxModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Google\GoogleGeminiModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Google\GoogleGeminiRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\GPT\GPT4oModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Midjourney\MidjourneyModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\MiracleVision\MiracleVisionModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Official\OfficialProxyModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen\QwenImageEditModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen\QwenImageModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Volcengine\VolcengineImageGenerateV3Model;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Volcengine\VolcengineModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\VolcengineArk\VolcengineArkModel;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\VolcengineArk\VolcengineArkRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\AzureOpenAIImageEditRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\AzureOpenAIImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\FluxModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\GPT4oModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\MidjourneyModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\OfficialProxyRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageEditRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\QwenImageModelRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\VolcengineModelRequest;
use InvalidArgumentException;

class ImageGenerateFactory
{
    /**
     * eachmodelsupportfixedratio例mappingtable.
     */
    private const SIZE_FIXED_RATIOS = [
        'VolcengineArk' => [
            '1:1' => ['2048', '2048'],
            '4:3' => ['2304', '1728'],
            '3:4' => ['1728', '2304'],
            '16:9' => ['2560', '1440'],
            '9:16' => ['1440', '2560'],
            '3:2' => ['2496', '1664'],
            '2:3' => ['1664', '2496'],
            '21:9' => ['3024', '1296'],
        ],
    ];

    public static function create(ImageGenerateModelType $imageGenerateType, array $serviceProviderConfig): ImageGenerate
    {
        return match ($imageGenerateType) {
            ImageGenerateModelType::Official => new OfficialProxyModel($serviceProviderConfig),
            ImageGenerateModelType::Midjourney => new MidjourneyModel($serviceProviderConfig),
            ImageGenerateModelType::Volcengine => new VolcengineModel($serviceProviderConfig),
            ImageGenerateModelType::VolcengineImageGenerateV3 => new VolcengineImageGenerateV3Model($serviceProviderConfig),
            ImageGenerateModelType::Flux => new FluxModel($serviceProviderConfig),
            ImageGenerateModelType::MiracleVision => new MiracleVisionModel($serviceProviderConfig),
            ImageGenerateModelType::TTAPIGPT4o => new GPT4oModel($serviceProviderConfig),
            ImageGenerateModelType::AzureOpenAIImageGenerate => new AzureOpenAIImageGenerateModel($serviceProviderConfig),
            ImageGenerateModelType::AzureOpenAIImageEdit => new AzureOpenAIImageEditModel($serviceProviderConfig),
            ImageGenerateModelType::QwenImage => new QwenImageModel($serviceProviderConfig),
            ImageGenerateModelType::QwenImageEdit => new QwenImageEditModel($serviceProviderConfig),
            ImageGenerateModelType::GoogleGemini => new GoogleGeminiModel($serviceProviderConfig),
            ImageGenerateModelType::VolcengineArk => new VolcengineArkModel($serviceProviderConfig),
            default => throw new InvalidArgumentException('not support ' . $imageGenerateType->value),
        };
    }

    public static function createRequestType(ImageGenerateModelType $imageGenerateType, array $data): ImageGenerateRequest
    {
        return match ($imageGenerateType) {
            ImageGenerateModelType::Official => self::createOfficialProxyRequest($data),
            ImageGenerateModelType::Volcengine => self::createVolcengineRequest($data),
            ImageGenerateModelType::VolcengineImageGenerateV3 => self::createVolcengineRequest($data),
            ImageGenerateModelType::Midjourney => self::createMidjourneyRequest($data),
            ImageGenerateModelType::Flux => self::createFluxRequest($data),
            ImageGenerateModelType::TTAPIGPT4o => self::createGPT4oRequest($data),
            ImageGenerateModelType::AzureOpenAIImageGenerate => self::createAzureOpenAIImageGenerateRequest($data),
            ImageGenerateModelType::AzureOpenAIImageEdit => self::createAzureOpenAIImageEditRequest($data),
            ImageGenerateModelType::QwenImage => self::createQwenImageRequest($data),
            ImageGenerateModelType::QwenImageEdit => self::createQwenImageEditRequest($data),
            ImageGenerateModelType::GoogleGemini => self::createGoogleGeminiRequest($data),
            ImageGenerateModelType::VolcengineArk => self::createVolcengineArkRequest($data),
            default => throw new InvalidArgumentException('not support ' . $imageGenerateType->value),
        };
    }

    private static function createOfficialProxyRequest(array $data): OfficialProxyRequest
    {
        return new OfficialProxyRequest([
            'prompt' => $data['user_prompt'] ?? '',
            'model' => $data['model'] ?? '',
            'n' => $data['generate_num'] ?? 1,
            'sequential_image_generation' => $data['sequential_image_generation'] ?? 'disabled',
            'size' => $data['size'] ?? '1024x1024',
            'images' => $data['reference_images'] ?? [],
        ]);
    }

    private static function createGPT4oRequest(array $data): GPT4oModelRequest
    {
        $request = new GPT4oModelRequest();
        $request->setReferImages($data['reference_images']);
        $request->setPrompt($data['user_prompt']);
        return $request;
    }

    private static function createVolcengineRequest(array $data): VolcengineModelRequest
    {
        // parse size parameterfor width and height
        [$width, $height] = self::parseSizeToWidthHeight($data['size'] ?? '1024x1024');

        $request = new VolcengineModelRequest(
            $width,
            $height,
            $data['user_prompt'],
            $data['negative_prompt'],
        );
        isset($data['generate_num']) && $request->setGenerateNum($data['generate_num']);
        $request->setUseSr((bool) $data['use_sr']);
        $request->setReferenceImage($data['reference_images']);
        $request->setModel($data['model']);
        $request->setOrganizationCode($data['organization_code']);
        return $request;
    }

    private static function createMidjourneyRequest(array $data): MidjourneyModelRequest
    {
        $model = $data['model'];
        $mode = strtolower(explode('-', $model, limit: 2)[1] ?? 'fast');

        // Midjourney notuse宽highparameter,onlyneed prompt and mode,butis Request categoryinheritneedthistheseparameter
        // 所bywegivedefaultvalue即can
        $request = new MidjourneyModelRequest('1024', '1024', $data['user_prompt'], $data['negative_prompt']);
        $request->setModel($mode);

        // Midjourney notclosecorespecific宽highratio例,butweretainthisfieldby防willcomeneed
        if (isset($data['size'])) {
            [$width, $height] = self::parseSizeToWidthHeight($data['size']);
            $ratio = self::calculateRatio((int) $width, (int) $height);
            $request->setRatio($ratio);
        }

        isset($data['generate_num']) && $request->setGenerateNum($data['generate_num']);
        return $request;
    }

    private static function createFluxRequest(array $data): FluxModelRequest
    {
        $model = $data['model'];
        if (! in_array($model, ImageGenerateModelType::getFluxModes())) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
        }
        $model = strtolower($model);

        // parse size parameterfor width and height
        [$widthStr, $heightStr] = self::parseSizeToWidthHeight($data['size'] ?? '1024x1024');
        $width = (int) $widthStr;
        $height = (int) $heightStr;

        // todo xhy first fallbackbottom,因for整text generationgraphalsonothave闭环
        if (
            ! ($width === 1024 && $height === 1024)
            && ! ($width === 1024 && $height === 1792)
            && ! ($width === 1792 && $height === 1024)
        ) {
            $width = 1024;
            $height = 1024;
        }

        $request = new FluxModelRequest((string) $width, (string) $height, $data['user_prompt'], $data['negative_prompt']);
        $request->setModel($model);
        isset($data['generate_num']) && $request->setGenerateNum($data['generate_num']);
        $request->setWidth((string) $width);
        $request->setHeight((string) $height);
        return $request;
    }

    private static function createAzureOpenAIImageGenerateRequest(array $data): AzureOpenAIImageGenerateRequest
    {
        $request = new AzureOpenAIImageGenerateRequest();
        $request->setPrompt($data['user_prompt']);

        // Set optional parameters
        if (isset($data['size'])) {
            $request->setSize($data['size']);
        }
        if (isset($data['quality'])) {
            $request->setQuality($data['quality']);
        }
        if (isset($data['generate_num'])) {
            $request->setN((int) $data['generate_num']);
        }
        // Handle image URLs from different sources
        if (isset($data['reference_images']) && is_array($data['reference_images'])) {
            $request->setReferenceImages($data['reference_images']);
        } elseif (isset($data['reference_images'])) {
            // Backward compatibility for single image
            $request->setReferenceImages([$data['reference_images']]);
        } else {
            // Default to empty array if no images provided
            $request->setReferenceImages([]);
        }

        return $request;
    }

    private static function createAzureOpenAIImageEditRequest(array $data): AzureOpenAIImageEditRequest
    {
        $request = new AzureOpenAIImageEditRequest();
        $request->setPrompt($data['user_prompt'] ?? $data['prompt'] ?? '');

        // Handle image URLs from different sources
        if (isset($data['reference_images']) && is_array($data['reference_images'])) {
            $request->setReferenceImages($data['reference_images']);
        } elseif (isset($data['reference_images'])) {
            // Backward compatibility for single image
            $request->setReferenceImages([$data['reference_images']]);
        } else {
            // Default to empty array if no images provided
            $request->setReferenceImages([]);
        }

        // Optional mask parameter
        if (isset($data['mask_url'])) {
            $request->setMaskUrl($data['mask_url']);
        }

        // Set size parameter
        if (isset($data['size'])) {
            $request->setSize($data['size']);
        }

        // Set number of images to generate
        if (isset($data['generate_num'])) {
            $request->setN((int) $data['generate_num']);
        } elseif (isset($data['n'])) {
            $request->setN((int) $data['n']);
        }

        return $request;
    }

    private static function createQwenImageRequest(array $data): QwenImageModelRequest
    {
        // parse size parameterfor width and height
        [$width, $height] = self::parseSizeToWidthHeight($data['size'] ?? '1328x1328');

        $request = new QwenImageModelRequest(
            $width,
            $height,
            $data['user_prompt'],
            $data['negative_prompt'] ?? '',
            $data['model'] ?? 'qwen-image'
        );

        if (isset($data['generate_num'])) {
            $request->setGenerateNum($data['generate_num']);
        }

        if (isset($data['prompt_extend'])) {
            $request->setPromptExtend($data['prompt_extend']);
        }

        if (isset($data['watermark'])) {
            $request->setWatermark($data['watermark']);
        }

        if (isset($data['organization_code'])) {
            $request->setOrganizationCode($data['organization_code']);
        }

        return $request;
    }

    private static function createQwenImageEditRequest(array $data): QwenImageEditRequest
    {
        $request = new QwenImageEditRequest(
            $data['user_prompt'] ?? $data['prompt'] ?? '',
            $data['image_urls'] ?? [],
            $data['model'] ?? 'qwen-image-edit'
        );

        if (isset($data['generate_num'])) {
            $request->setGenerateNum($data['generate_num']);
        }

        $request->setImageUrls($data['reference_images']);

        return $request;
    }

    private static function createGoogleGeminiRequest(array $data): GoogleGeminiRequest
    {
        $request = new GoogleGeminiRequest(
            '', // width - Google Gemininotuse
            '', // height - Google Gemininotuse
            $data['user_prompt'] ?? '',
            '', // negative_prompt - Google Gemininotuse
            $data['model'] ?? 'gemini-2.5-flash-image-preview'
        );

        if (isset($data['generate_num'])) {
            $request->setGenerateNum($data['generate_num']);
        }

        if (isset($data['reference_images'])) {
            $request->setReferImages($data['reference_images']);
        }

        return $request;
    }

    private static function createVolcengineArkRequest(array $data): VolcengineArkRequest
    {
        // parse size parameterfor width and height(use VolcengineArk fixedratio例configuration)
        [$width, $height] = self::parseSizeToWidthHeight($data['size'] ?? '1024x1024', ImageGenerateModelType::VolcengineArk->value);

        $request = new VolcengineArkRequest(
            $width,
            $height,
            $data['user_prompt'],
        );

        if (isset($data['generate_num'])) {
            $request->setGenerateNum($data['generate_num']);
        }

        if (isset($data['reference_images'])) {
            $request->setReferImages($data['reference_images']);
        }

        if (isset($data['model'])) {
            $request->setModel($data['model']);
        }

        if (isset($data['organization_code'])) {
            $request->setOrganizationCode($data['organization_code']);
        }

        if (isset($data['response_format'])) {
            $request->setResponseFormat($data['response_format']);
        }

        // processgroupgraphfeatureparameter
        if (isset($data['sequential_image_generation'])) {
            $request->setSequentialImageGeneration($data['sequential_image_generation']);
        }

        // processgroupgraphfeatureoptionparameter
        if (isset($data['sequential_image_generation_options']) && is_array($data['sequential_image_generation_options'])) {
            $request->setSequentialImageGenerationOptions($data['sequential_image_generation_options']);
        }

        return $request;
    }

    /**
     * parseeachtype size formatfor [width, height] array.
     * supportformat:1024x1024, 1024*1024, 2k, 3k, 16:9, 1:1 etc.
     * @param string $size sizestring
     * @param null|string $modelKey modelkey名,iffinger定thenpriorityusethemodelfixedratio例configuration
     */
    private static function parseSizeToWidthHeight(string $size, ?string $modelKey = null): array
    {
        $size = trim($size);

        // processstandardformat:1024x1024
        if (preg_match('/^(\d+)[x×](\d+)$/i', $size, $matches)) {
            return [(string) $matches[1], (string) $matches[2]];
        }

        // processmultiplynumberformat:1024*1024
        if (preg_match('/^(\d+)\*(\d+)$/', $size, $matches)) {
            return [(string) $matches[1], (string) $matches[2]];
        }

        // process k format:2k, 3k etc
        if (preg_match('/^(\d+)k$/i', $size, $matches)) {
            $resolution = (int) $matches[1] * 1024;
            return [(string) $resolution, (string) $resolution];
        }

        // processratio例format:16:9, 1:1, 3:4 etc
        if (preg_match('/^(\d+):(\d+)$/', $size, $matches)) {
            $width = (int) $matches[1];
            $height = (int) $matches[2];

            // trygetfixedratio例configuration
            $fixedSize = self::getFixedRatioSize($modelKey, $size);
            if ($fixedSize !== null) {
                return $fixedSize;
            }

            // ifnothavefixedconfiguration,according tonormalconvert(based on1024for基准)
            if ($width >= $height) {
                // 横to
                $actualWidth = 1024;
                $actualHeight = (int) (1024 * $height / $width);
            } else {
                // 纵to
                $actualHeight = 1024;
                $actualWidth = (int) (1024 * $width / $height);
            }

            return [(string) $actualWidth, (string) $actualHeight];
        }

        return ['1024', '1024'];
    }

    /**
     * getfinger定modelfixedratio例sizeconfiguration.
     * @param null|string $modelKey modelkey名
     * @param string $ratioKey ratio例key名,如 "1:1", "16:9"
     * @return null|array if存infixedconfigurationreturn [width, height] array,nothenreturn null table示needuseconvert
     */
    private static function getFixedRatioSize(?string $modelKey, string $ratioKey): ?array
    {
        // ifnothavefinger定model,directlyreturn null
        if ($modelKey === null) {
            return null;
        }

        // checkwhether存inthemodelfixedratio例configuration
        if (isset(self::SIZE_FIXED_RATIOS[$modelKey])) {
            return self::SIZE_FIXED_RATIOS[$modelKey][$ratioKey] ?? self::SIZE_FIXED_RATIOS[$modelKey]['1:1'];
        }

        // ifnot存in,return null table示needuseconvert
        return null;
    }

    /**
     * calculate宽highratio例(from LLMAppService 移passcomelogic).
     */
    private static function calculateRatio(int $width, int $height): string
    {
        $gcd = self::gcd($width, $height);
        $ratioWidth = $width / $gcd;
        $ratioHeight = $height / $gcd;
        return $ratioWidth . ':' . $ratioHeight;
    }

    /**
     * calculatemostbig公约数(from LLMAppService 移passcomelogic).
     */
    private static function gcd(int $a, int $b): int
    {
        // Handle edge case where both numbers are zero
        if ($a === 0 && $b === 0) {
            throw new InvalidArgumentException('Both numbers cannot be zero');
        }

        // Use absolute values to ensure positive result
        $a = (int) abs($a);
        $b = (int) abs($b);

        // Iterative approach to avoid stack overflow for large numbers
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }
}
