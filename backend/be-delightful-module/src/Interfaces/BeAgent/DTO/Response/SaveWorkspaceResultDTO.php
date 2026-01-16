<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
/** * workspace SaveResultDTO * for workspace Create/update operation Return Data. */

class SaveWorkspaceResultDTO extends AbstractDTO 
{
 /** * workspace ID. */ 
    public string $id; /** * Function. */ 
    public function __construct(?array $data = null) 
{
 parent::__construct($data); 
}
 /** * Fromworkspace IDCreateDTO. * * @param int $id workspace ID */ 
    public 
    static function fromId(int $id): self 
{
 $dto = new self(); $dto->id = (string) $id; return $dto; 
}
 /** * Getworkspace ID. */ 
    public function getId(): string 
{
 return $this->id; 
}
 /** * Set workspace ID. * * @param int $id workspace ID */ 
    public function setId(int $id): self 
{
 $this->id = (string) $id; return $this; 
}
 
}
 
