<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\Interface;

use App\Infrastructure\Core\HighAvailability\DTO\EndpointDTO;
use App\Infrastructure\Core\HighAvailability\DTO\EndpointRequestDTO;
use App\Infrastructure\Core\HighAvailability\DTO\EndpointResponseDTO;

interface HighAvailabilityInterface
{
    /**
     * Get available endpoint list.
     *
     * Query endpoint list from business side for load balancing and high availability selection
     *
     * @param string $endpointType Model ID
     * @param string $orgCode Organization code
     * @param null|string $provider Service provider, e.g., Microsoft | Volcano | Alibaba Cloud, optional
     * @param null|string $endpointName Endpoint name (optional), e.g., East US, Japan for Microsoft provider
     * @return EndpointDTO[] Endpoint list
     */
    public function getEndpointList(
        string $endpointType,
        string $orgCode,
        ?string $provider = null,
        ?string $endpointName = null
    ): array;

    /**
     * Get available endpoint.
     *
     * First query endpoint list from business side, then select the best performing endpoint based on load balancing algorithm and statistics
     * Selection criteria:
     * 1. Highest success rate
     * 2. Shortest response time
     * 3. If lastSelectedEndpointId is provided, prioritize that endpoint (for conversation continuation)
     *
     * @param EndpointRequestDTO $request 接入点requestparameter
     * @return null|EndpointDTO Available endpoint, returns null if no available endpoint
     */
    public function getAvailableEndpoint(EndpointRequestDTO $request): ?EndpointDTO;

    /**
     * record接入点的response并自动processsuccess/failedstatus，以及用于后续的dataanalyze。
     *
     * 该method将:
     * 1. recordrequeststatisticsdata
     * 2. according torequestsuccess或failedstatus自动触发熔断器feedback
     *
     * @param EndpointResponseDTO $response 接入点response实体
     */
    public function recordResponse(EndpointResponseDTO $response): bool;
}
