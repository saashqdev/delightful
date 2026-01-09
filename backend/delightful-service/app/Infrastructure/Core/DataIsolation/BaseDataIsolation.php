<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\DataIsolation;

use App\Infrastructure\Util\Context\CoContext;

class BaseDataIsolation implements DataIsolationInterface
{
    /**
     * 当前的organization编码.
     */
    private string $currentOrganizationCode;

    /**
     * 当前的userid.
     */
    private string $currentUserId;

    private string $delightfulId;

    /**
     * 当前环境 app_env().
     */
    private string $environment;

    private bool $enabled = true;

    /**
     * 多organization下的环境 ID.
     */
    private int $envId = 0;

    private ThirdPlatformDataIsolationManagerInterface $thirdPlatformDataIsolationManager;

    private string $thirdPlatformUserId;

    private string $thirdPlatformOrganizationCode;

    /**
     * 是否包含官方organization.
     */
    private bool $containOfficialOrganization = false;

    /**
     * 是否仅仅包含官方organization.
     */
    private bool $onlyOfficialOrganization = false;

    /**
     * 官方organizationcodes.
     */
    private array $officialOrganizationCodes = [];

    private SubscriptionManagerInterface $subscriptionManager;

    private array $lazyFunctions = [];

    public function __construct(string $currentOrganizationCode = '', string $userId = '', string $delightfulId = '')
    {
        $this->environment = app_env();
        $this->currentOrganizationCode = $currentOrganizationCode;
        $this->currentUserId = $userId;
        $this->delightfulId = $delightfulId;
        $this->thirdPlatformDataIsolationManager = \Hyperf\Support\make(ThirdPlatformDataIsolationManagerInterface::class);
        $this->subscriptionManager = \Hyperf\Support\make(SubscriptionManagerInterface::class);

        if (config('office_organization')) {
            // 目前只有 1 个官方organization
            $this->officialOrganizationCodes = [config('office_organization')];
        }
    }

    public static function createByBaseDataIsolation(BaseDataIsolation $baseDataIsolation): static
    {
        /* @phpstan-ignore-next-line */
        $self = new static(
            currentOrganizationCode: $baseDataIsolation->getCurrentOrganizationCode(),
            userId: $baseDataIsolation->getCurrentUserId(),
            delightfulId: $baseDataIsolation->getDelightfulId()
        );
        $self->extends($baseDataIsolation);
        return $self;
    }

    public function getThirdPlatformDataIsolationManager(): ThirdPlatformDataIsolationManagerInterface
    {
        return $this->thirdPlatformDataIsolationManager;
    }

    public function extends(DataIsolationInterface $parentDataIsolation): void
    {
        $this->currentOrganizationCode = $parentDataIsolation->getCurrentOrganizationCode();
        $this->currentUserId = $parentDataIsolation->getCurrentUserId();
        $this->delightfulId = $parentDataIsolation->getDelightfulId();
        $this->envId = $parentDataIsolation->getEnvId();
        $this->enabled = $parentDataIsolation->isEnable();
        $this->subscriptionManager = $parentDataIsolation->getSubscriptionManager();

        $this->thirdPlatformOrganizationCode = $parentDataIsolation->getThirdPlatformOrganizationCode();
        $this->thirdPlatformUserId = $parentDataIsolation->getThirdPlatformUserId();
        $this->thirdPlatformDataIsolationManager->extends($parentDataIsolation);
    }

    public function getOrganizationCodes(): array
    {
        if ($this->onlyOfficialOrganization) {
            return $this->officialOrganizationCodes;
        }
        if (! empty($this->currentOrganizationCode)) {
            $organizationCodes = [$this->currentOrganizationCode];
        } else {
            $organizationCodes = [];
        }
        if ($this->containOfficialOrganization) {
            $organizationCodes = array_merge($organizationCodes, $this->officialOrganizationCodes);
        }
        return array_unique($organizationCodes);
    }

    public function getCurrentOrganizationCode(): string
    {
        return $this->currentOrganizationCode;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getCurrentUserId(): string
    {
        return $this->currentUserId;
    }

    public function isEnable(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function disabled(): static
    {
        $this->enabled = false;
        return $this;
    }

    public function getDelightfulId(): string
    {
        return $this->delightfulId;
    }

    public function setDelightfulId(string $delightfulId): static
    {
        $this->delightfulId = $delightfulId;
        return $this;
    }

    public function getEnvId(): int
    {
        return $this->envId;
    }

    public function setEnvId(int $envId): static
    {
        $this->envId = $envId;
        return $this;
    }

    public function setCurrentUserId(string $currentUserId): static
    {
        $this->currentUserId = $currentUserId;
        return $this;
    }

    public function getThirdPlatformUserId(): string
    {
        return $this->thirdPlatformUserId ?? '';
    }

    public function setThirdPlatformUserId(string $thirdPlatformUserId): static
    {
        $this->thirdPlatformUserId = $thirdPlatformUserId;
        return $this;
    }

    public function getThirdPlatformOrganizationCode(): string
    {
        return $this->thirdPlatformOrganizationCode ?? '';
    }

    public function setThirdPlatformOrganizationCode(string $thirdPlatformOrganizationCode): static
    {
        $this->thirdPlatformOrganizationCode = $thirdPlatformOrganizationCode;
        return $this;
    }

    public function setCurrentOrganizationCode(string $currentOrganizationCode): static
    {
        $this->currentOrganizationCode = $currentOrganizationCode;
        return $this;
    }

    public function isContainOfficialOrganization(): bool
    {
        return $this->containOfficialOrganization;
    }

    public function setContainOfficialOrganization(bool $containOfficialOrganization): void
    {
        $this->containOfficialOrganization = $containOfficialOrganization;
    }

    public function isOnlyOfficialOrganization(): bool
    {
        return $this->onlyOfficialOrganization;
    }

    public function setOnlyOfficialOrganization(bool $onlyOfficialOrganization): void
    {
        $this->onlyOfficialOrganization = $onlyOfficialOrganization;
    }

    public function getOfficialOrganizationCodes(): array
    {
        return $this->officialOrganizationCodes;
    }

    public function getOfficialOrganizationCode(): string
    {
        return $this->officialOrganizationCodes[0] ?? '';
    }

    public function setOfficialOrganizationCodes(array $officialOrganizationCodes): void
    {
        $this->officialOrganizationCodes = $officialOrganizationCodes;
    }

    public function isOfficialOrganization(): bool
    {
        return in_array($this->currentOrganizationCode, $this->officialOrganizationCodes, true);
    }

    public function getLanguage(): string
    {
        return CoContext::getLanguage();
    }

    public function getSubscriptionManager(): SubscriptionManagerInterface
    {
        if (isset($this->lazyFunctions['initSubscription'])) {
            $lazyFun = $this->lazyFunctions['initSubscription'];
            unset($this->lazyFunctions['initSubscription']);
            $lazyFun();
        }
        return $this->subscriptionManager;
    }

    public function addLazyFunction(string $key, callable $function): void
    {
        $this->lazyFunctions[$key] = $function;
    }
}
