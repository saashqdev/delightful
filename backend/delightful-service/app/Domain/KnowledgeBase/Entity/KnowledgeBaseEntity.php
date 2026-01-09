<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity;

use App\Domain\Flow\Entity\ValueObject\Code;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentConfig;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeType;
use App\Domain\KnowledgeBase\Entity\ValueObject\RetrieveConfig;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\EmbeddingGenerator;
use App\Infrastructure\Core\Embeddings\VectorStores\VectorStoreDriver;
use App\Infrastructure\Core\Embeddings\VectorStores\VectorStoreInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;

/**
 * knowledge base.
 */
class KnowledgeBaseEntity extends AbstractKnowledgeBaseEntity
{
    protected ?FragmentConfig $fragmentConfig = null;

    protected ?int $id = null;

    protected string $organizationCode;

    protected string $code;

    protected string $icon = '';

    protected int $version;

    protected string $name;

    protected string $description = '';

    protected int $type;

    protected bool $enabled;

    protected string $businessId = '';

    protected KnowledgeSyncStatus $syncStatus;

    protected string $syncStatusMessage = '';

    protected ?string $model = null;

    protected string $vectorDB;

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    /**
     * businessmaintainexpecttotal.
     */
    protected int $expectedNum = 0;

    /**
     * businessmaintainalreadycompletequantity.
     */
    protected int $completedNum = 0;

    /**
     * retrieveconfiguration.
     *
     * containretrievestrategy,retrievemethod,重sortconfigurationetcparameter
     */
    protected ?RetrieveConfig $retrieveConfig = null;

    /**
     * slicesegmentquantity.
     */
    protected int $fragmentCount = 0;

    /**
     * expectedslicesegmentquantity.
     */
    protected int $expectedCount = 0;

    /**
     * alreadycompleteslicesegmentquantity.
     */
    protected int $completedCount = 0;

    protected int $userOperation = 0;

    protected ?array $embeddingConfig = null;

    protected int $wordCount = 0;

    protected ?int $sourceType = null;

    private string $forceCreateCode = '';

    public function shouldCreate(): bool
    {
        if (! empty($this->forceCreateCode)) {
            return true;
        }
        return empty($this->code);
    }

    public function getCollectionName(): string
    {
        return $this->getCode() . '-' . $this->getVersion();
    }

