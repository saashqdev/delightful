<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\Softdelete s;
/** * @property int $id * @property string $user_id user id * @property string $user_organization_code user groupEncode * @property string $chat_conversation_id Sessionid * @property string $name workspace Name * @property int $is_archived whether 0No 1yes * @property string $created_at * @property string $updated_at * @property string $deleted_at * @property int $current_topic_id * @property int $current_project_id current Project ID * @property int $status Status 0:Normal 1:Display 2delete */

class WorkspaceModel extends AbstractModel 
{
 use Softdelete s;
/** * The table associated with the model. */ protected ?string $table = 'magic_super_agent_workspaces'; /** * The attributes that are mass assignable. */ 
    protected array $fillable = [ 'id', 'user_id', 'user_organization_code', 'chat_conversation_id', 'name', 'is_archived', 'created_uid', 'updated_uid', 'created_at', 'updated_at', 'deleted_at', 'current_topic_id', 'current_project_id', 'status', ]; /** * The attributes that should be cast to native types. */ 
    protected array $casts = [ 'id' => 'integer', 'is_archived' => 'integer', 'status' => 'integer', 'current_topic_id' => 'integer', 'current_project_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime', ]; 
}
 
