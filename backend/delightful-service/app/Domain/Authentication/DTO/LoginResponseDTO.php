<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\DTO;

use App\Domain\Contact\Entity\AbstractEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Infrastructure\Core\Contract\Session\LoginResponseInterface;

class LoginResponseDTO extends AbstractEntity implements LoginResponseInterface
{
    protected string $delightfulId = '';

    protected string $delightfulUserId = '';

    protected string $organizationName = '';

    protected ?string $organizationLogo = null;

    protected string $delightfulOrganizationCode = '';

    protected string $thirdPlatformOrganizationCode = '';

    protected string $thirdPlatformUserId = '';

    protected ?PlatformType $thirdPlatformType = null;

    public function getDelightfulId(): string
    {
        return $this->delightfulId;
    }

    public function setDelightfulId(string $delightfulId): static
    {
        $this->delightfulId = $delightfulId;
        return $this;
    }

    public function getDelightfulUserId(): string
    {
        return $this->delightfulUserId;
    }

    public function setDelightfulUserId(string $delightfulUserId): static
    {
        $this->delightfulUserId = $delightfulUserId;
        return $this;
    }

    public function getDelightfulOrganizationCode(): string
    {
        return $this->delightfulOrganizationCode;
    }

    public function setDelightfulOrganizationCode(string $delightfulOrganizationCode): static
    {
        $this->delightfulOrganizationCode = $delightfulOrganizationCode;
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

    public function getThirdPlatformUserId(): string
    {
        return $this->thirdPlatformUserId ?? '';
    }

    public function setThirdPlatformUserId(string $thirdPlatformUserId): static
    {
        $this->thirdPlatformUserId = $thirdPlatformUserId;
        return $this;
    }

    public function getThirdPlatformType(): PlatformType
    {
        return $this->thirdPlatformType;
    }

    public function setThirdPlatformType(null|PlatformType|string $thirdPlatformType): static
    {
        if (is_null($thirdPlatformType)) {
            $this->thirdPlatformType = null;
            return $this;
        }
        if ($thirdPlatformType instanceof PlatformType) {
            $this->thirdPlatformType = $thirdPlatformType;
        } else {
            $this->thirdPlatformType = PlatformType::from($thirdPlatformType);
        }
        return $this;
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName ?? '';
    }

    public function setOrganizationName(string $organizationName): static
    {
        $this->organizationName = $organizationName;
        return $this;
    }

    public function getOrganizationLogo(): ?string
    {
        return $this->organizationLogo ?? null;
    }

    public function setOrganizationLogo(?string $organizationLogo): static
    {
        $this->organizationLogo = $organizationLogo;
        return $this;
    }
}
