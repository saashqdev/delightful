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
     * generatestandard化端pointtype标识.
     *
     * @param HighAvailabilityAppType $appType 高canuseapplicationtype
     * @param string $modelId modelID
     * @return string standard化端pointtype标识
     */
    public static function generateEndpointType(HighAvailabilityAppType $appType, string $modelId): string
    {
        return $appType->value . DelimiterType::HIGH_AVAILABILITY->value . $modelId;
    }

    /**
     * fromformat化端pointtype标识middlealso原originalmodelID.
     *
     * @param string $formattedModelId maybecontainformat化front缀modelID
     * @return string originalmodelID
     */
    public static function extractOriginalModelId(string $formattedModelId): string
    {
        // 遍历所have HighAvailabilityAppType 枚举value
        foreach (HighAvailabilityAppType::cases() as $appType) {
            $prefix = $appType->value . DelimiterType::HIGH_AVAILABILITY->value;

            // ifmatchtofront缀，then移exceptfront缀returnoriginal modelId
            if (str_starts_with($formattedModelId, $prefix)) {
                return substr($formattedModelId, strlen($prefix));
            }
        }

        // ifnothavematchtoanyfront缀，then直接returnoriginalvalue
        return $formattedModelId;
    }

    /**
     * checkgive定stringwhetherforformat化端pointtype标识.
     *
     * @param string $modelId 待checkmodelID
     * @return bool whetherforformat化端pointtype标识
     */
    public static function isFormattedEndpointType(string $modelId): bool
    {
        // 遍历所have HighAvailabilityAppType 枚举value
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
