<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowToolSetAppService;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Interfaces\Flow\Assembler\ToolSet\DelightfulFlowToolSetAssembler;
use App\Interfaces\Flow\DTO\ToolSet\DelightfulFlowToolSetDTO;
use BeDelightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowToolSetApiFlow extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowToolSetAppService $delightfulFlowToolSetAppService;

    public function save()
    {
        $authorization = $this->getAuthorization();

        $DTO = new DelightfulFlowToolSetDTO($this->request->all());

        $DO = DelightfulFlowToolSetAssembler::createDO($DTO);
        $entity = $this->delightfulFlowToolSetAppService->save($authorization, $DO);
        $icons = $this->delightfulFlowToolSetAppService->getIcons($entity->getOrganizationCode(), [$entity->getIcon()]);
        return DelightfulFlowToolSetAssembler::createDTO($entity, $icons);
    }

    public function queries()
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();

        $query = new DelightfulFlowToolSetQuery($this->request->all());
        $query->withToolsSimpleInfo = true;
        $result = $this->delightfulFlowToolSetAppService->queries($authorization, $query, $page);
        return DelightfulFlowToolSetAssembler::createPageListDTO(
            total: $result['total'],
            list: $result['list'],
            page: $page,
            users: [],
            icons: $result['icons'] ?? []
        );
    }

    public function show(string $code)
    {
        $authorization = $this->getAuthorization();
        $entity = $this->delightfulFlowToolSetAppService->getByCode($authorization, $code);
        $icons = $this->delightfulFlowToolSetAppService->getIcons($entity->getOrganizationCode(), [$entity->getIcon()]);
        return DelightfulFlowToolSetAssembler::createDTO($entity, $icons);
    }

    public function destroy(string $code)
    {
        $authorization = $this->getAuthorization();
        $this->delightfulFlowToolSetAppService->destroy($authorization, $code);
    }
}
