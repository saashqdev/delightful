<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

use Delightful\FlowExprEngine\Component;

class TopicConfig
{
    /**
     * @var string assigned_topic fingersettopic / recent_topic most近topic
     */
    private string $type;

    private ?Component $name;

    public function __construct(string $type, ?Component $name = null)
    {
        $this->type = $type;
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): ?Component
    {
        return $this->name;
    }

    public function toConfigArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name?->toArray(),
        ];
    }
}
