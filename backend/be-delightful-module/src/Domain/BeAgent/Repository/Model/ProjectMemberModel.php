<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * Project member model.
 */
class ProjectMemberModel extends AbstractModel
{
    protected ?string $table = 'delightful_be_agent_project_members';

    protected array $fillable = [
        'id',
        'project_id',
        'target_type',
        'target_id',
        'role',
        'organization_code',
        'status',
        'invited_by',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'project_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Belongs to project.
     */
    public function project()
    {
        return $this->belongsTo(ProjectModel::class, 'project_id', 'id');
    }
}
