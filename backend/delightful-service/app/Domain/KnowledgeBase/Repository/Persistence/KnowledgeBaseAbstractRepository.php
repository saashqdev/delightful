<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;

abstract class KnowledgeBaseAbstractRepository extends AbstractRepository
{
    protected array $attributeMaps = [
        'creator' => 'created_uid',
        'modifier' => 'updated_uid',
    ];
}
