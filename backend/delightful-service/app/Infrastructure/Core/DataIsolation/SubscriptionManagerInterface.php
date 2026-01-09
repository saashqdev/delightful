<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\DataIsolation;

use App\Domain\Provider\Entity\ValueObject\ModelType;

interface SubscriptionManagerInterface
{
    /**
     * 是否启用订阅功能.
     */
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function setCurrentSubscription(string $subscriptionId, array $subscriptionInfo, array $modelIdsGroupByType = []): void;

    /**
     * get当前订阅ID.
     */
    public function getCurrentSubscriptionId(): string;

    /**
     * get当前订阅information.
     */
    public function getCurrentSubscriptionInfo(): array;

    /**
     * get当前可用的模型ID列表, 如果returnnull表示不限制.
     *
     * @return null|array<string>
     */
    public function getAvailableModelIds(?ModelType $modelType): ?array;

    public function isValidModelAvailable(string $modelId, ?ModelType $modelType): bool;

    public function isPaidSubscription(): bool;

    public function toArray(): array;
}
