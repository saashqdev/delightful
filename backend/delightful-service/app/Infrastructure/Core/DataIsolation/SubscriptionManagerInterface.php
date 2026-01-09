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
     * 是否启用subscribefeature.
     */
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function setCurrentSubscription(string $subscriptionId, array $subscriptionInfo, array $modelIdsGroupByType = []): void;

    /**
     * getwhen前subscribeID.
     */
    public function getCurrentSubscriptionId(): string;

    /**
     * getwhen前subscribeinformation.
     */
    public function getCurrentSubscriptionInfo(): array;

    /**
     * getwhen前可用的modelID列表, ifreturnnull表示不限制.
     *
     * @return null|array<string>
     */
    public function getAvailableModelIds(?ModelType $modelType): ?array;

    public function isValidModelAvailable(string $modelId, ?ModelType $modelType): bool;

    public function isPaidSubscription(): bool;

    public function toArray(): array;
}
