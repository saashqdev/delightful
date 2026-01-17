<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Domain\Chat\Entity\AbstractEntity;

/**
 * Tool entity class.
 */
class BeAgentTool extends AbstractEntity
{
    /**
     * Tool ID.
     */
    protected ?string $id = null;

    /**
     * Tool name.
     */
    protected ?string $name = null;

    /**
     * Tool action.
     */
    protected ?string $action = null;

    /**
     * Tool status.
     */
    protected ?string $status = null;

    /**
     * Tool remark.
     */
    protected ?string $remark = null;

    /**
     * Tool detail.
     */
    protected ?array $detail = null;

    /**
     * Tool attachments.
     */
    protected ?array $attachments = null;

    public function __construct(array $tool)
    {
        parent::__construct($tool);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getDetail(): ?array
    {
        return $this->detail;
    }

    public function setDetail(?array $detail): void
    {
        $this->detail = $detail;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'action' => $this->action,
            'status' => $this->status,
            'remark' => $this->remark,
            'detail' => $this->detail,
            'attachments' => $this->attachments,
        ];
    }
}
