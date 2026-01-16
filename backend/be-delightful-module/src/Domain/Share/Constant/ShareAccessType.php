<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Constant;

/** * ShareTypeEnum. */

enum ShareAccessType: int 
{
 case SelfOnly = 1; // only case OrganizationInternal = 2; // OrganizationInternal case SpecificTarget = 3; // specified Department/Member case Internet = 4; // (need Link) /** * GetShareTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::SelfOnly => 'only ', self::OrganizationInternal => 'OrganizationInternal', self::SpecificTarget => 'specified Department/Member', self::Internet => '', 
}
; 
}
 /** * check whether need PasswordProtected. */ 
    public function needsPassword(): bool 
{
 return $this === self::Internet; 
}
 /** * check whether need specified Target. */ 
    public function needsTargets(): bool 
{
 return $this === self::SpecificTarget; 
}
 
}
 
