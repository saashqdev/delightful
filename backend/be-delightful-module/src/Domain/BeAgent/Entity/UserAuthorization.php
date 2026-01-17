<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\UserType;

class UserAuthorization
{
    /**
     * Account ID under a specific organization, i.e., user_id.
     */
    protected string $id = '';

    /**
     * Delightful ID generated after user registration, globally unique.
     */
    protected string $delightfulId = '';

    protected UserType $userType;

    /**
     * User status under this organization: 0: frozen, 1: activated, 2: resigned, 3: quit.
     */
    protected string $status;

    protected string $realName = '';

    protected string $nickname = '';

    protected string $avatar = '';

    /**
     * Organization currently selected by the user.
     */
    protected string $organizationCode = '';

    protected string $applicationCode = '';

    /**
     * Mobile number, without international dialing code.
     */
    protected string $mobile = '';

    /**
     * International dialing code for mobile number.
     */
    protected string $countryCode = '';

    protected array $permissions = [];

    // Environment ID where the current user is located
    protected int $delightfulEnvId = 0;

    // Original organization code from third-party platform
    protected string $thirdPlatformOrganizationCode = '';

    // Original user ID from third-party platform
    protected ?string $thirdPlatformUserId = '';

    // Third-party platform type
    protected ?PlatformType $thirdPlatformType = null;

    public function __construct()
    {
    }

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function setUserType(UserType $userType): static
    {
        $this->userType = $userType;
        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): UserAuthorization
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function setRealName(string $realName): UserAuthorization
    {
        $this->realName = $realName;
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): UserAuthorization
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    public function getApplicationCode(): string
    {
        return $this->applicationCode;
    }

    public function setApplicationCode(string $applicationCode): UserAuthorization
    {
        $this->applicationCode = $applicationCode;
        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): UserAuthorization
    {
        $this->id = $id;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): UserAuthorization
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getDelightfulId(): string
    {
        return $this->delightfulId;
    }

    public function setDelightfulId(string $delightfulId): void
    {
        $this->delightfulId = $delightfulId;
    }

    public function getDelightfulEnvId(): int
    {
        return $this->delightfulEnvId;
    }

    public function setDelightfulEnvId(int $delightfulEnvId): void
    {
        $this->delightfulEnvId = $delightfulEnvId;
    }

    public function getThirdPlatformOrganizationCode(): string
    {
        return $this->thirdPlatformOrganizationCode;
    }

    public function setThirdPlatformOrganizationCode(string $thirdPlatformOrganizationCode): void
    {
        $this->thirdPlatformOrganizationCode = $thirdPlatformOrganizationCode;
    }

    public function getThirdPlatformUserId(): string
    {
        return $this->thirdPlatformUserId;
    }

    public function setThirdPlatformUserId(string $thirdPlatformUserId): void
    {
        $this->thirdPlatformUserId = $thirdPlatformUserId;
    }

    public function getThirdPlatformType(): PlatformType
    {
        return $this->thirdPlatformType;
    }

    public function setThirdPlatformType(null|PlatformType|string $thirdPlatformType): static
    {
        if (is_string($thirdPlatformType)) {
            $this->thirdPlatformType = PlatformType::from($thirdPlatformType);
        } else {
            $this->thirdPlatformType = $thirdPlatformType;
        }
        return $this;
    }

    public static function fromUserEntity(DelightfulUserEntity $userEntity): UserAuthorization
    {
        $authorization = new UserAuthorization();
        $authorization->setId($userEntity->getUserId());
        $authorization->setDelightfulId($userEntity->getDelightfulId());
        $authorization->setOrganizationCode($userEntity->getOrganizationCode());
        $authorization->setUserType($userEntity->getUserType());
        return $authorization;
    }
}
