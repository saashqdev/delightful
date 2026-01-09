<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowVersionAppService;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Interfaces\Flow\Assembler\FlowVersion\DelightfulFlowVersionAssembler;
use App\Interfaces\Flow\DTO\FlowVersion\DelightfulFlowVersionDTO;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowVersionFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowVersionAppService $delightfulFlowVersionAppService;

    /**
     * 版本列table.
     */
    public function queries(string $flowId)
    {
        $query = new DelightfulFLowVersionQuery($this->request->all());
        $query->setFlowCode($flowId);
        $query->setOrder(['id' => 'desc']);
        $page = $this->createPage();

        $result = $this->delightfulFlowVersionAppService->queries($this->getAuthorization(), $query, $page);

        return DelightfulFlowVersionAssembler::createPageListDTO($result['total'], $result['list'], $page, $result['users']);
    }

    /**
     * 版本详情.
     */
    public function show(string $flowId, string $versionId)
    {
        $version = $this->delightfulFlowVersionAppService->show($this->getAuthorization(), $flowId, $versionId);
        $icons = $this->delightfulFlowVersionAppService->getIcons($version->getOrganizationCode(), [$version->getDelightfulFlow()->getIcon()]);
        return DelightfulFlowVersionAssembler::createDelightfulFlowVersionDTO($version, $icons);
    }

    /**
     * 发布版本.
     */
    public function publish(string $flowId)
    {
        $authorization = $this->getAuthorization();
        $versionDTO = new DelightfulFlowVersionDTO($this->request->all());
        $versionDTO->setFlowCode($flowId);

        $versionDO = DelightfulFlowVersionAssembler::createDelightfulFlowVersionDO($versionDTO);

        $version = $this->delightfulFlowVersionAppService->publish($authorization, $versionDO);

        $icons = $this->delightfulFlowVersionAppService->getIcons($version->getOrganizationCode(), [$version->getDelightfulFlow()->getIcon()]);
        return DelightfulFlowVersionAssembler::createDelightfulFlowVersionDTO($version, $icons);
    }

    /**
     * 回滚版本.
     */
    public function rollback(string $flowId, string $versionId)
    {
        $version = $this->delightfulFlowVersionAppService->rollback($this->getAuthorization(), $flowId, $versionId);
        $icons = $this->delightfulFlowVersionAppService->getIcons($version->getOrganizationCode(), [$version->getDelightfulFlow()->getIcon()]);
        return DelightfulFlowVersionAssembler::createDelightfulFlowVersionDTO($version, $icons);
    }
}
