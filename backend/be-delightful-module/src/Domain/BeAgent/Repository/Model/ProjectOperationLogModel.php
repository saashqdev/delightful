<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * Project operation log model.
 */
class ProjectOperationLogModel extends AbstractModel
{
    /**
     * Whether to automatically maintain timestamps.
     */
    public bool $timestamps = true;

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_be_agent_project_operation_logs';

    /**
     * Fillable fields.
     */
    protected array $fillable = [
        'id',
        'project_id',
        'user_id',
        'organization_code',
        'operation_action',
        'resource_type',
        'resource_id',
        'resource_name',
        'operation_details',
        'operation_status',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    /**
     * Field type casting.
     */
    protected array $casts = [
        'id' => 'integer',
        'project_id' => 'integer',
        'operation_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Primary key field.
     */
    protected string $primaryKey = 'id';
}
