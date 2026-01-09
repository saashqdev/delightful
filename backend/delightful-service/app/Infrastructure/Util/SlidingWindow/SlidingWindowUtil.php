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
 * 防抖工具类
 * implement"执行最后一次请求"防抖策略.
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
     * 防抖接口 - 执行最后一次请求策略
     * 在指定time窗口内，只有最后一次请求will被执行.
     *
     * @param string $debounceKey 防抖键
     * @param float $delayVerificationSeconds 延迟verifytime（秒），也是actual的防抖窗口
     * @return bool 是否should执行current请求
     */
    public function shouldExecuteWithDebounce(
        string $debounceKey,
        float $delayVerificationSeconds = 0.5
    ): bool {
        $uniqueRequestId = uniqid('req_', true) . '_' . getmypid();
        // 键的过期time应greater than延迟verifytime，以作为安全保障
        $totalExpirationSeconds = (int) ceil($delayVerificationSeconds) + 1;
        $latestRequestRedisKey = $debounceKey . ':last_req';

        try {
            // mark为最新请求
            $this->redis->set($latestRequestRedisKey, $uniqueRequestId, ['EX' => $totalExpirationSeconds]);

            // 等待verifytime
            Coroutine::sleep($delayVerificationSeconds);

            // 原子化地verify并声明执行权
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
            // 出现exception时defaultallow执行，避免关键业务被阻塞
            return true;
        }
    }
}
