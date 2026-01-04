<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\ApiResponse\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class ApiResponse extends AbstractAnnotation
{
    /**
     * Structure version.
     */
    public string $version;

    /**
     * Whether to enable transformation.
     */
    public bool $needTransform;

    public function __construct(string $version = 'standard', bool $needTransform = true)
    {
        $this->version = $version;
        $this->needTransform = $needTransform;
    }
}
