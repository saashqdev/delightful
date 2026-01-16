<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TokenUsagerecord Entity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TokenUsagerecord RepositoryInterface;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;
/** * TokenUsingrecord Service. */

class TokenUsagerecord DomainService 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    private readonly TokenUsagerecord RepositoryInterface $tokenUsagerecord Repository, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 /** * Create a new token usage record. * * @param DataIsolation $dataIsolation Data isolation context * @param TokenUsagerecord Entity $entity Token usage record entity * @return TokenUsagerecord Entity Created entity with ID */ 
    public function create(DataIsolation $dataIsolation, TokenUsagerecord Entity $entity): TokenUsagerecord Entity 
{
 return $this->tokenUsagerecord Repository->create($dataIsolation, $entity); 
}
 /** * Get token usage record by ID. * * @param DataIsolation $dataIsolation Data isolation context * @param int $id record ID * @return null|TokenUsagerecord Entity Token usage record entity or null if not found */ 
    public function getById(DataIsolation $dataIsolation, int $id): ?TokenUsagerecord Entity 
{
 return $this->tokenUsagerecord Repository->getById($dataIsolation, $id); 
}
 /** * Get token usage records by task ID. * * @param DataIsolation $dataIsolation Data isolation context * @param string $taskId Task ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTaskId(DataIsolation $dataIsolation, string $taskId): array 
{
 return $this->tokenUsagerecord Repository->getByTaskId($dataIsolation, $taskId); 
}
 /** * Get token usage records by topic ID. * * @param DataIsolation $dataIsolation Data isolation context * @param int $topicId Topic ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTopicId(DataIsolation $dataIsolation, int $topicId): array 
{
 return $this->tokenUsagerecord Repository->getByTopicId($dataIsolation, $topicId); 
}
 /** * Get token usage records by organization and user. * * @param DataIsolation $dataIsolation Data isolation context * @param string $organizationCode Organization code * @param string $userId user ID * @param null|string $startDate Start date (Y-m-d H:i:s format) * @param null|string $endDate End date (Y-m-d H:i:s format) * @return array Array of TokenUsagerecord Entity */ 
    public function getByOrganizationAnduser ( DataIsolation $dataIsolation, string $organizationCode, string $userId, ?string $startDate = null, ?string $endDate = null ): array 
{
 return $this->tokenUsagerecord Repository->getByOrganizationAnduser ( $dataIsolation, $organizationCode, $userId, $startDate, $endDate ); 
}
 /** * Calculate total tokens for a specific task. * * @param DataIsolation $dataIsolation Data isolation context * @param string $taskId Task ID * @return array Array with total statistics */ 
    public function calculateTaskTokens(DataIsolation $dataIsolation, string $taskId): array 
{
 $records = $this->getByTaskId($dataIsolation, $taskId); $totalInputTokens = 0; $totalOutputTokens = 0; $totalTokens = 0; $totalCachedTokens = 0; $totalCacheWriteTokens = 0; $totalReasoningTokens = 0; foreach ($records as $record) 
{
 $totalInputTokens += $record->getTotalInputTokens(); $totalOutputTokens += $record->getTotalOutputTokens(); $totalTokens += $record->getTotalTokens(); $totalCachedTokens += $record->getCachedTokens(); $totalCacheWriteTokens += $record->getCacheWriteTokens(); $totalReasoningTokens += $record->getReasoningTokens(); 
}
 return [ 'total_input_tokens' => $totalInputTokens, 'total_output_tokens' => $totalOutputTokens, 'total_tokens' => $totalTokens, 'total_cached_tokens' => $totalCachedTokens, 'total_cache_write_tokens' => $totalCacheWriteTokens, 'total_reasoning_tokens' => $totalReasoningTokens, 'record_count' => count($records), ]; 
}
 /** * Create a new token usage record. * * @param TokenUsagerecord Entity $entity Token usage record entity * @return TokenUsagerecord Entity Created entity with ID */ 
    public function createrecord (TokenUsagerecord Entity $entity): TokenUsagerecord Entity 
{
 try 
{
 // Validate entity $this->validateEntity($entity); // Save through repository $savedEntity = $this->tokenUsagerecord Repository->save($entity); $this->logger->info('Token usage record created successfully', [ 'id' => $savedEntity->getId(), 'topic_id' => $savedEntity->getTopicId(), 'task_id' => $savedEntity->getTaskId(), 'total_tokens' => $savedEntity->getTotalTokens(), ]); return $savedEntity; 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to create token usage record', [ 'topic_id' => $entity->getTopicId(), 'task_id' => $entity->getTaskId(), 'error' => $e->getMessage(), ]); throw $e; 
}
 
}
 /** * Find existing record by unique key for idempotency check. * * @param int $topicId Topic ID * @param string $taskId Task ID * @param null|string $sandboxId Sandbox ID * @param null|string $modelId Model ID * @return null|TokenUsagerecord Entity Existing record or null if not found */ 
    public function findByUniqueKey(int $topicId, string $taskId, ?string $sandboxId, ?string $modelId): ?TokenUsagerecord Entity 
{
 try 
{
 return $this->tokenUsagerecord Repository->findByUniqueKey($topicId, $taskId, $sandboxId, $modelId); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to find token usage record by unique key', [ 'topic_id' => $topicId, 'task_id' => $taskId, 'sandbox_id' => $sandboxId, 'model_id' => $modelId, 'error' => $e->getMessage(), ]); throw $e; 
}
 
}
 /** * Validate token usage record entity. * * @param TokenUsagerecord Entity $entity Entity to validate * @throws InvalidArgumentException If validation fails */ 
    private function validateEntity(TokenUsagerecord Entity $entity): void 
{
 if (empty($entity->getTopicId())) 
{
 throw new InvalidArgumentException('Topic ID is required'); 
}
 if (empty($entity->getTaskId())) 
{
 throw new InvalidArgumentException('Task ID is required'); 
}
 if (empty($entity->getOrganizationCode())) 
{
 throw new InvalidArgumentException('Organization code is required'); 
}
 if (empty($entity->getuser Id())) 
{
 throw new InvalidArgumentException('user ID is required'); 
}
 if ($entity->getTotalTokens() < 0) 
{
 throw new InvalidArgumentException('Total tokens cannot be negative'); 
}
 
}
 
}
 
