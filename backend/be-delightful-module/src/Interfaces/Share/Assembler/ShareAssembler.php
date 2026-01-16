<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\Assembler;

use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemWithPasswordDTO;
/** * Share * Convert toDifferentTypeDTO. */

class ShareAssembler 
{
 
    public function __construct( 
    private ResourceShareDomainService $shareDomainService ) 
{
 
}
 /** * ShareConvert toBaseDTO. * * @param ResourceShareEntity $share Share * @return ShareItemDTO BaseShareDTO */ 
    public function toDto(ResourceShareEntity $share): ShareItemDTO 
{
 $dto = new ShareItemDTO(); $dto->id = (string) $share->getId(); $dto->resourceId = $share->getResourceId(); $dto->resourceType = $share->getResourceType(); $dto->resourceTypeName = ResourceType::tryFrom($share->getResourceType())->name ?? ''; $dto->shareCode = $share->getShareCode(); $dto->hasPassword = ! empty($share->getPassword()); $dto->shareType = $share->getShareType(); return $dto; 
}
 /** * ShareConvert toPasswordDTO. * * @param ResourceShareEntity $share Share * @return ShareItemWithPasswordDTO PasswordShareDTO */ 
    public function toDtoWithPassword(ResourceShareEntity $share): ShareItemWithPasswordDTO 
{
 // CreateBaseDTO $baseDto = $this->toDto($share); // GetDecryptPassword $password = ''; if ($baseDto->hasPassword) 
{
 $password = $this->shareDomainService->getDecryptedPassword($share); 
}
 // CreatePasswordDTO return ShareItemWithPasswordDTO::fromBaseDto($baseDto, $password); 
}
 
}
 
