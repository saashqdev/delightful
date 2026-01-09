<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\DTO\ConversationListQueryDTO;
use App\Domain\Chat\DTO\PageResponseDTO\ConversationsPageResponseDTO;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Repository\Facade\DelightfulChatConversationRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatConversationModel;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\ConversationAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;

class DelightfulChatConversationRepository implements DelightfulChatConversationRepositoryInterface
{
    public function __construct(
        protected DelightfulChatConversationModel $delightfulChatConversationModel,
        private readonly Redis $redis
    ) {
    }

    public function getConversationsByUserIds(DelightfulConversationEntity $conversation, ConversationListQueryDTO $queryDTO, array $userIds): ConversationsPageResponseDTO
    {
        $conversationIds = $queryDTO->getIds();
        $limit = $queryDTO->getLimit() ?: 100;
        $offset = (int) ($queryDTO->getPageToken() ?: 0);
        $query = $this->delightfulChatConversationModel::query()->whereIn('user_id', $userIds);
        $conversationIds && $query->whereIn('id', $conversationIds);
        if ($queryDTO->getStatus() !== null) {
            $query->where('status', $queryDTO->getStatus());
        }
        if ($queryDTO->getIsTop() !== null) {
            $query->where('is_top', $queryDTO->getIsTop());
        }
        if ($queryDTO->getIsMark() !== null) {
            $query->where('is_mark', $queryDTO->getIsMark());
        }
        if ($queryDTO->getIsNotDisturb() !== null) {
            $query->where('is_not_disturb', $queryDTO->getIsNotDisturb());
        }
        $query->orderBy('is_top', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->offset($offset);
        $conversations = Db::select($query->toSql(), $query->getBindings());
        $items = ConversationAssembler::getConversationEntities($conversations);

        $hasMore = count($items) === $limit;
        $pageToken = $hasMore ? (string) ($offset + $limit) : '';
        return new ConversationsPageResponseDTO([
            'page_token' => $pageToken,
            'has_more' => $hasMore,
            'items' => $items,
        ]);
    }

    /**
     * @return DelightfulConversationEntity[]
     */
    public function getConversationByIds(array $conversationIds): array
    {
        $query = $this->delightfulChatConversationModel::query()->whereIn('id', $conversationIds);
        $conversations = Db::select($query->toSql(), $query->getBindings());
        return ConversationAssembler::getConversationEntities($conversations);
    }

    public function addConversation(DelightfulConversationEntity $conversation): DelightfulConversationEntity
    {
        $time = date('Y-m-d H:i:s');
        if (empty($conversation->getUserOrganizationCode()) || empty($conversation->getReceiveOrganizationCode())) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_ORGANIZATION_CODE_EMPTY);
        }
        $conversationData = [
            'id' => (string) IdGenerator::getSnowId(),
            'user_id' => $conversation->getUserId(),
            'user_organization_code' => $conversation->getUserOrganizationCode(),
            'receive_type' => $conversation->getReceiveType()->value,
            'receive_id' => $conversation->getReceiveId(),
            'receive_organization_code' => $conversation->getReceiveOrganizationCode(),
            'is_not_disturb' => 0,
            'status' => ConversationStatus::Normal->value,
            'is_top' => 0,
            'is_mark' => 0,
            'extra' => '',
            'created_at' => $time,
            'updated_at' => $time,
            'deleted_at' => null,
            'instructs' => $conversation->getInstructs(),
            'translate_config' => $conversation->getTranslateConfig(),
        ];
        $this->delightfulChatConversationModel::query()->create($conversationData);
        return ConversationAssembler::getConversationEntity($conversationData);
    }

    public function getConversationByUserIdAndReceiveId(DelightfulConversationEntity $conversation): ?DelightfulConversationEntity
    {
        $conversationData = $this->getConversationArrayByUserIdAndReceiveId($conversation);
        if (empty($conversationData)) {
            return null;
        }
        return ConversationAssembler::getConversationEntity($conversationData);
    }

    public function getConversationById(string $conversationId): ?DelightfulConversationEntity
    {
        $conversation = $this->getConversationArrayById($conversationId);
        if (empty($conversation)) {
            return null;
        }
        return ConversationAssembler::getConversationEntity($conversation);
    }

    /**
     * (分organization)getuser与指定user的session窗口info.
     * @return array<DelightfulConversationEntity>
     */
    public function getConversationsByReceiveIds(string $userId, array $receiveIds, ?string $userOrganizationCode = null): array
    {
        $query = $this->delightfulChatConversationModel::query()
            ->where('user_id', $userId)
            ->whereIn('receive_id', $receiveIds);

        $userOrganizationCode && $query->where('organization_code', $userOrganizationCode);
        $conversations = Db::select($query->toSql(), $query->getBindings());
        return ConversationAssembler::getConversationEntities($conversations);
    }

    public function getReceiveConversationBySenderConversationId(string $senderConversationId): ?DelightfulConversationEntity
    {
        // get发件方的info
        $senderConversationEntity = $this->getConversationById($senderConversationId);
        if ($senderConversationEntity === null) {
            return null;
        }
        // get收件方的session窗口
        $receiveConversationDTO = new DelightfulConversationEntity();
        $receiveConversationDTO->setUserId($senderConversationEntity->getReceiveId());
        $receiveConversationDTO->setReceiveId($senderConversationEntity->getUserId());
        $receiveConversationEntity = $this->getConversationByUserIdAndReceiveId($receiveConversationDTO);
        return $receiveConversationEntity ?? null;
    }

    public function batchAddConversation(array $conversations): bool
    {
        $time = date('Y-m-d H:i:s');
        $conversationData = [];
        foreach ($conversations as $conversation) {
            if (empty($conversation['id'])) {
                $id = (string) IdGenerator::getSnowId();
            } else {
                $id = $conversation['id'];
            }
            if (empty($conversation['user_organization_code']) || empty($conversation['receive_organization_code'])) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_ORGANIZATION_CODE_EMPTY);
            }
            $conversationData[] = [
                'id' => $id,
                'user_id' => $conversation['user_id'],
                'user_organization_code' => $conversation['user_organization_code'],
                'receive_type' => $conversation['receive_type'],
                'receive_id' => $conversation['receive_id'],
                'receive_organization_code' => $conversation['receive_organization_code'],
                'is_not_disturb' => 0,
                'is_top' => 0,
                'is_mark' => 0,
                'extra' => '',
                'created_at' => $time,
                'updated_at' => $time,
                'deleted_at' => null,
                'status' => ConversationStatus::Normal->value,
            ];
        }
        return $this->delightfulChatConversationModel::query()->insert($conversationData);
    }

    /**
     * @return DelightfulConversationEntity[]
     */
    public function batchGetConversations(array $userIds, string $receiveId, ConversationType $receiveType): array
    {
        $query = $this->delightfulChatConversationModel::query()
            ->whereIn('user_id', $userIds)
            ->where('receive_id', $receiveId)
            ->where('receive_type', $receiveType->value);
        $conversations = Db::select($query->toSql(), $query->getBindings());
        return ConversationAssembler::getConversationEntities($conversations);
    }

    /**
     * 批量移exceptsession窗口.
     */
    public function batchRemoveConversations(array $userIds, string $receiveId, ConversationType $receiveType): int
    {
        if (empty($userIds)) {
            return 0;
        }
        return $this->delightfulChatConversationModel::query()
            ->whereIn('user_id', $userIds)
            ->where('receive_id', $receiveId)
            ->where('receive_type', $receiveType->value)
            ->update([
                'status' => ConversationStatus::Delete->value,
            ]);
    }

    // 批量updatesession窗口
    public function batchUpdateConversations(array $conversationIds, array $updateData): int
    {
        return $this->delightfulChatConversationModel::query()
            ->whereIn('id', $conversationIds)
            ->update($updateData);
    }

    public function getAllConversationList(): array
    {
        return $this->delightfulChatConversationModel::query()->get()->toArray();
    }

    #[CacheEvict(prefix: 'conversation', value: '_#{conversationId}')]
    public function saveInstructs(string $conversationId, array $instructs): void
    {
        $this->delightfulChatConversationModel->newQuery()->where('id', $conversationId)->update(['instructs' => Json::encode($instructs)]);
    }

    /**
     * @return DelightfulConversationEntity[]
     */
    public function getRelatedConversationsWithInstructByUserId(array $userIds): array
    {
        $query = $this->delightfulChatConversationModel->newQuery()
            ->whereIn('user_id', $userIds)
            ->orWhereIn('receive_id', $userIds)
            ->whereNotNull('instructs')
            ->where('instructs', '<>', '');
        $conversations = Db::select($query->toSql(), $query->getBindings());
        return ConversationAssembler::getConversationEntities($conversations);
    }

    /**
     * 批量updatesession窗口的交互指令.
     * @param array $updateData format为：[['conversation_id' => 'xxx', 'instructs' => [...]], ...]
     */
    public function batchUpdateInstructs(array $updateData): void
    {
        if (empty($updateData)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $cases = [];
        $ids = [];
        $bindings = [];

        foreach ($updateData as $data) {
            $cases[] = 'WHEN ? THEN ?';
            $ids[] = $data['conversation_id'];
            $bindings[] = $data['conversation_id'];
            $bindings[] = Json::encode($data['instructs']);
        }

        $bindings[] = $now;
        $bindings = array_merge($bindings, $ids);

        $sql = sprintf(
            'UPDATE %s SET instructs = CASE id %s END, updated_at = ? WHERE id IN (%s)',
            $this->delightfulChatConversationModel->getTable(),
            implode(' ', $cases),
            implode(',', array_fill(0, count($ids), '?'))
        );

        $this->delightfulChatConversationModel::query()
            ->getConnection()
            ->update($sql, $bindings);
    }

    #[CacheEvict(prefix: 'conversation', value: '_#{id}')]
    public function updateConversationById(string $id, array $data): int
    {
        $time = date('Y-m-d H:i:s');
        $data['updated_at'] = $time;
        unset($data['id']);
        return $this->delightfulChatConversationModel::query()
            ->where('id', $id)
            ->update($data);
    }

    public function updateConversationStatusByIds(array $ids, ConversationStatus $status): int
    {
        $time = date('Y-m-d H:i:s');
        return $this->delightfulChatConversationModel::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => $status->value,
                'updated_at' => $time,
            ]);
    }

    private function getConversationArrayByUserIdAndReceiveId(DelightfulConversationEntity $conversation): ?array
    {
        $cacheKey = sprintf(
            'conversation_%s_%s_%s_%s',
            $conversation->getUserId(),
            $conversation->getReceiveId(),
            $conversation->getUserOrganizationCode(),
            $conversation->getReceiveOrganizationCode()
        );
        $conversationData = $this->redis->get($cacheKey);
        if ($conversationData) {
            return Json::decode($conversationData);
        }
        $query = $this->delightfulChatConversationModel::query()
            ->where('user_id', $conversation->getUserId())
            ->where('receive_id', $conversation->getReceiveId())
            ->when($conversation->hasReceiveType(), function ($query) use ($conversation) {
                $query->where('receive_type', $conversation->getReceiveType()->value);
            });
        // receive_type +  receive_id 其实是all局唯一的,can确定organizationencoding. but是ifneedquery时指定organization,also是加上
        if ($conversation->getUserOrganizationCode()) {
            $query->where('user_organization_code', $conversation->getUserOrganizationCode());
        }
        if ($conversation->getReceiveOrganizationCode()) {
            $query->where('receive_organization_code', $conversation->getReceiveOrganizationCode());
        }
        $result = Db::select($query->toSql(), $query->getBindings())[0] ?? [];
        if ($result) {
            $this->redis->setex($cacheKey, 60, Json::encode($result));
        }
        return $result;
    }

    // 避免 redis cacheserialize的object,占usetoo多内存
    #[Cacheable(prefix: 'conversation', value: '_#{conversationId}', ttl: 10)]
    private function getConversationArrayById(string $conversationId): array
    {
        $query = $this->delightfulChatConversationModel::query()->where('id', $conversationId);
        $conversation = Db::select($query->toSql(), $query->getBindings())[0] ?? [];
        return empty($conversation) ? [] : $conversation;
    }
}
