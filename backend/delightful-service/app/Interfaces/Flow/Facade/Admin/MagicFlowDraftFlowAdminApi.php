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
    protected DelightfulFlowDraftAppService $magicFlowDraftAppService;

    /**
     * 保存草稿.
     */
    public function save(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $magicFlowDraftDTO = DelightfulFlowDraftAssembler::createFlowDraftDTOByMixed($this->request->all());
        $magicFlowDraftDTO->setFlowCode($flowId);

        $magicFlowDraftDO = DelightfulFlowDraftAssembler::createDelightfulFlowDraftDO($magicFlowDraftDTO);

        $magicFlowDraft = $this->magicFlowDraftAppService->save($authorization, $magicFlowDraftDO);
        $icons = $this->magicFlowDraftAppService->getIcons($magicFlowDraft->getOrganizationCode(), [$magicFlowDraft->getDelightfulFlow()['icon'] ?? '']);
        return DelightfulFlowDraftAssembler::createDelightfulFlowDraftDTO($magicFlowDraft, [], $icons);
    }

    /**
     * 查询草稿列表.
     */
    public function queries(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $query = new DelightfulFLowDraftQuery($this->request->all());

        // 仅查询最新的记录
        $page = new Page(1, DelightfulFlowDraftEntity::MAX_RECORD);
        $query->setOrder(['id' => 'desc']);
        $query->flowCode = $flowId;

        $result = $this->magicFlowDraftAppService->queries($authorization, $query, $page);

        return DelightfulFlowDraftAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    }

    /**
     * 查询草稿详情.
     */
    public function show(string $flowId, string $draftId)
    {
        $magicFlowDraft = $this->magicFlowDraftAppService->show($this->getAuthorization(), $flowId, $draftId);
        $icons = $this->magicFlowDraftAppService->getIcons($magicFlowDraft->getOrganizationCode(), [$magicFlowDraft->getDelightfulFlow()['icon'] ?? '']);
        return DelightfulFlowDraftAssembler::createDelightfulFlowDraftDTO($magicFlowDraft, [], $icons);
    }

    /**
     * 删除草稿.
     */
    public function remove(string $flowId, string $draftId)
    {
        $this->magicFlowDraftAppService->remove($this->getAuthorization(), $flowId, $draftId);
    }
}
