<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
/** * Createinfo DTO. */

class creator info DTO extends AbstractDTO 
{
 /** * @var string user ID */ 
    protected string $userId = ''; /** * @var string user */ 
    protected string $nickname = ''; /** * @var string avatar URL */ 
    protected string $avatarUrl = ''; /** * Fromuser CreateDTO. * @param mixed $userEntity */ 
    public 
    static function fromuser Entity($userEntity): self 
{
 $dto = new self(); $dto->setuser Id($userEntity->getuser Id()); $dto->setNickname($userEntity->getNickname()); $dto->setAvatarUrl($userEntity->getAvatarUrl() ?? ''); return $dto; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'user_id' => $this->userId, 'nickname' => $this->nickname, 'avatar_url' => $this->avatarUrl, ]; 
}
 // Getters and Setters 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function setuser Id(string $userId): self 
{
 $this->userId = $userId; return $this; 
}
 
    public function getNickname(): string 
{
 return $this->nickname; 
}
 
    public function setNickname(string $nickname): self 
{
 $this->nickname = $nickname; return $this; 
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
 
