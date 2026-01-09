<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\IdGenerator;

use Hyperf\Context\ApplicationContext;
use Hyperf\Snowflake\IdGeneratorInterface;

class IdGenerator
{
    public static function getSnowId(): int
    {
        return ApplicationContext::getContainer()->get(IdGeneratorInterface::class)->generate();
    }

    public static function getDelightfulOrganizationCode(): string
    {
        return self::getUniqueId32();
    }

    /**
     * generate固定length(32位)的string,尽力保证唯一property.
     */
    public static function getUniqueId32(): string
    {
        $bin2hex = bin2hex(random_bytes(64));
        return md5(microtime() . $bin2hex);
    }

    /**
     * generate固定length的string,尽力保证唯一property.
     */
    public static function getUniqueIdSha256(): string
    {
        $bin2hex = bin2hex(random_bytes(64));
        return hash('sha256', microtime() . $bin2hex);
    }
}
