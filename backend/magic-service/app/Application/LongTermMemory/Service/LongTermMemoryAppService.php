<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\LongTermMemory\Service;

use App\Application\LongTermMemory\DTO\EvaluateConversationRequestDTO;
use App\Application\LongTermMemory\DTO\ShouldRememberDTO;
use App\Application\LongTermMemory\Enum\MemoryEvaluationStatus;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Application\ModelGateway\Service\ModelConfigAppService;
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Domain\LongTermMemory\DTO\CreateMemoryDTO;
use App\Domain\LongTermMemory\DTO\MemoryQueryDTO;
use App\Domain\LongTermMemory\DTO\MemoryStatsDTO;
use App\Domain\LongTermMemory\DTO\UpdateMemoryDTO;
use App\Domain\LongTermMemory\Entity\LongTermMemoryEntity;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryType;
use App\Domain\LongTermMemory\Service\LongTermMemoryDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\ErrorCode\LongTermMemoryErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\LLMParse\LLMResponseParseUtil;
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Dtyq\SuperMagic\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationAction;
use Dtyq\SuperMagic\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationScenario;
use Dtyq\SuperMagic\Domain\SuperAgent\Service\ProjectDomainService;
use Hyperf\Odin\Contract\Model\ModelInterface;
use Hyperf\Odin\Message\SystemMessage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * Long-term memory application service.
 */
class LongTermMemoryAppService
{
    private const MEMORY_SCORE_THRESHOLD = 3; // Default threshold for memory creation

    public function __construct(
        private readonly LongTermMemoryDomainService $longTermMemoryDomainService,
        private readonly ModelGatewayMapper $modelGatewayMapper,
        private readonly LoggerInterface $logger,
        private readonly ProjectDomainService $projectDomainService,
    ) {
    }

    /**
     * Create a memory.
     */
    public function createMemory(CreateMemoryDTO $dto): string
    {
        // Business rules validation
        $this->validateMemoryContent($dto->content);
        $this->validateMemoryPendingContent($dto->pendingContent);

        // If a project ID is provided, verify existence and user permission
        if ($dto->projectId !== null) {
            $this->validateProjectAccess($dto->projectId, $dto->orgId, $dto->userId);
        }

        return $this->longTermMemoryDomainService->create($dto);
    }

    /**
     * Update a memory.
     */
    public function updateMemory(string $memoryId, UpdateMemoryDTO $dto): void
    {
        // Business rules validation
        if ($dto->content !== null) {
            $this->validateMemoryContent($dto->content);
        }
        if ($dto->pendingContent !== null) {
            $this->validateMemoryPendingContent($dto->pendingContent);
        }
        $this->longTermMemoryDomainService->updateMemory($memoryId, $dto);
    }

    /**
     * Delete a memory.
     */
    public function deleteMemory(string $memoryId): void
    {
        $this->longTermMemoryDomainService->deleteMemory($memoryId);
    }

