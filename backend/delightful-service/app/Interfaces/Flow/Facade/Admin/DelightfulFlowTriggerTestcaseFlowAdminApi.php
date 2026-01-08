<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowTriggerTestcaseAppService;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowTriggerTestcaseQuery;
use App\Interfaces\Flow\Assembler\TriggerTestcase\DelightfulFlowTriggerTestcaseAssembler;
use App\Interfaces\Flow\DTO\TriggerTestcase\DelightfulFlowTriggerTestcaseDTO;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowTriggerTestcaseFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowTriggerTestcaseAppService $delightfulFlowTriggerTestcaseAppService;

    public function save(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $delightfulFlowTriggerTestcaseDTO = new DelightfulFlowTriggerTestcaseDTO($this->request->all());
        $delightfulFlowTriggerTestcaseDTO->setFlowCode($flowId);

        $delightfulFlowTriggerTestcaseEntity = DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDO($delightfulFlowTriggerTestcaseDTO);

        $delightfulFlowTriggerTestcaseEntity = $this->delightfulFlowTriggerTestcaseAppService->save($authorization, $delightfulFlowTriggerTestcaseEntity);

        return DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDTO($delightfulFlowTriggerTestcaseEntity);
    }

    public function show(string $flowId, string $testcaseId)
    {
        $delightfulFlowTriggerTestcaseEntity = $this->delightfulFlowTriggerTestcaseAppService->show($this->getAuthorization(), $flowId, $testcaseId);

        return DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDTO($delightfulFlowTriggerTestcaseEntity);
    }

    public function remove(string $flowId, string $testcaseId)
    {
        $this->delightfulFlowTriggerTestcaseAppService->remove($this->getAuthorization(), $flowId, $testcaseId);
    }

    public function queries(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $delightfulFlowTriggerTestcaseQuery = new DelightfulFLowTriggerTestcaseQuery($this->request->all());
        $delightfulFlowTriggerTestcaseQuery->flowCode = $flowId;
        $delightfulFlowTriggerTestcaseQuery->setOrder(['id' => 'desc']);

        $page = $this->createPage();

        $result = $this->delightfulFlowTriggerTestcaseAppService->queries($authorization, $delightfulFlowTriggerTestcaseQuery, $page);

        return DelightfulFlowTriggerTestcaseAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    }
}
