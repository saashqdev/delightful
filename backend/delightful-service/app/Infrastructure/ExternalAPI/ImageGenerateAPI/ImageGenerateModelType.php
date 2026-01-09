<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI;

use InvalidArgumentException;

enum ImageGenerateModelType: string
{
    case Midjourney = 'Midjourney';
    case Volcengine = 'Volcengine';
    case VolcengineImageGenerateV3 = 'VolcengineImageGenerateV3';
    case Flux = 'Flux';
    case MiracleVision = 'MiracleVision';
    case TTAPIGPT4o = 'GPT4o';
    case AzureOpenAIImageGenerate = 'AzureOpenAI-ImageGenerate';
    case AzureOpenAIImageEdit = 'AzureOpenAI-ImageEdit';
    case QwenImage = 'Qwen-Image';
    case QwenImageEdit = 'Qwen-ImageEdit';
    case GoogleGemini = 'GoogleGemini';
    case VolcengineArk = 'VolcengineArk';

    // 目front美graphaiultra clearmodel_id
    case MiracleVisionHightModelId = 'miracleVision_mtlab';

    // officialservicequotient
    case Official = 'Official';

    /**
     * frommodelnamegetto应type.
     */
    public static function fromModel(string $model, bool $throw = true): self
    {
        return match (true) {
            in_array($model, self::getOfficialModes()) => self::Official,
            in_array($model, self::getMidjourneyModes()) => self::Midjourney,
            in_array($model, self::getFluxModes()) => self::Flux,
            in_array($model, self::getVolcengineModes()) => self::Volcengine,
            in_array($model, self::getVolcengineImageGenerateV3Modes()) => self::VolcengineImageGenerateV3,
            in_array($model, self::getGPT4oModes()) => self::TTAPIGPT4o,
            in_array($model, self::getAzureOpenAIModes()) => self::AzureOpenAIImageGenerate,
            in_array($model, self::getAzureOpenAIEditModes()) => self::AzureOpenAIImageEdit,
            in_array($model, self::getQwenImageModes()) => self::QwenImage,
            in_array($model, self::getQwenImageEditModes()) => self::QwenImageEdit,
            in_array($model, self::getGoogleGeminiModes()) => self::GoogleGemini,
            in_array($model, self::getVolcengineArkModes()) => self::VolcengineArk,
            default => $throw ? throw new InvalidArgumentException('Unsupported model type: ' . $model) : self::Volcengine,
        };
    }

    /**
     * Midjourney所havemode.
     * @return string[]
     */
    public static function getMidjourneyModes(): array
    {
        return ['Midjourney-Fast', 'Midjourney-Relax', 'Midjourney-Turbo', 'Midjourney', 'turbo', 'relax', 'fast'];
    }

    /**
     * Flux所havemode.
     * @return string[]
     */
    public static function getFluxModes(): array
    {
        return ['Flux1-Dev', 'Flux1-Schnell', 'Flux1-Pro', 'flux1-pro', 'flux1-dev', 'flux1-schnell'];
    }

    /**
     * Volecengin所havemode.
     * @return string[]
     */
    public static function getVolcengineModes(): array
    {
        return ['Volcengine', 'high_aes_general_v21_L', 'byteedit_v2.0', 'Volcengine_byteedit_v2', 'Volcengine_high_aes_general_v21_L'];
    }

    public static function getVolcengineImageGenerateV3Modes(): array
    {
        return ['high_aes_general_v30l_zt2i'];
    }

    public static function getMiracleVisionModes(): array
    {
        return ['mtlab'];
    }

    /**
     * @return string[]
     */
    public static function getGPT4oModes(): array
    {
        return [self::TTAPIGPT4o->value];
    }

    public static function getAzureOpenAIModes(): array
    {
        return [self::AzureOpenAIImageGenerate->value];
    }

    public static function getAzureOpenAIEditModes(): array
    {
        return [self::AzureOpenAIImageEdit->value];
    }

    public static function getQwenImageModes(): array
    {
        return [self::QwenImage->value, 'qwen-image', 'wan2.2-t2i-flash'];
    }

    public static function getQwenImageEditModes(): array
    {
        return [self::QwenImageEdit->value, 'qwen-image-edit'];
    }

    public static function getGoogleGeminiModes(): array
    {
        return ['gemini-2.5-flash-image-preview', 'GoogleGemini', 'gemini-3-pro-image-preview'];
    }

    public static function getVolcengineArkModes(): array
    {
        return ['doubao-seedream-4-0-250828', 'VolcengineArk'];
    }

    public static function getOfficialModes(): array
    {
        return ['Official'];
    }
}
