<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Service;

use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\LongTermMemory\Assembler\LongTermMemoryAssembler;
use App\Domain\LongTermMemory\DTO\CreateMemoryDTO;
use App\Domain\LongTermMemory\DTO\MemoryQueryDTO;
use App\Domain\LongTermMemory\DTO\UpdateMemoryDTO;
use App\Domain\LongTermMemory\Entity\LongTermMemoryEntity;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryCategory;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryStatus;
use App\Domain\LongTermMemory\Repository\LongTermMemoryRepositoryInterface;
use App\ErrorCode\LongTermMemoryErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use DateTime;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationAction;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationScenario;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\BeAgentMessage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * 长期记忆领域服务
 */
readonly class LongTermMemoryDomainService
{
    public function __construct(
        private LongTermMemoryRepositoryInterface $repository,
        private LoggerInterface $logger,
        private LockerInterface $locker,
        private DelightfulMessageRepositoryInterface $messageRepository,
    ) {
    }

    /**
     * 批量强化记忆.
     */
    public function reinforceMemories(array $memoryIds): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // generate锁名称和所有者（based on记忆IDsort后generate唯一锁名）
        sort($memoryIds);
        $lockName = 'memory:batch:reinforce:' . md5(implode(',', $memoryIds));
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            $this->logger->error('Failed to acquire lock for batch memory reinforcement', [
                'lock_name' => $lockName,
                'memory_ids' => $memoryIds,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            // 批量query记忆
            $memories = $this->repository->findByIds($memoryIds);

            if (empty($memories)) {
                $this->logger->debug('No memories found for reinforcement', ['memory_ids' => $memoryIds]);
                return;
            }

            // 批量强化
            foreach ($memories as $memory) {
                $memory->reinforce();
            }

            // 批量save更新
            if (! $this->repository->updateBatch($memories)) {
                $this->logger->error('Failed to batch reinforce memories', ['memory_ids' => $memoryIds]);
                ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
            }

            $this->logger->info('Batch reinforced memories successfully', ['count' => count($memories)]);
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * 批量handle记忆建议（接受/拒绝）.
     */
    public function batchProcessMemorySuggestions(array $memoryIds, MemoryOperationAction $action, MemoryOperationScenario $scenario = MemoryOperationScenario::ADMIN_PANEL, ?string $delightfulMessageId = null): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // validate当 scenario 是 memory_card_quick 时，delightfulMessageId must提供
        if ($scenario === MemoryOperationScenario::MEMORY_CARD_QUICK && empty($delightfulMessageId)) {
            throw new InvalidArgumentException('delightful_message_id is required when scenario is memory_card_quick');
        }

        // generate锁名称和所有者（based on记忆IDsort后generate唯一锁名）
        sort($memoryIds);
        $lockName = sprintf('memory:batch:%s:%s:%s', $action->value, $scenario->value, md5(implode(',', $memoryIds)));
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            if ($action === MemoryOperationAction::ACCEPT) {
                // 批量query记忆
                $memories = $this->repository->findByIds($memoryIds);

                // 批量接受记忆建议：将pending_content移动到content，settingstatus为已接受，启用记忆
                foreach ($memories as $memory) {
                    // 如果有pending_content，则将其移动到content
                    if ($memory->getPendingContent() !== null) {
                        // 将pending_content的value复制到content字段
                        $memory->setContent($memory->getPendingContent());
                        // 清nullpending_content字段
                        $memory->setPendingContent(null);
                    }

                    // settingstatus为已生效
                    $memory->setStatus(MemoryStatus::ACTIVE);

                    // 启用记忆
                    $memory->setEnabledInternal(true);
                }

                // 批量save更新
                if (! $this->repository->updateBatch($memories)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
                }
            } elseif ($action === MemoryOperationAction::REJECT) {
                // 批量拒绝记忆建议：according to记忆status决定delete还是清nullpending_content
                $memories = $this->repository->findByIds($memoryIds);

                $memoriesToDelete = [];
                $memoriesToUpdate = [];

                foreach ($memories as $memory) {
                    $content = $memory->getContent();
                    $pendingContent = $memory->getPendingContent();

                    // 如果content为null且PendingContent不为null，直接delete记忆
                    if (empty($content) && ! empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                    // 如果content和PendingContent都不为null，则清nullPendingContent即可，不要delete记忆
                    elseif (! empty($content) && ! empty($pendingContent)) {
                        $memory->setPendingContent(null);
                        $memory->setStatus(MemoryStatus::ACTIVE);
                        $memoriesToUpdate[] = $memory;
                    }
                    // 如果content不为null但PendingContent为null，也直接delete记忆（原有逻辑保持）
                    elseif (! empty($content) && empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                    // 如果content为null且PendingContent也为null，直接delete记忆（原有逻辑保持）
                    elseif (empty($content) && empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                }

                // 批量deleteneeddelete的记忆
                if (! empty($memoriesToDelete) && ! $this->repository->deleteBatch($memoriesToDelete)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::DELETION_FAILED);
                }

                // 批量更新need清nullpending_content的记忆
                if (! empty($memoriesToUpdate) && ! $this->repository->updateBatch($memoriesToUpdate)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
                }
            }

            // 如果是 memory_card_quick 场景，need更新对应的message内容
            if ($scenario === MemoryOperationScenario::MEMORY_CARD_QUICK && ! empty($delightfulMessageId)) {
                $this->updateMessageWithMemoryOperation($delightfulMessageId, $action, $memoryIds);
            }
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * 访问记忆（更新访问statistics）.
     */
    public function accessMemory(string $memoryId): void
    {
        $memory = $this->repository->findById($memoryId);
        if (! $memory) {
            $this->logger->debug(sprintf('Memory not found for access tracking: %s', $memoryId));
            return;
        }

        $memory->access();

        if (! $this->repository->update($memory)) {
            $this->logger->error(sprintf('Failed to update access stats for memory: %s', $memoryId));
        }
    }

    /**
     * 批量访问记忆.
     */
    public function accessMemories(array $memoryIds): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // 批量query记忆
        $memories = $this->repository->findByIds($memoryIds);

        if (empty($memories)) {
            $this->logger->debug('No memories found for access tracking', ['memory_ids' => $memoryIds]);
            return;
        }

        // 批量更新访问statistics
        foreach ($memories as $memory) {
            $memory->access();
        }

        // 批量save更新
        if (! $this->repository->updateBatch($memories)) {
            $this->logger->error('Failed to batch update access stats for memories', ['memory_ids' => $memoryIds]);
        }
    }

    public function create(CreateMemoryDTO $dto): string
    {
        // generate锁名称和所有者
        $lockName = sprintf('memory:create:%s:%s:%s', $dto->orgId, $dto->appId, $dto->userId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            $this->logger->error('Failed to acquire lock for memory creation', [
                'lock_name' => $lockName,
                'user_id' => $dto->userId,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::CREATION_FAILED);
        }

        try {
            // validateuser记忆quantity限制
            $count = $this->countByUser($dto->orgId, $dto->appId, $dto->userId);
            if ($count >= 40) {
                throw new InvalidArgumentException(trans('long_term_memory.entity.user_memory_limit_exceeded'));
            }

            $memory = new LongTermMemoryEntity();
            $memory->setId((string) IdGenerator::getSnowId());
            $memory->setOrgId($dto->orgId);
            $memory->setAppId($dto->appId);
            $memory->setProjectId($dto->projectId);
            $memory->setUserId($dto->userId);
            $memory->setMemoryType($dto->memoryType);
            $memory->setStatus($dto->status);
            $memory->setEnabledInternal($dto->enabled);
            $memory->setContent($dto->content);
            $memory->setPendingContent($dto->pendingContent);
            $memory->setExplanation($dto->explanation);
            $memory->setOriginText($dto->originText);
            $memory->setTags($dto->tags);
            $memory->setMetadata($dto->metadata);
            $memory->setImportance($dto->importance);
            $memory->setConfidence($dto->confidence);
            if ($dto->expiresAt) {
                $memory->setExpiresAt($dto->expiresAt);
            }

            if (! $this->repository->save($memory)) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::CREATION_FAILED);
            }

            return $memory->getId();
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    public function updateMemory(string $memoryId, UpdateMemoryDTO $dto): void
    {
        // generate锁名称和所有者
        $lockName = sprintf('memory:update:%s', $memoryId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            $this->logger->error('Failed to acquire lock for memory update', [
                'lock_name' => $lockName,
                'memory_id' => $memoryId,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            $memory = $this->repository->findById($memoryId);
            if (! $memory) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::MEMORY_NOT_FOUND);
            }

            // 如果更新了pending_content，needaccording to业务规则调整status
            if ($dto->pendingContent !== null) {
                $this->adjustMemoryStatusBasedOnPendingContent($memory, $dto->pendingContent);
            }

            LongTermMemoryAssembler::updateEntityFromDTO($memory, $dto);

            if (! $this->repository->update($memory)) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
            }

            $this->logger->info('Memory updated successfully: {id}', ['id' => $memoryId]);
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    public function deleteMemory(string $memoryId): void
    {
        // generate锁名称和所有者
        $lockName = sprintf('memory:delete:%s', $memoryId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            $this->logger->error('Failed to acquire lock for memory deletion', [
                'lock_name' => $lockName,
                'memory_id' => $memoryId,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::DELETION_FAILED);
        }

        try {
            $memory = $this->repository->findById($memoryId);
            if (! $memory) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::MEMORY_NOT_FOUND);
            }

            if (! $this->repository->delete($memoryId)) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::DELETION_FAILED);
            }

            $this->logger->info('Memory deleted successfully: {id}', ['id' => $memoryId]);
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * according to项目ID列表批量delete记忆.
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @param array $projectIds 项目ID列表
     * @return int delete的记录quantity
     */
    public function deleteMemoriesByProjectIds(string $orgId, string $appId, string $userId, array $projectIds): int
    {
        if (empty($projectIds)) {
            return 0;
        }

        // filternull的项目ID
        $validProjectIds = array_filter($projectIds, static fn ($id) => ! empty($id));
        if (empty($validProjectIds)) {
            return 0;
        }

        // 一条SQL批量delete
        return $this->repository->deleteByProjectIds($orgId, $appId, $userId, $validProjectIds);
    }

    /**
     * 获取user的valid记忆并buildhint词string.
     */
    public function getEffectiveMemoriesForPrompt(string $orgId, string $appId, string $userId, ?string $projectId, int $maxLength = 4000): string
    {
        // 获取user全局记忆（没有项目ID的记忆）
        $generalMemoryLimit = MemoryCategory::GENERAL->getEnabledLimit();
        $generalMemories = $this->repository->findEffectiveMemoriesByUser($orgId, $appId, $userId, '', $generalMemoryLimit);

        // 获取项目相关记忆
        $projectMemoryLimit = MemoryCategory::PROJECT->getEnabledLimit();
        $projectMemories = $this->repository->findEffectiveMemoriesByUser($orgId, $appId, $userId, $projectId ?? '', $projectMemoryLimit);

        // merge记忆，按分数sort
        $memories = array_merge($generalMemories, $projectMemories);

        // filter掉should被淘汰的记忆
        $validMemories = array_filter($memories, function ($memory) {
            return ! $this->shouldMemoryBeEvicted($memory);
        });

        // 按valid分数sort
        usort($validMemories, static function ($a, $b) {
            return $b->getEffectiveScore() <=> $a->getEffectiveScore();
        });

        // 限制总length
        $selectedMemories = [];
        $totalLength = 0;

        foreach ($validMemories as $memory) {
            $memoryLength = mb_strlen($memory->getContent());

            if ($totalLength + $memoryLength <= $maxLength) {
                $selectedMemories[] = $memory;
                $totalLength += $memoryLength;
            } else {
                break;
            }
        }

        $this->logger->info('Selected {count} memories for prompt (total length: {length})', [
            'count' => count($selectedMemories),
            'length' => $totalLength,
        ]);

        // 记录访问
        $memoryIds = array_map(static fn ($memory) => $memory->getId(), $selectedMemories);
        $this->accessMemories($memoryIds);

        // build记忆hint词string
        if (empty($selectedMemories)) {
            return '';
        }

        $prompt = '<user长期记忆>';

        foreach ($selectedMemories as $memory) {
            $memoryId = $memory->getId();
            $memoryText = $memory->getContent();
            $prompt .= sprintf("\n[记忆ID: %s] %s", $memoryId, $memoryText);
        }

        $prompt .= "\n</user长期记忆>";

        return $prompt;
    }

    /**
     * 获取记忆statisticsinfo.
     */
    public function getMemoryStats(string $orgId, string $appId, string $userId): array
    {
        $totalCount = $this->repository->countByUser($orgId, $appId, $userId);
        $typeCount = $this->repository->countByUserAndType($orgId, $appId, $userId);
        $totalSize = $this->repository->getTotalSizeByUser($orgId, $appId, $userId);

        $memoriesToEvict = $this->repository->findMemoriesToEvict($orgId, $appId, $userId);
        $memoriesToCompress = $this->repository->findMemoriesToCompress($orgId, $appId, $userId);

        return [
            'total_count' => $totalCount,
            'type_count' => $typeCount,
            'total_size' => $totalSize,
            'evictable_count' => count($memoriesToEvict),
            'compressible_count' => count($memoriesToCompress),
            'average_size' => $totalCount > 0 ? (int) ($totalSize / $totalCount) : 0,
        ];
    }

    /**
     * 查找记忆 by ID.
     */
    public function findById(string $memoryId): ?LongTermMemoryEntity
    {
        return $this->repository->findById($memoryId);
    }

    /**
     * 通用querymethod (use DTO).
     * @return LongTermMemoryEntity[]
     */
    public function findMemories(MemoryQueryDTO $dto): array
    {
        return $this->repository->findMemories($dto);
    }

    /**
     * according toqueryconditionstatistics记忆quantity.
     */
    public function countMemories(MemoryQueryDTO $dto): int
    {
        return $this->repository->countMemories($dto);
    }

    /**
     * statisticsuser记忆quantity.
     */
    public function countByUser(string $orgId, string $appId, string $userId): int
    {
        return $this->repository->countByUser($orgId, $appId, $userId);
    }

    /**
     * 批量check记忆是否属于user.
     */
    public function filterMemoriesByUser(array $memoryIds, string $orgId, string $appId, string $userId): array
    {
        return $this->repository->filterMemoriesByUser($memoryIds, $orgId, $appId, $userId);
    }

    /**
     * 批量启用或禁用记忆.
     * @param array $memoryIds 记忆ID列表
     * @param bool $enabled 启用status
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @return int success更new记录quantity
     */
    public function batchUpdateEnabled(array $memoryIds, bool $enabled, string $orgId, string $appId, string $userId): int
    {
        if (empty($memoryIds)) {
            $this->logger->warning('Empty memory IDs list provided for batch enable/disable');
            return 0;
        }

        // generate锁名称和所有者（based on记忆IDsort后generate唯一锁名）
        sort($memoryIds);
        $enabledStatus = $enabled ? 'enable' : 'disable';
        $lockName = sprintf('memory:batch:%s:%s', $enabledStatus, md5(implode(',', $memoryIds)));
        $lockOwner = getmypid() . '_' . microtime(true);

        // 获取互斥锁
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            // validate记忆ID的valid性和所属权
            $validMemoryIds = $this->repository->filterMemoriesByUser($memoryIds, $orgId, $appId, $userId);
            if (empty($validMemoryIds)) {
                return 0;
            }

            // 如果是启用记忆，进行quantity限制check
            if ($enabled) {
                $this->validateMemoryEnablementLimits($validMemoryIds, $orgId, $appId, $userId);
            }

            // execute批量更新
            return $this->repository->batchUpdateEnabled($validMemoryIds, $enabled, $orgId, $appId, $userId);
        } finally {
            // ensure释放锁
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * 判断记忆是否should被淘汰.
     */
    public function shouldMemoryBeEvicted(LongTermMemoryEntity $memory): bool
    {
        // 过期时间check
        if ($memory->getExpiresAt() && $memory->getExpiresAt() < new DateTime()) {
            return true;
        }

        // valid分数过低
        if ($memory->getEffectiveScore() < 0.1) {
            return true;
        }

        // 长时间未访问且重要性很低
        if ($memory->getLastAccessedAt() && $memory->getImportance() < 0.2) {
            $daysSinceLastAccess = new DateTime()->diff($memory->getLastAccessedAt())->days;
            if ($daysSinceLastAccess > 30) {
                return true;
            }
        }

        return false;
    }

    /**
     * validate记忆启用quantity限制.
     * @param array $memoryIds 要启用的记忆ID列表
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @throws BusinessException 当启用quantity超过限制时抛出exception
     */
    private function validateMemoryEnablementLimits(array $memoryIds, string $orgId, string $appId, string $userId): void
    {
        // 获取要启用的记忆实体
        $memoriesToEnable = $this->repository->findByIds($memoryIds);

        // 获取current项目记忆和全局记忆的启用quantity
        $currentProjectCount = $this->repository->getEnabledMemoryCountByCategory($orgId, $appId, $userId, MemoryCategory::PROJECT);
        $currentGeneralCount = $this->repository->getEnabledMemoryCountByCategory($orgId, $appId, $userId, MemoryCategory::GENERAL);

        $currentEnabledCounts = [
            MemoryCategory::PROJECT->value => $currentProjectCount,
            MemoryCategory::GENERAL->value => $currentGeneralCount,
        ];

        // 计算启用后各类别的quantity
        $projectedCounts = $currentEnabledCounts;

        foreach ($memoriesToEnable as $memory) {
            $projectId = $memory->getProjectId();
            $category = MemoryCategory::fromProjectId($projectId);
            $categoryKey = $category->value;

            if (! isset($projectedCounts[$categoryKey])) {
                $projectedCounts[$categoryKey] = 0;
            }

            // 只有current未启用的记忆才will增加count
            if (! $memory->isEnabled()) {
                ++$projectedCounts[$categoryKey];
            }
        }

        // check是否超过限制
        foreach ($projectedCounts as $categoryKey => $projectedCount) {
            $category = MemoryCategory::from($categoryKey);
            $limit = $category->getEnabledLimit();

            if ($projectedCount > $limit) {
                $categoryName = $category->getDisplayName();
                ExceptionBuilder::throw(LongTermMemoryErrorCode::ENABLED_MEMORY_LIMIT_EXCEEDED, trans('long_term_memory.memory_category_limit_exceeded', ['category' => $categoryName, 'limit' => $limit]));
            }
        }
    }

    /**
     * according topending_content的变化调整记忆status.
     */
    private function adjustMemoryStatusBasedOnPendingContent(LongTermMemoryEntity $memory, ?string $pendingContent): void
    {
        $currentStatus = $memory->getStatus();
        $hasPendingContent = ! empty($pendingContent);

        // 获取新status
        $newStatus = $this->determineNewMemoryStatus($currentStatus, $hasPendingContent);

        // 只在statusneed改变时才更新
        if ($newStatus !== $currentStatus) {
            $memory->setStatus($newStatus);
        }
    }

    /**
     * according tocurrentstatus和pending_content的存在确定新status.
     */
    private function determineNewMemoryStatus(MemoryStatus $currentStatus, bool $hasPendingContent): MemoryStatus
    {
        // statusconvert矩阵
        return match ([$currentStatus, $hasPendingContent]) {
            // pending_content为null时的statusconvert
            [MemoryStatus::PENDING_REVISION, false], [MemoryStatus::ACTIVE, false] => MemoryStatus::ACTIVE,        // 修订complete → 生效
            [MemoryStatus::PENDING, false], [MemoryStatus::PENDING, true] => MemoryStatus::PENDING,                 // 待接受status保持不变
            // pending_content不为null时的statusconvert
            [MemoryStatus::ACTIVE, true], [MemoryStatus::PENDING_REVISION, true] => MemoryStatus::PENDING_REVISION,         // 生效记忆有修订 → 待修订
            // default情况（不should到达这里）
            default => $currentStatus,
        };
    }

    /**
     * 更新message内容，setting记忆操作info.
     */
    private function updateMessageWithMemoryOperation(string $delightfulMessageId, MemoryOperationAction $action, array $memoryIds): void
    {
        try {
            // according to delightful_message_id querymessage数据
            $messageEntity = $this->messageRepository->getMessageByDelightfulMessageId($delightfulMessageId);

            if (! $messageEntity) {
                $this->logger->warning('Message not found for memory operation update', [
                    'delightful_message_id' => $delightfulMessageId,
                    'action' => $action->value,
                    'memory_ids' => $memoryIds,
                ]);
                return;
            }

            $superAgentMessage = $messageEntity->getContent();
            if (! $superAgentMessage instanceof BeAgentMessage) {
                return;
            }

            // setting MemoryOperation
            $superAgentMessage->setMemoryOperation([
                'action' => $action->value,
                'memory_id' => $memoryIds[0] ?? null,
                'scenario' => MemoryOperationScenario::MEMORY_CARD_QUICK->value,
            ]);

            // 更新message内容
            $updatedContent = $superAgentMessage->toArray();
            $this->messageRepository->updateMessageContent($delightfulMessageId, $updatedContent);
        } catch (Throwable $e) {
            // 静默handle更新fail，不影响主要process
            $this->logger->warning('Failed to update message with memory operation', [
                'delightful_message_id' => $delightfulMessageId,
                'action' => $action->value,
                'memory_ids' => $memoryIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
