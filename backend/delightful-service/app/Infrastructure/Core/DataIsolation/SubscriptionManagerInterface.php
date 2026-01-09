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
     * whetherenablesubscribefeature.
     */
    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function setCurrentSubscription(string $subscriptionId, array $subscriptionInfo, array $modelIdsGroupByType = []): void;

    /**
     * getwhenfrontsubscribeID.
     */
    public function getCurrentSubscriptionId(): string;

    /**
     * getwhenfrontsubscribeinformation.
     */
    public function getCurrentSubscriptionInfo(): array;

    /**
     * getwhenfrontcanusemodelIDcolumn表, ifreturnnullindicatenotlimit.
     *
     * @return null|array<string>
     */
    public function getAvailableModelIds(?ModelType $modelType): ?array;

    public function isValidModelAvailable(string $modelId, ?ModelType $modelType): bool;

    public function isPaidSubscription(): bool;

    public function toArray(): array;
}
