<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\DTO\Message\ChatMessage\AIImageCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\ImageConvertHighCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\RichTextMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Service\DelightfulChatDomainService;

use function di;

class AbstractAIImageAppService extends AbstractAppService
{
    protected function getReferContentForAIImage(string $referMessageId): ?MessageInterface
    {
        $delightfulChatDomainService = di(DelightfulChatDomainService::class);
        // getmessage
        $referSeq = $delightfulChatDomainService->getSeqMessageByIds([$referMessageId])[0] ?? [];
        // false如message有quote，getquotemessage
        if (! empty($referSeq['refer_message_id'])) {
            $referSeq = $delightfulChatDomainService->getSeqMessageByIds([$referSeq['refer_message_id']])[0] ?? [];
        }
        // getquotemessage文本内容
        $referMessage = $delightfulChatDomainService->getMessageByDelightfulMessageId($referSeq['delightful_message_id'] ?? '');
        return $referMessage?->getContent();
    }

    protected function getReferTextByContentForAIImage(MessageInterface $content): ?string
    {
        if ($content instanceof AIImageCardMessage || $content instanceof ImageConvertHighCardMessage) {
            return $content->getReferText();
        }
        if ($content instanceof TextMessage) {
            return $content->getContent();
        }
        if ($content instanceof RichTextMessage) {
            return $content->getTextContent();
        }
        return null;
    }
}
