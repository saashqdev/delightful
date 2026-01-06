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
    protected DelightfulFlowTriggerTestcaseAppService $magicFlowTriggerTestcaseAppService;

    public function save(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $magicFlowTriggerTestcaseDTO = new DelightfulFlowTriggerTestcaseDTO($this->request->all());
        $magicFlowTriggerTestcaseDTO->setFlowCode($flowId);

        $magicFlowTriggerTestcaseEntity = DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDO($magicFlowTriggerTestcaseDTO);

        $magicFlowTriggerTestcaseEntity = $this->magicFlowTriggerTestcaseAppService->save($authorization, $magicFlowTriggerTestcaseEntity);

        return DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDTO($magicFlowTriggerTestcaseEntity);
    }

    public function show(string $flowId, string $testcaseId)
    {
        $magicFlowTriggerTestcaseEntity = $this->magicFlowTriggerTestcaseAppService->show($this->getAuthorization(), $flowId, $testcaseId);

        return DelightfulFlowTriggerTestcaseAssembler::createDelightfulFlowTriggerTestcaseDTO($magicFlowTriggerTestcaseEntity);
    }

    public function remove(string $flowId, string $testcaseId)
    {
        $this->magicFlowTriggerTestcaseAppService->remove($this->getAuthorization(), $flowId, $testcaseId);
    }

    public function queries(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $magicFlowTriggerTestcaseQuery = new DelightfulFLowTriggerTestcaseQuery($this->request->all());
        $magicFlowTriggerTestcaseQuery->flowCode = $flowId;
        $magicFlowTriggerTestcaseQuery->setOrder(['id' => 'desc']);

        $page = $this->createPage();

        $result = $this->magicFlowTriggerTestcaseAppService->queries($authorization, $magicFlowTriggerTestcaseQuery, $page);

        return DelightfulFlowTriggerTestcaseAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    }
}
