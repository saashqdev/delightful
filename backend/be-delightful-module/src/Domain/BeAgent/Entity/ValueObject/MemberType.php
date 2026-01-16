<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
/** * MemberTypeValueObject * * MemberTypeValidate Rule */

enum MemberType: string 
{
 case USER = 'user '; case DEPARTMENT = 'Department'; /** * FromStringCreateInstance. */ 
    public 
    static function fromString(string $type): self 
{
 return match ($type) 
{
 'user ' => self::USER, 'Department' => self::DEPARTMENT, default => ExceptionBuilder::throw(SuperAgentErrorCode::INVALID_MEMBER_TYPE) 
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
 /** * whether as user Type. */ 
    public function isuser (): bool 
{
 return $this === self::USER; 
}
 /** * whether as DepartmentType. */ 
    public function isDepartment(): bool 
{
 return $this === self::DEPARTMENT; 
}
 /** * GetDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::USER => 'user ', self::DEPARTMENT => 'Department', 
}
; 
}
 
}
 
