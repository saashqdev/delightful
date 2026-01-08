<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api;

use HyperfTest\HttpTestCase;

/**
 * This file is proprietary to Lighthouse Engine, disclosure is prohibited.
 * @internal
 */
class AbstractHttpTest extends HttpTestCase
{
    public function getOrganizationCode(): string
    {
        return '000';
    }

    protected function getApiKey(): string
    {
        // Prioritize unit test specified token, use default token if not exists
        return \Hyperf\Support\env('UNIT_TEST_USER_TOKEN') ?: \Hyperf\Support\env('DELIGHTFUL_API_DEFAULT_ACCESS_TOKEN', 'unit_test_user_token');
    }
}
