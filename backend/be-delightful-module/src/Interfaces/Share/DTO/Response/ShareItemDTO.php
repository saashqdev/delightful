<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Response;

/** * ShareItemDTO. */

class ShareItemDTO 
{
 /** * @var string ShareID */ 
    public string $id = ''; /** * @var string ResourceID */ 
    public string $resourceId = ''; /** * @var int ResourceType */ 
    public int $resourceType = 0; /** * @var string ResourceTypeName */ 
    public string $resourceTypeName = ''; /** * @var string Share code */ 
    public string $shareCode = ''; /** * @var bool whether Set Password */ 
    public bool $hasPassword = false; /** * @var int ShareType */ 
    public int $shareType = 0; /** * DTOConvert toArray. * * @return array AssociationArray */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'resource_id' => $this->resourceId, 'resource_type' => $this->resourceType, 'resource_type_name' => $this->resourceTypeName, 'share_code' => $this->shareCode, 'has_password' => $this->hasPassword, 'share_type' => $this->shareType, ]; 
}
 
}
 
