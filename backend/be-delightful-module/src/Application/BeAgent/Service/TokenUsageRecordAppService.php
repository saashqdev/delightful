<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TokenUsagerecord Entity;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TokenUsagerecord DomainService;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * TokenUsingrecord ApplyService. */

class TokenUsagerecord AppService 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    private readonly TokenUsagerecord DomainService $tokenUsagerecord DomainService, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 /** * Create a new token usage record. * * @param TokenUsagerecord Entity $entity Token usage record entity * @return TokenUsagerecord Entity Created entity with ID * @throws Throwable */ 
    public function createrecord (TokenUsagerecord Entity $entity): TokenUsagerecord Entity 
{
 try 
{
 // Create data isolation context based on organization code $dataIsolation = DataIsolation::create($entity->getOrganizationCode(), $entity->getuser Id()); // Create the record through domain service $createdEntity = $this->tokenUsagerecord DomainService->create($dataIsolation, $entity); $this->logger->info('Token usage record created successfully', [ 'id' => $createdEntity->getId(), 'task_id' => $createdEntity->getTaskId(), 'topic_id' => $createdEntity->getTopicId(), 'total_tokens' => $createdEntity->getTotalTokens(), 'usage_type' => $createdEntity->getUsageType(), ]); return $createdEntity; 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to create token usage record', [ 'task_id' => $entity->getTaskId(), 'topic_id' => $entity->getTopicId(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); throw $e; 
}
 
}
 /** * Find existing record by unique key for idempotency check. * * @param int $topicId Topic ID * @param string $taskId Task ID * @param null|string $sandboxId Sandbox ID * @param null|string $modelId Model ID * @return null|TokenUsagerecord Entity Existing record or null if not found */ 
    public function findByUniqueKey(int $topicId, string $taskId, ?string $sandboxId, ?string $modelId): ?TokenUsagerecord Entity 
{
 return $this->tokenUsagerecord DomainService->findByUniqueKey($topicId, $taskId, $sandboxId, $modelId); 
}
 /** * Get token usage record by ID. * * @param string $organizationCode Organization code * @param string $userId user ID * @param int $id record ID * @return null|TokenUsagerecord Entity Token usage record entity or null if not found */ 
    public function getById(string $organizationCode, string $userId, int $id): ?TokenUsagerecord Entity 
{
 try 
{
 $dataIsolation = DataIsolation::create($organizationCode, $userId); return $this->tokenUsagerecord DomainService->getById($dataIsolation, $id); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to get token usage record by ID', [ 'id' => $id, 'organization_code' => $organizationCode, 'user_id' => $userId, 'error' => $e->getMessage(), ]); return null; 
}
 
}
 /** * Get token usage records by task ID. * * @param string $organizationCode Organization code * @param string $userId user ID * @param string $taskId Task ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTaskId(string $organizationCode, string $userId, string $taskId): array 
{
 try 
{
 $dataIsolation = DataIsolation::create($organizationCode, $userId); return $this->tokenUsagerecord DomainService->getByTaskId($dataIsolation, $taskId); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to get token usage records by task ID', [ 'task_id' => $taskId, 'organization_code' => $organizationCode, 'user_id' => $userId, 'error' => $e->getMessage(), ]); return []; 
}
 
}
 /** * Get token usage records by topic ID. * * @param string $organizationCode Organization code * @param string $userId user ID * @param int $topicId Topic ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTopicId(string $organizationCode, string $userId, int $topicId): array 
{
 try 
{
 $dataIsolation = DataIsolation::create($organizationCode, $userId); return $this->tokenUsagerecord DomainService->getByTopicId($dataIsolation, $topicId); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to get token usage records by topic ID', [ 'topic_id' => $topicId, 'organization_code' => $organizationCode, 'user_id' => $userId, 'error' => $e->getMessage(), ]); return []; 
}
 
}
 /** * Get token usage records by organization and user. * * @param string $organizationCode Organization code * @param string $userId user ID * @param null|string $startDate Start date (Y-m-d H:i:s format) * @param null|string $endDate End date (Y-m-d H:i:s format) * @return array Array of TokenUsagerecord Entity */ 
    public function getByOrganizationAnduser ( string $organizationCode, string $userId, ?string $startDate = null, ?string $endDate = null ): array 
{
 try 
{
 $dataIsolation = DataIsolation::create($organizationCode, $userId); return $this->tokenUsagerecord DomainService->getByOrganizationAnduser ( $dataIsolation, $organizationCode, $userId, $startDate, $endDate ); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to get token usage records by organization and user', [ 'organization_code' => $organizationCode, 'user_id' => $userId, 'start_date' => $startDate, 'end_date' => $endDate, 'error' => $e->getMessage(), ]); return []; 
}
 
}
 /** * Calculate total tokens for a specific task. * * @param string $organizationCode Organization code * @param string $userId user ID * @param string $taskId Task ID * @return array Array with total statistics */ 
    public function calculateTaskTokens(string $organizationCode, string $userId, string $taskId): array 
{
 try 
{
 $dataIsolation = DataIsolation::create($organizationCode, $userId); return $this->tokenUsagerecord DomainService->calculateTaskTokens($dataIsolation, $taskId); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to calculate task tokens', [ 'task_id' => $taskId, 'organization_code' => $organizationCode, 'user_id' => $userId, 'error' => $e->getMessage(), ]); return [ 'total_input_tokens' => 0, 'total_output_tokens' => 0, 'total_tokens' => 0, 'total_cached_tokens' => 0, 'total_cache_write_tokens' => 0, 'total_reasoning_tokens' => 0, 'record_count' => 0, ]; 
}
 
}
 
}
 
