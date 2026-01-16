<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Entity;

use App\Infrastructure\Core\AbstractEntity;
use DateTime;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
/** * ResourceShare. */

class ResourceShareEntity extends AbstractEntity 
{
 /** * @var int primary key ID */ 
    protected int $id = 0; /** * @var string ResourceID */ 
    protected string $resourceId = ''; /** * @var int ResourceType */ 
    protected int $resourceType = 0; /** * @var string ResourceName */ 
    protected string $resourceName = ''; /** * @var string Share code */ 
    protected string $shareCode = ''; /** * @var int ShareType */ 
    protected int $shareType = 0; /** * @var null|string PasswordOptional */ protected ?string $password = null; /** * @var bool whether EnabledPasswordProtected */ 
    protected bool $isPasswordEnabled = false; /** * @var null|string Expiration timeOptional */ protected ?string $expireAt = null; /** * @var int View */ 
    protected int $viewCount = 0; /** * @var string creator user ID */ 
    protected string $createdUid = ''; /** * @var string Updateuser ID */ 
    protected string $updatedUid = ''; /** * @var string OrganizationCode */ 
    protected string $organizationCode = ''; /** * @var string TargetIDsSpecificTargetTypeUsing * Format[
{
 type : 1, id : 123 
}
, 
{
 type : 2, id : 456 
}
] * type: 1-user 2-Department3-Group */ 
    protected string $targetIds = ''; /** * @var null|array ExtraPropertyfor Extensioninfo  */ protected ?array $extra = null; /** * @var bool whether EnabledInviteLink */ 
    protected bool $isEnabled = true; /** * @var null|string Creation time */ protected ?string $createdAt = null; /** * @var null|string Update time */ protected ?string $updatedAt = null; /** * @var null|string Deletion time */ protected ?string $deletedAt = null; /** * Function. */ 
    public function __construct(array $data = []) 
{
 // DefaultSet $this->id = 0; // Set DefaultIDas 0 $this->viewCount = 0; $this->password = null; $this->expireAt = null; $this->deletedAt = null; $this->targetIds = '[]'; // Store as JSON string $this->extra = null; $this->isEnabled = true; // DefaultEnabled $this->isPasswordEnabled = false; // DefaultEnabled $this->initProperty($data); 
}
 /** * GetResourceTypeEnum. */ 
    public function getResourceTypeEnum(): ResourceType 
{
 return ResourceType::from($this->resourceType); 
}
 /** * Set ResourceTypeEnum. */ 
    public function setResourceTypeEnum(ResourceType $resourceType): self 
{
 $this->resourceType = $resourceType->value; return $this; 
}
 /** * GetShareTypeEnum. */ 
    public function getShareTypeEnum(): ShareAccessType 
{
 return ShareAccessType::from($this->shareType); 
}
 /** * Set ShareTypeEnum. */ 
    public function setShareTypeEnum(ShareAccessType $shareType): self 
{
 $this->shareType = $shareType->value; return $this; 
}
 /** * GetTargetIDArray. */ 
    public function getTargetIdsArray(): array 
{
 return json_decode($this->targetIds, true) ?: []; 
}
 /** * Set TargetIDArray. */ 
    public function setTargetIdsArray(array $targetIds): self 
{
 $this->targetIds = json_encode($targetIds); return $this; 
}
 /** * check Sharewhether Expired */ 
    public function isExpired(): bool 
{
 if ($this->expireAt === null) 
{
 return false; 
}
 $expireDateTime = new DateTime($this->expireAt); return $expireDateTime < new DateTime(); 
}
 /** * check Sharewhether delete d. */ 
    public function isdelete d(): bool 
{
 return $this->deletedAt !== null; 
}
 /** * check Sharewhether valid. */ 
    public function isValid(): bool 
{
 return ! $this->isExpired() && ! $this->isdelete d(); 
}
 /** * IncreaseView. */ 
    public function incrementViewCount(): void 
{
 ++$this->viewCount; 
}
 /** * Validate Password */ 
    public function verifyPassword(string $inputPassword): bool 
{
 if (empty($this->password)) 
{
 return true; 
}
 // ActualApplyin UsingPasswordHasherRowSafePasswordValidate return $this->password === $inputPassword; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'id' => $this->id, 'resource_id' => $this->resourceId, 'resource_type' => $this->resourceType, 'resource_name' => $this->resourceName, 'share_code' => $this->shareCode, 'share_type' => $this->shareType, 'password' => $this->password, 'is_password_enabled' => $this->isPasswordEnabled, 'expire_at' => $this->expireAt, 'view_count' => $this->viewCount, 'created_uid' => $this->createdUid, 'updated_uid' => $this->updatedUid, 'organization_code' => $this->organizationCode, 'target_ids' => $this->targetIds, 'extra' => $this->extra, 'is_enabled' => $this->isEnabled, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'deleted_at' => $this->deletedAt, ]; // RemovenullValue return array_filter($result, function ($value) 
{
 return $value !== null; 
}
); 
}
 // Getters and Setters 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId(int $id): self 
{
 $this->id = $id; return $this; 
}
 
    public function getResourceId(): string 
{
 return $this->resourceId; 
}
 
    public function setResourceId(string $resourceId): self 
{
 $this->resourceId = $resourceId; return $this; 
}
 
    public function getResourceType(): int 
{
 return $this->resourceType; 
}
 
    public function setResourceType(int $resourceType): self 
{
 $this->resourceType = $resourceType; return $this; 
}
 
    public function getResourceName(): string 
{
 return $this->resourceName; 
}
 
    public function setResourceName(string $resourceName): self 
{
 $this->resourceName = $resourceName; return $this; 
}
 
    public function getShareCode(): string 
{
 return $this->shareCode; 
}
 
    public function setShareCode(string $shareCode): self 
{
 $this->shareCode = $shareCode; return $this; 
}
 
    public function getShareType(): int 
{
 return $this->shareType; 
}
 
    public function setShareType(int $shareType): self 
{
 $this->shareType = $shareType; return $this; 
}
 
    public function getPassword(): ?string 
{
 return $this->password; 
}
 
    public function setPassword(?string $password): self 
{
 $this->password = $password; return $this; 
}
 
    public function getIsPasswordEnabled(): bool 
{
 return $this->isPasswordEnabled; 
}
 
    public function setIsPasswordEnabled(bool $isPasswordEnabled): self 
{
 $this->isPasswordEnabled = $isPasswordEnabled; return $this; 
}
 
    public function getExpireAt(): ?string 
{
 return $this->expireAt; 
}
 
    public function setExpireAt(?string $expireAt): self 
{
 $this->expireAt = $expireAt; return $this; 
}
 
    public function getViewCount(): int 
{
 return $this->viewCount; 
}
 
    public function setViewCount(int $viewCount): self 
{
 $this->viewCount = $viewCount; return $this; 
}
 
    public function getCreatedUid(): string 
{
 return $this->createdUid; 
}
 
    public function setCreatedUid(?string $createdUid): self 
{
 $this->createdUid = $createdUid; return $this; 
}
 
    public function getUpdatedUid(): string 
{
 return $this->updatedUid; 
}
 
    public function setUpdatedUid(string $updatedUid): self 
{
 $this->updatedUid = $updatedUid; return $this; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function setOrganizationCode(string $organizationCode): self 
{
 $this->organizationCode = $organizationCode; return $this; 
}
 
    public function getTargetIds(): string 
{
 return $this->targetIds; 
}
 
    public function setTargetIds(string $targetIds): self 
{
 $this->targetIds = $targetIds; return $this; 
}
 
    public function getExtra(): ?array 
{
 return $this->extra; 
}
 
    public function setExtra(?array $extra): self 
{
 $this->extra = $extra; return $this; 
}
 /** * Getspecified ExtraPropertyValue. */ 
    public function getExtraAttribute(string $key, mixed $default = null): mixed 
{
 return $this->extra[$key] ?? $default; 
}
 /** * Set specified ExtraPropertyValue. */ 
    public function setExtraAttribute(string $key, mixed $value): self 
{
 if ($this->extra === null) 
{
 $this->extra = []; 
}
 $this->extra[$key] = $value; return $this; 
}
 
    public function getCreatedAt(): ?string 
{
 return $this->createdAt; 
}
 
    public function setCreatedAt(?string $createdAt): self 
{
 $this->createdAt = $createdAt; return $this; 
}
 
    public function getUpdatedAt(): ?string 
{
 return $this->updatedAt; 
}
 
    public function setUpdatedAt(?string $updatedAt): self 
{
 $this->updatedAt = $updatedAt; return $this; 
}
 
    public function getdelete dAt(): ?string 
{
 return $this->deletedAt; 
}
 
    public function setdelete dAt(?string $deletedAt): self 
{
 $this->deletedAt = $deletedAt; return $this; 
}
 
    public function getIsEnabled(): bool 
{
 return $this->isEnabled; 
}
 
    public function setIsEnabled(bool $isEnabled): self 
{
 $this->isEnabled = $isEnabled; return $this; 
}
 /** * EnabledShare. */ 
    public function enable(): self 
{
 $this->isEnabled = true; return $this; 
}
 /** * DisabledShare. */ 
    public function disable(): self 
{
 $this->isEnabled = false; return $this; 
}
 /** * Generate NumberPassword. * * @param int $length PasswordLengthDefault5 * @return string Generate Password */ 
    public 
    static function generateRandomPassword(int $length = 5): string 
{
 $maxNumber = (int) str_repeat('9', $length); return str_pad((string) random_int(0, $maxNumber), $length, '0', STR_PAD_LEFT); 
}
 /** * Generate PasswordSupportCustomPrimaryfor Test. * * @param int $length PasswordLength * @param null|int $seed nullUsingSystem * @return string Generate Password */ 
    public 
    static function generateRandomPasswordWithSeed(int $length = 5, ?int $seed = null): string 
{
 if ($seed !== null) 
{
 mt_srand($seed); $password = ''; for ($i = 0; $i < $length; ++$i) 
{
 $password .= (string) mt_rand(0, 9); 
}
 return $password; 
}
 return self::generateRandomPassword($length); 
}
 
}
 
