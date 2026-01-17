<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * Project file model (formerly task file model).
 */
class ProjectFileModel extends AbstractModel
{
    /**
     * Soft delete field.
     */
    public const DELETED_AT = 'deleted_at';

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_be_agent_project_files';

    /**
     * Primary key.
     */
    protected string $primaryKey = 'file_id';

    /**
     * Fillable fields.
     */
    protected array $fillable = [
        'user_id',
        'organization_code',
        'project_id',
        'topic_id',
        'task_id',
        'file_type',
        'file_name',
        'file_extension',
        'file_key',
        'file_size',
        'external_url',
        'storage_type',
        'is_hidden',
    ];

    /**
     * Field type casting.
     */
    protected array $casts = [
        'file_id' => 'integer',
        'project_id' => 'integer',
        'topic_id' => 'integer',
        'task_id' => 'integer',
        'file_size' => 'integer',
        'is_hidden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Date fields.
     */
    protected array $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the project that the file belongs to.
     */
    public function project()
    {
        return $this->belongsTo(ProjectModel::class, 'project_id', 'id');
    }

    /**
     * Get the topic that the file belongs to.
     */
    public function topic()
    {
        return $this->belongsTo(TopicModel::class, 'topic_id', 'id');
    }

    /**
     * Get the task that the file belongs to
     */
    public function task()
    {
        return $this->belongsTo(TaskModel::class, 'task_id', 'id');
    }
}
