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
     * generatestandardclientpointtypeidentifier.
     *
     * @param HighAvailabilityAppType $appType highcanuseapplicationtype
     * @param string $modelId modelID
     * @return string standardclientpointtypeidentifier
     */
    public static function generateEndpointType(HighAvailabilityAppType $appType, string $modelId): string
    {
        return $appType->value . DelimiterType::HIGH_AVAILABILITY->value . $modelId;
    }

    /**
     * fromformatclientpointtypeidentifiermiddlealsooriginaloriginalmodelID.
     *
     * @param string $formattedModelId maybecontainformat化frontsuffixmodelID
     * @return string originalmodelID
     */
    public static function extractOriginalModelId(string $formattedModelId): string
    {
        // traverse have HighAvailabilityAppType enumvalue
        foreach (HighAvailabilityAppType::cases() as $appType) {
            $prefix = $appType->value . DelimiterType::HIGH_AVAILABILITY->value;

            // ifmatchtofrontsuffix,thenmoveexceptfrontsuffixreturnoriginal modelId
            if (str_starts_with($formattedModelId, $prefix)) {
                return substr($formattedModelId, strlen($prefix));
            }
        }

        // ifnothavematchtoanyfrontsuffix,thendirectlyreturnoriginalvalue
        return $formattedModelId;
    }

    /**
     * checkgivesetstringwhetherforformatclientpointtypeidentifier.
     *
     * @param string $modelId pendingcheckmodelID
     * @return bool whetherforformatclientpointtypeidentifier
     */
    public static function isFormattedEndpointType(string $modelId): bool
    {
        // traverse have HighAvailabilityAppType enumvalue
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
