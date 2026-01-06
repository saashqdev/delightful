<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum Code: string
{
    case MagicFlow = 'DELIGHTFUL-FLOW';
    case MagicFlowNode = 'DELIGHTFUL-FLOW-NODE';
    case MagicFlowDraft = 'DELIGHTFUL-FLOW-DRAFT';
    case MagicFlowVersion = 'DELIGHTFUL-FLOW-VERSION';
    case MagicFlowApiKey = 'DELIGHTFUL-FLOW-API-KEY';
    case Knowledge = 'KNOWLEDGE';
    case ApiKeySK = 'api-sk';
    case MagicFlowToolSet = 'TOOL-SET';

    public function gen(): string
    {
        return $this->value . '-' . str_replace('.', '-', uniqid('', true));
    }

    public function genUserTopic(string $conversationId, string $topic): string
    {
        return $this->value . '-' . $conversationId . '-' . $topic;
    }

    public function genUserConversation(string $conversationId): string
    {
        return $this->value . '-' . $conversationId;
    }
}
