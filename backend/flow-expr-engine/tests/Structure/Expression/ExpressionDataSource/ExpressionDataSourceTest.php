<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\FlowExprEngine\Test\Structure\Expression\ExpressionDataSource;

use Dtyq\FlowExprEngine\Structure\Expression\ExpressionDataSource\ExpressionDataSource;
use Dtyq\FlowExprEngine\Test\BaseTestCase;

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
