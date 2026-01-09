<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\LongTermMemory\DTO;

use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Infrastructure\Core\AbstractDTO;

/**
 * 评估conversation内容requestDTO.
 */
class EvaluateConversationRequestDTO extends AbstractDTO
{
    /**
     * conversation内容.
     */
    public string $conversationContent = '';

    /**
     * use的模型名称.
     */
    public string $modelName = LLMModelEnum::DEEPSEEK_V3->value;

    public function getConversationContent(): string
    {
        return $this->conversationContent;
    }

    public function setConversationContent(string $conversationContent): void
    {
        $this->conversationContent = $conversationContent;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function setModelName(string $modelName): void
    {
        $this->modelName = $modelName;
    }
}
