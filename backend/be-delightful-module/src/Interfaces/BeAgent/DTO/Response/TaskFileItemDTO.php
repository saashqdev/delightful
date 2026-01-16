<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskFileSource;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;

class TaskFileItemDTO extends AbstractDTO 
{
 /** * FileID. */ 
    public string $fileId; /** * TaskID. */ 
    public string $taskId; /** * Project ID. */ 
    public string $projectId = ''; /** * FileType. */ 
    public string $fileType; /** * FileName. */ 
    public string $fileName; /** * FileExtension. */ 
    public string $fileExtension; /** * FileKeyValue. */ 
    public string $fileKey; /** * FileRelativePath. */ 
    public string $relativeFilePath = ''; /** * FileSize. */ 
    public int $fileSize; /** * FileURL. */ 
    public string $fileUrl; /** * whether as HideFiletrue-yes false-No. */ 
    public bool $isHidden = false; /** * ThemeID. */ 
    public string $topicId = ''; /** * Update time. */ 
    public string $updatedAt = ''; /** * whether as Foldertrue-yes false-No. */ 
    public bool $isDirectory = false; /** * FileDataParse Array. */ public ?array $metadata = null; /** * SortValue. */ 
    public int $sort = 0; /** * parent FileID. */ 
    public string $parentId = ''; /** * SourceField. */ 
    public TaskFileSource $source = TaskFileSource::DEFAULT; /** * FromCreateDTO. */ 
    public 
    static function fromEntity(TaskFileEntity $entity, string $workDir = ''): self 
{
 $dto = new self(); $dto->fileId = (string) $entity->getFileId(); $dto->taskId = (string) $entity->getTaskId(); $dto->projectId = (string) $entity->getProjectId(); $dto->fileType = $entity->getFileType(); $dto->fileName = $entity->getFileName(); $dto->fileExtension = $entity->getFileExtension(); $dto->fileKey = $entity->getFileKey(); $dto->fileSize = $entity->getFileSize(); $dto->fileUrl = $entity->getExternalUrl(); $dto->isHidden = $entity->getIsHidden(); $dto->topicId = (string) $entity->getTopicId(); $dto->isDirectory = $entity->getIsDirectory(); $dto->sort = $entity->getSort(); $dto->parentId = (string) $entity->getParentId(); $dto->updatedAt = (string) $entity->getUpdatedAt(); // Handle metadata JSON decoding $metadata = $entity->getMetadata(); if ($metadata !== null) 
{
 $decodedMetadata = json_decode($metadata, true); $dto->metadata = (json_last_error() === JSON_ERROR_NONE) ? $decodedMetadata : null; 
}
 else 
{
 $dto->metadata = null; 
}
 // relative_file_path if (! empty($workDir)) 
{
 $dto->relativeFilePath = WorkDirectoryUtil::getRelativeFilePath( $entity->getFileKey(), $workDir ); 
}
 else 
{
 $dto->relativeFilePath = ''; 
}
 return $dto; 
}
 /** * FromArrayCreateDTO. */ 
    public 
    static function fromArray(array $data): self 
{
 $dto = new self(); $dto->fileId = (string) ($data['file_id'] ?? ''); $dto->taskId = (string) ($data['task_id'] ?? ''); $dto->projectId = (string) ($data['project_id'] ?? ''); $dto->fileType = $data['file_type'] ?? ''; $dto->fileName = $data['file_name'] ?? ''; $dto->fileExtension = $data['file_extension'] ?? ''; $dto->fileKey = $data['file_key'] ?? ''; $dto->fileSize = $data['file_size'] ?? 0; $dto->relativeFilePath = $data['relative_file_path'] ?? ''; $dto->fileUrl = $data['file_url'] ?? $data['external_url'] ?? ''; $dto->isHidden = $data['is_hidden'] ?? false; $dto->topicId = (string) ($data['topic_id'] ?? ''); $dto->updatedAt = (string) ($data['updated_at'] ?? ''); $dto->isDirectory = isset($data['is_directory']) ? (bool) $data['is_directory'] : false; $dto->sort = $data['sort'] ?? 0; $dto->parentId = (string) ($data['parent_id'] ?? ''); if (isset($data['source']) && is_string($data['source'])) 
{
 $dto->source = TaskFileSource::fromValue($data['source']); 
}
 else 
{
 $dto->source = TaskFileSource::DEFAULT; 
}
 // Handle metadata - could be string (JSON) or array $metadata = $data['metadata'] ?? null; if ($metadata !== null) 
{
 if (is_string($metadata)) 
{
 $decodedMetadata = json_decode($metadata, true); $dto->metadata = (json_last_error() === JSON_ERROR_NONE) ? $decodedMetadata : null; 
}
 elseif (is_array($metadata)) 
{
 $dto->metadata = $metadata; 
}
 else 
{
 $dto->metadata = null; 
}
 
}
 else 
{
 $dto->metadata = null; 
}
 return $dto; 
}
 /** * Convert toArray. * OutputUnderlineAPICompatible. */ 
    public function toArray(): array 
{
 return [ 'file_id' => $this->fileId, 'task_id' => $this->taskId, 'project_id' => $this->projectId, 'file_type' => $this->fileType, 'file_name' => $this->fileName, 'file_extension' => $this->fileExtension, 'file_key' => $this->fileKey, 'file_size' => $this->fileSize, 'relative_file_path' => $this->relativeFilePath, 'file_url' => $this->fileUrl, 'is_hidden' => $this->isHidden, 'topic_id' => $this->topicId, 'updated_at' => $this->updatedAt, 'is_directory' => $this->isDirectory, 'metadata' => $this->metadata, 'sort' => $this->sort, 'parent_id' => $this->parentId, 'source' => $this->source->value, ]; 
}
 
}
 
