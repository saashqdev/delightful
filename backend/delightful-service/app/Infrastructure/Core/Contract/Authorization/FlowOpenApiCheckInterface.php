<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Authorization;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;

interface FlowOpenApiCheckInterface
{
    public function handle(DelightfulFlowApiChatDTO $magicFlowApiChatDTO): DelightfulUserAuthorization;
}
