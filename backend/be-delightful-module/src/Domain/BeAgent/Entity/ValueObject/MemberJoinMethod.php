<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

enum MemberJoinMethod: string 
{
 case INTERNAL = 'internal'; // TeamInvite case LINK = 'link'; // InviteLink 
}
 
