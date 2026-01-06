<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;

/**
 * @internal
 */
class BaseTest extends HttpTestCase
{
    public function testO()
    {
        $this->assertTrue(defined('DELIGHTFUL_ACCESS_TOKEN'));
    }
}
