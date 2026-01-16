<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
/** * ItemLogModel. */

class ProjectOperationLogModel extends AbstractModel 
{
 /** * whether automatic Timestamp. */ 
    public bool $timestamps = true; /** * table . */ protected ?string $table = 'magic_super_agent_project_operation_logs'; /** * Field. */ 
    protected array $fillable = [ 'id', 'project_id', 'user_id', 'organization_code', 'operation_action', 'resource_type', 'resource_id', 'resource_name', 'operation_details', 'operation_status', 'ip_address', 'created_at', 'updated_at', ]; /** * FieldTypeConvert. */ 
    protected array $casts = [ 'id' => 'integer', 'project_id' => 'integer', 'operation_details' => 'array', 'created_at' => 'datetime', 'updated_at' => 'datetime', ]; /** * primary key Field. */ 
    protected string $primaryKey = 'id'; 
}
 
