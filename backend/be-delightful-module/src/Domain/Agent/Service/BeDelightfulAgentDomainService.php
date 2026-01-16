<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinAgent;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\query \BeDelightfulAgentquery ;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentLimit;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentdelete dEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentDisabledEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentEnabledEvent;
use Delightful\BeDelightful\Domain\Agent\Event\BeDelightfulAgentSavedEvent;
use Delightful\BeDelightful\Domain\Agent\Repository\Facade\BeDelightfulAgentRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;
readonly

class BeDelightfulAgentDomainService 
{
 
    public function __construct( 
    protected BeDelightfulAgentRepositoryInterface $BeDelightfulAgentRepository ) 
{
 
}
 
    public function getByCode(BeDelightfulAgentDataIsolation $dataIsolation, string $code): ?BeDelightfulAgentEntity 
{
 $this->checkBuiltinAgentOperation($code); return $this->BeDelightfulAgentRepository->getByCode($dataIsolation, $code); 
}
 /** * @return array
{
total: int, list: array<BeDelightfulAgentEntity>
}
 */ 
    public function queries(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentquery $query, Page $page): array 
{
 return $this->BeDelightfulAgentRepository->queries($dataIsolation, $query, $page); 
}
 
    public function save(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentEntity $savingEntity): BeDelightfulAgentEntity 
{
 $savingEntity->setcreator ($dataIsolation->getcurrent user Id()); $savingEntity->setOrganizationCode($dataIsolation->getcurrent OrganizationCode()); $isCreate = $savingEntity->shouldCreate(); if ($isCreate) 
{
 // check user CreateQuantitywhether Limit $currentCount = $this->BeDelightfulAgentRepository->countBycreator ($dataIsolation, $dataIsolation->getcurrent user Id()); if ($currentCount >= BeDelightfulAgentLimit::MAX_AGENTS_PER_USER) 
{
 ExceptionBuilder::throw( BeDelightfulErrorCode::AgentLimitExceeded, 'super_magic.agent.limit_exceeded', ['limit' => BeDelightfulAgentLimit::MAX_AGENTS_PER_USER] ); 
}
 $entity = clone $savingEntity; $entity->prepareForCreation(); 
}
 else 
{
 $this->checkBuiltinAgentOperation($savingEntity->getCode()); $entity = $this->BeDelightfulAgentRepository->getByCode($dataIsolation, $savingEntity->getCode()); if (! $entity) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $savingEntity->getCode()]); 
}
 $savingEntity->prepareForModification($entity); 
}
 $savedEntity = $this->BeDelightfulAgentRepository->save($dataIsolation, $entity); AsyncEventUtil::dispatch(new BeDelightfulAgentSavedEvent($savedEntity, $isCreate)); return $savedEntity; 
}
 
    public function delete(BeDelightfulAgentDataIsolation $dataIsolation, string $code): bool 
{
 $this->checkBuiltinAgentOperation($code); $entity = $this->BeDelightfulAgentRepository->getByCode($dataIsolation, $code); if (! $entity) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $code]); 
}
 $result = $this->BeDelightfulAgentRepository->delete($dataIsolation, $code); if ($result) 
{
 AsyncEventUtil::dispatch(new BeDelightfulAgentdelete dEvent($entity)); 
}
 return $result; 
}
 
    public function enable(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity 
{
 $this->checkBuiltinAgentOperation($code); $entity = $this->getByCodeWithException($dataIsolation, $code); $entity->setEnabled(true); $entity->setModifier($dataIsolation->getcurrent user Id()); $entity->setUpdatedAt(new DateTime()); $savedEntity = $this->BeDelightfulAgentRepository->save($dataIsolation, $entity); AsyncEventUtil::dispatch(new BeDelightfulAgentEnabledEvent($savedEntity)); return $savedEntity; 
}
 
    public function disable(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity 
{
 $this->checkBuiltinAgentOperation($code); $entity = $this->getByCodeWithException($dataIsolation, $code); $entity->setEnabled(false); $entity->setModifier($dataIsolation->getcurrent user Id()); $entity->setUpdatedAt(new DateTime()); $savedEntity = $this->BeDelightfulAgentRepository->save($dataIsolation, $entity); AsyncEventUtil::dispatch(new BeDelightfulAgentDisabledEvent($savedEntity)); return $savedEntity; 
}
 
    public function getByCodeWithException(BeDelightfulAgentDataIsolation $dataIsolation, string $code): BeDelightfulAgentEntity 
{
 $this->checkBuiltinAgentOperation($code); $entity = $this->BeDelightfulAgentRepository->getByCode($dataIsolation, $code); if (! $entity) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::NotFound, 'common.not_found', ['label' => $code]); 
}
 return $entity; 
}
 /** * check whether as Built-inIfyes ThrowException. */ 
    private function checkBuiltinAgentOperation(string $code): void 
{
 $builtinAgent = BuiltinAgent::tryFrom($code); if ($builtinAgent) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::BuiltinAgentNotAllowed, 'super_magic.agent.builtin_not_allowed'); 
}
 
}
 
}
 
