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
        private readonly DelightfulFlowRepositoryInterface $delightfulFlowRepository,
        private readonly TaskSchedulerDomainService $taskSchedulerDomainService,
    ) {
    }

    /**
     * getsectionpointconfigurationtemplate.
     */
    public function getNodeTemplate(FlowDataIsolation $dataIsolation, Node $node): Node
    {
        return Node::generateTemplate($node->getNodeType(), $node->getParams(), $node->getNodeVersion());
    }

    /**
     * getprocess.
     */
    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowEntity
    {
        return $this->delightfulFlowRepository->getByCode($dataIsolation, $code);
    }

    /**
     * getprocess.
     * @return array<DelightfulFlowEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $codes): array
    {
        return $this->delightfulFlowRepository->getByCodes($dataIsolation, $codes);
    }

    /**
     * getprocess.
     */
    public function getByName(FlowDataIsolation $dataIsolation, string $name, Type $type): ?DelightfulFlowEntity
    {
        return $this->delightfulFlowRepository->getByName($dataIsolation, $name, $type);
    }

    public function createByAgent(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $savingDelightfulFlow->prepareForCreation();
        $savingDelightfulFlow->setEnabled(true);
        return $this->delightfulFlowRepository->save($dataIsolation, $savingDelightfulFlow);
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $dateTime = new DateTime();
        $savingDelightfulFlow->setCreatedAt($dateTime);
        $savingDelightfulFlow->setUpdatedAt($dateTime);
        $flow = $this->delightfulFlowRepository->save($dataIsolation, $savingDelightfulFlow);
        AsyncEventUtil::dispatch(new DelightfulFLowSavedEvent($flow, true));
        return $flow;
    }

    /**
     * saveprocess，仅基础info.
     */
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $savingDelightfulFlow->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlow->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlow->shouldCreate()) {
            $delightfulFlow = clone $savingDelightfulFlow;
            $delightfulFlow->prepareForCreation();
        } else {
            $delightfulFlow = $this->delightfulFlowRepository->getByCode($dataIsolation, $savingDelightfulFlow->getCode());
            if (! $delightfulFlow) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlow->getCode()]);
            }
            $savingDelightfulFlow->prepareForModification($delightfulFlow);
        }

        $flow = $this->delightfulFlowRepository->save($dataIsolation, $delightfulFlow);
        AsyncEventUtil::dispatch(new DelightfulFLowSavedEvent($flow, $savingDelightfulFlow->shouldCreate()));
        return $flow;
    }

    /**
     * savesectionpoint，nodes、edges.
     */
    public function saveNode(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $savingDelightfulFlow): DelightfulFlowEntity
    {
        $delightfulFlow = $this->delightfulFlowRepository->getByCode($dataIsolation, $savingDelightfulFlow->getCode());
        if (! $delightfulFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlow->getCode()]);
        }
        $savingDelightfulFlow->prepareForSaveNode($delightfulFlow);

        // todo 检测子processloopcall

        $this->delightfulFlowRepository->save($dataIsolation, $delightfulFlow);

        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($delightfulFlow));
        return $delightfulFlow;
    }

    /**
     * deleteprocess.
     */
    public function destroy(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $deletingDelightfulFlow): void
    {
        $deletingDelightfulFlow->prepareForDeletion();
        $this->delightfulFlowRepository->remove($dataIsolation, $deletingDelightfulFlow);
    }

    /**
     * queryprocess.
     * @return array{total: int, list: array<DelightfulFlowEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowQuery $query, Page $page): array
    {
        return $this->delightfulFlowRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * modifyprocessstatus.
     */
    public function changeEnable(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $delightfulFlow, ?bool $enable = null): void
    {
        // if传入明确statusvalue，then直接set
        if ($enable !== null) {
            // ifcurrentstatusand要setstatussame，then无需操as
            if ($delightfulFlow->isEnabled() === $enable) {
                return;
            }
            $delightfulFlow->setEnabled($enable);
        } else {
            // 否then保持原havefrom动切换逻辑
            $delightfulFlow->prepareForChangeEnable();
        }

        // ifenablestatusfortrue，needconductverify
        if ($delightfulFlow->isEnabled() && empty($delightfulFlow->getNodes())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.node.cannot_enable_empty_nodes');
        }

        $this->delightfulFlowRepository->changeEnable($dataIsolation, $delightfulFlow->getCode(), $delightfulFlow->isEnabled());
    }

    /**
     * createscheduletask.
     */
    public function createRoutine(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $delightfulFlow): void
    {
        // getstartsectionpointscheduleconfiguration
        /** @var null|StartNodeParamsConfig $startNodeParamsConfig */
        $startNodeParamsConfig = $delightfulFlow->getStartNode()?->getNodeParamsConfig();
        if (! $startNodeParamsConfig) {
            return;
        }
        $startNodeParamsConfig->validate();
        $routineConfigs = $startNodeParamsConfig->getRoutineConfigs();

        // useprocess code asforoutside部 id
        $externalId = $delightfulFlow->getCode();
        $retryTimes = 2;
        $callbackMethod = [DelightfulFlowExecuteAppService::class, 'routine'];
        $callbackParams = [
            'flowCode' => $delightfulFlow->getCode(),
        ];

        // 先cleanup一downhistoryscheduletaskand调degreerule
        $this->taskSchedulerDomainService->clearByExternalId($externalId);

        foreach ($routineConfigs as $branchId => $routineConfig) {
            try {
                $routineConfig->validate();
            } catch (Throwable $throwable) {
                simple_logger('CreateRoutine')->notice('invalidschedulerule', [
                    'flowCode' => $delightfulFlow->getCode(),
                    'branchId' => $branchId,
                    'routineConfig' => $routineConfig->toConfigArray(),
                    'error' => $throwable->getMessage(),
                ]);
            }

            $callbackParams['branchId'] = $branchId;
            $callbackParams['routineConfig'] = $routineConfig->toConfigArray();
            // ifisnot重复，那么is直接create调degreetask
            if ($routineConfig->getType() === RoutineType::NoRepeat) {
                $taskScheduler = new TaskScheduler();
                $taskScheduler->setExternalId($externalId);
                $taskScheduler->setName($delightfulFlow->getCode());
                $taskScheduler->setExpectTime($routineConfig->getDatetime());
                $taskScheduler->setType(2);
                $taskScheduler->setRetryTimes($retryTimes);
                $taskScheduler->setCallbackMethod($callbackMethod);
                $taskScheduler->setCallbackParams($callbackParams);
                $taskScheduler->setCreator($delightfulFlow->getCode());
                $this->taskSchedulerDomainService->create($taskScheduler);
            } else {
                $crontabRule = $routineConfig->getCrontabRule();
                $taskSchedulerCrontab = new TaskSchedulerCrontab();
                $taskSchedulerCrontab->setExternalId($externalId);
                $taskSchedulerCrontab->setName($delightfulFlow->getCode());
                $taskSchedulerCrontab->setCrontab($crontabRule);
                $taskSchedulerCrontab->setRetryTimes($retryTimes);
                $taskSchedulerCrontab->setEnabled(true);
                $taskSchedulerCrontab->setCallbackMethod($callbackMethod);
                $taskSchedulerCrontab->setCallbackParams($callbackParams);
                $taskSchedulerCrontab->setCreator($delightfulFlow->getCode());
                $taskSchedulerCrontab->setDeadline($routineConfig->getDeadline());
                $this->taskSchedulerDomainService->createCrontab($taskSchedulerCrontab);
            }
        }
    }
}
