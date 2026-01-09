<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\CircuitBreakerStatus;

/**
 * 用于在业务流程中同时保存业务ID和高可用组件接入点databaseID.
 */
class EndpointDTO extends AbstractDTO
{
    /**
     * getEndpointList 接口return的 id。
     * 不同的业务含义不同。对于model网关来说，这里的 id 是 service_provider_models table的 id。
     * 前端可能不支持 bigint，所以这里用 string.
     */
    protected ?string $businessId = null;

    /**
     * database接入点ID（高可用tableprimary key）.
     */
    protected ?string $endpointId = null;

    /**
     * 接入点type.
     */
    protected string $type;

    /**
     * 提供商.
     */
    protected ?string $provider = null;

    /**
     * 接入点name.
     */
    protected string $name;

    /**
     * configurationinfo.
     */
    protected ?string $config = null;

    /**
     * 资源的消耗的 id list. 一次请求可能会消耗多个资源。
     * @var null|string[]
     */
    protected ?array $resources = null;

    /**
     * 接入点是否启用.
     */
    protected bool $enabled = true;

    /**
     * 熔断status.
     */
    protected CircuitBreakerStatus $circuitBreakerStatus;

    /**
     * 负载均衡权重(0-100).
     */
    protected ?int $loadBalancingWeight = null;

    /**
     * createtime.
     */
    protected string $createdAt;

    /**
     * updatetime.
     */
    protected string $updatedAt;

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    // 原有 EndpointDTO 的所有method
    public function getResources(): ?array
    {
        return $this->resources ?? null;
    }

    public function setResources(null|array|string $resources): static
    {
        if (is_string($resources)) {
            $resources = json_decode($resources, true);
        }
        $this->resources = $resources;
        return $this;
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(?string $value): static
    {
        $this->config = $value;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt ?? '';
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt ?? '';
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCircuitBreakerStatus(): CircuitBreakerStatus
    {
        return $this->circuitBreakerStatus;
    }

    public function setCircuitBreakerStatus(CircuitBreakerStatus|string $circuitBreakerStatus): void
    {
        if (is_string($circuitBreakerStatus)) {
            $this->circuitBreakerStatus = CircuitBreakerStatus::fromString($circuitBreakerStatus);
            return;
        }
        $this->circuitBreakerStatus = $circuitBreakerStatus;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * set接入点是否启用.
     * @param bool|int|string $enabled 可传入布尔value、整数或string
     */
    public function setEnabled(bool|int|string $enabled): static
    {
        if (is_numeric($enabled)) {
            $this->enabled = (bool) $enabled;
        } else {
            $this->enabled = $enabled;
        }
        return $this;
    }

    // 新增的增强fieldmethod
    /**
     * getdatabase接入点ID.
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * setdatabase接入点ID.
     */
    public function setEndpointId(null|int|string $endpointId): static
    {
        if (is_int($endpointId)) {
            $endpointId = (string) $endpointId;
        }
        $this->endpointId = $endpointId;
        return $this;
    }

    /**
     * get业务ID.
     */
    public function getBusinessId(): ?string
    {
        return $this->businessId;
    }

    /**
     * set业务ID.
     */
    public function setBusinessId(null|int|string $businessId): static
    {
        if (is_int($businessId)) {
            $businessId = (string) $businessId;
        }
        $this->businessId = $businessId;
        return $this;
    }

    /**
     * check是否存在于database中.
     */
    public function hasEndpointId(): bool
    {
        return $this->endpointId !== null && $this->endpointId !== '';
    }

    /**
     * get负载均衡权重.
     */
    public function getLoadBalancingWeight(): ?int
    {
        return $this->loadBalancingWeight;
    }

    /**
     * set负载均衡权重.
     */
    public function setLoadBalancingWeight(?int $loadBalancingWeight): static
    {
        $this->loadBalancingWeight = $loadBalancingWeight;
        return $this;
    }
}
