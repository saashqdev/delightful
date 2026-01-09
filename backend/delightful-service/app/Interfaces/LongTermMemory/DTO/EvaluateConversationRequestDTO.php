<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\LongTermMemory\DTO;

use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Infrastructure\Core\AbstractDTO;

/**
 * 评估conversationcontentrequestDTO.
 */
class EvaluateConversationRequestDTO extends AbstractDTO
{
    /**
     * conversationcontent.
     */
    public string $conversationContent = '';

    /**
     * use的modelname.
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
