<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

class TotalMetricsDTO extends AbstractDTO 
{
 /** * @var int user Total */ 
    protected int $userCount = 0; /** * @var int topic Total */ 
    protected int $topicCount = 0; /** * Getuser Total. */ 
    public function getuser Count(): int 
{
 return $this->userCount; 
}
 /** * Set user Total. */ 
    public function setuser Count(int $userCount): self 
{
 $this->userCount = $userCount; return $this; 
}
 /** * Gettopic Total. */ 
    public function getTopicCount(): int 
{
 return $this->topicCount; 
}
 /** * Set topic Total. */ 
    public function setTopicCount(int $topicCount): self 
{
 $this->topicCount = $topicCount; return $this; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'user_count' => $this->userCount, 'topic_count' => $this->topicCount, ]; 
}
 
}
 
