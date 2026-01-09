<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Traits;

use Hyperf\Cache\Driver\DriverInterface;
use Hyperf\Cache\Driver\MemoryDriver;
use Hyperf\Context\ApplicationContext;
use Throwable;

trait DelightfulCacheTrait
{
    /**
     * cacheobjectpropertydown划lineand驼峰命名,avoid频繁calculate.
     */
    protected static ?DriverInterface $propertyCacheDriver = null;

    /**
     * getcachepoolinstance.
     */
    protected function getDriver(): DriverInterface
    {
        if (! self::$propertyCacheDriver instanceof DriverInterface) {
            self::$propertyCacheDriver = new MemoryDriver(ApplicationContext::getContainer(), [
                'prefix' => 'delightful-field-camelize:',
                'skip_cache_results' => [null, '', []],
                // 128M
                'size' => 128 * 1024 * 1024,
                'throw_when_size_exceeded' => true,
            ], );
        }
        return self::$propertyCacheDriver;
    }

    /**
     * categorypropertyinframework运lineo clockisnot变,所bythiswithinusecache,avoidduplicatecalculate.
     * ifhasContaineris false,theninstructionnothaveusecontainer,notquerycache.
     */
    protected function getUnCamelizeValueFromCache(string $key): string
    {
        $cacheDriver = $this->getDriver();
        $cacheKey = 'function_un_camelize_' . $key;
        try {
            $value = $cacheDriver->get($cacheKey);
            if ($value) {
                return $value;
            }

            $value = un_camelize($key);
            $cacheDriver->set($cacheKey, $value);
            return $value;
        } catch (Throwable $exception) {
            echo 'error:getCamelizeValueFromCache:' . $exception->getMessage();
            return un_camelize($key);
        }
    }

    /**
     * categorypropertyinframework运lineo clockisnot变,所bythiswithinusecache,avoidduplicatecalculate.
     */
    protected function getCamelizeValueFromCache(string $key): string
    {
        $cacheDriver = $this->getDriver();
        $cacheKey = 'function_camelize_' . $key;
        try {
            $value = $cacheDriver->get($cacheKey);
            if ($value) {
                return $value;
            }
            $value = camelize($key);
            $cacheDriver->set($cacheKey, $value);
            return $value;
        } catch (Throwable $exception) {
            echo 'error:getCamelizeValueFromCache:' . $exception->getMessage();
            return camelize($key);
        }
    }
}
