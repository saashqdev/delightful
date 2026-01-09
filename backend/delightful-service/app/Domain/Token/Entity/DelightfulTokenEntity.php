<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Entity;

use App\Domain\Token\Entity\ValueObject\DelightfulTokenType;
use App\Domain\Token\Repository\Facade\DelightfulTokenExtraInterface;
use Hyperf\Codec\Json;

class DelightfulTokenEntity extends AbstractEntity
{
    protected int $id;

    protected DelightfulTokenType $type;

    protected string $typeRelationValue;

    protected string $token;

    protected string $createdAt;

    protected string $updatedAt;

    protected string $expiredAt;

    protected ?DelightfulTokenExtraInterface $extra = null;

    // ifthethird-partyplatform toke toolong,then搞shortpoint,convenientatstorageandquery
    public function getDelightfulShortToken(string $longToken): string
    {
        if (strlen($longToken) > 128) {
            return hash('sha256', $longToken);
        }
        return $longToken;
    }

    public function getExtra(): ?DelightfulTokenExtraInterface
    {
        return $this->extra;
    }

    public function setExtra(null|array|DelightfulTokenExtraInterface|string $extra): void
    {
        if (is_string($extra) && $extra !== '') {
            $extra = Json::decode($extra);
        }
        if (empty($extra)) {
            $extra = null;
        } elseif (is_array($extra)) {
            $extra = make(DelightfulTokenExtraInterface::class)->setTokenExtraData($extra);
        }
        $this->extra = $extra;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getType(): DelightfulTokenType
    {
        return $this->type;
    }

    public function setType(int|DelightfulTokenType $type): void
    {
        if (is_int($type)) {
            $this->type = DelightfulTokenType::from($type);
        } else {
            $this->type = $type;
        }
    }

    public function getTypeRelationValue(): string
    {
        return $this->typeRelationValue;
    }

    public function setTypeRelationValue(string $typeRelationValue): void
    {
        $this->typeRelationValue = $typeRelationValue;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getExpiredAt(): string
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(string $expiredAt): void
    {
        $this->expiredAt = $expiredAt;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }
}
