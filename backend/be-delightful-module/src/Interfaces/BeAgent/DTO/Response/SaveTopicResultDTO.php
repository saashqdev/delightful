<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
/** * topic SaveResultDTO * for topic Create/update operation Return Data. */

class SaveTopicResultDTO extends AbstractDTO 
{
 /** * topic ID * StringTypePairTaskStatustable primary key . */ 
    public string $id; /** * Function. */ 
    public function __construct(?array $data = null) 
{
 parent::__construct($data); 
}
 /** * FromTaskStatusIDCreateDTO. * * @param int $id TaskStatusID(primary key ) */ 
    public 
    static function fromId(int $id): self 
{
 $dto = new self(); $dto->id = (string) $id; return $dto; 
}
 /** * GetTaskStatusID. */ 
    public function getId(): string 
{
 return $this->id; 
}
 /** * Set TaskStatusID. * * @param int $id TaskStatusID(primary key ) */ 
    public function setId(int $id): self 
{
 $this->id = (string) $id; return $this; 
}
 
}
 
