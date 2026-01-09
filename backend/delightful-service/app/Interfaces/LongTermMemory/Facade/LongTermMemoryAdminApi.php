<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\LongTermMemory\Facade;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\LongTermMemory\DTO\EvaluateConversationRequestDTO;
use App\Application\LongTermMemory\Enum\AppCodeEnum;
use App\Application\LongTermMemory\Service\LongTermMemoryAppService;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\LongTermMemory\DTO\CreateMemoryDTO;
use App\Domain\LongTermMemory\DTO\MemoryQueryDTO;
use App\Domain\LongTermMemory\DTO\UpdateMemoryDTO;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryStatus;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\AbstractApi;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DelightfulUserAuthorizationTrait;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationAction;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationScenario;
use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Rule;
use Psr\Log\LoggerInterface;

use function Hyperf\Translation\trans;

/**
 * 长期记忆后台管理 API.
 */
#[ApiResponse('low_code')]
class LongTermMemoryAdminApi extends AbstractApi
{
    use DelightfulUserAuthorizationTrait;

    protected LoggerInterface $logger;

    public function __construct(
        protected RequestInterface $request,
        protected ValidatorFactoryInterface $validator,
        protected LoggerFactory $loggerFactory,
        protected LongTermMemoryAppService $longTermMemoryAppService,
        protected DelightfulChatMessageAppService $delightfulChatMessageAppService,
        protected ModelGatewayMapper $modelGatewayMapper
    ) {
        parent::__construct($request);
        $this->logger = $this->loggerFactory->get(get_class($this));
    }

    /**
     * create记忆.
     */
    public function createMemory(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'explanation' => 'nullable|string',
            'content' => 'required|string',
            'status' => ['string', Rule::enum(MemoryStatus::class)],
            'enabled' => 'nullable|boolean',
            'tags' => 'array｜nullable',
            'project_id' => 'nullable|integer|string',
        ];

        $validatedParams = $this->checkParams($params, $rules);

