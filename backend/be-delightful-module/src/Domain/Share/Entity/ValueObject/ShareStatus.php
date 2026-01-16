<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Entity\ValueObject;

use DateTime;
/** * ShareStatusValueObject * table Sharecurrent StatusValiddelete . */

class ShareStatus 
{
 /** * StatusValid. */ 
    public 
    const STATUS_ACTIVE = 'active'; /** * Status */ 
    public 
    const STATUS_EXPIRED = 'expired'; /** * Statusdelete d. */ 
    public 
    const STATUS_DELETED = 'deleted'; /** * StatusPasswordError. */ 
    public 
    const STATUS_PASSWORD_ERROR = 'password_error'; /** * Statuspermission . */ 
    public 
    const STATUS_NO_PERMISSION = 'no_permission'; /** * current StatusValue */ 
    private string $status; /** * Expiration time. */ private ?DateTime $expireAt; /** * Deletion time. */ private ?DateTime $deletedAt; /** * Function. */ 
    private function __construct(string $status, ?DateTime $expireAt = null, ?DateTime $deletedAt = null) 
{
 $this->status = $status; $this->expireAt = $expireAt; $this->deletedAt = $deletedAt; 
}
 /** * Convert toString. */ 
    public function __toString(): string 
{
 return $this->status; 
}
 /** * CreateValidStatus */ 
    public 
    static function active(?DateTime $expireAt = null): self 
{
 return new self(self::STATUS_ACTIVE, $expireAt, null); 
}
 /** * CreateStatus */ 
    public 
    static function expired(DateTime $expireAt): self 
{
 return new self(self::STATUS_EXPIRED, $expireAt, null); 
}
 /** * Createdelete dStatus */ 
    public 
    static function deleted(DateTime $deletedAt): self 
{
 return new self(self::STATUS_DELETED, null, $deletedAt); 
}
 /** * CreatePasswordErrorStatus */ 
    public 
    static function passwordError(): self 
{
 return new self(self::STATUS_PASSWORD_ERROR); 
}
 /** * Createpermission Status */ 
    public 
    static function nopermission (): self 
{
 return new self(self::STATUS_NO_PERMISSION); 
}
 /** * GetStatusValue */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * GetExpiration time. */ 
    public function getExpireAt(): ?DateTime 
{
 return $this->expireAt; 
}
 /** * GetDeletion time. */ 
    public function getdelete dAt(): ?DateTime 
{
 return $this->deletedAt; 
}
 /** * check Statuswhether as active . */ 
    public function isActive(): bool 
{
 return $this->status === self::STATUS_ACTIVE; 
}
 /** * check Statuswhether as */ 
    public function isExpired(): bool 
{
 return $this->status === self::STATUS_EXPIRED; 
}
 /** * check Statuswhether as delete d. */ 
    public function isdelete d(): bool 
{
 return $this->status === self::STATUS_DELETED; 
}
 /** * check Statuswhether as PasswordError. */ 
    public function isPasswordError(): bool 
{
 return $this->status === self::STATUS_PASSWORD_ERROR; 
}
 /** * check Statuswhether as permission . */ 
    public function isNopermission (): bool 
{
 return $this->status === self::STATUS_NO_PERMISSION; 
}
 /** * check Sharewhether . */ 
    public function isAccessible(): bool 
{
 return $this->isActive(); 
}
 
}
 
