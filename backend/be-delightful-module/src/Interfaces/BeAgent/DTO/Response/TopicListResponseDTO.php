<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;

class Topiclist ResponseDTO extends AbstractDTO 
{
 /** * @var TopicItemDTO[] topic list */ 
    protected array $list = []; /** * @var int Total */ 
    protected int $total = 0; /** * Fromlist CreateResponse DTO. */ 
    public 
    static function fromResult(array $result): self 
{
 $dto = new self(); $list = []; foreach ($result['list'] as $entity) 
{
 if ($entity instanceof TopicEntity) 
{
 $list[] = TopicItemDTO::fromEntity($entity); 
}
 
}
 $dto->setlist ($list); $dto->setTotal($result['total']); return $dto; 
}
 
    public function getlist (): array 
{
 return $this->list; 
}
 
    public function setlist (array $list): self 
{
 $this->list = $list; return $this; 
}
 
    public function getTotal(): int 
{
 return $this->total; 
}
 
    public function setTotal(int $total): self 
{
 $this->total = $total; return $this; 
}
 
}
 
