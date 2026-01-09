<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authentication\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Interfaces\Kernel\DTO\Traits\OperatorDTOTrait;
use App\Interfaces\Kernel\DTO\Traits\StringIdDTOTrait;

class ApiKeyProviderDTO extends AbstractDTO
{
    use OperatorDTOTrait;
    use StringIdDTOTrait;

    /**
     * organizationcode.
     */
    protected string $organizationCode = '';

    /**
     * associatecode.
     */
    protected string $relCode = '';

    /**
     * associatetype.
     */
    protected int $relType = 0;

    /**
     * APIkeyname.
     */
    protected string $name = '';

    /**
     * APIkeydescription.
     */
    protected string $description = '';

    /**
     * key.
     */
    protected string $secretKey = '';

    /**
     * conversationID.
     */
    protected string $conversationId = '';

    /**
     * 是否enable.
     */
    protected bool $enabled = true;

    /**
     * 最后usetime.
     */
    protected ?string $lastUsed = null;

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getRelCode(): string
    {
        return $this->relCode;
    }

    public function setRelCode(string $relCode): void
    {
        $this->relCode = $relCode;
    }

    public function getRelType(): int
    {
        return $this->relType;
    }

    public function setRelType(int $relType): void
    {
        $this->relType = $relType;
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

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    public function setConversationId(string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getLastUsed(): ?string
    {
        return $this->lastUsed;
    }

    public function setLastUsed(mixed $lastUsed): void
    {
        $this->lastUsed = $this->createDateTimeString($lastUsed);
    }
}
