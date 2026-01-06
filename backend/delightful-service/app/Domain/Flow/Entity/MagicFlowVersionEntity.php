<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity;

use App\Domain\Flow\Entity\ValueObject\Code;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;

class DelightfulFlowVersionEntity extends AbstractEntity
{
    protected ?int $id = null;

    protected string $flowCode;

    protected string $code;

    protected string $name;

    protected string $description = '';

    protected DelightfulFlowEntity $delightfulFlow;

    protected string $organizationCode;

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    public function prepareForCreation(): void
    {
        $this->requiredValidate();

        $this->code = Code::DelightfulFlowVersion->gen();
        $this->modifier = $this->creator;
        $this->updatedAt = $this->createdAt;

        $delightfulFlow = $this->delightfulFlow;
        $this->delightfulFlowPrepareForSave($delightfulFlow);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFlowCode(): string
    {
        return $this->flowCode;
    }

    public function setFlowCode(string $flowCode): void
    {
        $this->flowCode = $flowCode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDelightfulFlow(): DelightfulFlowEntity
    {
        return $this->delightfulFlow;
    }

    public function setDelightfulFlow(DelightfulFlowEntity $delightfulFlow): void
    {
        $this->delightfulFlow = $delightfulFlow;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): void
    {
        $this->modifier = $modifier;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    private function delightfulFlowPrepareForSave(DelightfulFlowEntity $delightfulFlow): void
    {
        $delightfulFlow->setCode($this->flowCode);
        $delightfulFlow->setOrganizationCode($this->organizationCode);
        $delightfulFlow->setCreator($this->creator);
        $delightfulFlow->setCreatedAt($this->createdAt);
        $delightfulFlow->setModifier($this->creator);
        $delightfulFlow->setUpdatedAt($this->createdAt);
    }

    private function requiredValidate(): void
    {
        if (empty($this->name)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.name.empty');
        }
        if (empty($this->flowCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.flow_code.empty');
        }
        if (empty($this->delightfulFlow)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.flow_entity.empty');
        }
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.organization_code.empty');
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.creator.empty');
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
    }
}
