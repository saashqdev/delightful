<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Domain\Contact\Entity\Magicuser Entity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\user Type;

class user Authorization 
{
 /** * AccountAtOrganizationunder id,user_id. */ 
    protected string $id = ''; /** * user RegisterGenerate magic_id,Global */ 
    protected string $magicId = ''; 
    protected user Type $userType; /** * user AtOrganizationunder Status:0:,1:Activated,2:,3:Exit. */ 
    protected string $status; 
    protected string $realName = ''; 
    protected string $nickname = ''; 
    protected string $avatar = ''; /** * user current SelectOrganization. */ 
    protected string $organizationCode = ''; 
    protected string $applicationCode = ''; /** * Phone,Without international dial code */ 
    protected string $mobile = ''; /** * Phone */ 
    protected string $countryCode = ''; 
    protected array $permissions = []; // current user Environmentid 
    protected int $magicEnvId = 0; // Third-partyPlatformoriginal organization code 
    protected string $thirdPlatformOrganizationCode = ''; // Third-partyPlatformoriginal user ID protected ?string $thirdPlatformuser Id = ''; // Third-partyPlatformType protected ?PlatformType $thirdPlatformType = null; 
    public function __construct() 
{
 
}
 
    public function getuser Type(): user Type 
{
 return $this->userType; 
}
 
    public function setuser Type(user Type $userType): static 
{
 $this->userType = $userType; return $this; 
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
 
    public function setAvatar(string $avatar): user Authorization 
{
 $this->avatar = $avatar; return $this; 
}
 
    public function getRealName(): string 
{
 return $this->realName; 
}
 
    public function setRealName(string $realName): user Authorization 
{
 $this->realName = $realName; return $this; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function setOrganizationCode(string $organizationCode): user Authorization 
{
 $this->organizationCode = $organizationCode; return $this; 
}
 
    public function getApplicationCode(): string 
{
 return $this->applicationCode; 
}
 
    public function setApplicationCode(string $applicationCode): user Authorization 
{
 $this->applicationCode = $applicationCode; return $this; 
}
 
    public function getpermission s(): array 
{
 return $this->permissions; 
}
 
    public function setpermission s(array $permissions): void 
{
 $this->permissions = $permissions; 
}
 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function setId(string $id): user Authorization 
{
 $this->id = $id; return $this; 
}
 
    public function getNickname(): string 
{
 return $this->nickname; 
}
 
    public function setNickname(string $nickname): user Authorization 
{
 $this->nickname = $nickname; return $this; 
}
 
    public function getMagicId(): string 
{
 return $this->magicId; 
}
 
    public function setMagicId(string $magicId): void 
{
 $this->magicId = $magicId; 
}
 
    public function getMagicEnvId(): int 
{
 return $this->magicEnvId; 
}
 
    public function setMagicEnvId(int $magicEnvId): void 
{
 $this->magicEnvId = $magicEnvId; 
}
 
    public function getThirdPlatformOrganizationCode(): string 
{
 return $this->thirdPlatformOrganizationCode; 
}
 
    public function setThirdPlatformOrganizationCode(string $thirdPlatformOrganizationCode): void 
{
 $this->thirdPlatformOrganizationCode = $thirdPlatformOrganizationCode; 
}
 
    public function getThirdPlatformuser Id(): string 
{
 return $this->thirdPlatformuser Id; 
}
 
    public function setThirdPlatformuser Id(string $thirdPlatformuser Id): void 
{
 $this->thirdPlatformuser Id = $thirdPlatformuser Id; 
}
 
    public function getThirdPlatformType(): PlatformType 
{
 return $this->thirdPlatformType; 
}
 
    public function setThirdPlatformType(null|PlatformType|string $thirdPlatformType): static 
{
 if (is_string($thirdPlatformType)) 
{
 $this->thirdPlatformType = PlatformType::from($thirdPlatformType); 
}
 else 
{
 $this->thirdPlatformType = $thirdPlatformType; 
}
 return $this; 
}
 
    public 
    static function fromuser Entity(Magicuser Entity $userEntity): user Authorization 
{
 $authorization = new user Authorization(); $authorization->setId($userEntity->getuser Id()); $authorization->setMagicId($userEntity->getMagicId()); $authorization->setOrganizationCode($userEntity->getOrganizationCode()); $authorization->setuser Type($userEntity->getuser Type()); return $authorization; 
}
 
}
 
