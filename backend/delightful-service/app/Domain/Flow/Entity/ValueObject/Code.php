<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum Code: string
{
    case DelightfulFlow = 'DELIGHTFUL-FLOW';
    case DelightfulFlowNode = 'DELIGHTFUL-FLOW-NODE';
    case DelightfulFlowDraft = 'DELIGHTFUL-FLOW-DRAFT';
    case DelightfulFlowVersion = 'DELIGHTFUL-FLOW-VERSION';
    case DelightfulFlowApiKey = 'DELIGHTFUL-FLOW-API-KEY';
    case Knowledge = 'KNOWLEDGE';
    case ApiKeySK = 'api-sk';
    case DelightfulFlowToolSet = 'TOOL-SET';

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
