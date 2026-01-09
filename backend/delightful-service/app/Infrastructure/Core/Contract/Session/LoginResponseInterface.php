<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Session;

use App\Domain\Contact\Entity\ValueObject\PlatformType;

interface LoginResponseInterface
{
    public function getDelightfulId(): string;

    public function setDelightfulId(string $delightfulId): self;

    public function getDelightfulUserId(): string;

    public function setDelightfulUserId(string $delightfulUserId): self;

    public function getDelightfulOrganizationCode(): string;

    public function setDelightfulOrganizationCode(string $delightfulOrganizationCode): self;

    public function getThirdPlatformOrganizationCode(): string;

    public function setThirdPlatformOrganizationCode(string $thirdPlatformOrganizationCode): self;

    public function getThirdPlatformUserId(): string;

    public function setThirdPlatformUserId(string $thirdPlatformUserId): self;

    public function getThirdPlatformType(): PlatformType;

    public function setThirdPlatformType(null|PlatformType|string $thirdPlatformType): self;

    /**
     * 转换为arrayformat.
     *
     * @return array<string, mixed> contain所有property的array
     */
    public function toArray(): array;
}
