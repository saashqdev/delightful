<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\ApiResponse\Response;

/**
 * Low-code platform response structure.
 */
class LowCodeResponse extends AbstractResponse
{
    protected int $successCode = 1000;

    public function body(): array
    {
        $result = [];
        $result['code'] = $this->code;
        $result['message'] = $this->message;
        $result['data'] = $this->data ?? null;

        return $result;
    }
}
