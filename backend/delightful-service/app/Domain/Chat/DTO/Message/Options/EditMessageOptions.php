<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Options;

use App\Domain\Chat\Entity\AbstractEntity;

class EditMessageOptions extends AbstractEntity
{
    // 被edit的messageid，用于关联message的多个version
    protected string $delightfulMessageId;

    // version numberid，send方不用填写，服务端自动generate
    protected ?string $messageVersionId;

    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    public function getDelightfulMessageId(): ?string
    {
        return $this->delightfulMessageId ?? null;
    }

    public function setDelightfulMessageId(?string $delightfulMessageId): static
    {
        isset($delightfulMessageId) && $this->delightfulMessageId = $delightfulMessageId;
        return $this;
    }

    public function getMessageVersionId(): ?string
    {
        return $this->messageVersionId ?? null;
    }

    public function setMessageVersionId(?string $messageVersionId): static
    {
        $this->messageVersionId = $messageVersionId;
        return $this;
    }
}
