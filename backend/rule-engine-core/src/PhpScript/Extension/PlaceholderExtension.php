<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Extension;

use Dtyq\RuleEngineCore\PhpScript\NodeVisitor\PlaceholderNodeVisitor;
use Twig\Extension\AbstractExtension;

class PlaceholderExtension extends AbstractExtension
{
    private PlaceholderNodeVisitor $placeholderNodeVisitor;

    public function __construct()
    {
        $this->placeholderNodeVisitor = new PlaceholderNodeVisitor();
    }

    public function getNodeVisitors()
    {
        return [$this->placeholderNodeVisitor];
    }

    public function getPlaceholders(): array
    {
        return $this->placeholderNodeVisitor->getPlaceholders();
    }
}
