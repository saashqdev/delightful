<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Assembler;

use App\Application\Provider\DTO\AiAbilityDetailDTO;
use App\Application\Provider\DTO\AiAbilityListDTO;
use App\Domain\Provider\Entity\AiAbilityEntity;
use App\Infrastructure\Util\Aes\AesUtil;
use Hyperf\Codec\Json;

use function Hyperf\Config\config;

/**
 * AI能力装配器.
 */
class AiAbilityAssembler
{
    /**
     * AI能力Entityconvert为ListDTO.
     */
    public static function entityToListDTO(AiAbilityEntity $entity, string $locale = 'zh_CN'): AiAbilityListDTO
    {
        return new AiAbilityListDTO(
            id: (string) ($entity->getId()),
            code: $entity->getCode()->value,
            name: $entity->getLocalizedName($locale),
            description: $entity->getLocalizedDescription($locale),
            status: $entity->getStatus()->value,
        );
    }

    /**
     * AI能力Entityconvert为DetailDTO.
     */
    public static function entityToDetailDTO(AiAbilityEntity $entity, string $locale = 'zh_CN'): AiAbilityDetailDTO
    {
        // getoriginalconfiguration
        $config = $entity->getConfig();

        // 递归脱敏所有 api_key field（support任意嵌套结构）
        $maskedConfig = self::maskConfigRecursively($config);

        return new AiAbilityDetailDTO(
            id: $entity->getId() ?? 0,
            code: $entity->getCode()->value,
            name: $entity->getLocalizedName($locale),
            description: $entity->getLocalizedDescription($locale),
            icon: $entity->getIcon(),
            sortOrder: $entity->getSortOrder(),
            status: $entity->getStatus()->value,
            config: $maskedConfig,
        );
    }

    /**
     * AI能力Entitylistconvert为ListDTOlist.
     *
     * @param array<AiAbilityEntity> $entities
     * @return array<AiAbilityListDTO>
     */
    public static function entitiesToListDTOs(array $entities, string $locale = 'zh_CN'): array
    {
        $dtos = [];
        foreach ($entities as $entity) {
            $dtos[] = self::entityToListDTO($entity, $locale);
        }
        return $dtos;
    }

    /**
     * AI能力listDTO转array.
     *
     * @param array<AiAbilityListDTO> $dtos
     */
    public static function listDTOsToArray(array $dtos): array
    {
        $result = [];
        foreach ($dtos as $dto) {
            $result[] = $dto->toArray();
        }
        return $result;
    }

    /**
     * 对configurationdata进行decrypt.
     *
     * @param string $config encrypt的configurationstring
     * @param string $salt 盐value(通常是recordID)
     * @return array decrypt后的configurationarray
     */
    public static function decodeConfig(string $config, string $salt): array
    {
        $decode = AesUtil::decode(self::_getAesKey($salt), $config);
        if (! $decode) {
            return [];
        }
        return Json::decode($decode);
    }

    /**
     * 对configurationdata进行encoding(JSONencoding + AESencrypt).
     *
     * @param array $config configurationarray
     * @param string $salt 盐value(通常是recordID)
     * @return string encrypt后的configurationstring
     */
    public static function encodeConfig(array $config, string $salt): string
    {
        $jsonEncoded = Json::encode($config);
        return AesUtil::encode(self::_getAesKey($salt), $jsonEncoded);
    }

    /**
     * 递归脱敏configuration中的所有 api_key field.
     *
     * @param array $config configurationarray
     * @return array 脱敏后的configurationarray
     */
    private static function maskConfigRecursively(array $config): array
    {
        $result = [];

        foreach ($config as $key => $value) {
            // 如果是 api_key field，进行脱敏（前4后4）
            if ($key === 'api_key' && is_string($value) && ! empty($value)) {
                $result[$key] = self::maskApiKey($value);
            }
            // 如果是array，递归process
            elseif (is_array($value)) {
                $result[$key] = self::maskConfigRecursively($value);
            }
            // 其他value直接赋value
            else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 脱敏 API Key.
     *
     * @param string $apiKey original API Key
     * @param int $prefixLength 保留前几位（default3）
     * @param int $suffixLength 保留后几位（default3）
     * @return string 脱敏后的 API Key
     */
    private static function maskApiKey(string $apiKey, int $prefixLength = 4, int $suffixLength = 4): string
    {
        $length = mb_strlen($apiKey);
        $minLength = $prefixLength + $suffixLength;

        // 如果 key 太短，全部脱敏
        if ($length <= $minLength) {
            return str_repeat('*', $length);
        }

        // 显示前N位和后N位
        $prefix = mb_substr($apiKey, 0, $prefixLength);
        $suffix = mb_substr($apiKey, -$suffixLength);
        $maskLength = $length - $minLength;

        return $prefix . str_repeat('*', $maskLength) . $suffix;
    }

    /**
     * generateAESencryptkey(基础key + 盐value).
     *
     * @param string $salt 盐value
     * @return string AESkey
     */
    private static function _getAesKey(string $salt): string
    {
        return config('abilities.ai_ability_aes_key') . $salt;
    }
}
