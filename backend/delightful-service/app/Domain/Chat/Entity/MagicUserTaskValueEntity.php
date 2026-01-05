<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity;

use App\Infrastructure\Core\AbstractEntity;

class MagicUserTaskValueEntity extends AbstractEntity
{
    protected int $interval;

    protected string $unit;

    protected array $values;

    protected string $deadline;
}
