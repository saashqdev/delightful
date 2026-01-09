<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\DelightfulTopicMessageEntity;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatTopicMessageModel;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatTopicModel;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Constants\Order;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use App\Interfaces\Chat\Assembler\TopicAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;

class DelightfulChatTopicRepository implements DelightfulChatTopicRepositoryInterface
{
    public function __construct(
        protected DelightfulChatTopicModel $topicModel,
        protected DelightfulChatTopicMessageModel $topicMessagesModel,
        protected DelightfulChatConversationRepository $conversationRepository,
        protected DelightfulChatSeqRepositoryInterface $seqRepository,
    ) {
    }

    // create话题
    public function createTopic(DelightfulTopicEntity $delightfulTopicEntity): DelightfulTopicEntity
    {
        if (empty($delightfulTopicEntity->getOrganizationCode())) {
            ExceptionBuilder::throw(
                ChatErrorCode::INPUT_PARAM_ERROR,
                'chat.common.param_error',
                ['param' => 'organization_code null']
            );
        }
        $time = date('Y-m-d H:i:s');
        $data = $delightfulTopicEntity->toArray();
        if (empty($data['id'])) {
            $data['id'] = IdGenerator::getSnowId();
            $delightfulTopicEntity->setId((string) $data['id']);
        }
        if (empty($data['topic_id'])) {
            $data['topic_id'] = IdGenerator::getSnowId();
            $delightfulTopicEntity->setTopicId((string) $data['topic_id']);
        }
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $this->topicModel::query()->create($data);
        return $delightfulTopicEntity;
    }

