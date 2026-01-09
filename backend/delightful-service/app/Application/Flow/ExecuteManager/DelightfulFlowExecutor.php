<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager;

use App\Application\Flow\ExecuteManager\Archive\FlowExecutorArchiveCloud;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionDataCollector;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionFlowCollector;
use App\Application\Flow\ExecuteManager\ExecutionData\FlowStreamStatus;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct\Message;
use App\Application\Flow\ExecuteManager\Stream\FlowEventStreamManager;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\ValueObject\ExecuteLogStatus;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Service\DelightfulFlowExecuteLogDomainService;
use App\Domain\Flow\Service\DelightfulFlowWaitMessageDomainService;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Dag\Dag;
use App\Infrastructure\Core\Dag\Vertex;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\FlowExprEngine\Kernel\Utils\Functions;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class DelightfulFlowExecutor
{
    private Dag $dag;

    private ?string $rootId = null;

    /**
     * 用于record nodes 的 next_nodes，作为 edges 的编排.
     */
    private array $nextNodeIds = [];

    private LoggerInterface $logger;

    private ?string $appointRootId = null;

    private ?int $waitMessageId = null;

    private string $executorId;

    private bool $success = true;

    private DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity;

    private DelightfulFlowExecuteLogDomainService $delightfulFlowExecuteLogDomainService;

    private LockerInterface $locker;

    private bool $inLoop = false;

    public function __construct(
        private readonly DelightfulFlowEntity $delightfulFlowEntity,
        private readonly ExecutionData $executionData,
        private bool $async = false,
        ?DelightfulFlowExecuteLogEntity $lastDelightfulFlowExecuteLogEntity = null,
    ) {
        if ($lastDelightfulFlowExecuteLogEntity) {
            $this->delightfulFlowExecuteLogEntity = $lastDelightfulFlowExecuteLogEntity;
        }
        $this->locker = di(LockerInterface::class);
        $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('DelightfulFlowExecutor');
        $this->delightfulFlowExecuteLogDomainService = di(DelightfulFlowExecuteLogDomainService::class);
        $this->init();
    }

    public function setInLoop(bool $inLoop): void
    {
        $this->inLoop = $inLoop;
    }

    public function execute(?TriggerType $appointTriggerType = null): array
    {
        // 真正开始执行时，才会产生执行 id
        $this->createExecuteLog();
        $this->executorId = (string) $this->delightfulFlowExecuteLogEntity->getId();

        if ($this->async) {
            Coroutine::defer(function () use ($appointTriggerType) {
                $this->logger->info('AsyncStart', ['executor_id' => $this->executorId]);
                $this->setAsync(false);
                $this->execute($appointTriggerType);
            });
            $this->logger->info('AsyncInit', ['executor_id' => $this->executorId]);
            return [];
        }

        $startTime = microtime(true);
        $args['execution_data'] = $this->executionData;
        $args['appoint_trigger_type'] = $appointTriggerType;
        try {
            $this->begin($args);
            if ($this->delightfulFlowEntity->hasCallback()) {
                $result = $this->executeCallback();
                $this->delightfulFlowEntity->setCallbackResult($result);
                return $result;
            }
            return $this->dag->run($args);
        } finally {
            $this->end($args, $startTime);
        }
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setDelightfulFlowExecuteLogEntity(DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $this->delightfulFlowExecuteLogEntity = $delightfulFlowExecuteLogEntity;
    }

    public function getExecutorId(): string
    {
        return $this->executorId;
    }

    public function setAsync(bool $async): void
    {
        $this->async = $async;
    }

    protected function init(): void
    {
        if (! $this->delightfulFlowEntity->hasCallback()) {
            $this->handleWaitMessage();
            $this->dag = new Dag();
            $this->addNodes($this->delightfulFlowEntity);
            $this->addEdges();
            $this->checkCircularDependencies();
        } else {
            // 具有 callback 的流程不参与重试与异步
            $this->async = false;
        }
        if ($this->executionData->getExecutionType()->isDebug()) {
            // debug 下不允许异步
            $this->async = false;
        }
        if ($this->async) {
            $this->executionData->setStream(false, '');
        }
        if (empty($this->executionData->getAgentId())) {
            $this->executionData->setAgentId($this->delightfulFlowEntity->getAgentId());
        }
        if (empty($this->executionData->getDelightfulFlowEntity())) {
            $this->executionData->setDelightfulFlowEntity($this->delightfulFlowEntity);
        }
        if (! ExecutionFlowCollector::get($this->executionData->getUniqueId())) {
            ExecutionFlowCollector::add($this->executionData->getUniqueId(), $this->delightfulFlowEntity);
        }
    }

    protected function begin(array $args): void
    {
        // 同时只能有一个流程id在执行
        if (! $this->locker->mutexLock($this->getLockerKey(), $this->executorId)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, "{$this->executorId} is running");
        }
        $this->updateStatus(ExecuteLogStatus::Running);
        $this->logger->info('FlowStart', [
            'executor_id' => $this->executorId,
            'execution_data_id' => $this->executionData->getId(),
            'flow_code' => $this->executionData->getFlowCode(),
            'flow_version' => $this->executionData->getFlowVersion(),
            'trigger_type' => $this->executionData->getTriggerType()->value,
            'execution_type' => $this->executionData->getExecutionType()->value,
            'trigger_params' => $this->executionData->getTriggerData()->getParams(),
        ]);

        /** @var TriggerType $appointTriggerType */
        $appointTriggerType = $args['appoint_trigger_type'];
        if ($appointTriggerType === TriggerType::LoopStart) {
            // 循环时，不处理后面的数据
            return;
        }

        $this->executionData->setFlowCode(
            $this->delightfulFlowEntity->getCode(),
            $this->delightfulFlowEntity->getVersionCode(),
            $this->delightfulFlowEntity->getCreator()
        );

        // 为了在运行中，给有需要get当前流程的节点使用
        ExecutionDataCollector::add($this->executionData);
    }

    protected function handledNode(Node $node, VertexResult $vertexResult): void
    {
        $nodeDebugResult = $node->getNodeDebugResult();
        if (! $nodeDebugResult->isSuccess()) {
            // 只要有一个节点是fail的，那么流程就是fail
            $this->success = false;
        }
        $this->logger->info('HandledNode', [
            'executor_id' => $this->executorId,
            'success' => $nodeDebugResult->isSuccess(),
            'elapsed_time' => $nodeDebugResult->getElapsedTime(),
            'node_id' => $node->getNodeId(),
            'node_version' => $node->getNodeVersion(),
            'node_type' => $node->getNodeDefine()->getName(),
            'node_name' => $node->getName(),
            'children_ids' => $vertexResult->getChildrenIds(),
            'skip_execute' => $node->getNodeParamsConfig()->isSkipExecute(),
            'node_debug_result' => $nodeDebugResult->toDesensitizationArray(),
        ]);

        // 归档
        $this->archiveToCloud($vertexResult);

        if (! $nodeDebugResult->isSuccess()) {
            // 如果是 API 请求，抛出errorinfo
            if ($this->executionData->getExecutionType()->isApi()) {
                // 如果不是助理parametercall 才recorderrorinfo
                if (! $this->executionData->getTriggerData()->isAssistantParamCall()) {
                    $errorMessage = new Message([], $this->executionData->getOriginConversationId());
                    $errorMessage->setErrorInformation($nodeDebugResult->getErrorMessage());
                    if ($this->executionData->isStream()) {
                        FlowEventStreamManager::write($errorMessage->toSteamResponse('error'));
                    } else {
                        $this->executionData->addReplyMessage($errorMessage);
                    }
                }
            }

            // 如果需要主动抛出exception
            if ($nodeDebugResult->isThrowException()) {
                if ($nodeDebugResult->isUnAuthorized()) {
                    throw new BusinessException($nodeDebugResult->getErrorMessage(), $nodeDebugResult->getErrorCode());
                }
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, $nodeDebugResult->getErrorMessage());
            }
        }
    }

    protected function end(array $args, float $startTime): void
    {
        $result = [];

        // 如果是异步call的 API 或者 执行fail了
        if ($this->executionData->getExecutionType()->isApi() || ! $this->success) {
            $result = match ($this->executionData->getTriggerType()) {
                TriggerType::ChatMessage => [
                    'messages' => $this->executionData->getReplyMessagesArray(),
                    'conversation_id' => $this->executionData->getOriginConversationId(),
                ],
                TriggerType::ParamCall => [
                    'result' => $this->delightfulFlowEntity->getResult(false),
                    'conversation_id' => $this->executionData->getOriginConversationId(),
                ],
                default => [],
            };
        }

        if ($this->success) {
            if ($this->waitMessageId) {
                di(DelightfulFlowWaitMessageDomainService::class)->handled(
                    $this->executionData->getDataIsolation(),
                    $this->waitMessageId
                );
            }

            $this->updateStatus(ExecuteLogStatus::Completed, $result);
        } else {
            $this->updateStatus(ExecuteLogStatus::Failed, $result);
        }

        // 将当前流程产生的 api 执行result传递给上一层的数据
        if ($parentExecutionData = ExecutionDataCollector::get($this->executionData->getUniqueParentId())) {
            foreach ($this->executionData->getReplyMessages() as $replyMessage) {
                $parentExecutionData->addReplyMessage($replyMessage);
            }
        }
        $this->logger->info(
            'FlowEnd',
            [
                'executor_id' => $this->executorId,
                'elapsed_time' => (string) Functions::calculateElapsedTime($startTime, microtime(true)),
                'success' => $this->success,
                'flow_code' => $this->executionData->getFlowCode(),
                'end_node' => $this->delightfulFlowEntity->getEndNode()?->getNodeId(),
            ]
        );
        $this->locker->release($this->getLockerKey(), $this->executorId);

        /** @var TriggerType $appointTriggerType */
        $appointTriggerType = $args['appoint_trigger_type'];
        if ($appointTriggerType === TriggerType::LoopStart) {
            // 循环时，不能delete该数据
            return;
        }

        $this->finishStream();
        ExecutionDataCollector::remove($this->executionData->getUniqueId());
        ExecutionFlowCollector::remove($this->executionData->getUniqueId());
    }

    protected function finishStream(): void
    {
        if (! $this->executionData->isStream()) {
            return;
        }
        if ($this->executionData->getStreamStatus() !== FlowStreamStatus::Processing) {
            return;
        }
        // 只有主流程才能结束（第零层）
        if ($this->executionData->getLevel() !== 0) {
            return;
        }

        $this->executionData->setStreamStatus(FlowStreamStatus::Finished);

        // 只有 api 层面需要这样
        if ($this->executionData->getExecutionType()->isApi()) {
            FlowEventStreamManager::write('data: [DONE]' . "\n\n");
            FlowEventStreamManager::get()->end();
            FlowEventStreamManager::get()->close();
        }
    }

    protected function executeCallback(): array
    {
        $result = $this->delightfulFlowEntity->getCallback()($this->executionData);
        if (is_array($result)) {
            // 得把result赋value到结束节点上面
            $this->executionData->saveNodeContext($this->delightfulFlowEntity->getEndNode()->getNodeId(), $result);
        }
        if (! is_array($result)) {
            return [];
        }
        return $result;
    }

    protected function handleWaitMessage(): void
    {
        $waitMessageDomainService = di(DelightfulFlowWaitMessageDomainService::class);
        $lastWaitMessageEntity = $waitMessageDomainService->getLastWaitMessage(
            $this->executionData->getDataIsolation(),
            $this->executionData->getConversationId(),
            $this->delightfulFlowEntity->getCode(),
            $this->delightfulFlowEntity->getVersionCode()
        );
        if ($lastWaitMessageEntity) {
            $waitNode = $this->delightfulFlowEntity->getNodeById($lastWaitMessageEntity->getWaitNodeId());
            if (! $waitNode) {
                di(DelightfulFlowWaitMessageDomainService::class)->handled(
                    $this->executionData->getDataIsolation(),
                    $lastWaitMessageEntity->getId()
                );
                return;
            }
            $this->executionData->setTriggerType(TriggerType::WaitMessage);
            $this->executionData->loadPersistenceData($lastWaitMessageEntity->getPersistentData());
            $this->appointRootId = $lastWaitMessageEntity->getWaitNodeId();
            $this->waitMessageId = $lastWaitMessageEntity->getId();
        }
    }

    private function createExecuteLog(): void
    {
        if (! empty($this->delightfulFlowExecuteLogEntity)) {
            return;
        }
        $executeLog = new DelightfulFlowExecuteLogEntity();
        $executeLog->setExecuteDataId($this->executionData->getId());
        $executeLog->setConversationId($this->executionData->getConversationId());
        $executeLog->setFlowCode($this->delightfulFlowEntity->getCode());
        $executeLog->setFlowVersionCode($this->delightfulFlowEntity->getVersionCode());
        $executeLog->setExtParams([
            'appoint_root_id' => $this->appointRootId,
            'wait_message_id' => $this->waitMessageId,
            'organization_code' => $this->executionData->getDataIsolation()->getCurrentOrganizationCode(),
            'user_id' => $this->executionData->getDataIsolation()->getCurrentUserId(),
        ]);
        $executeLog->setOrganizationCode($this->executionData->getDataIsolation()->getCurrentOrganizationCode());
        $executeLog->setParentFlowCode($this->executionData->getParentFlowCode());
        $executeLog->setOperatorId($this->executionData->getOperator()->getUid());
        $executeLog->setLevel($this->executionData->getLevel());
        $executeLog->setFlowType($this->delightfulFlowEntity->getType()->value);
        $executeLog->setExecutionType($this->executionData->getExecutionType()->value);
        $this->delightfulFlowExecuteLogEntity = $this->delightfulFlowExecuteLogDomainService->create($this->executionData->getDataIsolation(), $executeLog);
    }

    private function updateStatus(ExecuteLogStatus $status, array $result = []): void
    {
        if (! isset($this->delightfulFlowExecuteLogEntity)) {
            return;
        }
        if ($status === $this->delightfulFlowExecuteLogEntity->getStatus()) {
            return;
        }
        $this->delightfulFlowExecuteLogEntity->setStatus($status);
        $this->delightfulFlowExecuteLogEntity->setResult($result);
        $this->delightfulFlowExecuteLogDomainService->updateStatus($this->executionData->getDataIsolation(), $this->delightfulFlowExecuteLogEntity);
    }

    private function checkCircularDependencies(): void
    {
        if ($this->dag->checkCircularDependencies()) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.executor.has_circular_dependencies', ['label' => $this->delightfulFlowEntity->getName()]);
        }
    }

    private function addNodes(DelightfulFlowEntity $delightfulFlowEntity): void
    {
        foreach ($delightfulFlowEntity->getNodes() as $node) {
            // 跳过在循环体中的节点
            if ($node->getParentId()) {
                continue;
            }
            // 运行前就先尝试进行所有节点的parameter检测，用于提前生成好 NodeParamsConfig
            try {
                $node->validate();
            } catch (Throwable $throwable) {
                // 有些是悬浮节点（即在流程运行中不会被使用节点)，兜底会在执行时再次进行parameter验证
            }

            $job = function (array $frontResults) use ($node): VertexResult {
                $vertexResult = new VertexResult();
                /** @var null|ExecutionData $executionData */
                $executionData = $frontResults['execution_data'] ?? null;
                if (! $executionData) {
                    return $vertexResult;
                }
                // 如果是debug 节点，并且不是 debug 模式运行，那么该节点不允许
                if ($node->getDebug() && ! $executionData->isDebug()) {
                    return $vertexResult;
                }

                $vertex = $this->dag->getVertex($node->getNodeId());
                // 这里一般来说不会为null，先不管null的情况
                $childrenIds = [];
                foreach ($vertex->children as $childVertex) {
                    // 不能自己连自己
                    if ($node->getNodeId() == $childVertex->key) {
                        continue;
                    }
                    $childrenIds[] = $childVertex->key;
                }
                // 默认是要调度下一级的，如果不需要调度，在具体的执行中可以set为[]
                $vertexResult->setChildrenIds($childrenIds);
                // 添加 flow
                $frontResults['current_flow_entity'] = $this->delightfulFlowEntity;
                $frontResults['isThrowException'] = false;
                Context::set('current_flow_entity.' . $executionData->getUniqueId(), $this->delightfulFlowEntity);
                NodeRunnerFactory::make($node)->execute($vertexResult, $executionData, $frontResults);
                $this->handledNode($node, $vertexResult);
                return $vertexResult;
            };
            $vertex = Vertex::make($job, $node->getNodeId());
            if (is_null($this->rootId)) {
                if ($this->appointRootId) {
                    // 如果有指定的，就用指定的
                    if ($node->getNodeId() === $this->appointRootId) {
                        $vertex->markAsRoot();
                        $this->rootId = $this->appointRootId;
                    }
                } else {
                    // 没有指定的必须使用开始节点
                    if ($node->isStart()) {
                        $vertex->markAsRoot();
                        $this->rootId = $node->getNodeId();
                    }
                }
            }
            $this->nextNodeIds[$node->getNodeId()] = $node->getNextNodes();
            $this->dag->addVertex($vertex);
        }
        if (! $this->rootId) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.executor.no_start_node', ['label' => $delightfulFlowEntity->getName()]);
        }
    }

    private function addEdges(): void
    {
        if (! $this->rootId) {
            return;
        }
        foreach ($this->nextNodeIds as $nodeId => $nextNodeIds) {
            foreach ($nextNodeIds as $nextNodeId) {
                if ($nextNodeId === $this->rootId) {
                    // root 节点不允许有父节点的连线
                    continue;
                }
                $this->dag->addEdgeByKey((string) $nodeId, (string) $nextNodeId);
            }
        }
    }

    private function archiveToCloud(VertexResult $vertexResult): void
    {
        // 已经运行过的，也不归档
        if ($vertexResult->hasDebugLog('history_vertex_result')) {
            return;
        }
        // 只有第一层的流程才会进行归档
        if (! $this->executionData->isTop() || $this->inLoop) {
            return;
        }
        if (isset($this->delightfulFlowExecuteLogEntity)) {
            $fromCoroutineId = Coroutine::id();
            Coroutine::create(function () use ($fromCoroutineId) {
                CoContext::copy($fromCoroutineId);

                // 利用自旋锁来控制只有一个在保存
                if (! $this->locker->spinLock($this->getLockerKey() . ':archive', $this->delightfulFlowExecuteLogEntity->getExecuteDataId(), 20)) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'archive file failed');
                }

                FlowExecutorArchiveCloud::put(
                    organizationCode: $this->executionData->getDataIsolation()->getCurrentOrganizationCode(),
                    key: $this->delightfulFlowExecuteLogEntity->getExecuteDataId(),
                    data: [
                        'execution_data' => $this->executionData,
                        'delightful_flow' => $this->delightfulFlowEntity,
                    ]
                );

                $this->locker->release($this->getLockerKey() . ':archive', $this->executorId);
            });
        }
    }

    private function getLockerKey(): string
    {
        return 'DelightfulFLowExecutor:' . $this->executorId;
    }
}
