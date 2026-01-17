<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinAgent;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentLimit;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentDeletedEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentDisabledEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentEnabledEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentSavedEvent;
use Delightful\BeDelightful\Domain\Agent\Repository\Facade\BeDelightfulAgentRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;

readonly class BeDelightfulAgentDomainService
{
    public function __construct(
        protected BeDelightfulAgentRepositoryInterface $beDelightfulAgentRepository
    ) {
    }

    public function getByCode(BeDelightfulAgentDataIsolation $dataIsolation, string $code): ?BeDelightfulAgentEntity
    {
        $this->checkBuiltinAgentOperation($code);
        return $this->beDelightfulAgentRepository->getByCode($dataIsolation, $code);
    }

    /**
     * @return array{total: int, list: array<BeDelightfulAgentEntity>}
     */
    public function queries(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentQuery $query, Page $page): array
    {
        return $this->beDelightfulAgentRepository->queries($dataIsolation, $query, $page);
    }

    public function save(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentEntity $savingEntity): BeDelightfulAgentEntity
    {
        $savingEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        $isCreate = $savingEntity->shouldCreate();

        if ($isCreate) {
            // 检查用户创建的智能体数量是否超过限制
            $currentCount = $this->beDelightfulAgentRepository->countByCreator($dataIsolation, $dataIsolation->getCurrentUserId());
            if ($currentCount >= BeDelightfulAgentLimit::MAX_AGENTS_PER_USER) {
                ExceptionBuilder::throw(
                    BeDelightfulErrorCode::AgentLimitExceeded,
                    'super_magic.agent.limit_exceeded',
                    ['limit' => BeDelightfulAgentLimit::MAX_AGENTS_PER_USER]
                );
            }

            $entity = clone $savingEntity;
            $entity->prepareForCreation();
        } else {
            $this->checkBuiltinAgentOperation($savingEntity->getCode());

            $entity = $this->beDelightfulAgentRepository->getByCode($dataIsolation, $savingEntity->getCode());
            if (! $entity) {
                ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $savingEntity->getCode()]);
            }

            $savingEntity->prepareForModification($entity);
        }

        $savedEntity = $this->beDelightfulAgentRepository->save($dataIsolation, $entity);

        AsyncEventUtil::dispatch(new BeDelightfulAgentSavedEvent($savedEntity, $isCreate));

        return $savedEntity;
    }

    public function delete(BeDelightfulAgentDataIsolation $dataIsolation, string $code): bool
    {
        $this->checkBuiltinAgentOperation($code);

        $entity = $this->beDelightfulAgentRepository->getByCode($dataIsolation, $code);
        if (! $entity) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $code]);
        }

        $result = $this->beDelightfulAgentRepository->delete($dataIsolation, $code);

        if ($result) {
            AsyncEventUtil::dispatch(new BeDelightfulAgentDeletedEvent($entity));
        }

        return $result;
    }

    public function enable(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity
    {
        $this->checkBuiltinAgentOperation($code);
        $entity = $this->getByCodeWithException($dataIsolation, $code);

        $entity->setEnabled(true);
        $entity->setModifier($dataIsolation->getCurrentUserId());
        $entity->setUpdatedAt(new DateTime());

        $savedEntity = $this->beDelightfulAgentRepository->save($dataIsolation, $entity);

        AsyncEventUtil::dispatch(new BeDelightfulAgentEnabledEvent($savedEntity));

        return $savedEntity;
    }

    public function disable(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity
    {
        $this->checkBuiltinAgentOperation($code);

        $entity = $this->getByCodeWithException($dataIsolation, $code);

        $entity->setEnabled(false);
        $entity->setModifier($dataIsolation->getCurrentUserId());
        $entity->setUpdatedAt(new DateTime());

        $savedEntity = $this->beDelightfulAgentRepository->save($dataIsolation, $entity);

        AsyncEventUtil::dispatch(new BeDelightfulAgentDisabledEvent($savedEntity));

        return $savedEntity;
    }

    public function getByCodeWithException(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity
    {
        $this->checkBuiltinAgentOperation($code);

        $entity = $this->beDelightfulAgentRepository->getByCode($dataIsolation, $code);
        if (! $entity) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $code]);
        }

        return $entity;
    }

    /**
     * 检查是否为内置智能体，如果是则抛出异常.
     */
    private function checkBuiltinAgentOperation(string $code): void
    {
        $builtinAgent = BuiltinAgent::tryFrom($code);
        if ($builtinAgent) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::BuiltinAgentNotAllowed, 'super_magic.agent.builtin_not_allowed');
        }
    }
}
