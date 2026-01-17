<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * Project member setting model.
 */
class ProjectMemberSettingModel extends AbstractModel
{
    protected ?string $table = 'delightful_be_agent_project_member_settings';

    protected array $fillable = [
        'id',
        'user_id',
        'project_id',
        'organization_code',
        'is_pinned',
        'pinned_at',
        'is_bind_workspace',
        'bind_workspace_id',
        'last_active_at',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'project_id' => 'integer',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
        'is_bind_workspace' => 'boolean',
        'bind_workspace_id' => 'integer',
        'last_active_at' => 'datetime',
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
