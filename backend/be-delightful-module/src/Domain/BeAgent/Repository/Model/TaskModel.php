<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\Softdelete s;
/** * TaskModel. */

class TaskModel extends AbstractModel 
{
 use Softdelete s;
/** * table . */ protected ?string $table = 'magic_super_agent_task'; /** * primary key . */ 
    protected string $primaryKey = 'id'; /** * Field. */ 
    protected array $fillable = [ 'id', 'user_id', 'workspace_id', 'project_id', 'topic_id', 'from_task_id', 'task_id', 'sandbox_id', 'prompt', 'attachments', 'mentions', 'task_status', 'work_dir', 'task_mode', 'err_msg', 'started_at', 'finished_at', 'created_at', 'updated_at', 'deleted_at', ]; /** * Field. */ 
    protected array $dates = [ 'started_at', 'finished_at', 'created_at', 'updated_at', 'deleted_at', ]; /** * GetTaskbelongs to workspace . */ 
    public function workspace() 
{
 return $this->belongsTo(WorkspaceModel::class, 'workspace_id', 'id'); 
}
 /** * GetTaskbelongs to Item. */ 
    public function project() 
{
 return $this->belongsTo(ProjectModel::class, 'project_id', 'id'); 
}
 /** * GetTaskbelongs to topic . */ 
    public function topic() 
{
 return $this->belongsTo(TopicModel::class, 'topic_id', 'id'); 
}
 
}
 
