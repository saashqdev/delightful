<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory\Persistence;

use App\Application\Flow\ExecuteManager\Memory\LLMMemoryMessage;
use App\Application\Flow\ExecuteManager\Memory\MemoryQuery;
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessage;
use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\ValueObject\AggregateSearch\AggregateAISearchCardResponseType;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Flow\Entity\DelightfulFlowMemoryHistoryEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\MemoryType;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowMemoryHistoryQuery;
use App\Domain\Flow\Service\DelightfulFlowMemoryHistoryDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use Carbon\Carbon;
use DateTime;

class ChatMemory implements MemoryPersistenceInterface
{
    public function __construct(
        protected DelightfulChatDomainService $delightfulChatDomainService,
        protected DelightfulFlowMemoryHistoryDomainService $delightfulFlowMemoryHistoryDomainService,
    ) {
    }

    public function queries(MemoryQuery $memoryQuery, array $ignoreMessageIds = []): array
    {
        $imMessages = $this->getImChatMessages($memoryQuery);

        $mountIds = [];
        $messageLists = [];
        foreach ($imMessages as $imMessage) {
            if (in_array($imMessage->getDelightfulMessageId(), $ignoreMessageIds)) {
                continue;
            }

            $customMessage = LLMMemoryMessage::createByChatMemory($imMessage);
            if (! $customMessage) {
                continue;
            }

            $messageLists[] = $customMessage;
            $mountIds[] = $customMessage->getMessageId();
        }

        return $this->mountMessages($mountIds, $messageLists);
    }

    public function store(LLMMemoryMessage $LLMMemoryMessage): void
    {
        // onlyprocessmount
        if (empty($LLMMemoryMessage->getMountId())) {
            return;
        }

        // thiswithinstorageis historymessagestoragesectionpoint mountmessage
        $history = new DelightfulFlowMemoryHistoryEntity();
        $history->setType(MemoryType::Mount);
        $history->setConversationId($LLMMemoryMessage->getConversationId());
        $history->setTopicId($LLMMemoryMessage->getTopicId());
        $history->setRequestId($LLMMemoryMessage->getRequestId());
        $history->setMessageId($LLMMemoryMessage->getMessageId());
        $history->setMountId($LLMMemoryMessage->getMountId());
        $history->setRole($LLMMemoryMessage->getRole()->value);
        $history->setContent($LLMMemoryMessage->getOriginalContent());
        $history->setCreatedUid($LLMMemoryMessage->getUid());
        $history->setCreatedAt(new DateTime());
        $flowDataIsolation = FlowDataIsolation::create(userId: $LLMMemoryMessage->getUid());
        $this->delightfulFlowMemoryHistoryDomainService->create($flowDataIsolation, $history);
    }

    /**
     * alreadyalreadyisrowgoodsequence havemessage.
     * @return array<DelightfulMessageEntity>
     */
    public function getImChatMessages(MemoryQuery $memoryQuery): array
    {
        $seqLimit = $memoryQuery->getLimit();

        // todo back続inquery侧optimize
        // whenfor ai_card message,samemessagehave 20 item,needgo重,butisinquerytime,isnotknowhaveduplicate
        // inthiswithinfirstputquantityquery,at mostquery 200 item,然backagainconduct重.
        $seqLimit = ($seqLimit * 20 <= 200) ? $seqLimit * 20 : 200;

        $messagesQueryDTO = (new MessagesQueryDTO());
        $messagesQueryDTO->setConversationId($memoryQuery->getOriginConversationId());
        $messagesQueryDTO->setLimit($seqLimit);
        $messagesQueryDTO->setTopicId($memoryQuery->getTopicId());
        if ($memoryQuery->getStartTime()) {
            $messagesQueryDTO->setTimeStart(Carbon::make($memoryQuery->getStartTime()));
        }
        if ($memoryQuery->getEndTime()) {
            $messagesQueryDTO->setTimeEnd(Carbon::make($memoryQuery->getEndTime()));
        }

        $clientSeq = $this->delightfulChatDomainService->getConversationChatMessages($memoryQuery->getOriginConversationId(), $messagesQueryDTO);
        $messageIds = [];

        foreach ($clientSeq as $seqResponseDTO) {
            // cardinfoonlygetbigmodelreturn,bigmodelreturnfeaturehave type = 1, parent_id = 0
            if ($seqResponseDTO->getSeq()?->getMessage()?->getContent() instanceof AggregateAISearchCardMessage) {
                /** @var AggregateAISearchCardMessage $aggregateAISearchCardMessage */
                $aggregateAISearchCardMessage = $seqResponseDTO->getSeq()?->getMessage()?->getContent();
                if ($aggregateAISearchCardMessage->getType() != AggregateAISearchCardResponseType::LLM_RESPONSE || ! empty($aggregateAISearchCardMessage->getParentId())) {
                    continue;
                }
            }

            $messageId = $seqResponseDTO->getSeq()->getMessage()->getDelightfulMessageId();
            if ($messageId) {
                $messageIds[] = $messageId;
            }
            // specialprocess, whenstartgo重,andreturnitemcountgreater thanequal limit,thennotagaincontinuequery
            if (count($messageIds) >= $memoryQuery->getLimit()) {
                break;
            }
        }
        $messageLists = [];
        if (! empty($messageIds)) {
            $imMessages = $this->delightfulChatDomainService->getMessageEntitiesByMaicMessageIds($messageIds, $memoryQuery->getRangMessageTypes());
            foreach ($imMessages as $imMessage) {
                // thiswithinisforsortcorrect according to seq orderconductrow
                $index = array_search($imMessage->getDelightfulMessageId(), $messageIds);
                if ($index !== false) {
                    $messageLists[$index] = $imMessage;
                }
            }
        }
        // 按 key reverse order
        krsort($messageLists);
        return $messageLists;
    }

    /**
     * addmountmemory,即in Chat o clockcall historymessagestoragesectionpoint.
     * @return array<LLMMemoryMessage>
     */
    private function mountMessages(array $moundIds, array $messageLists): array
    {
        if (empty($moundIds) || empty($messageLists)) {
            return $messageLists;
        }
        $mountQuery = new DelightfulFlowMemoryHistoryQuery();
        $mountQuery->setMountIds($moundIds);
        $mountQuery->setType(MemoryType::Mount->value);
        $flowDataIsolation = FlowDataIsolation::create()->disabled();

        $mountMessages = [];
        $mountLists = $this->delightfulFlowMemoryHistoryDomainService->queries($flowDataIsolation, $mountQuery, Page::createNoPage())['list'] ?? [];
        foreach ($mountLists as $mountHistoryMessage) {
            $mountMessages[$mountHistoryMessage->getMountId()][] = LLMMemoryMessage::createByFlowMemory($mountHistoryMessage);
        }
        if (empty($mountMessages)) {
            return $messageLists;
        }

        // resetmountorder
        $messages = [];
        foreach ($messageLists as $customMessage) {
            $messages[] = $customMessage;
            $messageId = $customMessage->getMessageId();
            if (isset($mountMessages[$messageId])) {
                foreach ($mountMessages[$messageId] as $mountMessage) {
                    $messages[] = $mountMessage;
                }
            }
        }

        return $messages;
    }
}
