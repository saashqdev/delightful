<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentUtil;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionType;
use App\Application\Flow\ExecuteManager\ExecutionData\Operator;
use App\Application\Flow\ExecuteManager\ExecutionData\TriggerData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Application\Flow\ExecuteManager\Stream\FlowEventStreamManager;
use App\Application\Kernel\EnvManager;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\Agent\SenderExtraDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\ChatInstruction;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\InstructionType;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\ConversationId;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\ErrorCode\FlowErrorCode;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Collector\BuiltInToolSet\BuiltInToolSetCollector;
use App\Infrastructure\Core\Contract\Authorization\FlowOpenApiCheckInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;
use DateTime;
use Delightful\FlowExprEngine\ComponentFactory;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowExecuteAppService extends AbstractFlowAppService
{
    public function imChat(string $flowId, TriggerType $triggerType, array $senderEntities = []): void
    {
        $senderUserEntity = $senderEntities['sender'] ?? null;
        $senderAccountEntity = $senderEntities['sender_account'] ?? null;
        if (! $senderUserEntity instanceof DelightfulUserEntity) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'sender_user_not_found');
        }
        $seqEntity = $senderEntities['seq'] ?? null;
        if (! $seqEntity instanceof DelightfulSeqEntity) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'sender_seq_not_found');
        }
        $messageEntity = $senderEntities['message'] ?? null;
        if (! $messageEntity instanceof DelightfulMessageEntity && ! $seqEntity->canTriggerFlow()) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'sender_message_not_found');
        }

        $envId = 0;
        $senderExtra = $senderEntities['sender_extra'] ?? null;
        if ($senderExtra instanceof SenderExtraDTO) {
            $envId = $senderExtra->getDelightfulEnvId() ?? 0;
        }

        $authorization = new DelightfulUserAuthorization();
        $authorization
            ->setId($senderUserEntity->getUserId())
            ->setOrganizationCode($senderUserEntity->getOrganizationCode())
            ->setUserType($senderUserEntity->getUserType())
            ->setDelightfulEnvId($envId);

        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $dataIsolation->setContainOfficialOrganization(true);
        $flowData = $this->getFlow($dataIsolation, $flowId, [Type::Main]);
        $delightfulFlow = $flowData['flow'];

        $triggerData = new TriggerData(
            triggerTime: new DateTime($messageEntity?->getSendTime() ?? $seqEntity->getCreatedAt()),
            userInfo: ['user_entity' => $senderUserEntity, 'account_entity' => $senderAccountEntity],
            messageInfo: ['message_entity' => $messageEntity, 'seq_entity' => $seqEntity],
            globalVariable: $delightfulFlow->getGlobalVariable(),
            isIgnoreMessageEntity: $seqEntity->canTriggerFlow(),
        );
        $operator = $this->createExecutionOperator($authorization);
        $operator->setSourceId('im_chat');
        $executionData = new ExecutionData(
            flowDataIsolation: $dataIsolation,
            operator: $operator,
            triggerType: $triggerType,
            triggerData: $triggerData,
            conversationId: ConversationId::ImChat->gen($delightfulFlow->getCode() . '-' . $seqEntity->getConversationId()),
            originConversationId: $seqEntity->getConversationId(),
            executionType: ExecutionType::IMChat,
        );

        // ifis conversation，forcestart stream 模type
        if ($triggerType === TriggerType::ChatMessage) {
            $executionData->setStream(true);
        }

        $executionData->setSenderEntities($senderUserEntity, $seqEntity, $messageEntity);
        $executionData->setTopicId($seqEntity->getExtra()?->getTopicId());
        $executionData->setAgentId($delightfulFlow->getAgentId());
        if ($flowData['agent_version']) {
            $executionData->setInstructionConfigs($flowData['agent_version']->getInstructs());
        }
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);
        $executor->execute();

        // ifhavesectionpointexecutefail，throwexception
        foreach ($delightfulFlow->getNodes() as $node) {
            $nodeDebugResult = $node->getNodeDebugResult();
            if ($nodeDebugResult && ! $nodeDebugResult->isSuccess()) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, $nodeDebugResult->getErrorMessage());
            }
        }
    }

    public function apiChat(DelightfulFlowApiChatDTO $apiChatDTO): array
    {
        $apiChatDTO->validate();
        $authorization = di(FlowOpenApiCheckInterface::class)->handle($apiChatDTO);

        $flowDataIsolation = $this->createFlowDataIsolation($authorization);
        $operator = $this->createExecutionOperator($flowDataIsolation);

        $user = $apiChatDTO->getShareOptions('user');
        if (! $user instanceof DelightfulUserEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'user not found');
        }
        $account = $this->delightfulAccountDomainService->getByDelightfulId($user->getDelightfulId());
        $operator->setRealName($account?->getRealName());
        $operator->setSourceId($apiChatDTO->getShareOptions('source_id', 'sk_flow'));

        $flowData = $this->getFlow($flowDataIsolation, $apiChatDTO->getFlowCode(), [Type::Main]);
        $delightfulFlow = $flowData['flow'];

        // setfinger令
        $messageEntity = new TextMessage(['content' => $apiChatDTO->getMessage()]);
        if (! empty($apiChatDTO->getInstruction())) {
            $msgInstruct = $this->generateChatInstruction($apiChatDTO);
            $messageEntity->setInstructs($msgInstruct);
        }
        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: ['user_entity' => $user, 'account_entity' => $account],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity($messageEntity)],
            globalVariable: $delightfulFlow->getGlobalVariable(),
            attachments: AttachmentUtil::getByApiArray($apiChatDTO->getAttachments()),
        );
        $originConversationId = $apiChatDTO->getConversationId();
        $executionData = new ExecutionData(
            flowDataIsolation: $flowDataIsolation,
            operator: $operator,
            triggerType: TriggerType::ChatMessage,
            triggerData: $triggerData,
            conversationId: ConversationId::ApiKeyChat->gen($originConversationId),
            originConversationId: $originConversationId,
            executionType: ExecutionType::SKApi,
        );
        $executionData->setAgentId($delightfulFlow->getAgentId());
        if ($flowData['agent_version']) {
            $executionData->setInstructionConfigs($flowData['agent_version']->getInstructs());
        }
        $executionData->setStream($apiChatDTO->isStream(), $apiChatDTO->getVersion());
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData, async: $apiChatDTO->isAsync());
        if ($apiChatDTO->isStream()) {
            FlowEventStreamManager::get();
        }
        $executor->execute();
        if ($apiChatDTO->isAsync()) {
            return [
                'conversation_id' => $executionData->getOriginConversationId(),
                'task_id' => $executor->getExecutorId(),
            ];
        }

        return [
            'messages' => $executionData->getReplyMessagesArray(),
            'conversation_id' => $executionData->getOriginConversationId(),
        ];
    }

    public function apiParamCall(DelightfulFlowApiChatDTO $apiChatDTO): array
    {
        $apiChatDTO->validate(false);
        $authorization = di(FlowOpenApiCheckInterface::class)->handle($apiChatDTO);

        $flowDataIsolation = $this->createFlowDataIsolation($authorization);
        $operator = $this->createExecutionOperator($flowDataIsolation);

        $user = $apiChatDTO->getShareOptions('user');
        if (! $user instanceof DelightfulUserEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'user not found');
        }
        $account = $this->delightfulAccountDomainService->getByDelightfulId($user->getDelightfulId());
        $operator->setRealName($account?->getRealName());
        $operator->setSourceId($apiChatDTO->getShareOptions('source_id', 'sk_flow'));

        $operationValidate = 'read';
        if ($apiChatDTO->getShareOptions('source_id') === 'oauth2_flow') {
            $operationValidate = '';
        }
        $flowData = $this->getFlow($flowDataIsolation, $apiChatDTO->getFlowCode(), [Type::Sub, Type::Tools], operationValidate: $operationValidate);
        $delightfulFlow = $flowData['flow'];

        // setfinger令
        $messageEntity = new TextMessage(['content' => $apiChatDTO->getMessage()]);

        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: ['user_entity' => $user, 'account_entity' => $account],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity($messageEntity)],
            params: $apiChatDTO->getParams(),
            globalVariable: $delightfulFlow->getGlobalVariable(),
            attachments: AttachmentUtil::getByApiArray($apiChatDTO->getAttachments()),
        );
        $originConversationId = $apiChatDTO->getConversationId();
        $executionData = new ExecutionData(
            flowDataIsolation: $flowDataIsolation,
            operator: $operator,
            triggerType: TriggerType::ParamCall,
            triggerData: $triggerData,
            conversationId: ConversationId::ApiKeyChat->gen($originConversationId),
            originConversationId: $originConversationId,
            executionType: ExecutionType::SKApi,
        );
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData, async: $apiChatDTO->isAsync());
        $executor->execute();
        if ($apiChatDTO->isAsync()) {
            return [
                'conversation_id' => $executionData->getOriginConversationId(),
                'task_id' => $executor->getExecutorId(),
            ];
        }

        return [
            'conversation_id' => $executionData->getOriginConversationId(),
            'result' => $delightfulFlow->getResult(),
        ];
    }

    public function apiChatByMCPTool(FlowDataIsolation $flowDataIsolation, DelightfulFlowApiChatDTO $apiChatDTO): array
    {
        $user = $this->delightfulUserDomainService->getByUserId($flowDataIsolation->getCurrentUserId());
        if (! $user) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'user not found');
        }
        $account = $this->delightfulAccountDomainService->getByDelightfulId($user->getDelightfulId());
        if (! $account) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'account not found');
        }
        EnvManager::initDataIsolationEnv($flowDataIsolation, force: true);
        $operator = $this->createExecutionOperator($flowDataIsolation);
        $operator->setSourceId('mcp_tool');

        $flowData = $this->getFlow(
            $flowDataIsolation,
            $apiChatDTO->getFlowCode(),
            [Type::Main],
        );
        $delightfulFlow = $flowData['flow'];

        // Set instruction for chat scenario
        $messageEntity = new TextMessage(['content' => $apiChatDTO->getMessage()]);
        if (! empty($apiChatDTO->getInstruction())) {
            $msgInstruct = $this->generateChatInstruction($apiChatDTO);
            $messageEntity->setInstructs($msgInstruct);
        }

        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: ['user_entity' => $user, 'account_entity' => $account],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity($messageEntity)],
            params: $apiChatDTO->getParams(),
            globalVariable: $delightfulFlow->getGlobalVariable(),
            attachments: AttachmentUtil::getByApiArray($apiChatDTO->getAttachments()),
        );
        $originConversationId = $apiChatDTO->getConversationId() ?: IdGenerator::getUniqueId32();
        $executionData = new ExecutionData(
            flowDataIsolation: $flowDataIsolation,
            operator: $operator,
            triggerType: TriggerType::ChatMessage,
            triggerData: $triggerData,
            conversationId: ConversationId::ApiKeyChat->gen($originConversationId),
            originConversationId: $originConversationId,
            executionType: ExecutionType::SKApi,
        );
        $executionData->setAgentId($delightfulFlow->getAgentId());
        if ($flowData['agent_version']) {
            $executionData->setInstructionConfigs($flowData['agent_version']->getInstructs());
        }
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);
        $executor->execute();

        return [
            'messages' => $executionData->getReplyMessagesArray(),
            'conversation_id' => $executionData->getOriginConversationId(),
        ];
    }

    public function apiParamCallByRemoteTool(FlowDataIsolation $flowDataIsolation, DelightfulFlowApiChatDTO $apiChatDTO, string $sourceId = ''): array
    {
        $user = $this->delightfulUserDomainService->getByUserId($flowDataIsolation->getCurrentUserId());
        if (! $user) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'user not found');
        }
        EnvManager::initDataIsolationEnv($flowDataIsolation, force: true);
        $account = $this->delightfulAccountDomainService->getByDelightfulId($user->getDelightfulId());
        if (! $account) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'account not found');
        }
        $operator = $this->createExecutionOperator($flowDataIsolation);
        $operator->setSourceId($sourceId);

        $flowData = $this->getFlow(
            $flowDataIsolation,
            $apiChatDTO->getFlowCode(),
            [Type::Tools],
            operationValidate: 'read',
            flowVersionCode: $apiChatDTO->getFlowVersionCode()
        );
        $delightfulFlow = $flowData['flow'];

        $messageEntity = new TextMessage(['content' => $apiChatDTO->getMessage()]);

        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: ['user_entity' => $user, 'account_entity' => $account],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity($messageEntity)],
            params: $apiChatDTO->getParams(),
            globalVariable: $delightfulFlow->getGlobalVariable(),
            attachments: AttachmentUtil::getByApiArray($apiChatDTO->getAttachments()),
        );
        $originConversationId = $apiChatDTO->getConversationId() ?: IdGenerator::getUniqueId32();
        $executionData = new ExecutionData(
            flowDataIsolation: $flowDataIsolation,
            operator: $operator,
            triggerType: TriggerType::ParamCall,
            triggerData: $triggerData,
            conversationId: ConversationId::ApiKeyChat->gen($originConversationId),
            originConversationId: $originConversationId,
            executionType: ExecutionType::SKApi,
        );
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);
        $executor->execute();
        return [
            'result' => $delightfulFlow->getResult(),
        ];
    }

    public function getByExecuteId(DelightfulFlowApiChatDTO $apiChatDTO): DelightfulFlowExecuteLogEntity
    {
        $apiChatDTO->validate();
        if (empty($apiChatDTO->getTaskId())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'task_id is required');
        }
        $authorization = di(FlowOpenApiCheckInterface::class)->handle($apiChatDTO);
        $flowDataIsolation = $this->createFlowDataIsolation($authorization);

        $log = $this->delightfulFlowExecuteLogDomainService->getByExecuteId($flowDataIsolation, $apiChatDTO->getTaskId());
        if (! $log) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $apiChatDTO->getTaskId()]);
        }
        // 只canquerytheonelayerdata
        if (! $log->isTop()) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $apiChatDTO->getTaskId()]);
        }

        // checkwhether具havetheprocesspermission
        $this->getFlow($flowDataIsolation, $log->getFlowCode(), operationValidate: 'read');

        return $log;
    }

    /**
     * scheduletask触hair.
     */
    public static function routine(string $flowCode, string $branchId, array $routineConfig = []): void
    {
        // 暂o clockonlysystemlevel别scheduletask
        $dataIsolation = FlowDataIsolation::create();
        $delightfulFlow = di(DelightfulFlowDomainService::class)->getByCode($dataIsolation, $flowCode);
        if (! $delightfulFlow) {
            return;
        }
        $dataIsolation->setCurrentOrganizationCode($delightfulFlow->getOrganizationCode());
        $dataIsolation->setCurrentUserId($delightfulFlow->getCreator());
        EnvManager::initDataIsolationEnv($dataIsolation);

        $datetime = new DateTime();
        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: ['user_entity' => TriggerData::createUserEntity('system', 'routine', '')],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity(new TextMessage(['content' => '']))],
            params: [
                'trigger_time' => $datetime->format('Y-m-d H:i:s'),
                'trigger_timestamp' => $datetime->getTimestamp(),
                'branch_id' => $branchId,
                'routine_config' => $routineConfig,
            ],
            globalVariable: $delightfulFlow->getGlobalVariable(),
        );

        $operator = Operator::createByCrontab($delightfulFlow->getOrganizationCode());
        $operator->setSourceId('routine');
        $executionData = new ExecutionData(
            flowDataIsolation: $dataIsolation,
            operator: $operator,
            triggerType: TriggerType::Routine,
            triggerData: $triggerData,
            conversationId: ConversationId::Routine->gen($delightfulFlow->getCode() . '_routine'),
            executionType: ExecutionType::Routine,
        );
        if ($delightfulFlow->getType()->isMain()) {
            $agent = di(DelightfulAgentDomainService::class)->getByFlowCode($delightfulFlow->getCode());
            if ($agent) {
                $executionData->setAgentId($agent->getId());
                $delightfulFlow->setAgentId($agent->getId());
            }
        }
        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);

        $executor->execute();

        // checkerror
        foreach ($delightfulFlow->getNodes() as $node) {
            $nodeDebugResult = $node->getNodeDebugResult();
            if ($nodeDebugResult && ! $nodeDebugResult->isSuccess()) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, $nodeDebugResult->getErrorMessage());
            }
        }
    }

    /**
     * 试运line.
     */
    public function testRun(Authenticatable $authorization, DelightfulFlowEntity $delightfulFlowEntity, array $triggerConfig): array
    {
        // get助理info
        if ($delightfulFlowEntity->getType() == Type::Main) {
            $delightfulAgentEntity = $this->delightfulAgentDomainService->getByFlowCode($delightfulFlowEntity->getCode());
            $delightfulFlowEntity->setAgentId($delightfulAgentEntity->getId());
        }

        $triggerType = TriggerType::tryFrom($triggerConfig['trigger_type'] ?? 0);
        if ($triggerType === null) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => 'trigger_type']);
        }
        $flowDataIsolation = $this->createFlowDataIsolation($authorization);
        $delightfulFlowEntity->setOrganizationCode($flowDataIsolation->getCurrentOrganizationCode());

        $result = [
            'success' => true,
            'key' => '',
            'node_debug' => [],
        ];

        if (! empty($triggerConfig['trigger_data']['chat_time']) && strtotime($triggerConfig['trigger_data']['chat_time'])) {
            $triggerTime = new DateTime($triggerConfig['trigger_data']['chat_time']);
        } else {
            $triggerTime = new DateTime();
        }
        $nickname = $triggerConfig['trigger_data']['nickname'] ?? null;
        if (! $nickname && $authorization instanceof DelightfulUserAuthorization) {
            $nickname = $authorization->getNickname();
        }
        $operator = $this->createExecutionOperator($authorization);
        $operator->setSourceId('test_run');

        $triggerData = new TriggerData(
            triggerTime: $triggerTime,
            userInfo: ['user_entity' => TriggerData::createUserEntity($authorization->getId(), $nickname ?? $authorization->getId(), $operator->getOrganizationCode())],
            messageInfo: ['message_entity' => TriggerData::createMessageEntity(new TextMessage(['content' => $triggerConfig['trigger_data']['content'] ?? '']))],
            params: $triggerConfig['trigger_data'] ?? [],
            paramsForm: $triggerConfig['trigger_data_form'] ?? [],
            // 试运lineo clock，all局variableforhand动传入
            globalVariable: ComponentFactory::fastCreate($triggerConfig['global_variable'] ?? []) ?? $delightfulFlowEntity->getGlobalVariable(),
            attachments: AttachmentUtil::getByApiArray($triggerConfig['trigger_data']['files'] ?? []),
        );

        $delightfulFlowEntity->prepareTestRun();
        $delightfulFlowEntity->setCreator($flowDataIsolation->getCurrentUserId());

        $originConversationId = $triggerConfig['conversation_id'] ?? IdGenerator::getUniqueId32();
        $topicId = $triggerConfig['topic_id'] ?? '';
        $executionData = new ExecutionData(
            flowDataIsolation: $flowDataIsolation,
            operator: $operator,
            triggerType: $triggerType,
            triggerData: $triggerData,
            conversationId: ConversationId::DebugFlow->gen($operator->getUid() . '_tr_' . $originConversationId),
            originConversationId: $originConversationId,
            executionType: ExecutionType::Debug,
        );
        $executionData->setTopicId($topicId);
        $executionData->setAgentId($delightfulFlowEntity->getAgentId());
        $executionData->setDebug((bool) ($triggerConfig['debug'] ?? false));
        // 运lineprocessgraph，检测whethercan运line
        $executor = new DelightfulFlowExecutor($delightfulFlowEntity, $executionData);
        $executor->execute();

        // get node 运lineresult
        foreach ($delightfulFlowEntity->getNodes() as $node) {
            if ($node->getNodeDebugResult()) {
                // haveonefailthen判定forfail
                if (! $node->getNodeDebugResult()->isSuccess()) {
                    $result['success'] = false;
                }
                $result['node_debug'][$node->getNodeId()] = $node->getNodeDebugResult()->toArray();
            }
        }
        return $result;
    }

    /**
     * @return ChatInstruction[]
     */
    private function generateChatInstruction(DelightfulFlowApiChatDTO $apiChatDTO): array
    {
        $msgInstruct = [];
        foreach ($apiChatDTO->getInstruction() as $instruction) {
            $msgInstruct[] = new ChatInstruction([
                'value' => $instruction->getValue(),
                'instruction' => [
                    'id' => $instruction->getId(),
                    'name' => $instruction->getName(),
                    'instruction_type' => InstructionType::Flow->value,
                ],
            ]);
        }
        return $msgInstruct;
    }

    /**
     * getprocessinfo.
     *
     * @return array{flow: DelightfulFlowEntity, flow_version?: ?DelightfulFlowVersionEntity, agent?: ?DelightfulAgentEntity, agent_version?: ?DelightfulAgentVersionEntity}
     */
    private function getFlow(FlowDataIsolation $dataIsolation, string $flowId, ?array $types = null, string $operationValidate = '', string $flowVersionCode = ''): array
    {
        if ($tool = BuiltInToolSetCollector::getToolByCode($flowId)) {
            $flow = $tool->generateToolFlow($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
            return [
                'flow' => $flow,
                'flow_version' => null,
                'agent' => null,
                'agent_version' => null,
            ];
        }

        $delightfulFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowId);
        if (! $delightfulFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $flowId]);
        }
        if (! is_null($types) && ! in_array($delightfulFlow->getType(), $types)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.executor.unsupported_flow_type');
        }

        $flowVersion = null;
        $agent = null;
        $agentVersion = null;
        $agentId = '';
        switch ($delightfulFlow->getType()) {
            case Type::Main:
                $agent = $this->delightfulAgentDomainService->getByFlowCode($delightfulFlow->getCode());
                // 仅allowcreatepersoncanindisablestatusdowncall
                if ($agent->getCreatedUid() !== $dataIsolation->getCurrentUserId() && ! $agent->isAvailable()) {
                    ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.agent_disabled');
                }
                $agentVersion = $agent;
                if ($agent->getAgentVersionId()) {
                    $agentVersion = $this->delightfulAgentVersionDomainService->getById($agent->getAgentVersionId());
                    $flowVersionCode = $agentVersion->getFlowVersion();
                }
                $agentId = $agent->getId();
                break;
            case Type::Sub:
            case Type::Tools:
                if (! $delightfulFlow->isEnabled()) {
                    ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.flow_disabled');
                }
                break;
            default:
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.executor.unsupported_flow_type');
        }

        if (! empty($flowVersionCode)) {
            $flowVersion = $this->delightfulFlowVersionDomainService->show($dataIsolation, $flowId, $flowVersionCode);
            $delightfulFlow = $flowVersion->getDelightfulFlow();
            $delightfulFlow->setVersionCode($flowVersion->getCode());
        }
        $delightfulFlow->setAgentId((string) $agentId);

        if ($operationValidate) {
            $this->getFlowOperation($dataIsolation, $delightfulFlow)->validate($operationValidate, $flowId);
        }

        return [
            'flow' => $delightfulFlow,
            'flow_version' => $flowVersion,
            'agent' => $agent,
            'agent_version' => $agentVersion,
        ];
    }
}
