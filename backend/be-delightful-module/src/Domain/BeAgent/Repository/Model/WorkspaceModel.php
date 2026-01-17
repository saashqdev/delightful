<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\SoftDeletes;

/**
 * @property int $id
 * @property string $user_id User ID
 * @property string $user_organization_code User organization code
 * @property string $chat_conversation_id Chat conversation ID
 * @property string $name Workspace name
 * @property int $is_archived Whether archived 0-no 1-yes
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $current_topic_id
 * @property int $current_project_id Current project ID
 * @property int $status Status 0:normal 1:hidden 2:deleted
 */
class WorkspaceModel extends AbstractModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'delightful_be_agent_workspaces';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'id', 'user_id', 'user_organization_code', 'chat_conversation_id', 'name',
        'is_archived', 'created_uid', 'updated_uid', 'created_at', 'updated_at', 'deleted_at',
        'current_topic_id', 'current_project_id', 'status',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'is_archived' => 'integer',
        'status' => 'integer',
        'current_topic_id' => 'integer',
        'current_project_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