        // 手动checkcontent长度
        $contentLength = mb_strlen($validatedParams['content']);
        if ($contentLength > 5000) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterValidationFailed,
                'long_term_memory.api.content_length_exceeded'
            );
        }

        $authorization = $this->getAuthorization();
        $dto = new CreateMemoryDTO([
            'content' => $validatedParams['content'],
            'originText' => null,
            'explanation' => $validatedParams['explanation'] ?? null,
            'memoryType' => 'manual_input',
            'status' => $validatedParams['status'] ?? MemoryStatus::ACTIVE->value,
            'enabled' => $validatedParams['enabled'] ?? true,
            'confidence' => 0.8,
            'importance' => 0.8,
            'tags' => $validatedParams['tags'] ?? [],
            'metadata' => [],
            'orgId' => $authorization->getOrganizationCode(),
            'appId' => $authorization->getApplicationCode(),
            'projectId' => isset($validatedParams['project_id']) ? (string) $validatedParams['project_id'] : null,
            'userId' => $authorization->getId(),
            'expiresAt' => null,
        ]);
        $memoryId = $this->longTermMemoryAppService->createMemory($dto);

        return [
            'memory_id' => $memoryId,
            'message' => trans('long_term_memory.api.memory_created_successfully'),
            'content' => $validatedParams['content'],
        ];
    }

    /**
     * update记忆.
     */
    public function updateMemory(string $memoryId, RequestInterface $request): array
    {
        // 1. parameter验证
        $validatedParams = $this->validateUpdateMemoryParams($request);
        $authorization = $this->getAuthorization();

        // 2. permissioncheck
        $ownershipValidation = $this->validateMemoryOwnership($memoryId, $authorization);
        if (! $ownershipValidation['success']) {
            return $ownershipValidation;
        }

        // 3. 处理contentupdate并buildDTO
        $dto = $this->buildUpdateMemoryDTO(
            $validatedParams['content'] ?? null,
            $validatedParams['pending_content'] ?? null
        );
        $this->longTermMemoryAppService->updateMemory($memoryId, $dto);

        return [
            'success' => true,
            'message' => trans('long_term_memory.api.memory_updated_successfully'),
        ];
    }

    /**
     * delete记忆.
     */
    public function deleteMemory(string $memoryId): array
    {
        $authorization = $this->getAuthorization();

        // checkpermission
        if (! $this->longTermMemoryAppService->areMemoriesBelongToUser(
            [$memoryId],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        )) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.memory_not_belong_to_user'),
            ];
        }

        $this->longTermMemoryAppService->deleteMemory($memoryId);

        return [
            'success' => true,
            'message' => trans('long_term_memory.api.memory_deleted_successfully'),
        ];
    }

    /**
     * get记忆详情.
     */
    public function getMemory(string $memoryId): array
    {
        $authorization = $this->getAuthorization();

        // checkpermission
        if (! $this->longTermMemoryAppService->areMemoriesBelongToUser(
            [$memoryId],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        )) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.memory_not_belong_to_user'),
            ];
        }

        $memory = $this->longTermMemoryAppService->getMemory($memoryId);

        // get项目name
        $projectName = null;
        if ($memory->getProjectId()) {
            $projectName = $this->longTermMemoryAppService->getProjectNameById($memory->getProjectId());
        }

        return [
            'success' => true,
            'data' => [
                'id' => $memory->getId(),
                'content' => $memory->getContent(),
                'pending_content' => $memory->getPendingContent(),
                'origin_text' => $memory->getOriginText(),
                'memory_type' => $memory->getMemoryType()->value,
                'status' => $memory->getStatus()->value,
                'status_description' => $memory->getStatus()->getDescription(),
                'project_id' => $memory->getProjectId(),
                'project_name' => $projectName,
                'confidence' => $memory->getConfidence(),
                'importance' => $memory->getImportance(),
                'access_count' => $memory->getAccessCount(),
                'reinforcement_count' => $memory->getReinforcementCount(),
                'decay_factor' => $memory->getDecayFactor(),
                'tags' => $memory->getTags(),
                'metadata' => $memory->getMetadata(),
                'last_accessed_at' => $memory->getLastAccessedAt()?->format('Y-m-d H:i:s'),
                'last_reinforced_at' => $memory->getLastReinforcedAt()?->format('Y-m-d H:i:s'),
                'expires_at' => $memory->getExpiresAt()?->format('Y-m-d H:i:s'),
                'created_at' => $memory->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $memory->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'effective_score' => $memory->getEffectiveScore(),
            ],
        ];
    }

    /**
     * get记忆list.
     */
    public function getMemoryList(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'status' => 'array',
            'status.*' => ['string', Rule::enum(MemoryStatus::class)],
            'enabled' => 'boolean',
            'page_token' => 'string',
            'page_size' => 'integer|min:1|max:100',
        ];

        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();
        $pageSize = empty($validatedParams['page_size']) ? 20 : $validatedParams['page_size'];
        $status = empty($validatedParams['status']) ? null : $validatedParams['status'];
        $enabled = array_key_exists('enabled', $validatedParams) ? $validatedParams['enabled'] : null;
        $dto = new MemoryQueryDTO([
            'orgId' => $authorization->getOrganizationCode(),
            'appId' => AppCodeEnum::SUPER_DELIGHTFUL->value,
            'userId' => $authorization->getId(),
            'status' => $status,
            'enabled' => $enabled,
            'pageToken' => $validatedParams['page_token'] ?? null,
            'limit' => (int) $pageSize, // 传递原始页面大小，让应用service层处理pagination逻辑
        ]);
        // 解析 pageToken
        $dto->parsePageToken();
        $result = $this->longTermMemoryAppService->findMemories($dto);

        // 按updatetime降序sort（PHP sort）
        if (isset($result['data']) && is_array($result['data'])) {
            usort($result['data'], static function (array $a, array $b) {
                $timeB = isset($b['updated_at']) && ! empty($b['updated_at']) ? strtotime($b['updated_at']) : 0;
                $timeA = isset($a['updated_at']) && ! empty($a['updated_at']) ? strtotime($a['updated_at']) : 0;

                if ($timeB === $timeA) {
                    return strcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? ''));
                }

                return $timeB <=> $timeA;
            });
        }

        return $result;
    }

    /**
     * search记忆.
     */
    public function searchMemories(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'keyword' => 'required|string|min:1',
        ];

        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();

        $data = $this->longTermMemoryAppService->searchMemoriesWithProjectNames(
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId(),
            $validatedParams['keyword']
        );

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * 强化记忆.
     */
    public function reinforceMemory(string $memoryId): array
    {
        $authorization = $this->getAuthorization();

        // 批量验证记忆是否属于当前user
        $allMemoriesBelongToUser = $this->longTermMemoryAppService->areMemoriesBelongToUser(
            [$memoryId],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        );

        // check是否有不属于user的记忆
        if (! $allMemoriesBelongToUser) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.memory_not_belong_to_user'),
            ];
        }

        $this->longTermMemoryAppService->reinforceMemories([$memoryId]);

        return [
            'success' => true,
            'message' => trans('long_term_memory.api.memory_reinforced_successfully'),
        ];
    }

    /**
     * 批量强化记忆.
     */
    public function reinforceMemories(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'memory_ids' => 'required|array',
            'memory_ids.*' => 'string',
        ];

        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();

        // 批量验证所有记忆都属于当前user
        $allMemoriesBelongToUser = $this->longTermMemoryAppService->areMemoriesBelongToUser(
            $validatedParams['memory_ids'],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        );

        // check是否有不属于user的记忆
        if (! $allMemoriesBelongToUser) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.partial_memory_not_belong_to_user'),
            ];
        }

        $this->longTermMemoryAppService->reinforceMemories($validatedParams['memory_ids']);

        return [
            'success' => true,
            'message' => trans('long_term_memory.api.memories_batch_reinforced_successfully'),
        ];
    }

    /**
     * 批量处理记忆建议（接受/拒绝）.
     */
    public function batchProcessMemorySuggestions(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'memory_ids' => 'required|array|min:1',
            'memory_ids.*' => 'required|string',
            'action' => 'required|string|in:accept,reject',
            'scenario' => 'nullable|string|in:admin_panel,memory_card_quick',
            'delightful_message_id' => 'nullable|string',
        ];

        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();

        // 批量验证所有记忆都属于当前user
        $allMemoriesBelongToUser = $this->longTermMemoryAppService->areMemoriesBelongToUser(
            $validatedParams['memory_ids'],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        );

        // check是否有不属于user的记忆
        if (! $allMemoriesBelongToUser) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.partial_memory_not_belong_to_user'),
            ];
        }

        $action = $validatedParams['action'];
        $memoryIds = $validatedParams['memory_ids'];
        $scenarioString = $validatedParams['scenario'] ?? 'admin_panel'; // 默认为管理后台
        $scenario = MemoryOperationScenario::from($scenarioString);

        // 验证当 scenario 是 memory_card_quick 时，delightful_message_id 必须提供
        if ($scenarioString === 'memory_card_quick' && empty($validatedParams['delightful_message_id'])) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.delightful_message_id_required_for_memory_card_quick'),
            ];
        }

        try {
            if ($action === 'accept') {
                // 批量接受记忆建议：status 改为 accept，enabled 为 true
                $this->longTermMemoryAppService->batchProcessMemorySuggestions($memoryIds, MemoryOperationAction::ACCEPT, $scenario, $validatedParams['delightful_message_id'] ?? null);

                return [
                    'success' => true,
                    'message' => trans('long_term_memory.api.memories_accepted_successfully', ['count' => count($memoryIds)]),
                    'processed_count' => count($memoryIds),
                    'action' => 'accept',
                    'scenario' => $scenario->value,
                ];
            }
            // delete记忆或者拒绝update记忆
            $this->longTermMemoryAppService->batchProcessMemorySuggestions($memoryIds, MemoryOperationAction::REJECT, $scenario, $validatedParams['delightful_message_id'] ?? null);

            return [
                'success' => true,
                'message' => trans('long_term_memory.api.memories_rejected_successfully', ['count' => count($memoryIds)]),
                'processed_count' => count($memoryIds),
                'action' => 'reject',
                'scenario' => $scenario->value,
            ];
        } catch (Exception $e) {
            $actionText = $validatedParams['action'] === 'accept'
                ? trans('long_term_memory.api.action_accept')
                : trans('long_term_memory.api.action_reject');
            $this->logger->error(trans('long_term_memory.api.batch_process_memories_failed'), [
                'memory_ids' => $validatedParams['memory_ids'],
                'action' => $validatedParams['action'],
                'scenario' => $scenario->value,
                'user_id' => $authorization->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => trans('long_term_memory.api.batch_action_memories_failed', ['action' => $actionText, 'error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * 批量update记忆启用status.
     */
    public function batchUpdateMemoryStatus(RequestInterface $request): array
    {
        $params = $this->checkParams($request->all(), [
            'memory_ids' => 'required|array|min:1',
            'memory_ids.*' => 'required|string|max:36',
            'enabled' => 'required|boolean',
        ]);

        $authorization = $this->getAuthorization();
        $result = $this->longTermMemoryAppService->batchUpdateMemoryStatus(
            $params['memory_ids'],
            $params['enabled'],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        );

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * get记忆统计.
     */
    public function getMemoryStats(): array
    {
        $authorization = $this->getAuthorization();
        $stats = $this->longTermMemoryAppService->getMemoryStats(
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        );

        return [
            'success' => true,
            'data' => $stats->toArray(),
        ];
    }

    /**
     * get记忆hint词.
     */
    public function getMemoryPrompt(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'max_length' => 'integer|min:100|max:8000',
            'project_id' => 'string|max:36',
        ];
        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();

        $prompt = $this->longTermMemoryAppService->buildMemoryPrompt(
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId(),
            $validatedParams['project_id'] ?? null,
            $validatedParams['max_length'] ?? 4000
        );
        return [
            'success' => true,
            'data' => [
                'prompt' => $prompt,
            ],
        ];
    }

    /**
     * 评估conversationcontent以create记忆.
     */
    public function evaluateConversation(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'model_name' => 'string',
            'conversation_content' => 'string|max:65535',
            'app_id' => 'string',
            'tags' => 'array',
        ];

        $validatedParams = $this->checkParams($params, $rules);
        $authorization = $this->getAuthorization();

        $dto = new EvaluateConversationRequestDTO([
            'modelName' => $validatedParams['model_name'] ?? 'deepseek-v3',
            'conversationContent' => $validatedParams['conversation_content'] ?? '',
            'appId' => $validatedParams['app_id'] ?? $authorization->getApplicationCode(),
            'tags' => $validatedParams['tags'] ?? [],
        ]);

        return $this->longTermMemoryAppService->evaluateAndCreateMemory($dto, $authorization);
    }

    /**
     * 校验请求parameter.
     */
    protected function checkParams(array $params, array $rules, ?string $method = null): array
    {
        $validator = $this->validator->make($params, $rules);

        if ($validator->fails()) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterValidationFailed,
                'long_term_memory.api.validation_failed',
                ['errors' => implode(',', $validator->errors()->keys())]
            );
        }

        return $validator->validated();
    }

    /**
     * 验证update记忆的请求parameter.
     */
    private function validateUpdateMemoryParams(RequestInterface $request): array
    {
        $params = $request->all();
        $rules = [
            'content' => 'nullable|string',
            'pending_content' => 'nullable|string',
        ];

        $validatedParams = $this->checkParams($params, $rules);

        // 验证content和pending_content只能二选一
        $hasContent = ! empty($validatedParams['content']);
        $hasPendingContent = ! empty($validatedParams['pending_content']);

        if (! $hasContent && ! $hasPendingContent) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterValidationFailed,
                'long_term_memory.api.at_least_one_content_field_required'
            );
        }

        if ($hasContent && $hasPendingContent) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterValidationFailed,
                'long_term_memory.api.cannot_update_both_content_fields'
            );
        }

        // 手动checkcontent长度
        if (isset($validatedParams['content'])) {
            $contentLength = mb_strlen($validatedParams['content']);
            if ($contentLength > 5000) {
                ExceptionBuilder::throw(
                    GenericErrorCode::ParameterValidationFailed,
                    'long_term_memory.api.content_length_exceeded'
                );
            }
        }

        // 手动check pending_content 长度
        if (isset($validatedParams['pending_content'])) {
            $contentLength = mb_strlen($validatedParams['pending_content']);
            if ($contentLength > 5000) {
                ExceptionBuilder::throw(
                    GenericErrorCode::ParameterValidationFailed,
                    'long_term_memory.api.pending_content_length_exceeded'
                );
            }
        }

        return $validatedParams;
    }

    /**
     * 验证记忆所有权.
     *
     * @param mixed $authorization
     * @return array{success: bool, message?: string}
     */
    private function validateMemoryOwnership(string $memoryId, $authorization): array
    {
        if (! $this->longTermMemoryAppService->areMemoriesBelongToUser(
            [$memoryId],
            $authorization->getOrganizationCode(),
            $authorization->getApplicationCode(),
            $authorization->getId()
        )) {
            return [
                'success' => false,
                'message' => trans('long_term_memory.api.memory_not_belong_to_user'),
            ];
        }

        return ['success' => true];
    }

    /**
     * 处理contentupdate并buildupdate记忆的DTO.
     */
    private function buildUpdateMemoryDTO(?string $inputContent, ?string $inputPendingContent = null): UpdateMemoryDTO
    {
        // buildDTO（长度check已在parameter验证阶段complete，且至少有一个field不为空）
        $status = null;
        $explanation = null;

        // 如果update了content，setstatus为ACTIVE
        if ($inputContent !== null) {
            $status = MemoryStatus::ACTIVE->value;
            $explanation = trans('long_term_memory.api.user_manual_edit_explanation');
        }

        return new UpdateMemoryDTO([
            'content' => $inputContent,
            'pendingContent' => $inputPendingContent,
            'status' => $status,
            'explanation' => $explanation,
            'originText' => null,
            'tags' => null,
        ]);
    }
}
