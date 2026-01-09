<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mode\Assembler;

use App\Application\Mode\DTO\ModeGroupDTO;
use App\Interfaces\Mode\DTO\Request\CreateModeGroupRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeGroupRequest;

class ModeGroupApiAssembler
{
    /**
     * createrequestconvert为分组DTO.
     */
    public static function createRequestToModeGroupDTO(CreateModeGroupRequest $request): ModeGroupDTO
    {
        return new ModeGroupDTO($request->all());
    }

    /**
     * updaterequestconvert为分组DTO.
     */
    public static function updateRequestToModeGroupDTO(UpdateModeGroupRequest $request): ModeGroupDTO
    {
        return new ModeGroupDTO($request->all());
    }
}
