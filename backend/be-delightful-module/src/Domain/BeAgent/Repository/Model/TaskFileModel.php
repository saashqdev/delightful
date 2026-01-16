<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskFileSource;
use Hyperf\Database\Model\Softdelete s;

class TaskFileModel extends AbstractModel 
{
 use Softdelete s;
protected ?string $table = 'magic_super_agent_task_files'; 
    protected string $primaryKey = 'file_id'; /** * Fieldlist . */ 
    protected array $fillable = [ 'file_id', 'user_id', 'organization_code', 'project_id', 'topic_id', 'latest_modified_topic_id', 'task_id', 'latest_modified_task_id', 'file_type', 'file_name', 'file_extension', 'file_key', 'file_size', 'external_url', 'storage_type', // TypeFileprocess AppService.processAttachmentsArrayMethod 'is_hidden', // whether as HideFile 'is_directory', // whether as Directory 'sort', // SortField 'parent_id', // parent ID 'metadata', // FileData JSON 'source', // SourceField1-First page2-ItemDirectory3-agent 'created_at', 'updated_at', 'deleted_at', ]; /** * DefaultPropertyValue */ 
    protected array $attributes = [ 'storage_type' => StorageType::WORKSPACE->value, // DefaultTypeas workspace 'is_hidden' => 0, // DefaultIs notHideFile0-No1-yes 'is_directory' => 0, // DefaultIs notDirectory0-No1-yes 'sort' => 0, // DefaultSortas 0 'source' => TaskFileSource::HOME->value, // DefaultSourceas First page ]; /** * TypeConvert. */ 
    protected array $casts = [ 'is_hidden' => 'boolean', // automatic Databasein 0/1Convert tofalse/true 'is_directory' => 'boolean', // automatic Databasein 0/1Convert tofalse/true 'source' => TaskFileSource::class, // automatic Databasein intConvert toTaskFileSourceEnum 'storage_type' => StorageType::class, // automatic Databasein stringConvert toStorageTypeEnum ]; 
    public function getDates(): array 
{
 return []; 
}
 
}
 