    /**
     * Get memory details.
     */
    public function getMemory(string $memoryId): LongTermMemoryEntity
    {
        $memory = $this->longTermMemoryDomainService->findById($memoryId);

        if (! $memory) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::MEMORY_NOT_FOUND);
        }

        // Record access
        $this->longTermMemoryDomainService->accessMemory($memoryId);

        return $memory;
    }

    /**
     * General query method (using MemoryQueryDTO).
     * @return array{success: bool, data: array, has_more: bool, next_page_token: null|string, total: int}
     */
    public function findMemories(MemoryQueryDTO $dto): array
    {
        // Get total count (ignoring limit and offset)
        $countDto = clone $dto;
        $countDto->limit = 0; // No row limit
        $countDto->offset = 0; // No offset
        $total = $this->longTermMemoryDomainService->countMemories($countDto);

        // Store original page size
        $originalPageSize = $dto->limit;

        // Fetch one extra record to detect if there is a next page
        $queryDto = clone $dto;
        $queryDto->limit = $originalPageSize + 1;

        $memories = $this->longTermMemoryDomainService->findMemories($queryDto);

        // Handle pagination results
        $hasMore = count($memories) > $originalPageSize;
        if ($hasMore) {
            // Remove the extra fetched record
            array_pop($memories);
        }

        $nextPageToken = null;
        if ($hasMore) {
            // Generate next page token; offset advances by the original page size
            $nextOffset = $dto->offset + $originalPageSize;
            $nextPageToken = MemoryQueryDTO::generatePageToken($nextOffset);
        }

        $result = [
            'data' => $memories,
            'hasMore' => $hasMore,
            'nextPageToken' => $nextPageToken,
        ];

        // Collect project IDs and fetch project names in batch
        $projectIds = [];
        foreach ($result['data'] as $memory) {
            $projectId = $memory->getProjectId();
            if ($projectId && ! in_array($projectId, $projectIds)) {
                $projectIds[] = $projectId;
            }
        }

        $projectNames = $this->getProjectNamesBatch($projectIds);

        $data = array_map(function (LongTermMemoryEntity $memory) use ($projectNames) {
            $memoryArray = $memory->toArray();
            $projectId = $memory->getProjectId();
            $memoryArray['project_name'] = $projectId && isset($projectNames[$projectId]) ? $projectNames[$projectId] : null;
            return $memoryArray;
        }, $result['data']);

        return [
            'success' => true,
            'data' => $data,
            'has_more' => $result['hasMore'],
            'next_page_token' => $result['nextPageToken'],
            'total' => $total,
        ];
    }

    /**
     * Get project name by project ID.
     */
    public function getProjectNameById(?string $projectId): ?string
    {
        if ($projectId === null || $projectId === '') {
            return null;
        }

        try {
            $project = $this->projectDomainService->getProjectNotUserId((int) $projectId);
            return $project->getProjectName();
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get project names in batch.
     *
     * @param array $projectIds Array of project IDs
     * @return array Map of project ID => project name
     */
    public function getProjectNamesBatch(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        // Cast to int array
        $intIds = array_map('intval', $projectIds);

        // Query projects in batch
        $projects = $this->projectDomainService->getProjectsByIds($intIds);

        // Build project ID => project name map
        $projectNames = [];
        foreach ($projects as $project) {
            $projectNames[(string) $project->getId()] = $project->getProjectName();
        }

        return $projectNames;
    }

    /**
     * Get effective memories for system prompts.
     */
    public function getEffectiveMemoriesForPrompt(string $orgId, string $appId, string $userId, ?string $projectId, int $maxLength = 4000): string
    {
        return $this->longTermMemoryDomainService->getEffectiveMemoriesForPrompt($orgId, $appId, $userId, $projectId, $maxLength);
    }

    /**
     * Reinforce a memory.
     */
    public function reinforceMemory(string $memoryId): void
    {
        $this->reinforceMemories([$memoryId]);
    }

    /**
     * Reinforce memories in batch.
     */
    public function reinforceMemories(array $memoryIds): void
    {
        $this->longTermMemoryDomainService->reinforceMemories($memoryIds);
    }

    /**
     * Batch process memory suggestions (accept/reject).
     */
    public function batchProcessMemorySuggestions(array $memoryIds, MemoryOperationAction $action, MemoryOperationScenario $scenario = MemoryOperationScenario::ADMIN_PANEL, ?string $magicMessageId = null): void
    {
        $this->longTermMemoryDomainService->batchProcessMemorySuggestions($memoryIds, $action, $scenario, $magicMessageId);
    }

    /**
     * Get memory statistics.
     */
    public function getMemoryStats(string $orgId, string $appId, string $userId): MemoryStatsDTO
    {
        $stats = $this->longTermMemoryDomainService->getMemoryStats($orgId, $appId, $userId);

        return new MemoryStatsDTO($stats);
    }

    /**
     * Search memories.
     */
    public function searchMemories(string $orgId, string $appId, string $userId, string $keyword): array
    {
        $queryDto = new MemoryQueryDTO([
            'orgId' => $orgId,
            'appId' => $appId,
            'userId' => $userId,
            'keyword' => $keyword,
        ]);

        $memories = $this->longTermMemoryDomainService->findMemories($queryDto);

        // Record access
        $memoryIds = array_map(fn ($memory) => $memory->getId(), $memories);
        $this->longTermMemoryDomainService->accessMemories($memoryIds);

        return $memories;
    }

    /**
     * Search memories (with project names).
     */
    public function searchMemoriesWithProjectNames(string $orgId, string $appId, string $userId, string $keyword): array
    {
        $memories = $this->searchMemories($orgId, $appId, $userId, $keyword);

        // Collect project IDs and fetch project names in batch
        $projectIds = [];
        foreach ($memories as $memory) {
            $projectId = $memory->getProjectId();
            if ($projectId && ! in_array($projectId, $projectIds)) {
                $projectIds[] = $projectId;
            }
        }

        $projectNames = $this->getProjectNamesBatch($projectIds);

        return array_map(function (LongTermMemoryEntity $memory) use ($projectNames) {
            $memoryArray = $memory->toArray();
            $projectId = $memory->getProjectId();
            $memoryArray['project_name'] = $projectId && isset($projectNames[$projectId]) ? $projectNames[$projectId] : null;
            return $memoryArray;
        }, $memories);
    }

    /**
     * Build memory prompt content.
     */
    public function buildMemoryPrompt(string $orgId, string $appId, string $userId, ?string $projectId, int $maxLength = 4000): string
    {
        return $this->getEffectiveMemoriesForPrompt($orgId, $appId, $userId, $projectId, $maxLength);
    }

    /**
     * Check whether a memory belongs to the user.
     * @deprecated Use areMemoriesBelongToUser instead
     */
    public function isMemoryBelongToUser(string $memoryId, string $orgId, string $appId, string $userId): bool
    {
        return $this->areMemoriesBelongToUser([$memoryId], $orgId, $appId, $userId);
    }

    /**
     * Check in bulk whether memories belong to the user.
     */
    public function areMemoriesBelongToUser(array $memoryIds, string $orgId, string $appId, string $userId): bool
    {
        $validMemoryIds = $this->longTermMemoryDomainService->filterMemoriesByUser($memoryIds, $orgId, $appId, $userId);

        // Ensure all memories belong to the user
        return count($validMemoryIds) === count($memoryIds);
    }

    /**
     * Evaluate conversation content and optionally create a memory.
     */
    public function evaluateAndCreateMemory(
        EvaluateConversationRequestDTO $dto,
        MagicUserAuthorization $authorization
    ): array {
        try {
            // 1. Fetch chat model
            $model = $this->getChatModel($authorization);

            // 2. Decide whether to remember
            $shouldRemember = $this->shouldRememberContent($model, $dto);

            if (! $shouldRemember->remember) {
                return ['status' => MemoryEvaluationStatus::NO_MEMORY_NEEDED->value, 'reason' => $shouldRemember->explanation];
            }

            // 3. If needed, score the memory
            $score = $this->rateMemory($model, $shouldRemember->memory);

            // 4. Create the memory if the score exceeds the threshold
            if ($score >= self::MEMORY_SCORE_THRESHOLD) {
                $createDto = new CreateMemoryDTO([
                    'orgId' => $authorization->getOrganizationCode(),
                    'appId' => $dto->appId,
                    'userId' => $authorization->getId(),
                    'memoryType' => MemoryType::CONVERSATION_ANALYSIS->value,
                    'content' => $shouldRemember->memory,
                    'explanation' => $shouldRemember->explanation,
                    'tags' => array_merge($dto->tags, $shouldRemember->tags), // Merge external tags with LLM-generated tags
                ]);
                $memoryId = $this->createMemory($createDto);
                return ['status' => MemoryEvaluationStatus::CREATED->value, 'memory_id' => $memoryId, 'score' => $score];
            }

            return ['status' => MemoryEvaluationStatus::NOT_CREATED_LOW_SCORE->value, 'score' => $score];
        } catch (Throwable $e) {
            $this->logger->error('Failed to evaluate and create memory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw with a specific error code if it's not already a structured exception
            if ($e instanceof BusinessException) {
                throw $e;
            }
            ExceptionBuilder::throw(LongTermMemoryErrorCode::GENERAL_ERROR, throwable: $e);
        }
    }

    /**
     * Score a memory.
     */
    public function rateMemory(ModelInterface $model, string $memory): int
    {
        $promptFile = BASE_PATH . '/app/Application/LongTermMemory/Prompt/MemoryPrompt.text';
        $prompt = $this->loadPromptFile($promptFile);

        $prompt = str_replace(['${topic.messages}', '${a.memory}'], [$memory, $memory], $prompt);

        try {
            // Use a system prompt
            $response = $model->chat([new SystemMessage($prompt)]);
            $content = $response->getFirstChoice()?->getMessage()->getContent();
        } catch (Throwable $e) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_LLM_REQUEST_FAILED, throwable: $e);
        }

        if (preg_match('/SCORE:\s*(\d+)/', $content, $matches)) {
            return (int) $matches[1];
        }

        ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_SCORE_PARSE_FAILED);
    }

    /**
     * Decide whether to remember content.
     */
    public function shouldRememberContent(ModelInterface $model, EvaluateConversationRequestDTO $dto): ShouldRememberDTO
    {
        $promptFile = BASE_PATH . '/app/Application/LongTermMemory/Prompt/MemoryRatingPrompt.txt';
        $prompt = $this->loadPromptFile($promptFile);

        $prompt = str_replace('${topic.messages}', $dto->conversationContent, $prompt);

        try {
            // Use a system prompt
            $response = $model->chat([new SystemMessage($prompt)]);
            $firstChoiceContent = $response->getFirstChoice()?->getMessage()->getContent();
        } catch (Throwable $e) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_LLM_REQUEST_FAILED, throwable: $e);
        }

        if (empty($firstChoiceContent)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_LLM_RESPONSE_PARSE_FAILED);
        }

        // Handle non-JSON "no_memory_needed" response
        if (strlen($firstChoiceContent) < 20 && str_contains($firstChoiceContent, 'no_memory_needed')) {
            return new ShouldRememberDTO(['remember' => false, 'memory' => 'no_memory_needed', 'explanation' => 'LLM determined no memory was needed.', 'tags' => []]);
        }

        $parsed = LLMResponseParseUtil::parseJson($firstChoiceContent);

        if (! $parsed) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_LLM_RESPONSE_PARSE_FAILED);
        }

        if (isset($parsed['memory']) && str_contains($parsed['memory'], 'no_memory_needed')) {
            return new ShouldRememberDTO(['remember' => false, 'memory' => 'no_memory_needed', 'explanation' => $parsed['explanation'] ?? 'LLM determined no memory was needed.', 'tags' => []]);
        }

        if (! isset($parsed['memory'], $parsed['explanation'])) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::EVALUATION_LLM_RESPONSE_PARSE_FAILED);
        }

        return new ShouldRememberDTO(['remember' => true, 'memory' => $parsed['memory'], 'explanation' => $parsed['explanation'], 'tags' => $parsed['tags'] ?? []]);
    }

    /**
     * Batch update memory enabled status.
     */
    public function batchUpdateMemoryStatus(array $memoryIds, bool $enabled, string $orgId, string $appId, string $userId): array
    {
        $updatedCount = $this->longTermMemoryDomainService->batchUpdateEnabled(
            $memoryIds,
            $enabled,
            $orgId,
            $appId,
            $userId
        );

        return [
            'updated_count' => $updatedCount,
            'requested_count' => count($memoryIds),
        ];
    }

    /**
     * Get chat model.
     */
    private function getChatModel(MagicUserAuthorization $authorization): ModelInterface
    {
        $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
            LLMModelEnum::DEEPSEEK_V3->value
        );
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($authorization->getOrganizationCode(), $authorization->getId());
        return $this->modelGatewayMapper->getChatModelProxy($dataIsolation, $modelName);
    }

    /**
     * Load prompt file.
     */
    private function loadPromptFile(string $filePath): string
    {
        if (! file_exists($filePath)) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::PROMPT_FILE_NOT_FOUND, $filePath);
        }
        return file_get_contents($filePath);
    }

    /**
     * Validate memory content length.
     */
    private function validateMemoryContent(string $content): void
    {
        if (mb_strlen($content) > 65535) {
            throw new InvalidArgumentException(trans('long_term_memory.entity.content_too_long'));
        }
    }

    /**
     * Validate pending memory content length.
     */
    private function validateMemoryPendingContent(?string $pendingContent): void
    {
        if ($pendingContent !== null && mb_strlen($pendingContent) > 65535) {
            throw new InvalidArgumentException(trans('long_term_memory.entity.pending_content_too_long'));
        }
    }

    /**
     * Validate project access.
     * Ensure the project exists and belongs to the current user.
     * Note: only project owners can create project-specific memories.
     */
    private function validateProjectAccess(string $projectId, string $orgId, string $userId): void
    {
        // Fetch project via ProjectDomainService
        $project = $this->projectDomainService->getProjectNotUserId((int) $projectId);
        if ($project === null) {
            ExceptionBuilder::throw(LongTermMemoryErrorCode::PROJECT_NOT_FOUND);
        }

        // Verify organization code matches
        if ($project->getUserOrganizationCode() !== $orgId) {
            $this->logger->warning('Project organization code mismatch', [
                'projectId' => $projectId,
                'expected' => $orgId,
                'actual' => $project->getUserOrganizationCode(),
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::PROJECT_ACCESS_DENIED);
        }

        // Verify user is the project owner
        if ($project->getUserId() !== $userId) {
            $this->logger->warning('Project user ID mismatch', [
                'projectId' => $projectId,
                'expected' => $userId,
                'actual' => $project->getUserId(),
            ]);
            ExceptionBuilder::throw(LongTermMemoryErrorCode::PROJECT_ACCESS_DENIED);
        }

        $this->logger->debug('Project access validation successful', ['projectId' => $projectId]);
    }
}
