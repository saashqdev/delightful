<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\SlidingWindow;

use Hyperf\Coroutine\Coroutine;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 防抖toolcategory
 * implement"executemostbackonetimerequest"防抖strategy.
 */
class SlidingWindowUtil
{
    protected LoggerInterface $logger;

    public function __construct(
        protected Redis $redis
    ) {
        $this->logger = di(LoggerFactory::class)->get(get_class($this));
    }

    /**
     * 防抖interface - executemostbackonetimerequeststrategy
     * infinger定timewindowinside，onlymostbackonetimerequestwillbeexecute.
     *
     * @param string $debounceKey 防抖key
     * @param float $delayVerificationSeconds delayverifytime（second），alsoisactual防抖window
     * @return bool whethershouldexecutecurrentrequest
     */
    public function shouldExecuteWithDebounce(
        string $debounceKey,
        float $delayVerificationSeconds = 0.5
    ): bool {
        $uniqueRequestId = uniqid('req_', true) . '_' . getmypid();
        // keyexpiretime应greater thandelayverifytime，byasforsecurity保障
        $totalExpirationSeconds = (int) ceil($delayVerificationSeconds) + 1;
        $latestRequestRedisKey = $debounceKey . ':last_req';

        try {
            // markformostnewrequest
            $this->redis->set($latestRequestRedisKey, $uniqueRequestId, ['EX' => $totalExpirationSeconds]);

            // etc待verifytime
            Coroutine::sleep($delayVerificationSeconds);

            // 原子化groundverifyandstatementexecute权
            $script = <<<'LUA'
                if redis.call('get', KEYS[1]) == ARGV[1] then
                    return redis.call('del', KEYS[1])
                else
                    return 0
                end
LUA;
            $result = $this->redis->eval($script, [$latestRequestRedisKey, $uniqueRequestId], 1);
            return (int) $result === 1;
        } catch (Throwable $exception) {
            $this->logger->error('Debounce check failed: ' . $exception->getMessage(), [
                'debounce_key' => $debounceKey,
                'exception' => $exception,
            ]);
            // out现exceptiono clockdefaultallowexecute，avoidclosekeybusinessbe阻塞
            return true;
        }
    }
}
