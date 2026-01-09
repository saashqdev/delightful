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
 * 目front仅haveorganization隔离
 * 显type传入，防止隐type传入，导致not知道哪些placeneed做隔离.
 */
class DataIsolation extends AbstractObject
{
    /**
     * whenfront账numberid. 所have账number统one注意privacyprotected,nottothethird-partyreturn.
     */
    protected string $currentDelightfulId = '';

    /**
     * whenfrontorganizationencoding.
     */
    protected string $currentOrganizationCode = '';

    /**
     * whenfrontorganizationdownuserid.
     */
    protected ?string $currentUserId = null;

    /**
     * whenfrontenvironment.
     */
    protected string $environment;

    /**
     * whenfrontapplicationid.
     */
    protected ?string $currentAppId = null;

    /**
     * thethird-party平台organizationencoding.
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
