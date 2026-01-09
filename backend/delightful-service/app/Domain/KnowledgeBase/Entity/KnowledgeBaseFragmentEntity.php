<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity;

use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Embeddings\VectorStores\PointInfo;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;
use Hyperf\Codec\Json;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Stringable\Str;

use function mb_strlen;

/**
 * toquantityknowledge basetextslicesegment.
 */
class KnowledgeBaseFragmentEntity extends AbstractKnowledgeBaseEntity
{
    public const string PAYLOAD_PREFIX = '#';

    protected ?int $id = null;

    protected string $knowledgeCode = '';

    protected string $documentCode = '';

    /**
     * slicesegmentcontent.
     */
    protected string $content;

    protected array $metadata = [];

    /**
     * 业务 ID，canuseat业务方recordfrom己 ID usecomeconductupdatedata.
     */
    protected string $businessId = '';

    protected string $pointId = '';

    protected string $vector = '';

    protected KnowledgeSyncStatus $syncStatus;

    protected int $syncTimes = 0;

    protected string $syncStatusMessage = '';

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    protected float $score = 0;

    protected int $wordCount = 0;

    protected int $version = 1;

    public function shouldCreate(): bool
    {
        return empty($this->id);
    }

    public function prepareForCreation(): self
    {
        if (empty($this->knowledgeCode)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.knowledge_code.empty');
        }
        if (! isset($this->content) || trim($this->content) === '') {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.content.empty');
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.creator.empty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        $this->checkMetadata();

        $this->pointId = md5($this->content);
        $this->vector = '';
        $this->modifier = $this->creator;
        $this->updatedAt = $this->createdAt;
        $this->syncStatus = KnowledgeSyncStatus::NotSynced;
        $this->syncStatusMessage = '';
        $this->setWordCount(mb_strlen($this->content));
        return $this;
    }

    public function prepareForModification(KnowledgeBaseFragmentEntity $delightfulFlowKnowledgeFragmentEntity): self
    {
        if (empty($this->content)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.content.empty');
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.creator.empty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        $this->checkMetadata();

        $delightfulFlowKnowledgeFragmentEntity->setContent($this->content);
        $delightfulFlowKnowledgeFragmentEntity->setMetadata($this->metadata);
        $delightfulFlowKnowledgeFragmentEntity->setBusinessId($this->businessId);
        $delightfulFlowKnowledgeFragmentEntity->setModifier($this->creator);
        $delightfulFlowKnowledgeFragmentEntity->setUpdatedAt($this->createdAt);
        $delightfulFlowKnowledgeFragmentEntity->setDocumentCode($this->documentCode);
        $delightfulFlowKnowledgeFragmentEntity->setWordCount(mb_strlen($this->content));
        $delightfulFlowKnowledgeFragmentEntity->setVersion($this->version);
        return $this;
    }

    public function hasModify(KnowledgeBaseFragmentEntity $savingDelightfulFlowKnowledgeFragmentEntity): bool
    {
        // if content and metadata allnothavechange，thennotneedupdate
        if ($savingDelightfulFlowKnowledgeFragmentEntity->getContent() === $this->content
            && $savingDelightfulFlowKnowledgeFragmentEntity->getMetadata() === $this->metadata
            && $savingDelightfulFlowKnowledgeFragmentEntity->getBusinessId() === $this->businessId
            && $savingDelightfulFlowKnowledgeFragmentEntity->getDocumentCode() === $this->documentCode
        ) {
            return false;
        }
        return true;
    }

    public static function createByPointInfo(PointInfo $pointInfo, string $knowledgeCode): KnowledgeBaseFragmentEntity
    {
        $entity = new self();
        $entity->setKnowledgeCode($knowledgeCode);
        $entity->setVector(Json::encode($pointInfo->vector));
        $entity->setScore($pointInfo->score);

        $payload = $pointInfo->payload;

        $builtInfo = $payload[self::PAYLOAD_PREFIX . 'info'] ?? [];
        $content = $payload[self::PAYLOAD_PREFIX . 'content'] ?? '';
        $id = isset($builtInfo['id']) ? (int) $builtInfo['id'] : null;

        $entity->setPointId(md5($content));
        $entity->setId($id);
        $entity->setBusinessId($builtInfo['business_id'] ?? '');
        $entity->setContent($content);
        $entity->setCreator($builtInfo['creator'] ?? '');
        $entity->setCreatedAt(new DateTime($builtInfo['created_at'] ?? 'now'));
        $entity->setModifier($builtInfo['modifier'] ?? '');
        $entity->setUpdatedAt(new DateTime($builtInfo['updated_at'] ?? 'now'));
        unset($payload[self::PAYLOAD_PREFIX . 'info'], $payload[self::PAYLOAD_PREFIX . 'content']);
        $entity->setMetadata($payload);

        $entity->setSyncStatus(KnowledgeSyncStatus::Synced);

        return $entity;
    }

    public function getPayload(): array
    {
        $builtInfo = [
            'id' => (string) $this->id,
            'business_id' => $this->businessId,
            'document_code' => $this->documentCode,
            'version' => $this->version,
            'content' => $this->content,
            'creator' => $this->getCreator(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'modifier' => $this->getModifier(),
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        return array_merge($this->getMetadata(), [
            self::PAYLOAD_PREFIX . 'content' => $this->getContent(),
            self::PAYLOAD_PREFIX . 'info' => $builtInfo,
        ]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getKnowledgeCode(): string
    {
        return $this->knowledgeCode;
    }

    public function setKnowledgeCode(string $knowledgeCode): self
    {
        $this->knowledgeCode = $knowledgeCode;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getBusinessId(): string
    {
        return $this->businessId;
    }

    public function setBusinessId(string $businessId): self
    {
        $this->businessId = $businessId;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getPointId(): string
    {
        return $this->pointId;
    }

    public function setPointId(string $pointId): self
    {
        $this->pointId = $pointId;
        return $this;
    }

    public function getVector(): string
    {
        return $this->vector;
    }

    public function setVector(string $vector): self
    {
        $this->vector = $vector;
        return $this;
    }

    public function getSyncStatus(): KnowledgeSyncStatus
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(int|KnowledgeSyncStatus $syncStatus): self
    {
        is_int($syncStatus) && $syncStatus = KnowledgeSyncStatus::from($syncStatus);
        $this->syncStatus = $syncStatus;
        return $this;
    }

    public function getSyncTimes(): int
    {
        return $this->syncTimes;
    }

    public function setSyncTimes(int $syncTimes): self
    {
        $this->syncTimes = $syncTimes;
        return $this;
    }

    public function getSyncStatusMessage(): string
    {
        return $this->syncStatusMessage;
    }

    public function setSyncStatusMessage(string $syncStatusMessage): self
    {
        $this->syncStatusMessage = $syncStatusMessage;
        return $this;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime|string $createdAt): self
    {
        is_string($createdAt) && $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $createdAt);
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): self
    {
        $this->modifier = $modifier;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime|string $updatedAt): self
    {
        is_string($updatedAt) && $updatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $updatedAt);
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getDocumentCode(): string
    {
        return $this->documentCode;
    }

    public function setDocumentCode(string $documentCode): KnowledgeBaseFragmentEntity
    {
        $this->documentCode = $documentCode;
        return $this;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    // thiswithinnotusesetting，直接according tocontentcalculateoutcomethenline
    public function setWordCount(int $wordCount): KnowledgeBaseFragmentEntity
    {
        $this->wordCount = $wordCount;
        return $this;
    }

    /**
     * @param array<string> $fragmentContents
     * @return array<KnowledgeBaseFragmentEntity>
     */
    public static function fromFragmentContents(array $fragmentContents): array
    {
        $entities = [];
        $now = new DateTime();

        foreach ($fragmentContents as $content) {
            $entity = new self();
            $entity->setId(di(IdGeneratorInterface::class)->generate());
            $entity->setContent($content);
            $entity->setPointId(md5($content));
            $entity->setWordCount(mb_strlen($content));
            $entity->setKnowledgeCode('');
            $entity->setDocumentCode('');
            $entity->setBusinessId('');
            $entity->setCreator('');
            $entity->setCreatedAt($now);
            $entity->setModifier('');
            $entity->setUpdatedAt($now);
            $entity->setMetadata([]);
            $entity->setSyncStatus(KnowledgeSyncStatus::NotSynced);
            $entity->setSyncTimes(0);
            $entity->setSyncStatusMessage('');

            $entities[] = $entity;
        }

        return $entities;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;
        return $this;
    }

    private function checkMetadata(): self
    {
        foreach ($this->metadata as $key => $value) {
            if (Str::startsWith($key, self::PAYLOAD_PREFIX)) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'yuandata key cannotby ' . self::PAYLOAD_PREFIX . ' openhead');
            }
            if (! is_string($key)) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'yuandata  key mustisstring');
            }
            if (! is_string($value) && ! is_numeric($value)) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'yuandata  value 只canis stringornumber');
            }
        }
        return $this;
    }
}