    // update话题
    public function updateTopic(DelightfulTopicEntity $delightfulTopicEntity): DelightfulTopicEntity
    {
        $name = $delightfulTopicEntity->getName();
        // lengthnot能超pass 50
        if (mb_strlen($name) > 50) {
            ExceptionBuilder::throw(
                ChatErrorCode::INPUT_PARAM_ERROR,
                'chat.common.param_error',
                ['param' => 'topic_name']
            );
        }
        $this->checkEntity($delightfulTopicEntity);
        $this->topicModel::query()
            ->where('conversation_id', $delightfulTopicEntity->getConversationId())
            ->where('topic_id', $delightfulTopicEntity->getTopicId())
            ->update([
                'name' => $delightfulTopicEntity->getName(),
                'description' => $delightfulTopicEntity->getDescription(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        return $delightfulTopicEntity;
    }

    // delete话题
    public function deleteTopic(DelightfulTopicEntity $delightfulTopicDTO): int
    {
        $this->checkEntity($delightfulTopicDTO);
        return (int) $this->topicModel::query()
            ->where('conversation_id', $delightfulTopicDTO->getConversationId())
            ->where('topic_id', $delightfulTopicDTO->getTopicId())
            ->delete();
    }

    /**
     * getsessionsessionlist.
     * @param string[] $topicIds
     * @return array<DelightfulTopicEntity>
     */
    public function getTopicsByConversationId(string $conversationId, array $topicIds): array
    {
        $query = $this->topicModel::query()->where('conversation_id', $conversationId);
        ! empty($topicIds) && $query->whereIn('topic_id', $topicIds);
        $topics = Db::select($query->toSql(), $query->getBindings());
        return TopicAssembler::getTopicEntities($topics);
    }

    public function getTopicEntity(DelightfulTopicEntity $delightfulTopicDTO): ?DelightfulTopicEntity
    {
        $this->checkEntity($delightfulTopicDTO);
        $topic = $this->getTopicArray($delightfulTopicDTO);
        if ($topic === null) {
            return null;
        }
        return TopicAssembler::getTopicEntity($topic);
    }

    public function createTopicMessage(DelightfulTopicMessageEntity $topicMessageDTO): DelightfulTopicMessageEntity
    {
        if (empty($topicMessageDTO->getSeqId())) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_MESSAGE_NOT_FOUND);
        }
        $time = date('Y-m-d H:i:s');
        $data = $topicMessageDTO->toArray();
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $this->topicMessagesModel::query()->create($data);
        return $topicMessageDTO;
    }

    public function createTopicMessages(array $data): bool
    {
        return $this->topicMessagesModel::query()->insert($data);
    }

    /**
     * @return array<DelightfulTopicMessageEntity>
     */
    public function getTopicMessageByMessageIds(array $messageIds): array
    {
        $query = $this->topicMessagesModel::query()->whereIn('seq_id', $messageIds);
        $topicMessages = Db::select($query->toSql(), $query->getBindings());
        return TopicAssembler::getTopicMessageEntities($topicMessages);
    }

    /**
     * @return array<DelightfulTopicMessageEntity>
     */
    public function getTopicMessagesByConversationId(string $conversationId): array
    {
        $query = $this->topicMessagesModel::query()->where('conversation_id', $conversationId);
        $topicMessages = Db::select($query->toSql(), $query->getBindings());
        return TopicAssembler::getTopicMessageEntities($topicMessages);
    }

    public function getTopicByName(string $conversationId, string $topicName): ?DelightfulTopicEntity
    {
        $topic = $this->topicModel::query()
            ->where('conversation_id', $conversationId)
            ->where('name', $topicName);
        $topic = Db::select($topic->toSql(), $topic->getBindings())[0] ?? null;
        if (empty($topic)) {
            return null;
        }
        return TopicAssembler::getTopicEntity($topic);
    }

    public function getPrivateChatReceiveTopicEntity(string $senderTopicId, string $senderConversationId): ?DelightfulTopicEntity
    {
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setTopicId($senderTopicId);
        $topicDTO->setConversationId($senderConversationId);
        $senderTopicEntity = $this->getTopicEntity($topicDTO);
        if ($senderTopicEntity === null) {
            return null;
        }
        $receiveConversationEntity = $this->conversationRepository->getReceiveConversationBySenderConversationId($senderTopicEntity->getConversationId());
        if ($receiveConversationEntity === null) {
            return null;
        }
        $receiveTopicDTO = new DelightfulTopicEntity();
        $receiveTopicDTO->setTopicId($senderTopicEntity->getTopicId());
        $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
        $receiveTopicEntity = $this->getTopicEntity($receiveTopicDTO);
        return $receiveTopicEntity ?? null;
    }

    /**
     * 按timerangegetsessiondownsome话题message.
     * @return ClientSequenceResponse[]
     */
    public function getTopicMessages(MessagesQueryDTO $messagesQueryDTO): array
    {
        $delightfulTopicDTO = new DelightfulTopicEntity();
        $delightfulTopicDTO->setConversationId($messagesQueryDTO->getConversationId());
        $delightfulTopicDTO->setTopicId($messagesQueryDTO->getTopicId());
        $this->checkEntity($delightfulTopicDTO);
        $topicEntity = $this->getTopicEntity($delightfulTopicDTO);
        if ($topicEntity === null) {
            return [];
        }
        $timeStart = $messagesQueryDTO->getTimeStart();
        $timeEnd = $messagesQueryDTO->getTimeEnd();
        $pageToken = $messagesQueryDTO->getPageToken();
        $limit = $messagesQueryDTO->getLimit();
        $order = $messagesQueryDTO->getOrder();
        if ($order === Order::Desc) {
            $operator = '<';
            $direction = 'desc';
        } else {
            $operator = '>';
            $direction = 'asc';
        }
        $query = $this->topicMessagesModel::query()
            ->where('conversation_id', $delightfulTopicDTO->getConversationId())
            ->where('topic_id', $delightfulTopicDTO->getTopicId());
        if ($timeStart !== null) {
            $query->where('created_at', '>=', $timeStart->toDateTimeString());
        }
        if ($timeEnd !== null) {
            $query->where('created_at', '<=', $timeEnd->toDateTimeString());
        }
        if (! empty($pageToken)) {
            $query->where('seq_id', $operator, $pageToken);
        }
        $query->limit($limit)->orderBy('seq_id', $direction)->select('seq_id');
        $seqList = Db::select($query->toSql(), $query->getBindings());
        // according to seqIds getmessagedetail
        $seqIds = array_column($seqList, 'seq_id');
        $clientSequenceResponses = $this->seqRepository->getConversationMessagesBySeqIds($seqIds, $order);

        return SeqAssembler::sortSeqList($clientSequenceResponses, $order);
    }

    /**
     * passtopic_idget话题info（notneedconversation_id）.
     */
    public function getTopicByTopicId(string $topicId): ?DelightfulTopicEntity
    {
        if (empty($topicId)) {
            return null;
        }

        $topic = $this->topicModel::query()
            ->where('topic_id', $topicId)
            ->first();

        if (empty($topic)) {
            return null;
        }

        return TopicAssembler::getTopicEntity($topic->toArray());
    }

    public function deleteTopicByIds(array $ids)
    {
        $ids = array_values(array_filter(array_unique($ids)));
        if (empty($ids)) {
            return 0;
        }
        return $this->topicModel::query()->whereIn('id', $ids)->delete();
    }

    /**
     * Get topics by topic ID.
     * @param string $topicId 话题ID
     * @return DelightfulTopicEntity[] 话题实bodyarray
     */
    public function getTopicsByTopicId(string $topicId): array
    {
        $query = $this->topicModel::query()->where('topic_id', $topicId);
        $topics = Db::select($query->toSql(), $query->getBindings());
        return TopicAssembler::getTopicEntities($topics);
    }

    /**
     * Get topic messages by conversation ID, topic ID and max seq ID.
     * @param string $conversationId sessionID
     * @param string $topicId 话题ID
     * @param int $maxSeqId most大序columnID（containtheID）
     * @return DelightfulTopicMessageEntity[] 话题message实bodyarray
     */
    public function getTopicMessagesBySeqId(string $conversationId, string $topicId, int $maxSeqId): array
    {
        $query = $this->topicMessagesModel::query()
            ->where('conversation_id', $conversationId)
            ->where('topic_id', $topicId)
            ->where('seq_id', '<=', $maxSeqId)
            ->orderBy('seq_id', 'asc');

        $topicMessages = Db::select($query->toSql(), $query->getBindings());
        return TopicAssembler::getTopicMessageEntities($topicMessages);
    }

    // 避免 redis cacheserializeobject,占usetoo多inside存
    #[Cacheable(prefix: 'topic:id:conversation', value: '_#{delightfulTopicDTO.topicId}_#{delightfulTopicDTO.conversationId}', ttl: 60)]
    private function getTopicArray(DelightfulTopicEntity $delightfulTopicDTO): ?array
    {
        $query = $this->topicModel::query()
            ->where('conversation_id', $delightfulTopicDTO->getConversationId())
            ->where('topic_id', $delightfulTopicDTO->getTopicId());
        return Db::select($query->toSql(), $query->getBindings())[0] ?? null;
    }

    private function checkEntity($delightfulTopicEntity): void
    {
        if (empty($delightfulTopicEntity->getTopicId())) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }
        if (empty($delightfulTopicEntity->getConversationId())) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
    }
}
