<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use JsonSerializable;

class TopicMessagesResponseDTO implements JsonSerializable 
{
 /** * @var array Messagelist */ 
    protected array $list = []; /** * @var int record */ 
    protected int $total = 0; /** * @var int current Page number */ 
    protected int $page = 1; /** * Function. * * @param array $list Messagelist * @param int $total record * @param int $page current Page number */ 
    public function __construct(array $list = [], int $total = 0, int $page = 1) 
{
 $this->list = $list; $this->total = $total; $this->page = $page; 
}
 /** * GetMessagelist . */ 
    public function getlist (): array 
{
 return $this->list; 
}
 /** * Getrecord . */ 
    public function getTotal(): int 
{
 return $this->total; 
}
 /** * Getcurrent Page number. */ 
    public function getPage(): int 
{
 return $this->page; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'list' => $this->list, 'total' => $this->total, 'page' => $this->page, ]; 
}
 /** * Serializeas JSON. */ 
    public function jsonSerialize(): array 
{
 return $this->toArray(); 
}
 
}
 
