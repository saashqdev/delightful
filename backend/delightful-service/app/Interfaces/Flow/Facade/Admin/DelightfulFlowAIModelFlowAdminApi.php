<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowAIModelAppService;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\Assembler\AIModel\DelightfulFlowAIModelAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowAIModelFlowAdminApi extends AbstractFlowAdminApi
{
    #[Inject]
    protected DelightfulFlowAIModelAppService $delightfulFlowAIModelAppService;

    public function getEnabled()
    {
        /** @var DelightfulUserAuthorization $authorization */
        $authorization = $this->getAuthorization();
        $data = $this->delightfulFlowAIModelAppService->getEnabled($authorization);
        return DelightfulFlowAIModelAssembler::createEnabledListDTO($data['list']);
    }
}
