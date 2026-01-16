<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Output Tokens Details Value Object.
 */
class OutputTokensDetails
{
    public function __construct(
        private ?int $reasoningTokens
    ) {
    }

    public static function fromArray(?array $data): ?self
    {
        if (empty($data)) {
            return null;
        }

        return new self(
            $data['reasoning_tokens'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'reasoning_tokens' => $this->reasoningTokens,
        ];
    }

    public function getReasoningTokens(): ?int
    {
        return $this->reasoningTokens;
    }
}
