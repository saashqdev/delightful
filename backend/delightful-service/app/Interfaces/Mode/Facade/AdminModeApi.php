<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mode\Facade;

use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\Application\Mode\DTO\Admin\AdminModeAggregateDTO;
use App\Application\Mode\Service\AdminModeAppService;
use App\Infrastructure\Core\AbstractApi;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Mode\DTO\Request\CreateModeRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeRequest;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;

#[ApiResponse('low_code')]
class AdminModeApi extends AbstractApi
{
    public function __construct(
        private AdminModeAppService $adminModeAppService
    ) {
    }

    /**
     * getmode列表.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::QUERY)]
    public function getModes(RequestInterface $request)
    {
        $authorization = $this->getAuthorization();
        $page = new Page(
            (int) $request->input('page', 1),
            (int) $request->input('page_size', 20)
        );

        return $this->adminModeAppService->getModes($authorization, $page);
    }

    /**
     * getmodedetail.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::QUERY)]
    public function getMode(RequestInterface $request, string $id)
    {
        $authorization = $this->getAuthorization();
        return $this->adminModeAppService->getModeById($authorization, $id);
    }

    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::QUERY)]
    public function getOriginMode(RequestInterface $request, string $id)
    {
        $authorization = $this->getAuthorization();
        return $this->adminModeAppService->getOriginMode($authorization, $id);
    }

    /**
     * createmode.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::EDIT)]
    public function createMode(CreateModeRequest $request)
    {
        $authorization = $this->getAuthorization();
        $request->validated();
        return $this->adminModeAppService->createMode($authorization, $request);
    }

    /**
     * updatemode.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::EDIT)]
    public function updateMode(UpdateModeRequest $request, string $id)
    {
        $authorization = $this->getAuthorization();
        $request->validated();
        return $this->adminModeAppService->updateMode($authorization, $id, $request);
    }

    /**
     * updatemodestatus
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::EDIT)]
    public function updateModeStatus(RequestInterface $request, string $id)
    {
        $authorization = $this->getAuthorization();
        $status = (bool) $request->input('status', 1);

        $this->adminModeAppService->updateModeStatus($authorization, $id, $status);
    }

    /**
     * getdefaultmode.
     */
    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::QUERY)]
    public function getDefaultMode()
    {
        $authorization = $this->getAuthorization();
        return $this->adminModeAppService->getDefaultMode($authorization);
    }

    #[CheckPermission([DelightfulResourceEnum::ADMIN_AI_MODE], DelightfulOperationEnum::EDIT)]
    public function saveModeConfig(RequestInterface $request, string $id)
    {
        $authorization = $this->getAuthorization();
        $modeAggregateDTO = new AdminModeAggregateDTO($request->all());
        $modeAggregateDTO->getMode()->setId($id);
        return $this->adminModeAppService->saveModeConfig($authorization, $modeAggregateDTO);
    }
}
