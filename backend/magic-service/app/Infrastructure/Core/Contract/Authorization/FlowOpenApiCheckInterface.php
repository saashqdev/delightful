<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Authorization;

use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use App\Interfaces\Flow\DTO\MagicFlowApiChatDTO;

interface FlowOpenApiCheckInterface
{
    public function handle(MagicFlowApiChatDTO $magicFlowApiChatDTO): MagicUserAuthorization;
}
