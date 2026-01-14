<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Test\Structure\Value;

use BeDelightful\FlowExprEngine\Builder\ValueBuilder;
use BeDelightful\FlowExprEngine\Test\BaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class ValueMethodTest extends BaseTestCase
{
    public function testStrReplace()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "methods",
            "value": "str_replace",
            "name": "str_replace",
            "args": [
                {
                    "type": "const",
                    "const_value": [
                        {
                            "type": "input",
                            "value": "planet",
                            "name": "",
                            "args": null
                        }
                    ],
                    "expression_value": null
                },
                {
                    "type": "const",
                    "const_value": [
                        {
                            "type": "input",
                            "value": "world",
                            "name": "",
                            "args": null
                        }
                    ],
                    "expression_value": null
                },
                {
                    "type": "const",
                    "const_value": [
                        {
                            "type": "input",
                            "value": "hello planet",
                            "name": "",
                            "args": null
                        }
                    ],
                    "expression_value": null
                },
                {
                    "type": "const",
                    "const_value": [
                        {
                            "type": "input",
                            "value": "",
                            "name": "",
                            "args": null
                        }
                    ],
                    "expression_value": null
                }
            ]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);
        $this->assertEquals(str_replace('planet', 'world', 'hello planet'), $value->getResult());
    }
}
