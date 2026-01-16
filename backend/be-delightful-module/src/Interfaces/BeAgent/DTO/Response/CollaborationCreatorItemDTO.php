<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
/** * collaboration Itemcreator info DTO. */

class Collaborationcreator ItemDTO extends AbstractDTO 
{
 /** * @var string user ID (NumberID) */ 
    protected string $id = ''; /** * @var string user name */ 
    protected string $name = ''; /** * @var string user ID (StringID) */ 
    protected string $userId = ''; /** * @var string avatar URL */ 
    protected string $avatarUrl = ''; /** * Fromuser CreateDTO. * @param mixed $userEntity */ 
    public 
    static function fromuser Entity($userEntity): self 
{
 $dto = new self(); $dto->setId((string) $userEntity->getId()); $dto->setName($userEntity->getNickname()); $dto->setuser Id($userEntity->getuser Id()); $dto->setAvatarUrl($userEntity->getAvatarUrl() ?? ''); return $dto; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'name' => $this->name, 'user_id' => $this->userId, 'avatar_url' => $this->avatarUrl, ]; 
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
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function setuser Id(string $userId): self 
{
 $this->userId = $userId; return $this; 
}
 
    public function getAvatarUrl(): string 
{
 return $this->avatarUrl; 
}
 
    public function setAvatarUrl(string $avatarUrl): self 
{
 $this->avatarUrl = $avatarUrl; return $this; 
}
 
}
 
