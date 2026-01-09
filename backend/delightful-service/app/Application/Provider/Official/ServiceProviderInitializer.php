<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Official;

use Hyperf\DbConnection\Db;
use Throwable;

use function Hyperf\Support\now;

/**
 * Official Service Provider Initializer.
 * Initialize default service providers for new system setup.
 */
class ServiceProviderInitializer
{
    /**
     * Initialize official service providers.
     * @return array{success: bool, message: string, count: int}
     */
    public static function init(): array
    {
        // Check if service_provider table already has data
        $existingCount = Db::table('service_provider')->count();
        if ($existingCount > 0) {
            return [
                'success' => true,
                'message' => "Service provider table already has {$existingCount} records, skipping initialization.",
                'count' => 0,
            ];
        }

        // Get official organization code from config
        $officialOrgCode = config('service_provider.office_organization', '');
        if (empty($officialOrgCode)) {
            return [
                'success' => false,
                'message' => 'Official organization code not configured in service_provider.office_organization',
                'count' => 0,
            ];
        }

        $providers = self::getProviderData($officialOrgCode);
        $insertedCount = 0;

        try {
            Db::beginTransaction();

            foreach ($providers as $provider) {
                Db::table('service_provider')->insert($provider);
                ++$insertedCount;
            }

            Db::commit();

            return [
                'success' => true,
                'message' => "Successfully initialized {$insertedCount} service providers.",
                'count' => $insertedCount,
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to initialize service providers: ' . $e->getMessage(),
                'count' => 0,
            ];
        }
    }