    public function prepareForCreation(): void
    {
        if (empty($this->name)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'knowledge basename notcanforempty');
        }
        if (empty($this->type)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'knowledge basetype notcanforempty');
        }
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'organizationencoding notcanforempty');
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'create者 notcanforempty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        $this->checkModel();
        $this->checkVectorDB();

        if ($this->forceCreateCode) {
            $this->code = $this->forceCreateCode;
        } else {
            $this->code = Code::Knowledge->gen();
        }

        $this->version = 1;
        if (! isset($this->enabled)) {
            $this->enabled = false;
        }
        $this->syncStatus = KnowledgeSyncStatus::NotSynced;
        $this->modifier = $this->creator;
        $this->updatedAt = $this->createdAt;
    }

    public function prepareForModification(KnowledgeBaseEntity $delightfulFlowKnowledgeEntity): void
    {
        if (empty($this->name)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'knowledge basename notcanforempty');
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'create者 notcanforempty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        $this->checkModel();
        $this->checkVectorDB();

        $delightfulFlowKnowledgeEntity->setName($this->name);
        $delightfulFlowKnowledgeEntity->setDescription($this->description);
        $delightfulFlowKnowledgeEntity->setEnabled($this->enabled);
        $delightfulFlowKnowledgeEntity->setVectorDB($this->vectorDB);
        $delightfulFlowKnowledgeEntity->setModel($this->model);
        $delightfulFlowKnowledgeEntity->setModifier($this->creator);
        $delightfulFlowKnowledgeEntity->setUpdatedAt($this->createdAt);
        $delightfulFlowKnowledgeEntity->setIcon($this->icon);
        $delightfulFlowKnowledgeEntity->setFragmentConfig($this->fragmentConfig);
        $delightfulFlowKnowledgeEntity->setEmbeddingConfig($this->embeddingConfig);
        $delightfulFlowKnowledgeEntity->setRetrieveConfig($this->retrieveConfig);
        if (! empty($this->version)) {
            $delightfulFlowKnowledgeEntity->setVersion($this->version);
        }
    }

    public function prepareForModifyProcess(KnowledgeBaseEntity $delightfulFlowKnowledgeEntity): void
    {
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'create者 notcanforempty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
        if ($this->completedNum > $this->expectedNum) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'alreadycompletequantitynotcangreater thanexpectquantity');
        }
        $delightfulFlowKnowledgeEntity->setExpectedNum($this->expectedNum);
        $delightfulFlowKnowledgeEntity->setCompletedNum($this->completedNum);
        $delightfulFlowKnowledgeEntity->setModifier($this->creator);
        $delightfulFlowKnowledgeEntity->setUpdatedAt($this->createdAt);
    }

    public function getVectorDBDriver(): VectorStoreInterface
    {
        $driver = VectorStoreDriver::tryFrom($this->vectorDB);
        if ($driver === null) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "toquantitydatabase [{$this->vectorDB}] not存in");
        }
        return $driver->get();
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): static
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getSyncStatus(): KnowledgeSyncStatus
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(KnowledgeSyncStatus $syncStatus): static
    {
        $this->syncStatus = $syncStatus;
        return $this;
    }

    public function getSyncStatusMessage(): string
    {
        return $this->syncStatusMessage;
    }

    public function setSyncStatusMessage(string $syncStatusMessage): static
    {
        $this->syncStatusMessage = $syncStatusMessage;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->getEmbeddingConfig()['model_id'] ?? $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getVectorDB(): string
    {
        return $this->vectorDB;
    }

    public function setVectorDB(string $vectorDB): static
    {
        $this->vectorDB = $vectorDB;
        return $this;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): static
    {
        $this->modifier = $modifier;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getFragmentCount(): int
    {
        return $this->fragmentCount;
    }

    public function setFragmentCount(int $fragmentCount): static
    {
        $this->fragmentCount = $fragmentCount;
        return $this;
    }

    public function getExpectedCount(): int
    {
        return $this->expectedCount;
    }

    public function setExpectedCount(int $expectedCount): static
    {
        $this->expectedCount = $expectedCount;
        return $this;
    }

    public function getCompletedCount(): int
    {
        return $this->completedCount;
    }

    public function setCompletedCount(int $completedCount): static
    {
        $this->completedCount = $completedCount;
        return $this;
    }

    public function getUserOperation(): int
    {
        return $this->userOperation;
    }

    public function setUserOperation(int $userOperation): static
    {
        $this->userOperation = $userOperation;
        return $this;
    }

    public function getBusinessId(): string
    {
        return $this->businessId;
    }

    public function setBusinessId(string $businessId): void
    {
        $this->businessId = $businessId;
    }

    public function getExpectedNum(): int
    {
        return $this->expectedNum;
    }

    public function setExpectedNum(int $expectedNum): void
    {
        $this->expectedNum = $expectedNum;
    }

    public function getCompletedNum(): int
    {
        return $this->completedNum;
    }

    public function setCompletedNum(int $completedNum): void
    {
        $this->completedNum = $completedNum;
    }

    public function getForceCreateCode(): string
    {
        return $this->forceCreateCode;
    }

    public function setForceCreateCode(string $forceCreateCode): void
    {
        $this->forceCreateCode = $forceCreateCode;
    }

    public function getFragmentConfig(): ?FragmentConfig
    {
        return $this->fragmentConfig ?? $this->getDefaultFragmentConfig();
    }

    public function setFragmentConfig(null|array|FragmentConfig $fragmentConfig): self
    {
        // defaultconfiguration
        is_null($fragmentConfig) && $fragmentConfig = $this->getDefaultFragmentConfig();
        is_array($fragmentConfig) && $fragmentConfig = FragmentConfig::fromArray($fragmentConfig);
        $this->fragmentConfig = $fragmentConfig;
        return $this;
    }

    public function getEmbeddingConfig(): ?array
    {
        return $this->embeddingConfig;
    }

    public function setEmbeddingConfig(?array $embeddingConfig): self
    {
        isset($embeddingConfig['model_id']) && $this->model = $embeddingConfig['model_id'];
        // compatibleoldconfiguration,initializedefaultembeddingconfiguration
        is_null($embeddingConfig) && $embeddingConfig = ['model_id' => $this->model];
        $this->embeddingConfig = $embeddingConfig;
        return $this;
    }

    /**
     * getretrieveconfiguration.
     */
    public function getRetrieveConfig(): ?RetrieveConfig
    {
        return $this->retrieveConfig ?? RetrieveConfig::createDefault();
    }

    /**
     * setretrieveconfiguration.
     */
    public function setRetrieveConfig(null|array|RetrieveConfig $retrieveConfig): void
    {
        is_null($retrieveConfig) && $retrieveConfig = RetrieveConfig::createDefault();
        is_array($retrieveConfig) && $retrieveConfig = RetrieveConfig::fromArray($retrieveConfig);
        $this->retrieveConfig = $retrieveConfig;
    }

    /**
     * getorcreateretrieveconfiguration.
     *
     * ifretrieveconfigurationnot存in,thencreatedefaultconfiguration
     */
    public function getOrCreateRetrieveConfig(): RetrieveConfig
    {
        if ($this->retrieveConfig === null) {
            $this->retrieveConfig = RetrieveConfig::createDefault();
        }
        return $this->retrieveConfig;
    }

    public static function createCurrentTopicTemplate(string $organizationCode, string $creator): KnowledgeBaseEntity
    {
        $self = self::createTemplate($organizationCode, ConstValue::KNOWLEDGE_USER_CURRENT_TOPIC, $creator);
        $self->setName('currenttopic');
        $self->setDescription("{$creator} topic");
        $self->setType(KnowledgeType::UserTopic->value);
        return $self;
    }

    public static function createConversationTemplate(string $organizationCode, string $creator): KnowledgeBaseEntity
    {
        $self = self::createTemplate($organizationCode, ConstValue::KNOWLEDGE_USER_CURRENT_CONVERSATION, $creator);
        $self->setName('currentsession');
        $self->setDescription("{$creator} session");
        $self->setType(KnowledgeType::UserConversation->value);
        return $self;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): KnowledgeBaseEntity
    {
        $this->icon = $icon;
        return $this;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    public function getDefaultDocumentCode(): string
    {
        return $this->code . '-DEFAULT-DOC';
    }

    public function setWordCount(int $wordCount): void
    {
        $this->wordCount = $wordCount;
    }

    public function getSourceType(): ?int
    {
        return $this->sourceType;
    }

    public function setSourceType(?int $sourceType): static
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    private static function createTemplate(string $organizationCode, string $code, string $creator): KnowledgeBaseEntity
    {
        $self = new self();
        $self->setId(0);
        $self->setCode($code);
        $self->setEnabled(true);
        $self->setSyncStatus(KnowledgeSyncStatus::Synced);
        $self->setModel(EmbeddingGenerator::defaultModel());
        $self->setVectorDB(VectorStoreDriver::default()->value);
        $self->setOrganizationCode($organizationCode);
        $self->setCreator($creator);
        $self->setCreatedAt(new DateTime());
        $self->setModifier($creator);
        $self->setUpdatedAt(new DateTime());
        $self->setRetrieveConfig(RetrieveConfig::createDefault());
        return $self;
    }

    private function checkModel(): void
    {
        if (empty($this->model)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'model notcanforempty');
        }
    }

    private function checkVectorDB(): void
    {
        $this->getVectorDBDriver();
    }
}
