<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\SoftDeletes;

/**
 * Topic model.
 */
class TopicModel extends AbstractModel
{
    use SoftDeletes;

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_be_agent_topics';

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
        'user_organization_code',
        'workspace_id',
        'project_id',
        'from_topic_id',
        'chat_topic_id',
        'chat_conversation_id',
        'sandbox_id',
        'sandbox_config',
        'current_task_id',
        'current_task_status',
        'topic_mode',
        'topic_name',
        'work_dir',
        'task_mode',
        'cost',
        'source',
        'source_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_uid',
        'updated_uid',
        'commit_hash',
    ];

    /**
     * Date fields.
     */
    protected array $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
