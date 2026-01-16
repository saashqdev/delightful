<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
/** * InviteLinkResponseDTO. * * for Return InviteLinkbasic info */

class InvitationLinkResponseDTO 
{
 
    public function __construct( 
    public readonly string $id, 
    public readonly string $projectId, 
    public readonly string $token, 
    public readonly bool $isEnabled, 
    public readonly bool $isPasswordEnabled, 
    public readonly ?string $password, 
    public readonly string $defaultJoinpermission , 
    public readonly string $createdBy, 
    public readonly string $createdAt, ) 
{
 
}
 /** * FromResourceShareEntityCreateDTO. */ 
    public 
    static function fromEntity( ResourceShareEntity $shareEntity, ResourceShareDomainService $resourceShareDomainService ): self 
{
 // From extra FieldGet default_join_permission $defaultJoinpermission = $shareEntity->getExtraAttribute('default_join_permission', 'viewer'); return new self( id: (string) $shareEntity->getId(), projectId: $shareEntity->getResourceId(), token: $shareEntity->getShareCode(), isEnabled: $shareEntity->getIsEnabled(), isPasswordEnabled: $shareEntity->getIsPasswordEnabled(), password: $resourceShareDomainService->getDecryptedPassword($shareEntity), defaultJoinpermission : $defaultJoinpermission , createdBy: $shareEntity->getCreatedUid(), createdAt: $shareEntity->getCreatedAt(), ); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'project_id' => $this->projectId, 'token' => $this->token, 'is_enabled' => $this->isEnabled, 'is_password_enabled' => $this->isPasswordEnabled, 'password' => $this->isPasswordEnabled ? $this->password : '', 'default_join_permission' => $this->defaultJoinpermission , 'created_by' => $this->createdBy, 'created_at' => $this->createdAt, ]; 
}
 
}
 
