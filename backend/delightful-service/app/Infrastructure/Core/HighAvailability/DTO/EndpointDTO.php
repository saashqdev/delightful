<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\CircuitBreakerStatus;

/**
 * useatin业务processmiddlemeanwhilesave业务IDand高canusegroupitem接入pointdatabaseID.
 */
class EndpointDTO extends AbstractDTO
{
    /**
     * getEndpointList interfacereturn id。
     * different业务含义different。toatmodel网closecome说，thiswithin id is service_provider_models table id。
     * front端maybenot supported bigint，所bythiswithinuse string.
     */
    protected ?string $businessId = null;

    /**
     * database接入pointID（高canusetableprimary key）.
     */
    protected ?string $endpointId = null;

    /**
     * 接入pointtype.
     */
    protected string $type;

    /**
     * 提供quotient.
     */
    protected ?string $provider = null;

    /**
     * 接入pointname.
     */
    protected string $name;

    /**
     * configurationinfo.
     */
    protected ?string $config = null;

    /**
     * resourceconsume id list. onetimerequestmaybewillconsume多resource。
     * @var null|string[]
     */
    protected ?array $resources = null;

    /**
     * 接入pointwhetherenable.
     */
    protected bool $enabled = true;

    /**
     * circuit breakstatus.
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

    // 原have EndpointDTO 所havemethod
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
     * set接入pointwhetherenable.
     * @param bool|int|string $enabled can传入布尔value、integerorstring
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

    // newenhancefieldmethod
    /**
     * getdatabase接入pointID.
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * setdatabase接入pointID.
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
     * checkwhether存inatdatabasemiddle.
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
