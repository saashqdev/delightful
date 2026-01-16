<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use JsonSerializable;
/** * topic File upload STS Token Request DTO. */

class TopicUploadTokenRequestDTO implements JsonSerializable 
{
 /** * topic ID. */ 
    private string $topicId = ''; /** * Validseconds . */ 
    private int $expires = 3600; /** * FromRequestDataCreateDTO. */ 
    public 
    static function fromRequest(array $data): self 
{
 $instance = new self(); $instance->topicId = $data['topic_id'] ?? ''; $instance->expires = (int) ($data['expires'] ?? 3600); return $instance; 
}
 
    public function getTopicId(): string 
{
 return $this->topicId; 
}
 
    public function setTopicId(string $topicId): self 
{
 $this->topicId = $topicId; return $this; 
}
 
    public function getExpires(): int 
{
 return $this->expires; 
}
 
    public function setExpires(int $expires): self 
{
 $this->expires = $expires; return $this; 
}
 /** * ImplementationJsonSerializableInterface. */ 
    public function jsonSerialize(): array 
{
 return [ 'topic_id' => $this->topicId, 'expires' => $this->expires, ]; 
}
 
}
 
