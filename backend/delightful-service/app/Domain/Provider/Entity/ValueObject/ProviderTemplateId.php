<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * servicequotienttemplateIDenum
 * willProviderCodeandCategorygroup合mappingforfixed数value型string.
 */
enum ProviderTemplateId: string
{
    // Official 相close
    case OfficialLlm = '0';
    case OfficialVlm = '1';

    // Volcengine 相close
    case VolcengineLlm = '2';
    case VolcengineVlm = '3';

    // OpenAI 相close
    case OpenAILlm = '4';
    case OpenAIVlm = '5';

    // MicrosoftAzure 相close
    case MicrosoftAzureLlm = '6';
    case MicrosoftAzureVlm = '7';

    // Qwen 相close
    case QwenLlm = '8';
    case QwenVlm = '9';

    // DeepSeek 相close
    case DeepSeekLlm = '10';
    case DeepSeekVlm = '11';

    // Tencent 相close
    case TencentLlm = '12';
    case TencentVlm = '13';

    // TTAPI 相close
    case TTAPILlm = '14';
    case TTAPIVlm = '15';

    // MiracleVision 相close
    case MiracleVisionLlm = '16';
    case MiracleVisionVlm = '17';

    // AWSBedrock 相close
    case AWSBedrockLlm = '18';
    case AWSBedrockVlm = '19';
    case GoogleVlm = '20';
    case VolcengineArkVlm = '21';
    case Gemini = '22';

    /**
     * according toProviderCodeandCategorygetto应templateID.
     */
    public static function fromProviderCodeAndCategory(ProviderCode $providerCode, Category $category): ?self
    {
        return match ([$providerCode, $category]) {
            [ProviderCode::Official, Category::LLM] => self::OfficialLlm,
            [ProviderCode::Official, Category::VLM] => self::OfficialVlm,
            [ProviderCode::Volcengine, Category::LLM] => self::VolcengineLlm,
            [ProviderCode::Volcengine, Category::VLM] => self::VolcengineVlm,
            [ProviderCode::OpenAI, Category::LLM] => self::OpenAILlm,
            [ProviderCode::OpenAI, Category::VLM] => self::OpenAIVlm,
            [ProviderCode::MicrosoftAzure, Category::LLM] => self::MicrosoftAzureLlm,
            [ProviderCode::MicrosoftAzure, Category::VLM] => self::MicrosoftAzureVlm,
            [ProviderCode::Qwen, Category::LLM] => self::QwenLlm,
            [ProviderCode::Qwen, Category::VLM] => self::QwenVlm,
            [ProviderCode::DeepSeek, Category::LLM] => self::DeepSeekLlm,
            [ProviderCode::DeepSeek, Category::VLM] => self::DeepSeekVlm,
            [ProviderCode::Tencent, Category::LLM] => self::TencentLlm,
            [ProviderCode::Tencent, Category::VLM] => self::TencentVlm,
            [ProviderCode::TTAPI, Category::LLM] => self::TTAPILlm,
            [ProviderCode::TTAPI, Category::VLM] => self::TTAPIVlm,
            [ProviderCode::MiracleVision, Category::LLM] => self::MiracleVisionLlm,
            [ProviderCode::MiracleVision, Category::VLM] => self::MiracleVisionVlm,
            [ProviderCode::AWSBedrock, Category::LLM] => self::AWSBedrockLlm,
            [ProviderCode::AWSBedrock, Category::VLM] => self::AWSBedrockVlm,
            [ProviderCode::Google, Category::VLM] => self::GoogleVlm,
            [ProviderCode::VolcengineArk, Category::VLM] => self::VolcengineArkVlm,
            [ProviderCode::Gemini, Category::LLM] => self::Gemini,
            default => null,
        };
    }

