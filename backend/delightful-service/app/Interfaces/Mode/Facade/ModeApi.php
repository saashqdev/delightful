<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mode\Facade;

use App\Application\Mode\Service\ModeAppService;
use App\Infrastructure\Core\AbstractApi;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class ModeApi extends AbstractApi
{
    #[Inject]
    protected ModeAppService $modeAppService;

    public function getModes()
    {
        return $this->modeAppService->getModes($this->getAuthorization());
    }
}
