<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Token\Repository\Facade;

// Allows specifying arbitrary extra data
interface MagicTokenExtraInterface
{
    public function getMagicEnvId(): ?int;

    public function setMagicEnvId(?int $magicEnvId): void;

    public function setTokenExtraData(array $extraData): self;

    public function toArray(): array;
}
