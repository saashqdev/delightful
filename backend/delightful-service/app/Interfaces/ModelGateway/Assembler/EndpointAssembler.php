<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\ModelGateway\Assembler;

use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Infrastructure\Core\HighAvailability\DTO\EndpointDTO;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\CircuitBreakerStatus;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\DelimiterType;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\HighAvailabilityAppType;

class EndpointAssembler
{
    /**
     * generatestandard化的端点type标识.
     *
     * @param HighAvailabilityAppType $appType 高可用applicationtype
     * @param string $modelId modelID
     * @return string standard化的端点type标识
     */
    public static function generateEndpointType(HighAvailabilityAppType $appType, string $modelId): string
    {
        return $appType->value . DelimiterType::HIGH_AVAILABILITY->value . $modelId;
    }

    /**
     * 从format化的端点type标识中还原original的modelID.
     *
     * @param string $formattedModelId 可能containformat化前缀的modelID
     * @return string original的modelID
     */
    public static function extractOriginalModelId(string $formattedModelId): string
    {
        // 遍历所有的 HighAvailabilityAppType 枚举value
        foreach (HighAvailabilityAppType::cases() as $appType) {
            $prefix = $appType->value . DelimiterType::HIGH_AVAILABILITY->value;

            // 如果匹配到前缀，则移除前缀returnoriginal modelId
            if (str_starts_with($formattedModelId, $prefix)) {
                return substr($formattedModelId, strlen($prefix));
            }
        }

        // 如果没有匹配到任何前缀，则直接returnoriginalvalue
        return $formattedModelId;
    }

    /**
     * check给定的string是否为format化的端点type标识.
     *
     * @param string $modelId 待check的modelID
     * @return bool 是否为format化的端点type标识
     */
    public static function isFormattedEndpointType(string $modelId): bool
    {
        // 遍历所有的 HighAvailabilityAppType 枚举value
        foreach (HighAvailabilityAppType::cases() as $appType) {
            $prefix = $appType->value . DelimiterType::HIGH_AVAILABILITY->value;
            if (str_starts_with($modelId, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert multiple ProviderModelEntity to EndpointDTO array.
     *
     * @param ProviderModelEntity[] $providerModelEntities Service provider model entity array
     * @param HighAvailabilityAppType $appType High availability application type
     * @return EndpointDTO[]
     */
    public static function toEndpointEntities(array $providerModelEntities, HighAvailabilityAppType $appType): array
    {
        if (empty($providerModelEntities)) {
            return [];
        }
        $endpoints = [];
        foreach ($providerModelEntities as $providerModelEntity) {
            $endpoint = new EndpointDTO();
            // Set identification information to uniquely identify the endpoint in high availability service
            $endpoint->setBusinessId($providerModelEntity->getId());
            $endpoint->setType(self::generateEndpointType($appType, $providerModelEntity->getModelId()));
            $endpoint->setName($providerModelEntity->getModelVersion());
            $endpoint->setProvider((string) $providerModelEntity->getServiceProviderConfigId());
            $endpoint->setCircuitBreakerStatus(CircuitBreakerStatus::CLOSED);
            $endpoint->setEnabled(true);
            $endpoint->setLoadBalancingWeight($providerModelEntity->getLoadBalancingWeight());
            $endpoints[] = $endpoint;
        }

        return $endpoints;
    }
}
