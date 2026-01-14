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
class ValueDisplayTest extends BaseTestCase
{
    public function testMember()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "member",
            "value": "message",
            "name": "message",
            "args": null,
            "member_value": [
                {
                    "id": "430379931150888960",
                    "name": "Cai Lunduo",
                    "avatar": "",
                    "position": "Management trainee",
                    "user_groups": [],
                    "email": "team@delightful.cn",
                    "job_number": "",
                    "departments": [
                        {
                            "id": "552075023930417156",
                            "name": "Tech Center",
                            "path_name": "Tech Center"
                        }
                    ]
                }
            ]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals('Cai Lunduo', $value->getResult()[0]['name']);
    }

    public function testMemberWithFields()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "member",
            "value": "",
            "name": "message",
            "args": null,
            "member_value": [
                {
                    "id": "430379931150888960",
                    "name": "Cai Lunduo",
                    "type": "user",
                    "avatar": ""
                },
                {
                    "type": "fields",
                    "value": "9527.user",
                    "name": "name",
                    "args": []
                },
                {
                    "id": "430379931150888961",
                    "name": "Cai Lunduo",
                    "type": "department",
                    "avatar": ""
                }
            ]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals([
            [
                'id' => '430379931150888960',
                'name' => 'Cai Lunduo',
                'type' => 'user',
                'avatar' => '',
            ],
            [
                'id' => '111222',
                'name' => 'Cai Lunduo',
                'type' => 'user',
                'avatar' => 'xx',
            ],
            [
                'id' => '430379931150888961',
                'name' => 'Cai Lunduo',
                'type' => 'department',
                'avatar' => '',
            ],
        ], $value->getResult([
            '9527' => [
                'user' => [
                    'id' => '111222',
                    'name' => 'Cai Lunduo',
                    'type' => 'user',
                    'avatar' => 'xx',
                ],
            ],
        ]));
    }

    public function testDatetime()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "datetime",
            "value": "message",
            "name": "message",
            "args": null,
            "datetime_value": {
                "type": "today",
                "value": ""
            }
        },
        {
            "type": "fields",
            "value": "message",
            "name": "message",
            "args": null
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals(date('Y-m-d 00:00:00') . ' xxx', $value->getResult(['message' => ' xxx']));

        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "datetime",
            "value": "message",
            "name": "message",
            "args": null,
            "datetime_value": {
                "type": "trigger_time",
                "value": ""
            }
        },
        {
            "type": "fields",
            "value": "message",
            "name": "message",
            "args": null
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals(date('Y-m-d H:i:s') . ' xxx', $value->getResult(['message' => ' xxx']));
    }

    public function testMultiple()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "multiple",
            "value": "message",
            "name": "message",
            "args": null,
            "multiple_value": ["Fr4IOy1728959555812"]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals(['Fr4IOy1728959555812'], $value->getResult());
    }

    public function testSelect()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "select",
            "value": "message",
            "name": "message",
            "args": null,
            "select_value": ["Fr4IOy1728959555812"]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals(['Fr4IOy1728959555812'], $value->getResult());
    }

    public function testCheckbox()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "checkbox",
            "value": "message",
            "name": "message",
            "args": null,
            "checkbox_value": true
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertTrue($value->getResult());
    }

    public function testDepartmentNames()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "department_names",
            "value": "message",
            "name": "message",
            "args": null,
            "department_names_value": [
                "Tech Center", "Product Center"
            ]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals(['Tech Center', 'Product Center'], $value->getResult());
    }

    public function testNames()
    {
        $array = json_decode(<<<'JSON'
{
    "type": "const",
    "const_value": [
        {
            "type": "names",
            "value": "message",
            "name": "message",
            "args": null,
            "names_value": [
                {
                    "id": "552075023930417156",
                    "name": "Tech Center"
                },
                {
                   "id": "552075023930417157",
                   "name": "Tech Center 1"
                },
                {
                   "type": "fields",
                   "value": "9527.name"
                }
            ]
        }
    ],
    "expression_value": null
}
JSON, true);
        $builder = new ValueBuilder();
        $value = $builder->build($array);

        $this->assertEquals($array, $value->toArray());
        $this->assertEquals([
            ['id' => '552075023930417156', 'name' => 'Tech Center'],
            ['id' => '552075023930417157', 'name' => 'Tech Center 1'],
            ['id' => '552075023930417158', 'name' => 'Tech Center 2'],
        ], $value->getResult([
            '9527' => [
                'name' => [
                    'id' => '552075023930417158', 'name' => 'Tech Center 2',
                ],
            ],
        ]));
    }
}
