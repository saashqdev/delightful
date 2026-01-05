<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\ApiResponse\Response;

/**
 * Standard response structure.
 */
class StandardResponse extends AbstractResponse
{
    public function body(): array
    {
        $result = [];
        $result['code'] = $this->code;
        $result['message'] = $this->message;
        $this->data && $result['data'] = $this->data;

        return $result;
    }
}
