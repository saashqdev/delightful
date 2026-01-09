<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * 大modelreplymessage.
 */
interface LLMMessageInterface extends TextContentInterface
{
    // 推理content
    public function getReasoningContent(): ?string;

    public function setReasoningContent(?string $reasoningContent): static;

    // notcontain推理content大modelresponse
    public function getContent(): string;

    public function setContent(string $content): static;
}
