<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authorization\Web;

use App\Application\LongTermMemory\Enum\AppCodeEnum;
use App\Domain\Authentication\DTO\LoginCheckDTO;
use App\Domain\Authentication\DTO\LoginResponseDTO;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Contract\Session\SessionInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Qbhy\HyperfAuth\Authenticatable;

/**
 * if改thiscategoryname/property/命名emptybetween,请modify WebUserGuard.php  cacheKey ,avoidcacheno法also原
 */
class DelightfulUserAuthorization extends AbstractAuthorization
{
    /**
     * 账numberinsomeorganizationdownid,即user_id.
     */
    protected string $id = '';

    /**
     * userregisterbackgeneratedelightful_id,all局唯one
     */
    protected string $delightfulId = '';

    protected UserType $userType;

    /**
     * userintheorganizationdownstatus:0:freeze,1:activated,2:already离职,3:alreadyexit.
     */
    protected string $status;

    protected string $realName = '';

    protected string $nickname = '';

    protected string $avatar = '';

    /**
     * usercurrentchooseorganization.
     */
    protected string $organizationCode = '';

    protected string $applicationCode = '';

    /**
     * hand机number,not带国际冠码
     */
    protected string $mobile = '';

    /**
     * hand机number国际冠码
     */
    protected string $countryCode = '';

    protected array $permissions = [];

    // currentuser所处environmentid
    protected int $delightfulEnvId = 0;

    // thethree方platformoriginalorganizationencoding
    protected string $thirdPlatformOrganizationCode = '';

    // thethree方platformoriginaluser ID
    protected ?string $thirdPlatformUserId = '';

    // thethree方platformtype
    protected ?PlatformType $thirdPlatformType = null;

    public function __construct()
    {
    }

    public static function retrieveById($key): ?Authenticatable
    {
        $organizationCode = $key['organizationCode'] ?? '';
        $authorization = $key['authorization'] ?? '';
        if (empty($authorization) || empty($organizationCode)) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        $userDomainService = di(DelightfulUserDomainService::class);
        $accountDomainService = di(DelightfulAccountDomainService::class);
        $delightfulEnvDomainService = di(DelightfulOrganizationEnvDomainService::class);
        $sessionInterface = di(SessionInterface::class);

        $beDelightfulAgentUserId = $key['beDelightfulAgentUserId'] ?? '';
        if ($beDelightfulAgentUserId) {
            // process超level麦吉 agent user
            $sandboxToken = config('be-delightful.sandbox.token', '');
            if (empty($sandboxToken) || $sandboxToken !== $authorization) {
                ExceptionBuilder::throw(UserErrorCode::TOKEN_NOT_FOUND, 'token error');
            }
            $delightfulUserId = $beDelightfulAgentUserId;
            $delightfulEnvEntity = null;
            $loginResponseDTO = null;
            // 直接login
            goto create_user;
        }

        // 多environmentdown $authorization maybeduplicate,willhaveissue(概rate趋近infinitesmall)
        $delightfulEnvEntity = $delightfulEnvDomainService->getEnvironmentEntityByAuthorization($authorization);
        if ($delightfulEnvEntity === null) {
            $delightfulEnvEntity = $delightfulEnvDomainService->getCurrentDefaultDelightfulEnv();
            if ($delightfulEnvEntity === null) {
                // tokennothavebindenvironment,andnothavedefaultenvironmentconfiguration
                ExceptionBuilder::throw(ChatErrorCode::Delightful_ENVIRONMENT_NOT_FOUND);
            }
        }
        // ifis麦吉from己downhair Token,thenbyfrom己validation
        $loginCheckDTO = new LoginCheckDTO();
        $loginCheckDTO->setAuthorization($authorization);
        /** @var LoginResponseDTO[] $currentEnvDelightfulOrganizationUsers */
        $currentEnvDelightfulOrganizationUsers = $sessionInterface->loginCheck($loginCheckDTO, $delightfulEnvEntity, $organizationCode);
        $currentEnvDelightfulOrganizationUsers = array_column($currentEnvDelightfulOrganizationUsers, null, 'delightful_organization_code');
        $loginResponseDTO = $currentEnvDelightfulOrganizationUsers[$organizationCode] ?? null;
        if ($loginResponseDTO === null) {
            ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED);
        }
        $delightfulUserId = $loginResponseDTO->getDelightfulUserId();
        if (empty($delightfulUserId)) {
            ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED);
        }

        create_user:
        $userEntity = $userDomainService->getUserById($delightfulUserId);
        if ($userEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED);
        }
        $delightfulAccountEntity = $accountDomainService->getAccountInfoByDelightfulId($userEntity->getDelightfulId());
        if ($delightfulAccountEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED);
        }
        $delightfulUserInfo = new self();
        $delightfulUserInfo->setId($userEntity->getUserId());
        $delightfulUserInfo->setNickname($userEntity->getNickname());
        $delightfulUserInfo->setAvatar($userEntity->getAvatarUrl());
        $delightfulUserInfo->setStatus((string) $userEntity->getStatus()->value);
        $delightfulUserInfo->setOrganizationCode($userEntity->getOrganizationCode());
        $delightfulUserInfo->setDelightfulId($userEntity->getDelightfulId());
        $delightfulUserInfo->setDelightfulEnvId($delightfulEnvEntity?->getId() ?? 0);
        $delightfulUserInfo->setMobile($delightfulAccountEntity->getPhone());
        $delightfulUserInfo->setCountryCode($delightfulAccountEntity->getCountryCode());
        $delightfulUserInfo->setRealName($delightfulAccountEntity->getRealName());
        $delightfulUserInfo->setUserType($userEntity->getUserType());
        $delightfulUserInfo->setThirdPlatformUserId($loginResponseDTO?->getThirdPlatformUserId() ?? '');
        $delightfulUserInfo->setThirdPlatformOrganizationCode($loginResponseDTO?->getThirdPlatformOrganizationCode() ?? '');
        return $delightfulUserInfo;
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

    public function setAvatar(string $avatar): DelightfulUserAuthorization
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function setRealName(string $realName): DelightfulUserAuthorization
    {
        $this->realName = $realName;
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): DelightfulUserAuthorization
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    public function getApplicationCode(): string
    {
        return $this->applicationCode ?: AppCodeEnum::SUPER_DELIGHTFUL->value;
    }

    public function setApplicationCode(string $applicationCode): DelightfulUserAuthorization
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

    public function setId(string $id): DelightfulUserAuthorization
    {
        $this->id = $id;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): DelightfulUserAuthorization
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

    public static function fromUserEntity(DelightfulUserEntity $userEntity): DelightfulUserAuthorization
    {
        $authorization = new DelightfulUserAuthorization();
        $authorization->setId($userEntity->getUserId());
        $authorization->setDelightfulId($userEntity->getDelightfulId());
        $authorization->setOrganizationCode($userEntity->getOrganizationCode());
        $authorization->setUserType($userEntity->getUserType());
        return $authorization;
    }
}
