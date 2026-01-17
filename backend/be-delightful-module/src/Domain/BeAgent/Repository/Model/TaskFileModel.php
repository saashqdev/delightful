<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskFileSource;
use Hyperf\Database\Model\SoftDeletes;

class TaskFileModel extends AbstractModel
{
    use SoftDeletes;

    protected ?string $table = 'delightful_be_agent_task_files';

    protected string $primaryKey = 'file_id';

    /**
     * Fillable fields list.
     */
    protected array $fillable = [
        'file_id',
        'user_id',
        'organization_code',
        'project_id',
        'topic_id',
        'latest_modified_topic_id',
        'task_id',
        'latest_modified_task_id',
        'file_type',
        'file_name',
        'file_extension',
        'file_key',
        'file_size',
        'external_url',
        'storage_type', // Storage type, passed in by FileProcessAppService.processAttachmentsArray method
        'is_hidden', // Whether it is a hidden file
        'is_directory', // Whether it is a directory
        'sort', // Sort field
        'parent_id', // Parent ID
        'metadata', // File metadata, stores JSON
        'source', // Source field: 1-home, 2-project directory, 3-agent
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Default attribute values
     */
    protected array $attributes = [
        'storage_type' => StorageType::WORKSPACE->value, // Default storage type is workspace
        'is_hidden' => 0, // Default is not a hidden file: 0-no, 1-yes
        'is_directory' => 0, // Default is not a directory: 0-no, 1-yes
        'sort' => 0, // Default sort is 0
        'source' => TaskFileSource::HOME->value, // Default source is home
    ];

    /**
     * Type casting.
     */
    protected array $casts = [
        'is_hidden' => 'boolean', // Automatically convert 0/1 in database to false/true
        'is_directory' => 'boolean', // Automatically convert 0/1 in database to false/true
        'source' => TaskFileSource::class, // Automatically convert int in database to TaskFileSource enum
        'storage_type' => StorageType::class, // Automatically convert string in database to StorageType enum
    ];

    public function getDates(): array
    {
        return [];
    }
}
