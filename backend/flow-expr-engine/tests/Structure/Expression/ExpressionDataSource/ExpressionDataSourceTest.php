<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Test\Structure\Expression\ExpressionDataSource;

use BeDelightful\FlowExprEngine\Structure\Expression\ExpressionDataSource\ExpressionDataSource;
use BeDelightful\FlowExprEngine\Test\BaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class ExpressionDataSourceTest extends BaseTestCase
{
    public function testSystemMethods()
    {
        $expressionDataSource = new ExpressionDataSource(true);
        $this->assertNotEmpty($expressionDataSource->toArray());
    }
}
