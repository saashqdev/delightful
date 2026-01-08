<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Factory;

use App\Domain\Chat\Entity\DelightfulMessageEntity;

class DelightfulMessageFactory
{
    public static function arrayToEntity(array $message): DelightfulMessageEntity
    {
        return new DelightfulMessageEntity($message);
    }
}
