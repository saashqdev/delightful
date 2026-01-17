<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\SoftDeletes;

/**
 * Project model.
 */
class ProjectModel extends AbstractModel
{
    use SoftDeletes;

    /**
     * Soft delete field.
     */
    public const DELETED_AT = 'deleted_at';

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_be_agent_project';

    /**
     * Fillable fields.
     */
    protected array $fillable = [
        'id',
        'user_id',
        'user_organization_code',
        'workspace_id',
        'project_name',
        'project_description',
        'work_dir',
        'project_status',
        'current_topic_id',
        'current_topic_status',
        'is_collaboration_enabled',
        'default_join_permission',
        'project_mode',
        'source',
        'created_uid',
        'updated_uid',
    ];

    /**
     * Field type casting.
     */
    protected array $casts = [
        'id' => 'integer',
        'workspace_id' => 'integer',
        'is_collaboration_enabled' => 'integer',
        'default_join_permission' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the workspace that the project belongs to.
     */
    public function workspace()
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id', 'id');
    }

    /**
     * Get the topic list under the project.
     */
    public function topics()
    {
        return $this->hasMany(TopicModel::class, 'project_id', 'id');
    }

    /**
     * Get the file list under the project.
     */
    public function files()
    {
        return $this->hasMany(ProjectFileModel::class, 'project_id', 'id');
    }
}
