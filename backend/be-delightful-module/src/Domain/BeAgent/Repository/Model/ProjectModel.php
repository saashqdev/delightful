<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\Softdelete s;
/** * ItemModel. */

class ProjectModel extends AbstractModel 
{
 use Softdelete s;
/** * delete Field. */ 
    public 
    const DELETED_AT = 'deleted_at'; /** * table . */ protected ?string $table = 'magic_super_agent_project'; /** * Field. */ 
    protected array $fillable = [ 'id', 'user_id', 'user_organization_code', 'workspace_id', 'project_name', 'project_description', 'work_dir', 'project_status', 'current_topic_id', 'current_topic_status', 'is_collaboration_enabled', 'default_join_permission', 'project_mode', 'source', 'created_uid', 'updated_uid', ]; /** * FieldTypeConvert. */ 
    protected array $casts = [ 'id' => 'integer', 'workspace_id' => 'integer', 'is_collaboration_enabled' => 'integer', 'default_join_permission' => 'string', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime', ]; /** * GetItembelongs to workspace . */ 
    public function workspace() 
{
 return $this->belongsTo(WorkspaceModel::class, 'workspace_id', 'id'); 
}
 /** * GetItemunder topic list . */ 
    public function topics() 
{
 return $this->hasMany(TopicModel::class, 'project_id', 'id'); 
}
 /** * GetItemunder Filelist . */ 
    public function files() 
{
 return $this->hasMany(ProjectFileModel::class, 'project_id', 'id'); 
}
 
}
 
