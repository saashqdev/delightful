<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use function Hyperf\Translation\trans;
/** * MemberRoleValueObject * * MemberRolepermission Validate Rule */

enum MemberRole: string 
{
 case OWNER = 'owner'; case MANAGE = 'manage'; case EDITOR = 'editor'; case VIEWER = 'viewer'; /** * FromStringCreateInstance. */ 
    public 
    static function fromString(string $role): self 
{
 return match ($role) 
{
 'owner' => self::OWNER, 'manage' => self::MANAGE, 'editor' => self::EDITOR, 'viewer' => self::VIEWER, default => ExceptionBuilder::throw(SuperAgentErrorCode::INVALID_MEMBER_ROLE, trans('project.invalid_member_role')) 
}
; 
}
 /** * FromValueCreateInstance. */ 
    public 
    static function fromValue(string $value): self 
{
 return self::from($value); 
}
 /** * GetValue */ 
    public function getValue(): string 
{
 return $this->value; 
}
 /** * whether as owner Role. */ 
    public function isowner (): bool 
{
 return $this === self::OWNER; 
}
 /** * whether as manager Role. */ 
    public function isManager(): bool 
{
 return $this === self::MANAGE; 
}
 /** * whether as EditorRole. */ 
    public function isEditor(): bool 
{
 return $this === self::EDITOR; 
}
 /** * whether as ViewerRole. */ 
    public function isViewer(): bool 
{
 return $this === self::VIEWER; 
}
 /** * whether Havepermission . */ 
    public function hasWritepermission (): bool 
{
 return match ($this) 
{
 self::OWNER, self::MANAGE, self::EDITOR => true, self::VIEWER => false, 
}
; 
}
 /** * whether Havedelete permission . */ 
    public function hasdelete permission (): bool 
{
 return $this === self::OWNER; 
}
 /** * whether Havepermission InviteMemberSet permission . */ 
    public function hasManagepermission (): bool 
{
 return match ($this) 
{
 self::OWNER, self::MANAGE => true, self::EDITOR, self::VIEWER => false, 
}
; 
}
 /** * whether HaveSharepermission . */ 
    public function hasSharepermission (): bool 
{
 return match ($this) 
{
 self::OWNER, self::MANAGE, self::EDITOR => true, self::VIEWER => false, 
}
; 
}
 /** * whether CanSet shortcut . */ 
    public function canSetShortcut(): bool 
{
 return $this->hasSharepermission (); 
}
 /** * Getpermission Numberpermission . */ 
    public function getpermission Level(): int 
{
 return match ($this) 
{
 self::VIEWER => 1, self::EDITOR => 2, self::MANAGE => 3, self::OWNER => 4, 
}
; 
}
 /** * CompareRolepermission . */ 
    public function isHigherOrEqualThan(self $other): bool 
{
 return $this->getpermission Level() >= $other->getpermission Level(); 
}
 /** * GetAllAvailableRole. */ 
    public 
    static function getAllRoles(): array 
{
 return [self::OWNER, self::MANAGE, self::EDITOR, self::VIEWER]; 
}
 /** * GetAllRoleStringValue. */ 
    public 
    static function getAllRoleValues(): array 
{
 return array_map(fn ($role) => $role->value, self::getAllRoles()); 
}
 /** * Validate permission Level. */ 
    public 
    static function validatepermission Level(string $permission): MemberRole 
{
 $validpermission s = [ MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value, ]; if (! in_array($permission, $validpermission s, true)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVALID_MEMBER_ROLE, trans('project.invalid_member_role')); 
}
 return MemberRole::from($permission); 
}
 
}
 
