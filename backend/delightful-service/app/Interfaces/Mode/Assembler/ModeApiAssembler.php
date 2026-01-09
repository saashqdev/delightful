<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mode\Assembler;

use App\Application\Mode\DTO\Admin\AdminModeDTO;
use App\Interfaces\Mode\DTO\Request\CreateModeRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeRequest;

class ModeApiAssembler
{
    /**
     * create请求转换为详情DTO.
     */
    public static function createRequestToModeDTO(CreateModeRequest $request): AdminModeDTO
    {
        return new AdminModeDTO($request->all());
    }

    /**
     * update请求转换为详情DTO.
     */
    public static function updateRequestToModeDTO(UpdateModeRequest $request): AdminModeDTO
    {
        return new AdminModeDTO($request->all());
    }
}
