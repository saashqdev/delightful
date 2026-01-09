<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Infrastructure\Core\Traits\DataIsolationTrait;

class AbstractAppService extends AbstractKernelAppService
{
    use DataIsolationTrait;

    /**
     * 公共matchfield.
     */
    public function getCommonRules(): array
    {
        return [
            'context' => 'required',
            'context.organization_code' => 'string|nullable',
            'context.language' => 'string|nullable',
            'data' => 'required|array',
        ];
    }
}
