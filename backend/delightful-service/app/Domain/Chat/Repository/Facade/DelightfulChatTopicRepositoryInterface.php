<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\DelightfulTopicMessageEntity;

interface DelightfulChatTopicRepositoryInterface
{
    // create话题
    public function createTopic(DelightfulTopicEntity $delightfulTopicEntity): DelightfulTopicEntity;

    // update话题
    public function updateTopic(DelightfulTopicEntity $delightfulTopicEntity): DelightfulTopicEntity;

    // delete话题
    public function deleteTopic(DelightfulTopicEntity $delightfulTopicDTO): int;

    /**
     * getconversationconversationcolumn表.
     * @param string[] $topicIds
     * @return array<DelightfulTopicEntity>
     */
    public function getTopicsByConversationId(string $conversationId, array $topicIds): array;

    public function getTopicEntity(DelightfulTopicEntity $delightfulTopicDTO): ?DelightfulTopicEntity;

    public function createTopicMessage(DelightfulTopicMessageEntity $topicMessageDTO): DelightfulTopicMessageEntity;

    public function createTopicMessages(array $data): bool;

    /**
     * @return array<DelightfulTopicMessageEntity>
     */
    public function getTopicMessageByMessageIds(array $messageIds): array;

    public function getPrivateChatReceiveTopicEntity(string $senderTopicId, string $senderConversationId): ?DelightfulTopicEntity;

    public function getTopicByName(string $conversationId, string $topicName): ?DelightfulTopicEntity;

    /**
     * @return array<DelightfulTopicMessageEntity>
     */
    public function getTopicMessagesByConversationId(string $conversationId): array;

    /**
     * 按timerangegetconversationdownsome话题message.
     * @return ClientSequenceResponse[]
     */
    public function getTopicMessages(MessagesQueryDTO $messagesQueryDTO): array;

    /**
     * passtopic_idget话题information（notneedconversation_id）.
     */
    public function getTopicByTopicId(string $topicId): ?DelightfulTopicEntity;

    public function deleteTopicByIds(array $ids);

    /**
     * Get topics by topic ID.
     * @param string $topicId 话题ID
     * @return DelightfulTopicEntity[] 话题实bodyarray
     */
    public function getTopicsByTopicId(string $topicId): array;

    /**
     * Get topic messages by conversation ID, topic ID and max seq ID.
     * @param string $conversationId conversationID
     * @param string $topicId 话题ID
     * @param int $maxSeqId mostbig序columnID（containtheID）
     * @return DelightfulTopicMessageEntity[] 话题message实bodyarray
     */
    public function getTopicMessagesBySeqId(string $conversationId, string $topicId, int $maxSeqId): array;
}
