<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\PHPSandbox\ExecutableCode\Methods;

use BeDelightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\AbstractMethod;

class LogicalIfMethod extends AbstractMethod
{
    protected string $code = 'logical_if';

    protected string $name = 'logical_if';

    protected string $returnType = 'mixed';

    protected string $group = 'logic';

    protected string $desc = 'according tofingersetitemitemcomereturndifferentresult';

    protected array $args = [
        [
            'name' => 'logical',
            'type' => 'bool',
            'desc' => 'logic',
        ],
        [
            'name' => 'trueValue',
            'type' => 'mixed',
            'desc' => 'logicfortrueo clockreturnvalue',
        ],
        [
            'name' => 'falseValue',
            'type' => 'mixed',
            'desc' => 'logicforfalseo clockreturnvalue',
        ],
    ];

    public function getFunction(): ?callable
    {
        return function (bool $logical, mixed $trueValue = '', mixed $falseValue = ''): mixed {
            return $logical ? $trueValue : $falseValue;
        };
    }
}
