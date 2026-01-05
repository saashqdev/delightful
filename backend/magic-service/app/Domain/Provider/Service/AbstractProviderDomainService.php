<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;

abstract class AbstractProviderDomainService
{
    /**
     * Process config values that may be masked.
     * If a value looks masked (first 3 chars + asterisks + last 3 chars), fall back to the old raw value; otherwise keep the new value.
     *
     * @param ProviderConfigItem $newConfig New config data (may contain masked values)
     * @param ProviderConfigItem $oldConfig Old config data (contains original values)
     * @return ProviderConfigItem Config data after unmasking
     */
    protected function processDesensitizedConfig(
        ProviderConfigItem $newConfig,
        ProviderConfigItem $oldConfig
    ): ProviderConfigItem {
        // Check if ak is in masked format
        $ak = $newConfig->getAk();
        if (! empty($ak) && preg_match('/^.{3}\*+.{3}$/', $ak)) {
            $newConfig->setAk($oldConfig->getAk());
        }

        // Check if sk is in masked format
        $sk = $newConfig->getSk();
        if (! empty($sk) && preg_match('/^.{3}\*+.{3}$/', $sk)) {
            $newConfig->setSk($oldConfig->getSk());
        }

        // Check if apiKey is in masked format
        $apiKey = $newConfig->getApiKey();
        if (! empty($apiKey) && preg_match('/^.{3}\*+.{3}$/', $apiKey)) {
            $newConfig->setApiKey($oldConfig->getApiKey());
        }

        return $newConfig;
    }
}
