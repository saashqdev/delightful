<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TokenUsagerecord Entity;
/** * TokenUsingrecord Repository interface. */

interface TokenUsagerecord RepositoryInterface 
{
 /** * Create a new token usage record. * * @param DataIsolation $dataIsolation Data isolation context * @param TokenUsagerecord Entity $entity Token usage record entity * @return TokenUsagerecord Entity Created entity with ID */ 
    public function create(DataIsolation $dataIsolation, TokenUsagerecord Entity $entity): TokenUsagerecord Entity; /** * Get token usage record by ID. * * @param DataIsolation $dataIsolation Data isolation context * @param int $id record ID * @return null|TokenUsagerecord Entity Token usage record entity or null if not found */ 
    public function getById(DataIsolation $dataIsolation, int $id): ?TokenUsagerecord Entity; /** * Get token usage records by task ID. * * @param DataIsolation $dataIsolation Data isolation context * @param string $taskId Task ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTaskId(DataIsolation $dataIsolation, string $taskId): array; /** * Get token usage records by topic ID. * * @param DataIsolation $dataIsolation Data isolation context * @param int $topicId Topic ID * @return array Array of TokenUsagerecord Entity */ 
    public function getByTopicId(DataIsolation $dataIsolation, int $topicId): array; /** * Get token usage records by organization and user. * * @param DataIsolation $dataIsolation Data isolation context * @param string $organizationCode Organization code * @param string $userId user ID * @param null|string $startDate Start date (Y-m-d H:i:s format) * @param null|string $endDate End date (Y-m-d H:i:s format) * @return array Array of TokenUsagerecord Entity */ 
    public function getByOrganizationAnduser ( DataIsolation $dataIsolation, string $organizationCode, string $userId, ?string $startDate = null, ?string $endDate = null ): array; /** * Save token usage record. * * @param TokenUsagerecord Entity $entity Token usage record entity * @return TokenUsagerecord Entity Saved entity with ID */ 
    public function save(TokenUsagerecord Entity $entity): TokenUsagerecord Entity; /** * Find existing record by unique key for idempotency check. * * @param int $topicId Topic ID * @param string $taskId Task ID * @param null|string $sandboxId Sandbox ID * @param null|string $modelId Model ID * @return null|TokenUsagerecord Entity Existing record or null if not found */ 
    public function findByUniqueKey(int $topicId, string $taskId, ?string $sandboxId, ?string $modelId): ?TokenUsagerecord Entity; 
}
 
