<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\PHPSandbox\ExecutableCode\Methods;

use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class LogicalIfMethod extends AbstractMethod
{
    protected string $code = 'logical_if';

    protected string $name = 'logical_if';

    protected string $returnType = 'mixed';

    protected string $group = '逻辑';

    protected string $desc = 'according to指定的条件来returndifferent的结果';

    protected array $args = [
        [
            'name' => 'logical',
            'type' => 'bool',
            'desc' => '逻辑',
        ],
        [
            'name' => 'trueValue',
            'type' => 'mixed',
            'desc' => '逻辑为true时的return值',
        ],
        [
            'name' => 'falseValue',
            'type' => 'mixed',
            'desc' => '逻辑为false时的return值',
        ],
    ];

    public function getFunction(): ?callable
    {
        return function (bool $logical, mixed $trueValue = '', mixed $falseValue = ''): mixed {
            return $logical ? $trueValue : $falseValue;
        };
    }
}
