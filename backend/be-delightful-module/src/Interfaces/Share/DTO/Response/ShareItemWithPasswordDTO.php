<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Response;

/** * PasswordShareItemDTO. * DTOonly for need Return PasswordInterface. */

class ShareItemWithPasswordDTO extends ShareItemDTO 
{
 /** * @var string SharePassword */ 
    public string $pwd = ''; /** * FromBaseDTOCreatePasswordDTO. * * @param ShareItemDTO $baseDto BaseDTO * @param string $password DecryptPassword */ 
    public 
    static function fromBaseDto(ShareItemDTO $baseDto, string $password): self 
{
 $dto = new self(); $dto->id = $baseDto->id; $dto->resourceId = $baseDto->resourceId; $dto->resourceType = $baseDto->resourceType; $dto->resourceTypeName = $baseDto->resourceTypeName; $dto->shareCode = $baseDto->shareCode; $dto->hasPassword = $baseDto->hasPassword; $dto->pwd = $password; $dto->shareType = $baseDto->shareType; return $dto; 
}
 /** * DTOConvert toArray. * * @return array AssociationArray */ 
    public function toArray(): array 
{
 $data = parent::toArray(); $data['pwd'] = $this->pwd; return $data; 
}
 
}
 
