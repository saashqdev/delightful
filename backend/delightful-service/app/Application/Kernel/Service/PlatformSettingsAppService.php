<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Service;

use App\Application\Contact\UserSetting\UserSettingKey;
use App\Application\Kernel\DTO\PlatformSettings;
use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Service\DelightfulUserSettingDomainService;
use Hyperf\Redis\Redis;

class PlatformSettingsAppService
{
    private const string CACHE_KEY = 'delightful:platform_settings_cache';

    public function __construct(
        private readonly DelightfulUserSettingDomainService $userSettingDomainService,
        private readonly Redis $redis,
    ) {
    }

    public function get(): PlatformSettings
    {
        $cache = $this->redis->get(self::CACHE_KEY);
        if ($cache) {
            $data = json_decode($cache, true) ?? [];
            return PlatformSettings::fromArray($data);
        }

        $entity = $this->userSettingDomainService->getGlobal(UserSettingKey::PlatformSettings->value);
        $settings = $entity ? PlatformSettings::fromArray($entity->getValue()) : new PlatformSettings();
        $this->redis->set(self::CACHE_KEY, json_encode($settings->toArray()));
        return $settings;
    }

    public function save(PlatformSettings $settings): PlatformSettings
    {
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::PlatformSettings->value);
        $entity->setValue($settings->toArray());
        $entity->setDelightfulId(null);
        $this->userSettingDomainService->saveGlobal($entity);
        $this->redis->del(self::CACHE_KEY);
        return $settings;
    }
}