    /**
     * parsetemplateID,returnto应ProviderCodeandCategory.
     *
     * @return array{providerCode: ProviderCode, category: Category}
     */
    public function toProviderCodeAndCategory(): array
    {
        return match ($this) {
            self::OfficialLlm => ['providerCode' => ProviderCode::Official, 'category' => Category::LLM],
            self::OfficialVlm => ['providerCode' => ProviderCode::Official, 'category' => Category::VLM],
            self::VolcengineLlm => ['providerCode' => ProviderCode::Volcengine, 'category' => Category::LLM],
            self::VolcengineVlm => ['providerCode' => ProviderCode::Volcengine, 'category' => Category::VLM],
            self::OpenAILlm => ['providerCode' => ProviderCode::OpenAI, 'category' => Category::LLM],
            self::OpenAIVlm => ['providerCode' => ProviderCode::OpenAI, 'category' => Category::VLM],
            self::MicrosoftAzureLlm => ['providerCode' => ProviderCode::MicrosoftAzure, 'category' => Category::LLM],
            self::MicrosoftAzureVlm => ['providerCode' => ProviderCode::MicrosoftAzure, 'category' => Category::VLM],
            self::QwenLlm => ['providerCode' => ProviderCode::Qwen, 'category' => Category::LLM],
            self::QwenVlm => ['providerCode' => ProviderCode::Qwen, 'category' => Category::VLM],
            self::DeepSeekLlm => ['providerCode' => ProviderCode::DeepSeek, 'category' => Category::LLM],
            self::DeepSeekVlm => ['providerCode' => ProviderCode::DeepSeek, 'category' => Category::VLM],
            self::TencentLlm => ['providerCode' => ProviderCode::Tencent, 'category' => Category::LLM],
            self::TencentVlm => ['providerCode' => ProviderCode::Tencent, 'category' => Category::VLM],
            self::TTAPILlm => ['providerCode' => ProviderCode::TTAPI, 'category' => Category::LLM],
            self::TTAPIVlm => ['providerCode' => ProviderCode::TTAPI, 'category' => Category::VLM],
            self::MiracleVisionLlm => ['providerCode' => ProviderCode::MiracleVision, 'category' => Category::LLM],
            self::MiracleVisionVlm => ['providerCode' => ProviderCode::MiracleVision, 'category' => Category::VLM],
            self::AWSBedrockLlm => ['providerCode' => ProviderCode::AWSBedrock, 'category' => Category::LLM],
            self::AWSBedrockVlm => ['providerCode' => ProviderCode::AWSBedrock, 'category' => Category::VLM],
            self::GoogleVlm => ['providerCode' => ProviderCode::Google, 'category' => Category::VLM],
            self::VolcengineArkVlm => ['providerCode' => ProviderCode::VolcengineArk, 'category' => Category::VLM],
            self::Gemini => ['providerCode' => ProviderCode::Gemini, 'category' => Category::LLM],
        };
    }

    /**
     * gettemplatedescriptionname.
     */
    public function getDescription(): string
    {
        $mapping = $this->toProviderCodeAndCategory();
        $providerName = match ($mapping['providerCode']) {
            ProviderCode::Official => 'official',
            ProviderCode::Volcengine => 'Volcanoengine',
            ProviderCode::OpenAI => 'OpenAI',
            ProviderCode::MicrosoftAzure => 'Microsoft Azure',
            ProviderCode::Qwen => 'general meaningthousand问',
            ProviderCode::DeepSeek => 'DeepSeek',
            ProviderCode::Tencent => 'Tencent Cloud',
            ProviderCode::TTAPI => 'TTAPI',
            ProviderCode::MiracleVision => 'MiracleVision',
            ProviderCode::AWSBedrock => 'AWS Bedrock',
            ProviderCode::Google => 'Google',
            ProviderCode::VolcengineArk => 'Volcanoengine-Ark',
            ProviderCode::Gemini => 'Google Gemini',
            default => 'unknownservicequotient',
        };

        $categoryName = $mapping['category']->label();

        return "{$providerName}_{$categoryName}";
    }
}
