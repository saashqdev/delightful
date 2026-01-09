<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowTriggerTestcaseQuery;
use App\Infrastructure\Core\ValueObject\Page;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowTriggerTestcaseAppService extends AbstractFlowAppService
{
    /**
     * savetouchhairtestcollection.
     */
    public function save(Authenticatable $authorization, DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity): DelightfulFlowTriggerTestcaseEntity
    {
        return $this->delightfulFlowTriggerTestcaseDomainService->save($this->createFlowDataIsolation($authorization), $delightfulFlowTriggerTestcaseEntity);
    }

    /**
     * gettouchhairtestcollection.
     */
    public function show(Authenticatable $authorization, string $flowCode, string $testcaseCode): DelightfulFlowTriggerTestcaseEntity
    {
        return $this->delightfulFlowTriggerTestcaseDomainService->show($this->createFlowDataIsolation($authorization), $flowCode, $testcaseCode);
    }

    /**
     * deletetouchhairtestcollection.
     */
    public function remove(Authenticatable $authorization, string $flowCode, string $testcaseCode): void
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $testcaseEntity = $this->delightfulFlowTriggerTestcaseDomainService->show($dataIsolation, $flowCode, $testcaseCode);
        $this->delightfulFlowTriggerTestcaseDomainService->remove($dataIsolation, $testcaseEntity);
    }

    /**
     * querytouchhairtestcollection.
     * @return array{total: int, list: array<DelightfulFlowTriggerTestcaseEntity>, users: array}
     */
    public function queries(Authenticatable $authorization, DelightfulFLowTriggerTestcaseQuery $query, Page $page): array
    {
        $result = $this->delightfulFlowTriggerTestcaseDomainService->queries($this->createFlowDataIsolation($authorization), $query, $page);
        $userIds = [];
        foreach ($result['list'] as $item) {
            $userIds[] = $item->getCreator();
            $userIds[] = $item->getModifier();
        }
        $result['users'] = $this->delightfulUserDomainService->getByUserIds($this->createContactDataIsolation($this->createFlowDataIsolation($authorization)), $userIds);
        return $result;
    }
}
