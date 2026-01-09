<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowDraftAppService;
use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowDraftQuery;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\Assembler\FlowDraft\DelightfulFlowDraftAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowDraftFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowDraftAppService $delightfulFlowDraftAppService;

    /**
     * 保存草稿.
     */
    public function save(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $delightfulFlowDraftDTO = DelightfulFlowDraftAssembler::createFlowDraftDTOByMixed($this->request->all());
        $delightfulFlowDraftDTO->setFlowCode($flowId);

        $delightfulFlowDraftDO = DelightfulFlowDraftAssembler::createDelightfulFlowDraftDO($delightfulFlowDraftDTO);

        $delightfulFlowDraft = $this->delightfulFlowDraftAppService->save($authorization, $delightfulFlowDraftDO);
        $icons = $this->delightfulFlowDraftAppService->getIcons($delightfulFlowDraft->getOrganizationCode(), [$delightfulFlowDraft->getDelightfulFlow()['icon'] ?? '']);
        return DelightfulFlowDraftAssembler::createDelightfulFlowDraftDTO($delightfulFlowDraft, [], $icons);
    }

    /**
     * query草稿列table.
     */
    public function queries(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $query = new DelightfulFLowDraftQuery($this->request->all());

        // 仅query最新的记录
        $page = new Page(1, DelightfulFlowDraftEntity::MAX_RECORD);
        $query->setOrder(['id' => 'desc']);
        $query->flowCode = $flowId;

        $result = $this->delightfulFlowDraftAppService->queries($authorization, $query, $page);

        return DelightfulFlowDraftAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    }

    /**
     * query草稿详情.
     */
    public function show(string $flowId, string $draftId)
    {
        $delightfulFlowDraft = $this->delightfulFlowDraftAppService->show($this->getAuthorization(), $flowId, $draftId);
        $icons = $this->delightfulFlowDraftAppService->getIcons($delightfulFlowDraft->getOrganizationCode(), [$delightfulFlowDraft->getDelightfulFlow()['icon'] ?? '']);
        return DelightfulFlowDraftAssembler::createDelightfulFlowDraftDTO($delightfulFlowDraft, [], $icons);
    }

    /**
     * delete草稿.
     */
    public function remove(string $flowId, string $draftId)
    {
        $this->delightfulFlowDraftAppService->remove($this->getAuthorization(), $flowId, $draftId);
    }
}
