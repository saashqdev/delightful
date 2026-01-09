<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Assembler;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;
use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Repository\Persistence\Model\ProviderConfigModel;
use App\Infrastructure\Util\Aes\AesUtil;
use Hyperf\Codec\Json;
use Hyperf\Contract\TranslatorInterface;

use function Hyperf\Config\config;

class ProviderConfigAssembler
{
    public static function modelToEntity(ProviderConfigModel $model): ProviderConfigEntity
    {
        return new ProviderConfigEntity($model->toArray());
    }

    public static function toEntity(array $serviceProviderConfig): ProviderConfigEntity
    {
        [$preparedConfig, $decodeConfig] = self::prepareServiceProviderConfig($serviceProviderConfig);
        $preparedConfig['config'] = new ProviderConfigItem($decodeConfig);
        $translator = di(TranslatorInterface::class);
        $serviceProviderConfigEntity = new ProviderConfigEntity($preparedConfig);
        $serviceProviderConfigEntity->i18n($translator->getLocale());
        return $serviceProviderConfigEntity;
    }

    public static function toEntities(array $serviceProviderConfigs): array
    {
        if (empty($serviceProviderConfigs)) {
            return [];
        }
        $configEntities = [];
        foreach ($serviceProviderConfigs as $serviceProviderConfig) {
            $configEntities[] = self::toEntity((array) $serviceProviderConfig);
        }
        return $configEntities;
    }

    /**
     * willservicequotientconfigurationarrayconvertfor DTO list,containcomplete provider info.
     * @param array $serviceProviderConfigs servicequotientconfigurationarray
     * @param array $providerMap provider ID to provider datamapping
     * @return ProviderConfigDTO[]
     */
    public static function toDTOListWithProviders(array $serviceProviderConfigs, array $providerMap): array
    {
        if (empty($serviceProviderConfigs)) {
            return [];
        }
        $configDTOs = [];
        foreach ($serviceProviderConfigs as $serviceProviderConfig) {
            $configDTOs[] = self::toDTOWithProvider((array) $serviceProviderConfig, $providerMap);
        }
        return $configDTOs;
    }

    /**
     * willservicequotientconfigurationconvertfor DTO,containcomplete provider info.
     * @param array $serviceProviderConfig servicequotientconfigurationdata
     * @param array $providerMap provider ID to provider datamapping
     */
    public static function toDTOWithProvider(array $serviceProviderConfig, array $providerMap): ProviderConfigDTO
    {
        [$preparedConfig, $decodeConfig] = self::prepareServiceProviderConfig($serviceProviderConfig);
        $preparedConfig['config'] = $decodeConfig;
        // nospecialstatementnotprocess
        $preparedConfig['decryptedConfig'] = null;

        $translator = di(TranslatorInterface::class);
        $locale = $translator->getLocale();

        // from providerMap middlegettoshould provider info
        $providerId = $serviceProviderConfig['service_provider_id'];
        if (isset($providerMap[$providerId])) {
            $provider = $providerMap[$providerId];

            $translate = Json::decode($provider['translate']);
            // merge provider infotoconfigurationmiddle
            $preparedConfig['name'] = self::getTranslatedText($translate['name'] ?? [], $locale);
            $preparedConfig['description'] = self::getTranslatedText($translate['description'] ?? [], $locale);
            $preparedConfig['icon'] = $provider['icon'] ?? '';
            $preparedConfig['provider_type'] = $provider['provider_type'] ?? null;
            $preparedConfig['category'] = $provider['category'] ?? null;
            $preparedConfig['provider_code'] = $provider['provider_code'] ?? null;
            $preparedConfig['is_models_enable'] = $provider['is_models_enable'] ?? true;

            // directlyuse provider translateinfo(config middleonly ak,sk etcconfiguration,nothavetranslatedata)
            if (! empty($provider['translate'])) {
                $providerTranslate = is_string($provider['translate'])
                    ? Json::decode($provider['translate'])
                    : $provider['translate'];
                $preparedConfig['translate'] = $providerTranslate;
            }
        }

        return new ProviderConfigDTO($preparedConfig);
    }

    /**
     * @param $configEntities ProviderConfigEntity[]
     */
    public static function toArrays(array $configEntities): array
    {
        if (empty($configEntities)) {
            return [];
        }
        $result = [];
        foreach ($configEntities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }

    public static function decodeConfig(string $config, string $salt): array
    {
        $decode = AesUtil::decode(self::_getAesKey($salt), $config);
        if (! $decode) {
            return [];
        }
        return Json::decode($decode);
    }

    /**
     * toconfigurationdataconductencoding(JSONencoding + AESencrypt).
     */
    public static function encodeConfig(array $config, string $salt): string
    {
        $jsonEncoded = Json::encode($config);
        return AesUtil::encode(self::_getAesKey($salt), $jsonEncoded);
    }

    /**
     * preprocessservicequotientconfigurationdata,extractcommonlogic.
     * @return array [$preparedConfig, $decodeConfig]
     */
    private static function prepareServiceProviderConfig(array $serviceProviderConfig): array
    {
        $decodeConfig = $serviceProviderConfig['config'];
        if (is_string($serviceProviderConfig['config'])) {
            $decodeConfig = self::decodeConfig($serviceProviderConfig['config'], (string) $serviceProviderConfig['id']);
        }

        // setdefaulttranslate
        if (empty($serviceProviderConfig['translate'])) {
            $serviceProviderConfig['translate'] = [];
        }

        return [$serviceProviderConfig, $decodeConfig];
    }

    private static function _getAesKey(string $salt): string
    {
        return config('service_provider.model_aes_key') . $salt;
    }

    /**
     * Get translated text with fallback support.
     */
    private static function getTranslatedText(array $translations, string $locale): string
    {
        if (! empty($translations[$locale] ?? '')) {
            return $translations[$locale];
        }
        if (! empty($translations['zh_CN'] ?? '')) {
            return $translations['zh_CN'];
        }
        if (! empty($translations['en_US'] ?? '')) {
            return $translations['en_US'];
        }
        return '';
    }
}