    /**
     * Get service provider data.
     * @param string $orgCode Official organization code
     */
    private static function getProviderData(string $orgCode): array
    {
        $now = now();

        return [
            // Delightful - LLM (Official)
            [
                'id' => '759103339540475904',
                'name' => 'Delightful',
                'provider_code' => 'Official',
                'description' => 'by Delightful pass官方deploy API comeimplement AI modelcall,can直接购买pointsuse海quantitybigmodel.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/delightful.png',
                'provider_type' => 1, // Official
                'category' => 'llm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Delightful',
                        'zh_CN' => 'Delightful',
                    ],
                    'description' => [
                        'en_US' => 'The AI model invocation is achieved through the API officially deployed by Delightful, and you can directly purchase points to use a vast number of large models.',
                        'zh_CN' => 'by Delightful pass官方deploy API comeimplement AI modelcall,can直接购买pointsuse海quantitybigmodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // Microsoft Azure - LLM
            [
                'id' => '759109912413282304',
                'name' => 'Microsoft Azure',
                'provider_code' => 'MicrosoftAzure',
                'description' => 'Azure provide多type先enterAImodel,includeGPT-3.5andmostnewGPT-4系column,support多typedatatypeandcomplextask,致力atsecurity,can靠andcancontinueAIresolvesolution,',
                'icon' => 'DELIGHTFUL/713471849556451329/default/azure Avatars.png',
                'provider_type' => 0, // Normal
                'category' => 'llm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Microsoft Azure',
                        'zh_CN' => '微软 Azure',
                    ],
                    'description' => [
                        'en_US' => 'Azure provides a variety of advanced AI models, including GPT-3.5 and the latest GPT-4 series, supporting multiple data types and complex tasks, committed to safe, reliable and sustainable AI solutions.',
                        'zh_CN' => 'Azure provide多type先enterAImodel,includeGPT-3.5andmostnewGPT-4系column,support多typedatatypeandcomplextask,致力atsecurity,can靠andcancontinueAIresolvesolution,',
                    ],
                ]),
                'remark' => '',
            ],
            // Volcengine - LLM
            [
                'id' => '759110465734258688',
                'name' => '火山engine',
                'provider_code' => 'Volcengine',
                'description' => '字section跳动旗down云service平台,havefrom主研hair豆packagebigmodel系column.涵盖豆package通usemodel Pro,lite,具备differenttexthandleand综合can力,alsohaverole扮演,voice合becomeetc多typemodel.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/volcengine Avatars.png',
                'provider_type' => 0,
                'category' => 'llm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'ByteDance',
                        'zh_CN' => '字section跳动',
                    ],
                    'description' => [
                        'en_US' => 'A cloud service platform under ByteDance, with independently developed Doubao large model series. Includes Doubao general models Pro and lite with different text processing and comprehensive capabilities, as well as various models for role-playing, speech synthesis, etc.',
                        'zh_CN' => '字section跳动旗down云service平台,havefrom主研hair豆packagebigmodel系column.涵盖豆package通usemodel Pro,lite,具备differenttexthandleand综合can力,alsohaverole扮演,voice合becomeetc多typemodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // Volcengine - VLM
            [
                'id' => '759115881155366912',
                'name' => '火山engine',
                'provider_code' => 'Volcengine',
                'description' => 'provide多type智can绘graphbigmodel,生graphstyle多样,securityproperty极high,can亠泛application干教育,娱乐,办公etc场quantity.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/volcengine Avatars.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Volcengine',
                        'zh_CN' => '火山engine',
                    ],
                    'description' => [
                        'en_US' => 'Provides a variety of intelligent drawing models, with diverse image generation styles, extremely high security, and can be widely applied to education, entertainment, office and other scenarios.',
                        'zh_CN' => 'provide多type智can绘graphbigmodel,生graphstyle多样,securityproperty极high,can亠泛application干教育,娱乐,办公etc场quantity.',
                    ],
                ]),
                'remark' => '',
            ],
            // MiracleVision - VLM
            [
                'id' => '759116798252494849',
                'name' => '美graph奇想',
                'provider_code' => 'MiracleVision',
                'description' => '专注atperson脸技术,personbody技术,graphlikeidentify,graphlikehandle,graphlikegenerateetc核core领域',
                'icon' => 'DELIGHTFUL/713471849556451329/default/meitu-qixiang Avatars.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'MiracleVision',
                        'zh_CN' => '美graph奇想',
                    ],
                    'description' => [
                        'en_US' => 'Focused on facial technology, body technology, image recognition, image processing, image generation and other core areas',
                        'zh_CN' => '专注atperson脸技术,personbody技术,graphlikeidentify,graphlikehandle,graphlikegenerateetc核core领域',
                    ],
                ]),
                'remark' => '',
            ],
            // Delightful - VLM (Official)
            [
                'id' => '759144726407426049',
                'name' => 'Delightful',
                'provider_code' => 'Official',
                'description' => 'by Delightful pass官方deploy API comeimplement多type热门文生graph,graph生graphetcmodelcall,can直接购买pointsuse海quantitybigmodel.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/delightful.png',
                'provider_type' => 1, // Official
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Delightful',
                        'zh_CN' => 'Delightful',
                    ],
                    'description' => [
                        'en_US' => 'Delightful implements the invocation of various popular models such as text-to-image and image-to-image through the officially deployed API. You can directly purchase points to use a vast number of large models.',
                        'zh_CN' => 'by Delightful pass官方deploy API comeimplement多type热门文生graph,graph生graphetcmodelcall,can直接购买pointsuse海quantitybigmodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // TTAPI.io - VLM
            [
                'id' => '759145734546132992',
                'name' => 'TTAPI.io',
                'provider_code' => 'TTAPI',
                'description' => '整合多平台文生graph,文生videocan力,Midjourney API,DALL·E 3,Luma文生video,Flux APIserviceetcetc.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/TTAPI.io Avatars.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'TTAPI.io',
                        'zh_CN' => 'TTAPI.io',
                    ],
                    'description' => [
                        'en_US' => 'Integrates multi-platform text-to-image, text-to-video capabilities, Midjourney API, DALL·E 3, Luma text-to-video, Flux API service, etc.',
                        'zh_CN' => '整合多平台文生graph,文生videocan力,Midjourney API,DALL·E 3,Luma文生video,Flux APIserviceetcetc.',
                    ],
                ]),
                'remark' => '',
            ],
            // Custom OpenAI - LLM
            [
                'id' => '764067503220973568',
                'name' => 'customizeservicequotient',
                'provider_code' => 'OpenAI',
                'description' => '请useinterfaceand OpenAI API sameshapetypeservicequotient',
                'icon' => 'DELIGHTFUL/713471849556451329/default/defaultgraph标.png',
                'provider_type' => 0,
                'category' => 'llm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Custom service provider',
                        'zh_CN' => 'customizeservicequotient',
                    ],
                    'description' => [
                        'en_US' => 'Use a service provider with the same form of interface as the OpenAI API',
                        'zh_CN' => '请useinterfaceand OpenAI API sameshapetypeservicequotient',
                    ],
                ]),
                'remark' => 'support OpenAI API shapetype',
            ],
            // Amazon Bedrock - LLM
            [
                'id' => '771078297613344768',
                'name' => 'Amazon Bedrock',
                'provider_code' => 'AWSBedrock',
                'description' => 'Amazon Bedrock is亚马逊 AWS provideoneitemservice,专注atfor企业provide先enter AI languagemodeland视觉model.itsmodel家族include Anthropic  Claude 系column,Meta  Llama 3.1 系columnetc,涵盖from轻quantityleveltohighperformance多typechoose,supporttextgenerate,conversation,graphlikehandleetc多typetask,适useatdifferent规模and需求企业application.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/awsAvatars.png',
                'provider_type' => 0,
                'category' => 'llm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Amazon Bedrock',
                        'zh_CN' => 'Amazon Bedrock',
                    ],
                    'description' => [
                        'en_US' => "Amazon Bedrock is a service offered by Amazon AWS that focuses on advanced AI language models and visual models for businesses. Its model family, including Anthropic's Claude series and Meta's Llama 3.1 series, covers a variety of options from lightweight to high-performance, supporting a variety of tasks such as text generation, dialogue, image processing, and suitable for enterprise applications of different sizes and needs.",
                        'zh_CN' => 'Amazon Bedrock is亚马逊 AWS provideoneitemservice,专注atfor企业provide先enter AI languagemodeland视觉model.itsmodel家族include Anthropic  Claude 系column,Meta  Llama 3.1 系columnetc,涵盖from轻quantityleveltohighperformance多typechoose,supporttextgenerate,conversation,graphlikehandleetc多typetask,适useatdifferent规模and需求企业application.',
                    ],
                ]),
                'remark' => '',
            ],
            // Microsoft Azure - VLM
            [
                'id' => '792047422971920384',
                'name' => 'Microsoft Azure',
                'provider_code' => 'MicrosoftAzure',
                'description' => 'provide多type先enterAImodel,includeGPT-3.5andmostnewGPT-4系column,support多typedatatypeandcomplextask,致力atsecurity,can靠andcancontinueAIresolvesolution.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/azure Avatars.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Microsoft Azure',
                        'zh_CN' => 'Microsoft Azure',
                    ],
                    'description' => [
                        'en_US' => 'Azure offers a variety of advanced AI models, including GPT-3.5 and the latest GPT-4 series, supporting multiple data types and complex tasks, and is committed to providing safe, reliable and sustainable AI solutions.',
                        'zh_CN' => 'Azure provide多type先enterAImodel,includeGPT-3.5andmostnewGPT-4系column,support多typedatatypeandcomplextask,致力atsecurity,can靠andcancontinueAIresolvesolution.',
                    ],
                ]),
                'remark' => '',
            ],
            // Qwen - VLM
            [
                'id' => '792047422971920385',
                'name' => 'Qwen',
                'provider_code' => 'Qwen',
                'description' => 'provide通usegraphlikegeneratemodel,support多type艺术style,particularly擅longcomplextext渲染,especiallyismiddleEnglishtext渲染.',
                'icon' => 'DELIGHTFUL/713471849556451329/default/qwen Avatars White.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Qwen',
                        'zh_CN' => '阿within云hundred炼',
                    ],
                    'description' => [
                        'en_US' => 'It provides a universal image generation model, supports multiple artistic styles, and is particularly skilled at complex text rendering, especially in both Chinese and English text rendering.',
                        'zh_CN' => 'provide通usegraphlikegeneratemodel,support多type艺术style,particularly擅longcomplextext渲染,especiallyismiddleEnglishtext渲染.',
                    ],
                ]),
                'remark' => '',
            ],
            // Google Cloud - VLM
            [
                'id' => '792047422971920386',
                'name' => 'Google Cloud',
                'provider_code' => 'Google-Image',
                'description' => 'provide Gemini 2.5 Flash Image (Nano Banana) graphlikegeneratemodel,具备roleone致propertyhigh,精准graphlikeeditetc.',
                'icon' => $orgCode . '/713471849556451329/2c17c6393771ee3048ae34d6b380c5ec/Q-2terxwePTElOJ_ONtrw.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'Google Cloud',
                        'zh_CN' => 'Google Cloud',
                    ],
                    'description' => [
                        'en_US' => 'Gemini 2.5 Flash Image (Nano Banana) image generation model is provided, featuring high character consistency and precise image editing, etc.',
                        'zh_CN' => 'provide Gemini 2.5 Flash Image (Nano Banana) graphlikegeneratemodel,具备roleone致propertyhigh,精准graphlikeeditetc.',
                    ],
                ]),
                'remark' => '',
            ],
            // VolcengineArk - VLM
            [
                'id' => '792047422971920387',
                'name' => 'VolcengineArk',
                'provider_code' => 'VolcengineArk',
                'description' => '火山engineArk',
                'icon' => 'DELIGHTFUL/713471849556451329/default/volcengine Avatars.png',
                'provider_type' => 0,
                'category' => 'vlm',
                'status' => 1,
                'is_models_enable' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'translate' => json_encode([
                    'name' => [
                        'en_US' => 'VolcengineArk',
                        'zh_CN' => '火山engine(Ark)',
                    ],
                    'description' => [
                        'en_US' => '',
                        'zh_CN' => '',
                    ],
                ]),
                'remark' => '',
            ],
        ];
    }
}
