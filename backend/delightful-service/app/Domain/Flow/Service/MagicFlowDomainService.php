<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine\RoutineType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\StartNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowQuery;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Event\DelightfulFlowPublishedEvent;
use App\Domain\Flow\Event\DelightfulFLowSavedEvent;
use App\Domain\Flow\Repository\Facade\DelightfulFlowRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;
use Delightful\AsyncEvent\AsyncEventUtil;
use Delightful\TaskScheduler\Entity\TaskScheduler;
use Delightful\TaskScheduler\Entity\TaskSchedulerCrontab;
use Delightful\TaskScheduler\Service\TaskSchedulerDomainService;
use Throwable;

class DelightfulFlowDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowRepositoryInterface $magicFlowRepository,
        private readonly TaskSchedulerDomainService $taskSchedulerDomainService,
    ) {
    }

    /**
     * 获取节点配置模板.
     */
    public function getNodeTemplate(FlowDataIsolation $dataIsolation, Node $node): Node
    {
        return Node::generateTemplate($node->getNodeType(), $node->getParams(), $node->getNodeVersion());
    }

    /**
     * 获取流程.
     */
    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowEntity
    {
        return $this->magicFlowRepository->getByCode($dataIsolation, $code);
    }

    /**
     * 获取流程.
     * @return array<DelightfulFlowEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $codes): array
    {
        return $this->magicFlowRepository->getByCodes($dataIsolation, $codes);
    }

    /**
     * 获取流程.
     */
    public function getByName(FlowDataIsolation $dataIsolation, string $name, Type $type): ?DelightfulFlowEntity
    {
        return $this->magicFlowRepository->getByName($dataIsolation, $name, $type);
    }

    public function createByAgent(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $savingDelightfulFlow->prepareForCreation();
        $savingDelightfulFlow->setEnabled(true);
        return $this->magicFlowRepository->save($dataIsolation, $savingDelightfulFlow);
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $dateTime = new DateTime();
        $savingDelightfulFlow->setCreatedAt($dateTime);
        $savingDelightfulFlow->setUpdatedAt($dateTime);
        $flow = $this->magicFlowRepository->save($dataIsolation, $savingDelightfulFlow);
        AsyncEventUtil::dispatch(new DelightfulFLowSavedEvent($flow, true));
        return $flow;
    }

    /**
     * 保存流程，仅基础信息.
     */
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $savingDelightfulFlow->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlow->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlow->shouldCreate()) {
            $magicFlow = clone $savingDelightfulFlow;
            $magicFlow->prepareForCreation();
        } else {
            $magicFlow = $this->magicFlowRepository->getByCode($dataIsolation, $savingDelightfulFlow->getCode());
            if (! $magicFlow) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlow->getCode()]);
            }
            $savingDelightfulFlow->prepareForModification($magicFlow);
        }

        $flow = $this->magicFlowRepository->save($dataIsolation, $magicFlow);
        AsyncEventUtil::dispatch(new DelightfulFLowSavedEvent($flow, $savingDelightfulFlow->shouldCreate()));
        return $flow;
    }

    /**
     * 保存节点，nodes、edges.
     */
    public function saveNode(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $magicFlow = $this->magicFlowRepository->getByCode($dataIsolation, $savingDelightfulFlow->getCode());
        if (! $magicFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlow->getCode()]);
        }
        $savingDelightfulFlow->prepareForSaveNode($magicFlow);

        // todo 检测子流程循环调用

        $this->magicFlowRepository->save($dataIsolation, $magicFlow);

        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($magicFlow));
        return $magicFlow;
    }

    /**
     * 删除流程.
     */
    public function destroy(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $deletingDelightfulFlow): void
    {
        $deletingDelightfulFlow->prepareForDeletion();
        $this->magicFlowRepository->remove($dataIsolation, $deletingDelightfulFlow);
    }

    /**
     * 查询流程.
     * @return array{total: int, list: array<DelightfulFlowEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowQuery $query, Page $page): array
    {
        return $this->magicFlowRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * 修改流程状态.
     */
    public function changeEnable(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlow, ?bool $enable = null): void
    {
        // 如果传入了明确的状态值，则直接设置
        if ($enable !== null) {
            // 如果当前状态与要设置的状态相同，则无需操作
            if ($magicFlow->isEnabled() === $enable) {
                return;
            }
            $magicFlow->setEnabled($enable);
        } else {
            // 否则保持原有的自动切换逻辑
            $magicFlow->prepareForChangeEnable();
        }

        // 如果启用状态为true，需要进行验证
        if ($magicFlow->isEnabled() && empty($magicFlow->getNodes())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.cannot_enable_empty_nodes');
        }

        $this->magicFlowRepository->changeEnable($dataIsolation, $magicFlow->getCode(), $magicFlow->isEnabled());
    }

    /**
     * 创建定时任务.
     */
    public function createRoutine(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlow): void
    {
        // 获取开始节点的定时配置
        /** @var null|StartNodeParamsConfig $startNodeParamsConfig */
        $startNodeParamsConfig = $magicFlow->getStartNode()?->getNodeParamsConfig();
        if (! $startNodeParamsConfig) {
            return;
        }
        $startNodeParamsConfig->validate();
        $routineConfigs = $startNodeParamsConfig->getRoutineConfigs();

        // 使用流程的 code 作为外部 id
        $externalId = $magicFlow->getCode();
        $retryTimes = 2;
        $callbackMethod = [DelightfulFlowExecuteAppService::class, 'routine'];
        $callbackParams = [
            'flowCode' => $magicFlow->getCode(),
        ];

        // 先清理一下历史定时任务和调度规则
        $this->taskSchedulerDomainService->clearByExternalId($externalId);

        foreach ($routineConfigs as $branchId => $routineConfig) {
            try {
                $routineConfig->validate();
            } catch (Throwable $throwable) {
                simple_logger('CreateRoutine')->notice('无效的定时规则', [
                    'flowCode' => $magicFlow->getCode(),
                    'branchId' => $branchId,
                    'routineConfig' => $routineConfig->toConfigArray(),
                    'error' => $throwable->getMessage(),
                ]);
            }

            $callbackParams['branchId'] = $branchId;
            $callbackParams['routineConfig'] = $routineConfig->toConfigArray();
            // 如果是不重复的，那么是直接创建调度任务
            if ($routineConfig->getType() === RoutineType::NoRepeat) {
                $taskScheduler = new TaskScheduler();
                $taskScheduler->setExternalId($externalId);
                $taskScheduler->setName($magicFlow->getCode());
                $taskScheduler->setExpectTime($routineConfig->getDatetime());
                $taskScheduler->setType(2);
                $taskScheduler->setRetryTimes($retryTimes);
                $taskScheduler->setCallbackMethod($callbackMethod);
                $taskScheduler->setCallbackParams($callbackParams);
                $taskScheduler->setCreator($magicFlow->getCode());
                $this->taskSchedulerDomainService->create($taskScheduler);
            } else {
                $crontabRule = $routineConfig->getCrontabRule();
                $taskSchedulerCrontab = new TaskSchedulerCrontab();
                $taskSchedulerCrontab->setExternalId($externalId);
                $taskSchedulerCrontab->setName($magicFlow->getCode());
                $taskSchedulerCrontab->setCrontab($crontabRule);
                $taskSchedulerCrontab->setRetryTimes($retryTimes);
                $taskSchedulerCrontab->setEnabled(true);
                $taskSchedulerCrontab->setCallbackMethod($callbackMethod);
                $taskSchedulerCrontab->setCallbackParams($callbackParams);
                $taskSchedulerCrontab->setCreator($magicFlow->getCode());
                $taskSchedulerCrontab->setDeadline($routineConfig->getDeadline());
                $this->taskSchedulerDomainService->createCrontab($taskSchedulerCrontab);
            }
        }
    }
}
