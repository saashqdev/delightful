<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\DelightfulAIApi\Api\Response\Chat;

use App\Infrastructure\ExternalAPI\DelightfulAIApi\Kernel\AbstractResponse;

class CompletionsResponse extends AbstractResponse
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }
}
