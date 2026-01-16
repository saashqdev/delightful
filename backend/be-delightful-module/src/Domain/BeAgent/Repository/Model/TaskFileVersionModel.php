<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\Softdelete s;

class TaskFileVersionModel extends AbstractModel 
{
 use Softdelete s;
protected ?string $table = 'magic_super_agent_task_file_versions'; 
    protected string $primaryKey = 'id'; /** * Fieldlist . */ 
    protected array $fillable = [ 'id', 'file_id', 'organization_code', 'file_key', 'version', 'edit_type', 'created_at', 'updated_at', 'deleted_at', ]; /** * TypeConvert. */ 
    protected array $casts = [ 'id' => 'integer', 'file_id' => 'integer', 'version' => 'integer', 'edit_type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime', ]; 
}
 
