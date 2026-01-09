<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Flow;

interface NodeParamsConfigInterface
{
    public function setValidateScene(string $scene): void;

    /**
     * parametervalidation.
     */
    public function validate(): array;

    /**
     * getsectionpointconfigurationtemplate.
     */
    public function generateTemplate(): void;

    public function isSkipExecute(): bool;

    public function setSkipExecute(bool $skipExecute): void;
}
