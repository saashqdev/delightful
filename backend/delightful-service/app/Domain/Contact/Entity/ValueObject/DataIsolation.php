<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Infrastructure\Core\AbstractObject;

/**
 * data隔离 SaaS化
 * 目前仅有organization隔离
 * 显式传入，防止隐式传入，导致不知道哪些地方need做隔离.
 */
class DataIsolation extends AbstractObject
{
    /**
     * when前的账号id. 所有账号统一注意隐私保护,不对第third-partyreturn.
     */
    protected string $currentDelightfulId = '';

    /**
     * when前的organization编码.
     */
    protected string $currentOrganizationCode = '';

    /**
     * when前的organization下的userid.
     */
    protected ?string $currentUserId = null;

    /**
     * when前环境.
     */
    protected string $environment;

    /**
     * when前应用id.
     */
    protected ?string $currentAppId = null;

    /**
     * 第third-party平台organization编码.
     */
    protected ?string $thirdPartyOrganizationCode = null;

    protected ?UserType $userType = null;

    protected ?string $language = null;

    public function __construct(?array $data = null)
    {
        if (isset($data['user_type']) && is_numeric($data['user_type'])) {
            $data['user_type'] = UserType::from((int) $data['user_type']);
        }
        $this->environment = app_env();
        parent::__construct($data);
    }

    public function getUserType(): ?UserType
    {
        if (empty($this->userType) && ! empty($this->getCurrentUserId())) {
            $userEntity = di(DelightfulUserRepositoryInterface::class)->getUserById($this->getCurrentUserId());
            $userEntity && $this->setUserType($userEntity->getUserType());
        }
        return $this->userType;
    }

    public function setUserType(?UserType $userType): void
    {
        $this->userType = $userType;
    }

    public function getThirdPartyOrganizationCode(): ?string
    {
        return $this->thirdPartyOrganizationCode;
    }

    public function setThirdPartyOrganizationCode(?string $thirdPartyOrganizationCode): void
    {
        $this->thirdPartyOrganizationCode = $thirdPartyOrganizationCode;
    }

    public static function create(string $currentOrganizationCode = '', string $userId = ''): self
    {
        $static = new self();
        $static->setCurrentOrganizationCode(currentOrganizationCode: $currentOrganizationCode);
        $static->setCurrentUserId(currentUserId: $userId);
        return $static;
    }

    public function getCurrentDelightfulId(): string
    {
        return $this->currentDelightfulId;
    }

    public function setCurrentDelightfulId(string $currentDelightfulId): void
    {
        $this->currentDelightfulId = $currentDelightfulId;
    }

    public function getCurrentAppId(): ?string
    {
        return $this->currentAppId;
    }

    public function setCurrentAppId(?string $currentAppId): void
    {
        $this->currentAppId = $currentAppId;
    }

    public function getCurrentOrganizationCode(): string
    {
        return $this->currentOrganizationCode;
    }

    public function setCurrentOrganizationCode(string $currentOrganizationCode): void
    {
        $this->currentOrganizationCode = $currentOrganizationCode;
    }

    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    public function setCurrentUserId(?string $currentUserId): void
    {
        $this->currentUserId = $currentUserId;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    public static function simpleMake(string $currentOrganizationCode, ?string $userId = null): DataIsolation
    {
        $dataIsolation = new DataIsolation();
        $dataIsolation->setCurrentOrganizationCode(currentOrganizationCode: $currentOrganizationCode);
        $dataIsolation->setCurrentUserId(currentUserId: $userId);
        return $dataIsolation;
    }

    public static function simpleMakeOnlyEnvironment(): DataIsolation
    {
        return new DataIsolation();
    }

    public function isEnable(): bool
    {
        return true;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }
}
