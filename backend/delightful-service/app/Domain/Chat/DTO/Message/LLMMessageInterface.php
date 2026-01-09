<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * 大模型reply的message.
 */
interface LLMMessageInterface extends TextContentInterface
{
    // 推理内容
    public function getReasoningContent(): ?string;

    public function setReasoningContent(?string $reasoningContent): static;

    // 不包含推理内容的大模型响应
    public function getContent(): string;

    public function setContent(string $content): static;
}
