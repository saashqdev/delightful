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
 * long-termmemorydomainservice
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
     * batchquantitystrong化memory.
     */
    public function reinforceMemories(array $memoryIds): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // generatelocknameand haveperson(based onmemoryIDsortbackgenerateuniqueonelockname)
        sort($memoryIds);
        $lockName = 'memory:batch:reinforce:' . md5(implode(',', $memoryIds));
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            $this->logger->error('Failed to acquire lock for batch memory reinforcement', [
                'lock_name' => $lockName,
                'memory_ids' => $memoryIds,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            // batchquantityquerymemory
            $memories = $this->repository->findByIds($memoryIds);

            if (empty($memories)) {
                $this->logger->debug('No memories found for reinforcement', ['memory_ids' => $memoryIds]);
                return;
            }

            // batchquantitystrong化
            foreach ($memories as $memory) {
                $memory->reinforce();
            }

            // batchquantitysaveupdate
            if (! $this->repository->updateBatch($memories)) {
                $this->logger->error('Failed to batch reinforce memories', ['memory_ids' => $memoryIds]);
                ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
            }

            $this->logger->info('Batch reinforced memories successfully', ['count' => count($memories)]);
        } finally {
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * batchquantityhandlememorysuggestion(accept/reject).
     */
    public function batchProcessMemorySuggestions(array $memoryIds, MemoryOperationAction $action, MemoryOperationScenario $scenario = MemoryOperationScenario::ADMIN_PANEL, ?string $delightfulMessageId = null): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // validatewhen scenario is memory_card_quick o clock,delightfulMessageId mustprovide
        if ($scenario === MemoryOperationScenario::MEMORY_CARD_QUICK && empty($delightfulMessageId)) {
            throw new InvalidArgumentException('delightful_message_id is required when scenario is memory_card_quick');
        }

        // generatelocknameand haveperson(based onmemoryIDsortbackgenerateuniqueonelockname)
        sort($memoryIds);
        $lockName = sprintf('memory:batch:%s:%s:%s', $action->value, $scenario->value, md5(implode(',', $memoryIds)));
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            if ($action === MemoryOperationAction::ACCEPT) {
                // batchquantityquerymemory
                $memories = $this->repository->findByIds($memoryIds);

                // batchquantityacceptmemorysuggestion:willpending_contentmovetocontent,settingstatusforalreadyaccept,enablememory
                foreach ($memories as $memory) {
                    // ifhavepending_content,thenwillitsmovetocontent
                    if ($memory->getPendingContent() !== null) {
                        // willpending_contentvaluecopytocontentfield
                        $memory->setContent($memory->getPendingContent());
                        // clearnullpending_contentfield
                        $memory->setPendingContent(null);
                    }

                    // settingstatusforin effect
                    $memory->setStatus(MemoryStatus::ACTIVE);

                    // enablememory
                    $memory->setEnabledInternal(true);
                }

                // batchquantitysaveupdate
                if (! $this->repository->updateBatch($memories)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
                }
            } elseif ($action === MemoryOperationAction::REJECT) {
                // batchquantityrejectmemorysuggestion:according tomemorystatusdecidedeletealsoisclearnullpending_content
                $memories = $this->repository->findByIds($memoryIds);

                $memoriesToDelete = [];
                $memoriesToUpdate = [];

                foreach ($memories as $memory) {
                    $content = $memory->getContent();
                    $pendingContent = $memory->getPendingContent();

                    // ifcontentfornullandPendingContentnotfornull,directlydeletememory
                    if (empty($content) && ! empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                    // ifcontentandPendingContentallnotfornull,thenclearnullPendingContent即can,notwantdeletememory
                    elseif (! empty($content) && ! empty($pendingContent)) {
                        $memory->setPendingContent(null);
                        $memory->setStatus(MemoryStatus::ACTIVE);
                        $memoriesToUpdate[] = $memory;
                    }
                    // ifcontentnotfornullbutPendingContentfornull,alsodirectlydeletememory(originalhavelogicmaintain)
                    elseif (! empty($content) && empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                    // ifcontentfornullandPendingContentalsofornull,directlydeletememory(originalhavelogicmaintain)
                    elseif (empty($content) && empty($pendingContent)) {
                        $memoriesToDelete[] = $memory->getId();
                    }
                }

                // batchquantitydeleteneeddeletememory
                if (! empty($memoriesToDelete) && ! $this->repository->deleteBatch($memoriesToDelete)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::DELETION_FAILED);
                }

                // batchquantityupdateneedclearnullpending_contentmemory
                if (! empty($memoriesToUpdate) && ! $this->repository->updateBatch($memoriesToUpdate)) {
                    ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
                }
            }

            // ifis memory_card_quick scenario,needupdatetoshouldmessagecontent
            if ($scenario === MemoryOperationScenario::MEMORY_CARD_QUICK && ! empty($delightfulMessageId)) {
                $this->updateMessageWithMemoryOperation($delightfulMessageId, $action, $memoryIds);
            }
        } finally {
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * accessmemory(updateaccessstatistics).
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
     * batchquantityaccessmemory.
     */
    public function accessMemories(array $memoryIds): void
    {
        if (empty($memoryIds)) {
            return;
        }

        // batchquantityquerymemory
        $memories = $this->repository->findByIds($memoryIds);

        if (empty($memories)) {
            $this->logger->debug('No memories found for access tracking', ['memory_ids' => $memoryIds]);
            return;
        }

        // batchquantityupdateaccessstatistics
        foreach ($memories as $memory) {
            $memory->access();
        }

        // batchquantitysaveupdate
        if (! $this->repository->updateBatch($memories)) {
            $this->logger->error('Failed to batch update access stats for memories', ['memory_ids' => $memoryIds]);
        }
    }

    public function create(CreateMemoryDTO $dto): string
    {
        // generatelocknameand haveperson
        $lockName = sprintf('memory:create:%s:%s:%s', $dto->orgId, $dto->appId, $dto->userId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
        if (! $this->locker->mutexLock($lockName, $lockOwner, 30)) {
            $this->logger->error('Failed to acquire lock for memory creation', [
                'lock_name' => $lockName,
                'user_id' => $dto->userId,
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::CREATION_FAILED);
        }

        try {
            // validateusermemoryquantitylimit
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
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    public function updateMemory(string $memoryId, UpdateMemoryDTO $dto): void
    {
        // generatelocknameand haveperson
        $lockName = sprintf('memory:update:%s', $memoryId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
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

            // ifupdatepending_content,needaccording tobusinessruleadjuststatus
            if ($dto->pendingContent !== null) {
                $this->adjustMemoryStatusBasedOnPendingContent($memory, $dto->pendingContent);
            }

            LongTermMemoryAssembler::updateEntityFromDTO($memory, $dto);

            if (! $this->repository->update($memory)) {
                ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
            }

            $this->logger->info('Memory updated successfully: {id}', ['id' => $memoryId]);
        } finally {
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    public function deleteMemory(string $memoryId): void
    {
        // generatelocknameand haveperson
        $lockName = sprintf('memory:delete:%s', $memoryId);
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
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
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * according toprojectIDcolumntablebatchquantitydeletememory.
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @param array $projectIds projectIDcolumntable
     * @return int deleterecordquantity
     */
    public function deleteMemoriesByProjectIds(string $orgId, string $appId, string $userId, array $projectIds): int
    {
        if (empty($projectIds)) {
            return 0;
        }

        // filternullprojectID
        $validProjectIds = array_filter($projectIds, static fn ($id) => ! empty($id));
        if (empty($validProjectIds)) {
            return 0;
        }

        // oneitemSQLbatchquantitydelete
        return $this->repository->deleteByProjectIds($orgId, $appId, $userId, $validProjectIds);
    }

    /**
     * getuservalidmemoryandbuildhintwordstring.
     */
    public function getEffectiveMemoriesForPrompt(string $orgId, string $appId, string $userId, ?string $projectId, int $maxLength = 4000): string
    {
        // getuseralllocal memory(nothaveprojectIDmemory)
        $generalMemoryLimit = MemoryCategory::GENERAL->getEnabledLimit();
        $generalMemories = $this->repository->findEffectiveMemoriesByUser($orgId, $appId, $userId, '', $generalMemoryLimit);

        // getproject相closememory
        $projectMemoryLimit = MemoryCategory::PROJECT->getEnabledLimit();
        $projectMemories = $this->repository->findEffectiveMemoriesByUser($orgId, $appId, $userId, $projectId ?? '', $projectMemoryLimit);

        // mergememory,按minutecountsort
        $memories = array_merge($generalMemories, $projectMemories);

        // filterdropshouldbeeliminatememory
        $validMemories = array_filter($memories, function ($memory) {
            return ! $this->shouldMemoryBeEvicted($memory);
        });

        // 按validminutecountsort
        usort($validMemories, static function ($a, $b) {
            return $b->getEffectiveScore() <=> $a->getEffectiveScore();
        });

        // limittotallength
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

        // recordaccess
        $memoryIds = array_map(static fn ($memory) => $memory->getId(), $selectedMemories);
        $this->accessMemories($memoryIds);

        // buildmemoryhintwordstring
        if (empty($selectedMemories)) {
            return '';
        }

        $prompt = '<userlong-termmemory>';

        foreach ($selectedMemories as $memory) {
            $memoryId = $memory->getId();
            $memoryText = $memory->getContent();
            $prompt .= sprintf("\n[memoryID: %s] %s", $memoryId, $memoryText);
        }

        $prompt .= "\n</userlong-termmemory>";

        return $prompt;
    }

    /**
     * getmemorystatisticsinfo.
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
     * findmemory by ID.
     */
    public function findById(string $memoryId): ?LongTermMemoryEntity
    {
        return $this->repository->findById($memoryId);
    }

    /**
     * 通usequerymethod (use DTO).
     * @return LongTermMemoryEntity[]
     */
    public function findMemories(MemoryQueryDTO $dto): array
    {
        return $this->repository->findMemories($dto);
    }

    /**
     * according toqueryconditionstatisticsmemoryquantity.
     */
    public function countMemories(MemoryQueryDTO $dto): int
    {
        return $this->repository->countMemories($dto);
    }

    /**
     * statisticsusermemoryquantity.
     */
    public function countByUser(string $orgId, string $appId, string $userId): int
    {
        return $this->repository->countByUser($orgId, $appId, $userId);
    }

    /**
     * batchquantitycheckmemorywhetherbelongatuser.
     */
    public function filterMemoriesByUser(array $memoryIds, string $orgId, string $appId, string $userId): array
    {
        return $this->repository->filterMemoriesByUser($memoryIds, $orgId, $appId, $userId);
    }

    /**
     * batchquantityenableordisablememory.
     * @param array $memoryIds memoryIDcolumntable
     * @param bool $enabled enablestatus
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @return int successmorenewrecordquantity
     */
    public function batchUpdateEnabled(array $memoryIds, bool $enabled, string $orgId, string $appId, string $userId): int
    {
        if (empty($memoryIds)) {
            $this->logger->warning('Empty memory IDs list provided for batch enable/disable');
            return 0;
        }

        // generatelocknameand haveperson(based onmemoryIDsortbackgenerateuniqueonelockname)
        sort($memoryIds);
        $enabledStatus = $enabled ? 'enable' : 'disable';
        $lockName = sprintf('memory:batch:%s:%s', $enabledStatus, md5(implode(',', $memoryIds)));
        $lockOwner = getmypid() . '_' . microtime(true);

        // getmutually exclusivelock
        if (! $this->locker->mutexLock($lockName, $lockOwner, 60)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::UPDATE_FAILED);
        }

        try {
            // validatememoryIDvalidpropertyandbelong to权
            $validMemoryIds = $this->repository->filterMemoriesByUser($memoryIds, $orgId, $appId, $userId);
            if (empty($validMemoryIds)) {
                return 0;
            }

            // ifisenablememory,conductquantitylimitcheck
            if ($enabled) {
                $this->validateMemoryEnablementLimits($validMemoryIds, $orgId, $appId, $userId);
            }

            // executebatchquantityupdate
            return $this->repository->batchUpdateEnabled($validMemoryIds, $enabled, $orgId, $appId, $userId);
        } finally {
            // ensurereleaselock
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * judgememorywhethershouldbeeliminate.
     */
    public function shouldMemoryBeEvicted(LongTermMemoryEntity $memory): bool
    {
        // expiretimecheck
        if ($memory->getExpiresAt() && $memory->getExpiresAt() < new DateTime()) {
            return true;
        }

        // validminutecountpasslow
        if ($memory->getEffectiveScore() < 0.1) {
            return true;
        }

        // longtimenotaccessand重wantpropertyverylow
        if ($memory->getLastAccessedAt() && $memory->getImportance() < 0.2) {
            $daysSinceLastAccess = new DateTime()->diff($memory->getLastAccessedAt())->days;
            if ($daysSinceLastAccess > 30) {
                return true;
            }
        }

        return false;
    }

    /**
     * validatememoryenablequantitylimit.
     * @param array $memoryIds wantenablememoryIDcolumntable
     * @param string $orgId organizationID
     * @param string $appId applicationID
     * @param string $userId userID
     * @throws BusinessException whenenablequantityexceedspasslimito clockthrowexception
     */
    private function validateMemoryEnablementLimits(array $memoryIds, string $orgId, string $appId, string $userId): void
    {
        // getwantenablememoryactualbody
        $memoriesToEnable = $this->repository->findByIds($memoryIds);

        // getcurrentprojectmemoryandalllocal memoryenablequantity
        $currentProjectCount = $this->repository->getEnabledMemoryCountByCategory($orgId, $appId, $userId, MemoryCategory::PROJECT);
        $currentGeneralCount = $this->repository->getEnabledMemoryCountByCategory($orgId, $appId, $userId, MemoryCategory::GENERAL);

        $currentEnabledCounts = [
            MemoryCategory::PROJECT->value => $currentProjectCount,
            MemoryCategory::GENERAL->value => $currentGeneralCount,
        ];

        // calculateenablebackeachcategoryotherquantity
        $projectedCounts = $currentEnabledCounts;

        foreach ($memoriesToEnable as $memory) {
            $projectId = $memory->getProjectId();
            $category = MemoryCategory::fromProjectId($projectId);
            $categoryKey = $category->value;

            if (! isset($projectedCounts[$categoryKey])) {
                $projectedCounts[$categoryKey] = 0;
            }

            // onlycurrentnotenablememory才willincreasecount
            if (! $memory->isEnabled()) {
                ++$projectedCounts[$categoryKey];
            }
        }

        // checkwhetherexceedspasslimit
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
     * according topending_contentchangeadjustmemorystatus.
     */
    private function adjustMemoryStatusBasedOnPendingContent(LongTermMemoryEntity $memory, ?string $pendingContent): void
    {
        $currentStatus = $memory->getStatus();
        $hasPendingContent = ! empty($pendingContent);

        // getnewstatus
        $newStatus = $this->determineNewMemoryStatus($currentStatus, $hasPendingContent);

        // onlyinstatusneedaltero clock才update
        if ($newStatus !== $currentStatus) {
            $memory->setStatus($newStatus);
        }
    }

    /**
     * according tocurrentstatusandpending_contentexistsincertainnewstatus.
     */
    private function determineNewMemoryStatus(MemoryStatus $currentStatus, bool $hasPendingContent): MemoryStatus
    {
        // statusconvertmatrix
        return match ([$currentStatus, $hasPendingContent]) {
            // pending_contentfornullo clockstatusconvert
            [MemoryStatus::PENDING_REVISION, false], [MemoryStatus::ACTIVE, false] => MemoryStatus::ACTIVE,        // revisioncomplete → take effect
            [MemoryStatus::PENDING, false], [MemoryStatus::PENDING, true] => MemoryStatus::PENDING,                 // 待acceptstatusmaintainnotchange
            // pending_contentnotfornullo clockstatusconvert
            [MemoryStatus::ACTIVE, true], [MemoryStatus::PENDING_REVISION, true] => MemoryStatus::PENDING_REVISION,         // take effectmemoryhaverevision → 待revision
            // defaultsituation(notshouldto达thiswithin)
            default => $currentStatus,
        };
    }

    /**
     * updatemessagecontent,settingmemoryoperationasinfo.
     */
    private function updateMessageWithMemoryOperation(string $delightfulMessageId, MemoryOperationAction $action, array $memoryIds): void
    {
        try {
            // according to delightful_message_id querymessagedata
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

            // updatemessagecontent
            $updatedContent = $superAgentMessage->toArray();
            $this->messageRepository->updateMessageContent($delightfulMessageId, $updatedContent);
        } catch (Throwable $e) {
            // silenthandleupdatefail,notimpactmainprocess
            $this->logger->warning('Failed to update message with memory operation', [
                'delightful_message_id' => $delightfulMessageId,
                'action' => $action->value,
                'memory_ids' => $memoryIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
