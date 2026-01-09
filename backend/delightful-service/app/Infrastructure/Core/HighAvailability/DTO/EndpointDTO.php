<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\CircuitBreakerStatus;

/**
 * useatin业务process中meanwhilesave业务ID和高可usegroup件接入点databaseID.
 */
class EndpointDTO extends AbstractDTO
{
    /**
     * getEndpointList interfacereturn的 id。
     * different的业务含义different。对atmodel网关来说，这里的 id 是 service_provider_models table的 id。
     * 前端可能not supported bigint，所by这里use string.
     */
    protected ?string $businessId = null;

    /**
     * database接入点ID（高可usetableprimary key）.
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
     * 资源的consume的 id list. 一次request可能willconsume多个资源。
     * @var null|string[]
     */
    protected ?array $resources = null;

    /**
     * 接入点whetherenable.
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

    // 原have EndpointDTO 的所havemethod
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
     * set接入点whetherenable.
     * @param bool|int|string $enabled 可传入布尔value、整数orstring
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
     * checkwhether存inatdatabase中.
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
