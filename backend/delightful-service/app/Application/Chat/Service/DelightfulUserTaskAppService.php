<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionType;
use App\Application\Flow\ExecuteManager\ExecutionData\Operator;
use App\Application\Flow\ExecuteManager\ExecutionData\TriggerData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Application\Kernel\EnvManager;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Flow\Entity\ValueObject\ConversationId;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\ErrorCode\FlowErrorCode;
use App\ErrorCode\UserTaskErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\DTO\Response\UserTaskResponseDTO;
use App\Interfaces\Chat\DTO\UserTaskDTO;
use App\Interfaces\Chat\DTO\UserTaskValueDTO;
use DateTime;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\TaskScheduler\Entity\Query\Page;
use Delightful\TaskScheduler\Entity\Query\TaskSchedulerCrontabQuery;
use Delightful\TaskScheduler\Entity\TaskScheduler;
use Delightful\TaskScheduler\Entity\TaskSchedulerCrontab;
use Delightful\TaskScheduler\Entity\TaskSchedulerValue;
use Delightful\TaskScheduler\Entity\ValueObject\IntervalUnit;
use Delightful\TaskScheduler\Entity\ValueObject\TaskType;
use Delightful\TaskScheduler\Service\TaskConfigDomainService;
use Delightful\TaskScheduler\Service\TaskSchedulerDomainService;
use Hyperf\DbConnection\Annotation\Transactional;

class DelightfulUserTaskAppService extends AbstractAppService
{
    public function __construct(
        private TaskSchedulerDomainService $taskSchedulerDomainService,
        private DelightfulAgentDomainService $delightfulAgentDomainService,
        private DelightfulConversationDomainService $delightfulConversationDomainService,
        private DelightfulUserDomainService $delightfulUserDomainService,
    ) {
    }

    public function getExternalId(UserTaskDTO $userTaskDTO)
    {
        return md5($userTaskDTO->getName() . $userTaskDTO->getAgentId() . '-' . $userTaskDTO->getCreator() . $userTaskDTO->getType() . '-' . $userTaskDTO->getTopicId());
    }

