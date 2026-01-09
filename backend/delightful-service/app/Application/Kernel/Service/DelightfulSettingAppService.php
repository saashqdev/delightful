<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Service;

use App\Application\Contact\UserSetting\UserSettingKey;
use App\Application\Kernel\AbstractKernelAppService;
use App\Application\Kernel\DTO\GlobalConfig;
use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Service\DelightfulUserSettingDomainService;
use Hyperf\Redis\Redis;

class DelightfulSettingAppService extends AbstractKernelAppService
{
    private const string CACHE_KEY = 'delightful:global_config_cache';

    public function __construct(
        private readonly DelightfulUserSettingDomainService $delightfulUserSettingDomainService,
        private readonly Redis $redis,
    ) {
    }

    /**
     * saveall局configuration
     * all局configurationnot属atany账number、organizationoruser.
     */
    public function save(GlobalConfig $config): GlobalConfig
    {
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::GlobalConfig->value);
        $entity->setValue($config->toArray());

        $this->delightfulUserSettingDomainService->saveGlobal($entity);

        // resetcache
        $this->redis->del(self::CACHE_KEY);

        return $config;
    }

    /**
     * getall局configuration.
     */
    public function get(): GlobalConfig
    {
        $cache = $this->redis->get(self::CACHE_KEY);
        if ($cache) {
            $data = json_decode($cache, true) ?? [];
            return GlobalConfig::fromArray($data);
        }

        $entity = $this->delightfulUserSettingDomainService->getGlobal(UserSettingKey::GlobalConfig->value);
        $config = $entity ? GlobalConfig::fromArray($entity->getValue()) : new GlobalConfig();

        $this->redis->set(self::CACHE_KEY, json_encode($config->toArray()));

        return $config;
    }
}
