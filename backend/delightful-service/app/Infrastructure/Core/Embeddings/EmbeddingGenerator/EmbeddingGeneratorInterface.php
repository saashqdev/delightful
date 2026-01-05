<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Embeddings\EmbeddingGenerator;

use Hyperf\Odin\Contract\Model\EmbeddingInterface;

interface EmbeddingGeneratorInterface
{
    /**
     * @return array<float>
     */
    public function embedText(EmbeddingInterface $embeddingModel, string $text, array $options = []): array;
}
