<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Domain\Contact\Entity\MagicDepartmentEntity;
use App\Domain\Contact\Entity\Magicuser Entity;
use App\Infrastructure\Core\AbstractDTO;
/** * AuthorMemberinfo DTO. */

class CollaboratorMemberDTO extends AbstractDTO 
{
 /** * @var string MemberID (user_idor department_id) */ 
    protected string $id = ''; /** * @var string MemberName */ 
    protected string $name = ''; /** * @var string avatar URL */ 
    protected string $avatarUrl = ''; /** * @var string MemberType user |Department */ 
    protected string $type = ''; /** * FromMagicuser EntityObjectCreateDTO. */ 
    public 
    static function fromuser Entity(Magicuser Entity $userEntity): self 
{
 $dto = new self(); $dto->setId($userEntity->getuser Id()); $dto->setName($userEntity->getNickname()); $dto->setAvatarUrl($userEntity->getAvatarUrl()); $dto->setType('user '); return $dto; 
}
 /** * FromMagicDepartmentEntityObjectCreateDTO. */ 
    public 
    static function fromDepartmentEntity(MagicDepartmentEntity $departmentEntity): self 
{
 $dto = new self(); $dto->setId($departmentEntity->getDepartmentId() ?? ''); $dto->setName($departmentEntity->getName() ?? ''); $dto->setAvatarUrl(''); // Department usually has no avatar $dto->setType('Department'); return $dto; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'name' => $this->name, 'avatar_url' => $this->avatarUrl, 'type' => $this->type, ]; // According toTypeAddcorresponding IDField if ($this->type === 'user ') 
{
 $result['user_id'] = $this->id; 
}
 elseif ($this->type === 'Department') 
{
 $result['department_id'] = $this->id; 
}
 return $result; 
}
 // Getters and Setters 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function setId(string $id): self 
{
 $this->id = $id; return $this; 
}
 
    public function getName(): string 
{
 return $this->name; 
}
 
    public function setName(string $name): self 
{
 $this->name = $name; return $this; 
}
 
    public function getAvatarUrl(): string 
{
 return $this->avatarUrl; 
}
 
    public function setAvatarUrl(string $avatarUrl): self 
{
 $this->avatarUrl = $avatarUrl; return $this; 
}
 
    public function getType(): string 
{
 return $this->type; 
}
 
    public function setType(string $type): self 
{
 $this->type = $type; return $this; 
}
 
}
 
