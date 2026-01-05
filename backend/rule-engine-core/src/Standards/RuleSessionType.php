<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards;

use Dtyq\RuleEngineCore\Standards\Exception\ConfigurationException;

class RuleSessionType
{
    public const Stateful = 0;

    public const Stateless = 1;

    public string $name;

    public int $value;

    public static function from(int $value): static
    {
        $ruleSessionType = new static();
        switch ($value) {
            case static::Stateful:
                $ruleSessionType->name = 'Stateful';
                $ruleSessionType->value = static::Stateful;
                break;
            case static::Stateless:
                $ruleSessionType->name = 'Stateless';
                $ruleSessionType->value = static::Stateless;
                break;
            default:
                throw new ConfigurationException('Invalid enumeration value:' . static::class);
        }

        return $ruleSessionType;
    }
}
