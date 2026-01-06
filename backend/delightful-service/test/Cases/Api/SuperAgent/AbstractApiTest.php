<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\BeAgent;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 */
class AbstractApiTest extends AbstractHttpTest
{
    private string $authorization = '';

    protected function switchUserTest1(): string
    {
        return $this->authorization = env('TEST_TOKEN');
    }

    protected function switchUserTest2(): string
    {
        return $this->authorization = env('TEST2_TOKEN');
    }

    protected function getCommonHeaders(): array
    {
        return [
            'organization-code' => env('TEST_ORGANIZATION_CODE'),
            // 换成自己的
            'Authorization' => $this->authorization,
        ];
    }
}
