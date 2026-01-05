<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\ApiResponse\Response;

interface ResponseInterface
{
    /**
     * Success response.
     */
    public function success(mixed $data): static;

    /**
     * Error response.
     */
    public function error(int $code, string $message, mixed $data = null): static;

    /**
     * Return structure definition.
     */
    public function body(): array;
}