    #[Transactional]
    public function createTask(UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO)
    {
        $taskConfigDomainService = $this->getTaskConfigDomainService($userTaskDTO, $userTaskValueDTO);
        $crontabRule = $taskConfigDomainService->getCrontabRule(true);
        $externalId = $this->getExternalId($userTaskDTO);
        if ($this->taskSchedulerDomainService->existsByExternalId($externalId)) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_ALREADY_EXISTS);
        }
        $callbackMethod = $this->getCallbackMethod($userTaskDTO, $userTaskValueDTO);

        // 根据agent_id queryflow_code
        $flow = $this->delightfulAgentDomainService->getAgentById($userTaskDTO->getAgentId());
        if (empty($flow->getFlowCode())) {
            ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID, 'flow_code not found');
        }
        $flowCode = $flow->getFlowCode();

        // 根据conversation_id queryagent_user_id
        $conversation = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($userTaskDTO->getConversationId());
        // 兼容flow 中的conversation_id 跟chat 中的conversation_id 不一致issue
        if (empty($conversation)) {
            $dataIsolation = DataIsolation::create();
            $dataIsolation->setCurrentOrganizationCode($flow->getOrganizationCode());
            // 根据flowCode queryuser_id
            $delightfulUserEntity = $this->delightfulUserDomainService->getByAiCode($dataIsolation, $flowCode);
            if (empty($delightfulUserEntity->getUserId())) {
                ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID, 'agent_user_id not found');
            }
            $userTaskDTO->setAgentUserId($delightfulUserEntity->getUserId());
        } else {
            $userTaskDTO->setAgentUserId($conversation->getReceiveId());
        }

        $callbackParams = $this->getCallbackParams($userTaskDTO, $userTaskValueDTO, $flowCode);
        $enabled = true;
        // 如果是不重复的，那么是直接create调度task
        if ($taskConfigDomainService->getType() === TaskType::NoRepeat) {
            $taskScheduler = new TaskScheduler();
            $taskScheduler->setExternalId($externalId);
            $taskScheduler->setName($userTaskDTO->getName());
            $taskScheduler->setExpectTime($taskConfigDomainService->getDatetime());
            $taskScheduler->setType(2);
            $taskScheduler->setRetryTimes(3);
            $taskScheduler->setCallbackMethod($callbackMethod);
            $taskScheduler->setCallbackParams($callbackParams);
            $taskScheduler->setCreator($userTaskDTO->getCreator());
            $this->taskSchedulerDomainService->create($taskScheduler);
            $enabled = false;
        }

        // 如果是自定义重复，那么直接create调度task,同时关闭定时生成调度task
        if ($taskConfigDomainService->getType() === TaskType::CustomRepeat) {
            $this->createCustomRepeatTask($userTaskDTO, $userTaskValueDTO, $externalId, $callbackMethod, $callbackParams);
            $enabled = false;
        }

        $queryId = $this->getQueryId($userTaskDTO->getAgentId(), $userTaskDTO->getTopicId());
        $taskSchedulerCrontab = new TaskSchedulerCrontab();
        $taskSchedulerCrontab->setExternalId($externalId);
        $taskSchedulerCrontab->setName($userTaskDTO->getName());
        $taskSchedulerCrontab->setCrontab($crontabRule);
        $taskSchedulerCrontab->setRetryTimes(3);
        $taskSchedulerCrontab->setEnabled($enabled);
        $taskSchedulerCrontab->setCallbackMethod($callbackMethod);
        $taskSchedulerCrontab->setCallbackParams($callbackParams);
        $taskSchedulerCrontab->setCreator($userTaskDTO->getCreator());
        $taskSchedulerCrontab->setDeadline($taskConfigDomainService->getDeadline());
        $taskSchedulerCrontab->setFilterId($queryId);
        $this->taskSchedulerDomainService->createCrontab($taskSchedulerCrontab);
    }

    public function createCustomRepeatTask(UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO, $externalId, $callbackMethod, $callbackParams)
    {
        $taskConfigDomainService = $this->getTaskConfigDomainService($userTaskDTO, $userTaskValueDTO);
        $taskSchedulerValue = new TaskSchedulerValue();
        $taskSchedulerValue->setInterval($userTaskValueDTO->getInterval());
        $taskSchedulerValue->setUnit($userTaskValueDTO->getUnit());
        $taskSchedulerValue->setValues($userTaskValueDTO->getValues());
        $taskSchedulerValue->setMonth($userTaskValueDTO->getMonth());
        $expectTimes = $taskConfigDomainService->getCustomRepeatTaskExpectTimes($taskSchedulerValue);
        $taskSchedulers = [];
        foreach ($expectTimes as $expectTime) {
            $taskScheduler = new TaskScheduler();
            $taskScheduler->setExternalId($externalId);
            $taskScheduler->setName($userTaskDTO->getName());
            $taskScheduler->setExpectTime($expectTime);
            $taskScheduler->setType(2);
            $taskScheduler->setRetryTimes(3);
            $taskScheduler->setCallbackMethod($callbackMethod);
            $taskScheduler->setCallbackParams($callbackParams);
            $taskScheduler->setCreator($userTaskDTO->getCreator());
            $taskSchedulers[] = $taskScheduler;
        }
        if (count($taskSchedulers) > 0) {
            $this->taskSchedulerDomainService->batchCreate(scheduleTasks: $taskSchedulers);
        }
    }

    // getroutineConfig
    public function getTaskConfigDomainService(UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO)
    {
        $TaskType = TaskType::tryFrom($userTaskDTO->getType());
        if (! $TaskType) {
            // 抛exception
            ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID);
        }

        return new TaskConfigDomainService(
            type: $TaskType,
            day: $userTaskDTO->getDay(),
            time: $userTaskDTO->getTime(),
            unit: IntervalUnit::tryFrom($userTaskValueDTO->getUnit()) ?? IntervalUnit::Day,
            interval: $userTaskValueDTO->getInterval() ?? 0,
            values: $userTaskValueDTO->getValues() ?? [],
            deadline: $userTaskValueDTO->getDeadline() ?? null,
        );
    }

    public function getCallbackMethod(UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO)
    {
        return [self::class, 'callback'];
    }

    public function getCallbackParams(UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO, string $flowCode)
    {
        return [
            'user_task' => $userTaskDTO,
            'flow_code' => $flowCode,
            'user_task_value' => $userTaskValueDTO,
        ];
    }

    public function getTask(int $taskId): array
    {
        $task = $this->taskSchedulerDomainService->getByCrontabId($taskId);

        if (! $task) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_NOT_FOUND);
        }
        $task = UserTaskResponseDTO::entityToDTO($task);
        return $task->toArray();
    }

    #[Transactional]
    public function updateTask(int $taskId, UserTaskDTO $userTaskDTO, UserTaskValueDTO $userTaskValueDTO)
    {
        $task = $this->taskSchedulerDomainService->getByCrontabId($taskId);
        if (! $task) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_NOT_FOUND);
        }

        // 根据conversation_id queryagent_user_id
        $conversation = $this->delightfulConversationDomainService->getConversationByIdWithoutCheck($userTaskDTO->getConversationId());

        $userTaskDTO->setAgentUserId($conversation->getReceiveId());

        // 根据agent_id queryflow_code
        $flow = di(DelightfulAgentDomainService::class)->getAgentById($userTaskDTO->getAgentId());
        if (empty($flow->getFlowCode())) {
            ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID, 'flow_code not found');
        }
        $flowCode = $flow->getFlowCode();

        $taskConfigDomainService = $this->getTaskConfigDomainService($userTaskDTO, $userTaskValueDTO);
        $crontabRule = $taskConfigDomainService->getCrontabRule(true);

        $callbackMethod = $this->getCallbackMethod($userTaskDTO, $userTaskValueDTO);
        $callbackParams = $this->getCallbackParams($userTaskDTO, $userTaskValueDTO, $flowCode);

        $externalId = $this->getExternalId($userTaskDTO);
        if ($this->taskSchedulerDomainService->existsByExternalId($externalId) && $task->getExternalId() !== $externalId) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_ALREADY_EXISTS);
        }

        // 先清除待执行的task
        $this->taskSchedulerDomainService->clearTaskByExternalId($task->getExternalId());

        $enabled = true;
        // 如果是不重复的，那么是直接create调度task
        if ($taskConfigDomainService->getType() === TaskType::NoRepeat) {
            $taskScheduler = new TaskScheduler();
            $taskScheduler->setExternalId($externalId);
            $taskScheduler->setName($userTaskDTO->getName());
            $taskScheduler->setExpectTime($taskConfigDomainService->getDatetime());
            $taskScheduler->setType(2);
            $taskScheduler->setRetryTimes(3);
            $taskScheduler->setCallbackMethod($callbackMethod);
            $taskScheduler->setCallbackParams($callbackParams);
            $taskScheduler->setCreator($userTaskDTO->getCreator());
            $this->taskSchedulerDomainService->create($taskScheduler);
            $enabled = false;
        }

        // 如果是自定义重复，那么直接create调度task,同时关闭定时生成调度task
        if ($taskConfigDomainService->getType() === TaskType::CustomRepeat) {
            $this->createCustomRepeatTask($userTaskDTO, $userTaskValueDTO, $externalId, $callbackMethod, $callbackParams);
            $enabled = false;
        }

        $queryId = $this->getQueryId($userTaskDTO->getAgentId(), $userTaskDTO->getTopicId());
        $taskSchedulerCrontab = new TaskSchedulerCrontab();
        $taskSchedulerCrontab->setId($taskId);
        $taskSchedulerCrontab->setExternalId($externalId);
        $taskSchedulerCrontab->setName($userTaskDTO->getName());
        $taskSchedulerCrontab->setCrontab($crontabRule);
        $taskSchedulerCrontab->setRetryTimes(3);
        $taskSchedulerCrontab->setEnabled($enabled);
        $taskSchedulerCrontab->setCallbackMethod($callbackMethod);
        $taskSchedulerCrontab->setCallbackParams($callbackParams);
        $taskSchedulerCrontab->setCreator($userTaskDTO->getCreator());
        $taskSchedulerCrontab->setFilterId($queryId);
        $taskSchedulerCrontab->setDeadline($taskConfigDomainService->getDeadline());
        $this->taskSchedulerDomainService->saveCrontab($taskSchedulerCrontab);
    }

    #[Transactional]
    public function deleteTask(int $taskId)
    {
        $task = $this->taskSchedulerDomainService->getByCrontabId($taskId);
        if (! $task) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_NOT_FOUND);
        }
        $this->taskSchedulerDomainService->clearByExternalId($task->getExternalId());
    }

    public function getQueryId($agentId, $topicId)
    {
        return $agentId . '_' . $topicId;
    }

    public function listTaskByCreator($page, $pageSize, $creator, $queryId)
    {
        $query = new TaskSchedulerCrontabQuery();

        $query->setCreator(creator: $creator);
        // $query->setEnable(true);
        $query->setFilterId($queryId);

        $query->setOrder(['filter_id' => 'asc', 'created_at' => 'desc']);
        $page = new Page($page, $pageSize);

        $taskCrontabs = $this->taskSchedulerDomainService->queriesCrontab($query, $page);
        $tasks = [];
        foreach ($taskCrontabs['list'] as $taskCrontab) {
            $task = UserTaskResponseDTO::entityToDTO($taskCrontab);
            $tasks[] = $task->toArray();
        }
        $taskCrontabs['list'] = $tasks;
        return $taskCrontabs;
    }

    public function getCreator(string $agentId, string $creator): string
    {
        return $creator . '_' . $agentId;
    }

    public static function callback(string $flow_code, array $user_task, array $user_task_value)
    {
        $appMessageId = IdGenerator::getUniqueId32();
        $receiveSeqDTO = new DelightfulSeqEntity();
        $messageContent = new TextMessage();
        $content = $user_task['name'];
        if (! empty($user_task['description'])) {
            $content = 'taskname: ' . $user_task['name'] . ', taskdescription: ' . $user_task['description'];
        }
        $messageContent->setContent($content);
        $receiveSeqDTO->setContent($messageContent);
        $receiveSeqDTO->setSeqType(ChatMessageType::Text);
        $receiveSeqDTO->setReferMessageId('');
        $senderUserId = $user_task['creator'];
        $receiveUserId = $user_task['agent_user_id'];
        $topicId = $user_task['topic_id'] ?? '';
        di(DelightfulChatMessageAppService::class)->userSendMessageToAgent($receiveSeqDTO, $senderUserId, $receiveUserId, $appMessageId, false, null, ConversationType::Ai, $topicId);
    }

    // 后台task,不会模拟user发送message,  预留method，暂时没有用到
    // public static function asyncCallback(string $flow_code, array $user_task)
    // {
    //     $triggerConfig = [
    //         'trigger_type' => 1,
    //         'conversation_id' => $user_task['conversation_id'],
    //         'trigger_data_form' => [],
    //         'topic_id' => $user_task['topic_id'] ?? '',
    //         'trigger_data' => [
    //             'nickname' => $user_task['nickname'],
    //             'message_type' => 'text',
    //             'content' => $user_task['name'],
    //             'chat_time' => date('Y-m-d H:i:s'),
    //         ],
    //         'debug' => true,
    //     ];

    //     $taskConfigDomainService = [
    //         'day' => $user_task['day'],
    //         'time' => $user_task['time'],
    //         'type' => $user_task['type'],
    //         'topic' => [$user_task['name'] => null, 'type' => ''],
    //         'value' => $user_task['value'],
    //     ];

    //     $dataIsolation = FlowDataIsolation::create();
    //     $delightfulFlow = di(DelightfulFlowDomainService::class)->getByCode($dataIsolation, $flow_code);

    //     $authorization = new DelightfulUserAuthorization();
    //     $authorization->setDelightfulEnvId($user_task['delightful_env_id'] ?? 1);
    //     $authorization->setId($user_task['creator']);
    //     $authorization->setOrganizationCode($delightfulFlow->getOrganizationCode());
    //     $authorization->setUserType(UserType::Ai);

    //     $triggerType = TriggerType::tryFrom($triggerConfig['trigger_type'] ?? 0);
    //     // if ($triggerType === null) {
    //     //     ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => 'trigger_type']);
    //     // }

    //     // 改为静态call
    //     $flowDataIsolation = self::createFlowDataIsolationStaticMethod($authorization);

    //     // $flowDataIsolation = FlowDataIsolation::create();

    //     $result = [
    //         'success' => true,
    //         'key' => '',
    //         'node_debug' => [],
    //     ];

    //     $dataIsolation->setCurrentOrganizationCode($delightfulFlow->getOrganizationCode());
    //     EnvManager::initDataIsolationEnv($dataIsolation);

    //     $globalVariable = $delightfulFlow->getGlobalVariable();

    //     $datetime = new DateTime();
    //     $messageContent = 'test routine';
    //     $triggerData = new TriggerData(
    //         triggerTime: new DateTime(),
    //         userInfo: ['user_entity' => TriggerData::createUserEntity('system', 'routine', '')],
    //         messageInfo: ['message_entity' => TriggerData::createMessageEntity(new TextMessage(['content' => $messageContent]))],
    //         params: [
    //             'trigger_time' => $datetime->format('Y-m-d H:i:s'),
    //             'trigger_timestamp' => $datetime->getTimestamp(),
    //             //  'branch_id' => $branchId,
    //             'routine_config' => $taskConfigDomainService,
    //         ],
    //         globalVariable: $delightfulFlow->getGlobalVariable(),
    //     );

    //     // if (! empty($triggerConfig['trigger_data']['chat_time']) && strtotime($triggerConfig['trigger_data']['chat_time'])) {
    //     //     $triggerTime = new DateTime($triggerConfig['trigger_data']['chat_time']);
    //     // } else {
    //     $triggerTime = new DateTime();
    //     // }
    //     $nickname = $triggerConfig['trigger_data']['nickname'];
    //     // if (! $nickname && $authorization instanceof DelightfulUserAuthorization) {
    //     //     $nickname = $authorization->getNickname();
    //     // }
    //     // $operator = $this->createExecutionOperator($authorization);
    //     $operator = Operator::createByCrontab($delightfulFlow->getOrganizationCode());
    //     $triggerData = new TriggerData(
    //         triggerTime: $triggerTime,
    //         userInfo: ['user_entity' => TriggerData::createUserEntity($authorization->getId(), $nickname, $operator->getOrganizationCode())],
    //         messageInfo: ['message_entity' => TriggerData::createMessageEntity(new TextMessage(['content' => $triggerConfig['trigger_data']['content']]))],
    //         params: $triggerConfig['trigger_data'],
    //         paramsForm: $triggerConfig['trigger_data_form'],
    //         // 试运行时，全局variable为手动传入
    //         globalVariable: ComponentFactory::fastCreate($globalVariable) ?? $delightfulFlow->getGlobalVariable(),
    //     );

    //     $delightfulFlow->prepareTestRun();

    //     $originConversationId = $triggerConfig['conversation_id'] ?? IdGenerator::getUniqueId32();
    //     $topicId = $triggerConfig['topic_id'];
    //     $executionData = new ExecutionData(
    //         flowDataIsolation: $flowDataIsolation,
    //         operator: $operator,
    //         triggerType: $triggerType,
    //         triggerData: $triggerData,
    //         conversationId: ConversationId::DebugFlow->gen($operator->getUid() . '_tr_' . $originConversationId),
    //         originConversationId: $originConversationId,
    //         executionType: ExecutionType::Debug,
    //     );
    //     $executionData->setTopicId($topicId);
    //     $executionData->setDebug($triggerConfig['debug']);
    //     // 运行流程图，检测是否可以运行
    //     $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);
    //     $executor->execute();

    //     foreach ($delightfulFlow->getNodes() as $node) {
    //         $nodeDebugResult = $node->getNodeDebugResult();
    //         if ($nodeDebugResult && ! $nodeDebugResult->isSuccess()) {
    //             ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, $nodeDebugResult->getErrorMessage());
    //         }
    //     }

    //     // get node 运行result
    //     foreach ($delightfulFlow->getNodes() as $node) {
    //         if ($node->getNodeDebugResult()) {
    //             // 有一个fail就判定为fail
    //             if (! $node->getNodeDebugResult()->isSuccess()) {
    //                 $result['success'] = false;
    //             }
    //             if ($node->getNodeType() === NodeType::ReplyMessage) {
    //                 // 如果是回复message节点，则将messagecontent添加到result中
    //                 $result['message'] = $node->getNodeDebugResult()->getOutput();
    //             }
    //         }
    //     }
    //     return $result;
    // }
}
