<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Infrastructure\Core\HighAvailability\ValueObject\LoadBalancingType;
use App\Infrastructure\Core\HighAvailability\ValueObject\StatisticsLevel;

/**
 * 接入pointrequest DTO.
 * useatencapsulation getAvailableEndpoint methodrequestparameter.
 */
class EndpointRequestDTO extends AbstractDTO
{
    /**
     * 端pointtype/modelID.
     */
    protected string $endpointType;

    /**
     * organizationcode.
     */
    protected string $orgCode;

    /**
     * serviceprovidequotient (optional).
     */
    protected ?string $provider = null;

    /**
     * 端pointname (optional).
     */
    protected ?string $endpointName = null;

    /**
     * uptimechoose接入pointID (optional).
     * useatconversation延续etc场景，优先chooseuptimeuse接入point.
     */
    protected ?string $lastSelectedEndpointId = null;

    /**
     * 负载均衡type.
     */
    protected LoadBalancingType $balancingType = LoadBalancingType::RANDOM;

    /**
     * statisticslevel别.
     */
    protected StatisticsLevel $statisticsLevel = StatisticsLevel::LEVEL_MINUTE;

    /**
     * statisticstimerange（minute钟）.
     */
    protected int $timeRange = 30;

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getEndpointType(): string
    {
        return $this->endpointType ?? '';
    }

    public function setEndpointType(string $endpointType): static
    {
        $this->endpointType = $endpointType;
        return $this;
    }

    public function getOrgCode(): string
    {
        return $this->orgCode ?? '';
    }

    public function setOrgCode(string $orgCode): static
    {
        $this->orgCode = $orgCode;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getEndpointName(): ?string
    {
        return $this->endpointName;
    }

    public function setEndpointName(?string $endpointName): static
    {
        $this->endpointName = $endpointName;
        return $this;
    }

    public function getLastSelectedEndpointId(): ?string
    {
        return $this->lastSelectedEndpointId;
    }

    public function setLastSelectedEndpointId(?string $lastSelectedEndpointId): static
    {
        $this->lastSelectedEndpointId = $lastSelectedEndpointId;
        return $this;
    }

    public function getBalancingType(): LoadBalancingType
    {
        return $this->balancingType;
    }

    public function setBalancingType(LoadBalancingType $balancingType): static
    {
        $this->balancingType = $balancingType;
        return $this;
    }

    public function getStatisticsLevel(): StatisticsLevel
    {
        return $this->statisticsLevel;
    }

    public function setStatisticsLevel(StatisticsLevel $statisticsLevel): static
    {
        $this->statisticsLevel = $statisticsLevel;
        return $this;
    }

    public function getTimeRange(): int
    {
        return $this->timeRange;
    }

    public function setTimeRange(int $timeRange): static
    {
        $this->timeRange = max(1, $timeRange); // ensuretimerangeat leastfor1minute钟
        return $this;
    }

    /**
     * checkwhetherhaveuptimechoose接入pointID.
     */
    public function hasLastSelectedEndpointId(): bool
    {
        return $this->lastSelectedEndpointId !== null && $this->lastSelectedEndpointId !== '';
    }

    /**
     * fromarraydatacreateinstance便捷method.
     */
    public static function create(
        string $endpointType,
        string $orgCode,
        ?string $provider = null,
        ?string $endpointName = null,
        ?string $lastSelectedEndpointId = null,
        LoadBalancingType $balancingType = LoadBalancingType::RANDOM,
        StatisticsLevel $statisticsLevel = StatisticsLevel::LEVEL_MINUTE,
        int $timeRange = 30
    ): self {
        return new self([
            'endpointType' => $endpointType,
            'orgCode' => $orgCode,
            'provider' => $provider,
            'endpointName' => $endpointName,
            'lastSelectedEndpointId' => $lastSelectedEndpointId,
            'balancingType' => $balancingType,
            'statisticsLevel' => $statisticsLevel,
            'timeRange' => $timeRange,
        ]);
    }
}
