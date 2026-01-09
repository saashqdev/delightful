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

    protected string $desc = 'according tofinger定itemitemcomereturndifferentresult';

    protected array $args = [
        [
            'name' => 'logical',
            'type' => 'bool',
            'desc' => '逻辑',
        ],
        [
            'name' => 'trueValue',
            'type' => 'mixed',
            'desc' => '逻辑fortrueo clockreturnvalue',
        ],
        [
            'name' => 'falseValue',
            'type' => 'mixed',
            'desc' => '逻辑forfalseo clockreturnvalue',
        ],
    ];

    public function getFunction(): ?callable
    {
        return function (bool $logical, mixed $trueValue = '', mixed $falseValue = ''): mixed {
            return $logical ? $trueValue : $falseValue;
        };
    }
}
