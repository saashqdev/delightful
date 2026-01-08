<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Repository\Facade;

// Allows specifying arbitrary extra data
interface DelightfulTokenExtraInterface
{
    public function getDelightfulEnvId(): ?int;

    public function setDelightfulEnvId(?int $delightfulEnvId): void;

    public function setTokenExtraData(array $extraData): self;

    public function toArray(): array;
}
