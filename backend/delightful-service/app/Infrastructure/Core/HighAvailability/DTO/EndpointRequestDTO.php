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
 * 接入点request DTO.
 * 用于封装 getAvailableEndpoint method的requestparameter.
 */
class EndpointRequestDTO extends AbstractDTO
{
    /**
     * 端点type/modelID.
     */
    protected string $endpointType;

    /**
     * organization代码.
     */
    protected string $orgCode;

    /**
     * service提供商 (可选).
     */
    protected ?string $provider = null;

    /**
     * 端点name (可选).
     */
    protected ?string $endpointName = null;

    /**
     * 上次选择的接入点ID (可选).
     * 用于conversation延续等场景，优先选择上次use的接入点.
     */
    protected ?string $lastSelectedEndpointId = null;

    /**
     * 负载均衡type.
     */
    protected LoadBalancingType $balancingType = LoadBalancingType::RANDOM;

    /**
     * 统计级别.
     */
    protected StatisticsLevel $statisticsLevel = StatisticsLevel::LEVEL_MINUTE;

    /**
     * 统计timerange（分钟）.
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
        $this->timeRange = max(1, $timeRange); // ensuretimerangeat least为1分钟
        return $this;
    }

    /**
     * check是否有上次选择的接入点ID.
     */
    public function hasLastSelectedEndpointId(): bool
    {
        return $this->lastSelectedEndpointId !== null && $this->lastSelectedEndpointId !== '';
    }

    /**
     * 从array数据create实例的便捷method.
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
