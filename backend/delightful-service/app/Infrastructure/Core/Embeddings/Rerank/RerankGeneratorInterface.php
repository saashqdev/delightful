<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Embeddings\Rerank;

use App\Infrastructure\Core\Contract\Model\RerankInterface;

interface RerankGeneratorInterface
{
    public function rerank(RerankInterface $rerankModel, string $query, array $documents): array;
}
