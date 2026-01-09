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
        // 只process挂载
        if (empty($LLMMemoryMessage->getMountId())) {
            return;
        }

        // 这里storage的是 historymessagestorage节点 挂载message
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
     * 已经是排好序的所有message.
     * @return array<DelightfulMessageEntity>
     */
    public function getImChatMessages(MemoryQuery $memoryQuery): array
    {
        $seqLimit = $memoryQuery->getLimit();

        // todo 后续在query侧optimize
        // 当为 ai_card 的message，samemessage有 20 条，need去重，但是在query的时候，是不知道有重复的
        // 在这里先放量query，at mostquery 200 条，然后再进行重。
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
            // cardinfo只取大modelreturn，大modelreturn的特征有 type = 1, parent_id = 0
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
            // 特殊process, 当开启去重，且return的条数greater thanequal limit，则不再continuequery
            if (count($messageIds) >= $memoryQuery->getLimit()) {
                break;
            }
        }
        $messageLists = [];
        if (! empty($messageIds)) {
            $imMessages = $this->delightfulChatDomainService->getMessageEntitiesByMaicMessageIds($messageIds, $memoryQuery->getRangMessageTypes());
            foreach ($imMessages as $imMessage) {
                // 这里是为了sortcorrect according to seq 的顺序进行排
                $index = array_search($imMessage->getDelightfulMessageId(), $messageIds);
                if ($index !== false) {
                    $messageLists[$index] = $imMessage;
                }
            }
        }
        // 按 key 倒序
        krsort($messageLists);
        return $messageLists;
    }

    /**
     * 添加挂载记忆，即在 Chat 时call了 historymessagestorage节点.
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

        // reset挂载顺序
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
