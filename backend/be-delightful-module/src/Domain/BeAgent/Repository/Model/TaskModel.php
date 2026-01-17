<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\SoftDeletes;

/**
 * Task model.
 */
class TaskModel extends AbstractModel
{
    use SoftDeletes;

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_be_agent_task';

    /**
     * Primary key.
     */
    protected string $primaryKey = 'id';

    /**
     * Fillable fields.
     */
    protected array $fillable = [
        'id',
        'user_id',
        'workspace_id',
        'project_id',
        'topic_id',
        'from_task_id',
        'task_id',
        'sandbox_id',
        'prompt',
        'attachments',
        'mentions',
        'task_status',
        'work_dir',
        'task_mode',
        'err_msg',
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Date fields.
     */
    protected array $dates = [
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the workspace that the task belongs to.
     */
    public function workspace()
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id', 'id');
    }

    /**
     * Get the project that the task belongs to.
     */
    public function project()
    {
        return $this->belongsTo(ProjectModel::class, 'project_id', 'id');
    }

    /**
     * Get the topic that the task belongs to.
     */
    public function topic()
    {
        return $this->belongsTo(TopicModel::class, 'topic_id', 'id');
    }
}
