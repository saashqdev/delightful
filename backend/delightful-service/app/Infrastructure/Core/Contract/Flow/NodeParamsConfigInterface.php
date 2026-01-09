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
     * parameter校验.
     */
    public function validate(): array;

    /**
     * get节点configurationtemplate.
     */
    public function generateTemplate(): void;

    public function isSkipExecute(): bool;

    public function setSkipExecute(bool $skipExecute): void;
}
