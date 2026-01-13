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
                'description' => 'by Delightful passofficialdeploy API comeimplement AI modelcall,candirectlypurchasepointsuseseaquantitybigmodel.',
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
                        'en_US' => 'Delightful',
                    ],
                    'description' => [
                        'en_US' => 'The AI model invocation is achieved through the API officially deployed by Delightful, and you can directly purchase points to use a vast number of large models.',
                        'en_US' => 'by Delightful passofficialdeploy API comeimplement AI modelcall,candirectlypurchasepointsuseseaquantitybigmodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // Microsoft Azure - LLM
            [
                'id' => '759109912413282304',
                'name' => 'Microsoft Azure',
                'provider_code' => 'MicrosoftAzure',
                'description' => 'Azure providemultipletypefirstenterAImodel,includeGPT-3.5andmostnewGPT-4systemcolumn,supportmultipletypedatatypeandcomplextask,dedicateatsecurity,canrelyandcancontinueAIresolvesolution,',
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
                        'en_US' => 'Microsoft Azure',
                    ],
                    'description' => [
                        'en_US' => 'Azure provides a variety of advanced AI models, including GPT-3.5 and the latest GPT-4 series, supporting multiple data types and complex tasks, committed to safe, reliable and sustainable AI solutions.',
                        'en_US' => 'Azure providemultipletypefirstenterAImodel,includeGPT-3.5andmostnewGPT-4systemcolumn,supportmultipletypedatatypeandcomplextask,dedicateatsecurity,canrelyandcancontinueAIresolvesolution,',
                    ],
                ]),
                'remark' => '',
            ],
            // Volcengine - LLM
            [
                'id' => '759110465734258688',
                'name' => 'Volcanoengine',
                'provider_code' => 'Volcengine',
                'description' => 'fieldsectionByteDancedowncloudserviceplatform,havefrommain researchhairbeanpackagebigmodelsystemcolumn.cover Doubanpackagecommonusemodel Pro,lite,havedifferenttexthandleandcomprehensivecancapability,alsohaveroleplay,voicecombinebecomeetcmultipletypemodel.',
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
                        'en_US' => 'fieldsectionByteDance',
                    ],
                    'description' => [
                        'en_US' => 'A cloud service platform under ByteDance, with independently developed Doubao large model series. Includes Doubao general models Pro and lite with different text processing and comprehensive capabilities, as well as various models for role-playing, speech synthesis, etc.',
                        'en_US' => 'fieldsectionByteDancedowncloudserviceplatform,havefrommain researchhairbeanpackagebigmodelsystemcolumn.cover Doubanpackagecommonusemodel Pro,lite,havedifferenttexthandleandcomprehensivecancapability,alsohaveroleplay,voicecombinebecomeetcmultipletypemodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // Volcengine - VLM
            [
                'id' => '759115881155366912',
                'name' => 'Volcanoengine',
                'provider_code' => 'Volcengine',
                'description' => 'providemultipletypeintelligencecandrawgraphbigmodel,generategraphstylediverse,securitypropertyextremehigh,canwidespreadapplicationeducation,entertainment,officeetcfieldquantity.',
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
                        'en_US' => 'Volcanoengine',
                    ],
                    'description' => [
                        'en_US' => 'Provides a variety of intelligent drawing models, with diverse image generation styles, extremely high security, and can be widely applied to education, entertainment, office and other scenarios.',
                        'en_US' => 'providemultipletypeintelligencecandrawgraphbigmodel,generategraphstylediverse,securitypropertyextremehigh,canwidespreadapplicationeducation,entertainment,officeetcfieldquantity.',
                    ],
                ]),
                'remark' => '',
            ],
            // MiracleVision - VLM
            [
                'id' => '759116798252494849',
                'name' => 'aestheticgraphimagination',
                'provider_code' => 'MiracleVision',
                'description' => 'focusatpersonface technology,personbodytechnology,graphlikeidentify,graphlikehandle,graphlikegenerateetccorecoredomain',
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
                        'en_US' => 'aestheticgraphimagination',
                    ],
                    'description' => [
                        'en_US' => 'Focused on facial technology, body technology, image recognition, image processing, image generation and other core areas',
                        'en_US' => 'focusatpersonface technology,personbodytechnology,graphlikeidentify,graphlikehandle,graphlikegenerateetccorecoredomain',
                    ],
                ]),
                'remark' => '',
            ],
            // Delightful - VLM (Official)
            [
                'id' => '759144726407426049',
                'name' => 'Delightful',
                'provider_code' => 'Official',
                'description' => 'by Delightful passofficialdeploy API comeimplementmultipletypepopular text generationgraph,graphgenerategraphetcmodelcall,candirectlypurchasepointsuseseaquantitybigmodel.',
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
                        'en_US' => 'Delightful',
                    ],
                    'description' => [
                        'en_US' => 'Delightful implements the invocation of various popular models such as text-to-image and image-to-image through the officially deployed API. You can directly purchase points to use a vast number of large models.',
                        'en_US' => 'by Delightful passofficialdeploy API comeimplementmultipletypepopular text generationgraph,graphgenerategraphetcmodelcall,candirectlypurchasepointsuseseaquantitybigmodel.',
                    ],
                ]),
                'remark' => '',
            ],
            // TTAPI.io - VLM
            [
                'id' => '759145734546132992',
                'name' => 'TTAPI.io',
                'provider_code' => 'TTAPI',
                'description' => 'integrate multipleplatformtext generationgraph,text generationvideocancapability,Midjourney API,DALL·E 3,Lumatext generationvideo,Flux APIserviceetcetc.',
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
                        'en_US' => 'TTAPI.io',
                    ],
                    'description' => [
                        'en_US' => 'Integrates multi-platform text-to-image, text-to-video capabilities, Midjourney API, DALL·E 3, Luma text-to-video, Flux API service, etc.',
                        'en_US' => 'integrate multipleplatformtext generationgraph,text generationvideocancapability,Midjourney API,DALL·E 3,Lumatext generationvideo,Flux APIserviceetcetc.',
                    ],
                ]),
                'remark' => '',
            ],
            // Custom OpenAI - LLM
            [
                'id' => '764067503220973568',
                'name' => 'customizeservicequotient',
                'provider_code' => 'OpenAI',
                'description' => 'pleaseuseinterfaceand OpenAI API sameshapetypeservicequotient',
                'icon' => 'DELIGHTFUL/713471849556451329/default/defaultgraphmark.png',
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
                        'en_US' => 'customizeservicequotient',
                    ],
                    'description' => [
                        'en_US' => 'Use a service provider with the same form of interface as the OpenAI API',
                        'en_US' => 'pleaseuseinterfaceand OpenAI API sameshapetypeservicequotient',
                    ],
                ]),
                'remark' => 'support OpenAI API shapetype',
            ],
            // Amazon Bedrock - LLM
            [
                'id' => '771078297613344768',
                'name' => 'Amazon Bedrock',
                'provider_code' => 'AWSBedrock',
                'description' => 'Amazon Bedrock isAmazon AWS provideoneitemservice,focusatforenterpriseprovidefirstenter AI languagemodelandvisualmodel.itsmodelfamilyinclude Anthropic  Claude systemcolumn,Meta  Llama 3.1 systemcolumnetc,coverfromlightquantityleveltohighperformancemultipletypechoose,supporttextgenerate,conversation,graphlikehandleetcmultipletypetask,fituseatdifferentscaleandrequiremententerpriseapplication.',
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
                        'en_US' => 'Amazon Bedrock',
                    ],
                    'description' => [
                        'en_US' => "Amazon Bedrock is a service offered by Amazon AWS that focuses on advanced AI language models and visual models for businesses. Its model family, including Anthropic's Claude series and Meta's Llama 3.1 series, covers a variety of options from lightweight to high-performance, supporting a variety of tasks such as text generation, dialogue, image processing, and suitable for enterprise applications of different sizes and needs.",
                        'en_US' => 'Amazon Bedrock isAmazon AWS provideoneitemservice,focusatforenterpriseprovidefirstenter AI languagemodelandvisualmodel.itsmodelfamilyinclude Anthropic  Claude systemcolumn,Meta  Llama 3.1 systemcolumnetc,coverfromlightquantityleveltohighperformancemultipletypechoose,supporttextgenerate,conversation,graphlikehandleetcmultipletypetask,fituseatdifferentscaleandrequiremententerpriseapplication.',
                    ],
                ]),
                'remark' => '',
            ],
            // Microsoft Azure - VLM
            [
                'id' => '792047422971920384',
                'name' => 'Microsoft Azure',
                'provider_code' => 'MicrosoftAzure',
                'description' => 'providemultipletypefirstenterAImodel,includeGPT-3.5andmostnewGPT-4systemcolumn,supportmultipletypedatatypeandcomplextask,dedicateatsecurity,canrelyandcancontinueAIresolvesolution.',
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
                        'en_US' => 'Microsoft Azure',
                    ],
                    'description' => [
                        'en_US' => 'Azure offers a variety of advanced AI models, including GPT-3.5 and the latest GPT-4 series, supporting multiple data types and complex tasks, and is committed to providing safe, reliable and sustainable AI solutions.',
                        'en_US' => 'Azure providemultipletypefirstenterAImodel,includeGPT-3.5andmostnewGPT-4systemcolumn,supportmultipletypedatatypeandcomplextask,dedicateatsecurity,canrelyandcancontinueAIresolvesolution.',
                    ],
                ]),
                'remark' => '',
            ],
            // Qwen - VLM
            [
                'id' => '792047422971920385',
                'name' => 'Qwen',
                'provider_code' => 'Qwen',
                'description' => 'providecommonusegraphlikegeneratemodel,supportmultipletypeartstyle,particularlyexcellongcomplextextrender,especiallyismiddleEnglishtextrender.',
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
                        'en_US' => 'prefixwithincloudhundredrefine',
                    ],
                    'description' => [
                        'en_US' => 'It provides a universal image generation model, supports multiple artistic styles, and is particularly skilled at complex text rendering, especially in both Chinese and English text rendering.',
                        'en_US' => 'providecommonusegraphlikegeneratemodel,supportmultipletypeartstyle,particularlyexcellongcomplextextrender,especiallyismiddleEnglishtextrender.',
                    ],
                ]),
                'remark' => '',
            ],
            // Google Cloud - VLM
            [
                'id' => '792047422971920386',
                'name' => 'Google Cloud',
                'provider_code' => 'Google-Image',
                'description' => 'provide Gemini 2.5 Flash Image (Nano Banana) graphlikegeneratemodel,haveroleonetopropertyhigh,precisegraphlikeeditetc.',
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
                        'en_US' => 'Google Cloud',
                    ],
                    'description' => [
                        'en_US' => 'Gemini 2.5 Flash Image (Nano Banana) image generation model is provided, featuring high character consistency and precise image editing, etc.',
                        'en_US' => 'provide Gemini 2.5 Flash Image (Nano Banana) graphlikegeneratemodel,haveroleonetopropertyhigh,precisegraphlikeeditetc.',
                    ],
                ]),
                'remark' => '',
            ],
            // VolcengineArk - VLM
            [
                'id' => '792047422971920387',
                'name' => 'VolcengineArk',
                'provider_code' => 'VolcengineArk',
                'description' => 'VolcanoengineArk',
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
                        'en_US' => 'Volcanoengine(Ark)',
                    ],
                    'description' => [
                        'en_US' => '',
                        'en_US' => '',
                    ],
                ]),
                'remark' => '',
            ],
        ];
    }
}
