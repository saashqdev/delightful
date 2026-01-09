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
     * useatrecord nodes  next_nodes,asfor edges 编row.
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
        // trulystartexecuteo clock,才willproduceexecute id
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
            // 具have callback processnot参andretryandasync
            $this->async = false;
        }
        if ($this->executionData->getExecutionType()->isDebug()) {
            // debug downnotallowasync
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
        // meanwhileonlycanhaveoneprocessidinexecute
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
            // loopo clock,notprocessbacksurfacedata
            return;
        }

        $this->executionData->setFlowCode(
            $this->delightfulFlowEntity->getCode(),
            $this->delightfulFlowEntity->getVersionCode(),
            $this->delightfulFlowEntity->getCreator()
        );

        // forin运linemiddle,givehaveneedgetcurrentprocesssectionpointuse
        ExecutionDataCollector::add($this->executionData);
    }

    protected function handledNode(Node $node, VertexResult $vertexResult): void
    {
        $nodeDebugResult = $node->getNodeDebugResult();
        if (! $nodeDebugResult->isSuccess()) {
            // as long ashaveonesectionpointisfail,that么processthenisfail
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

        // archive
        $this->archiveToCloud($vertexResult);

        if (! $nodeDebugResult->isSuccess()) {
            // ifis API request,throwerrorinfo
            if ($this->executionData->getExecutionType()->isApi()) {
                // ifnotisassistantparametercall 才recorderrorinfo
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

            // ifneedactivethrowexception
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

        // ifisasynccall API or者 executefail
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

        // willcurrentprocessproduce api executeresultpassgiveuponelayerdata
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
            // loopo clock,notcandeletethedata
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
        // only主process才canend(thezerolayer)
        if ($this->executionData->getLevel() !== 0) {
            return;
        }

        $this->executionData->setStreamStatus(FlowStreamStatus::Finished);

        // only api layersurfaceneedthis样
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
            // result赋valuetoendsectionpointupsurface
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
            // skipinloopbodymiddlesectionpoint
            if ($node->getParentId()) {
                continue;
            }
            // 运linefrontthen先tryconduct所havesectionpointparameterdetect,useat提frontgenerategood NodeParamsConfig
            try {
                $node->validate();
            } catch (Throwable $throwable) {
                // havetheseis悬浮sectionpoint(即inprocess运linemiddlenotwillbeusesectionpoint),兜bottomwillinexecuteo clockagaintimeconductparameterverify
            }

            $job = function (array $frontResults) use ($node): VertexResult {
                $vertexResult = new VertexResult();
                /** @var null|ExecutionData $executionData */
                $executionData = $frontResults['execution_data'] ?? null;
                if (! $executionData) {
                    return $vertexResult;
                }
                // ifisdebug sectionpoint,andandnotis debug 模type运line,that么thesectionpointnotallow
                if ($node->getDebug() && ! $executionData->isDebug()) {
                    return $vertexResult;
                }

                $vertex = $this->dag->getVertex($node->getNodeId());
                // thiswithingeneralcome说notwillfornull,先not管nullsituation
                $childrenIds = [];
                foreach ($vertex->children as $childVertex) {
                    // notcanfrom己连from己
                    if ($node->getNodeId() == $childVertex->key) {
                        continue;
                    }
                    $childrenIds[] = $childVertex->key;
                }
                // defaultiswant调degreedownonelevel,ifnotneed调degree,inspecificexecutemiddlecansetfor[]
                $vertexResult->setChildrenIds($childrenIds);
                // add flow
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
                    // ifhavefinger定,thenusefinger定
                    if ($node->getNodeId() === $this->appointRootId) {
                        $vertex->markAsRoot();
                        $this->rootId = $this->appointRootId;
                    }
                } else {
                    // nothavefinger定mustusestartsectionpoint
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
                    // root sectionpointnotallowhave父sectionpoint连line
                    continue;
                }
                $this->dag->addEdgeByKey((string) $nodeId, (string) $nextNodeId);
            }
        }
    }

    private function archiveToCloud(VertexResult $vertexResult): void
    {
        // already经运linepass,alsonotarchive
        if ($vertexResult->hasDebugLog('history_vertex_result')) {
            return;
        }
        // onlytheonelayerprocess才willconductarchive
        if (! $this->executionData->isTop() || $this->inLoop) {
            return;
        }
        if (isset($this->delightfulFlowExecuteLogEntity)) {
            $fromCoroutineId = Coroutine::id();
            Coroutine::create(function () use ($fromCoroutineId) {
                CoContext::copy($fromCoroutineId);

                // 利usefrom旋lockcomecontrolonlyoneinsave
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
