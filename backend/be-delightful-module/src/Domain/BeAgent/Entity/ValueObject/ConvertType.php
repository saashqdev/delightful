<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

enum ConvertType: string 
{
 case PDF = 'pdf'; case PPT = 'ppt'; case IMAGE = 'image'; 
}
 
