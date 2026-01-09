<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Event\Subscribe;

use Hyperf\Context\ApplicationContext;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swow\Coroutine;
use Swow\Watchdog;
use WeakMap;

readonly class DelightfulWatchDogSubscriber implements ListenerInterface
{
    public function __construct()
    {
    }

    public function listen(): array
    {
        return [
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(object $event): void
    {
        if ((bool) env('ENABLE_DELIGHTFUL_WATCHDOG', true) !== true) {
            return;
        }
        $quantum = 10 * 1000 * 1000; // unit：毫秒
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get('DelightfulWatchDogSubscriber');
        // 看门狗找同阻塞的place
        $logger->info('麦吉看门狗，start！');
        $alertCountMap = new WeakMap();
        Watchdog::run($quantum * 5, 0, static function () use (&$alertCountMap, $logger) {
            $coroutine = Coroutine::getCurrent();
            $alertCount = ($alertCountMap[$coroutine] ??= 0) + 1;
            $alertCountMap[$coroutine] = $alertCount;
            // when单个协程运行超过 $millSeconds 时，will触发看门狗，打印协程call栈
            if ($alertCount > 1) {
                $trace = str_replace(["\n", "\r"], ' | ', $coroutine->getTraceAsString());
                $logger->error(sprintf(
                    '麦吉看门狗 发现阻塞 协程 id:%s，同个协程阻塞count：%s trace :%s ',
                    $coroutine->getId(),
                    $alertCount,
                    $trace
                ));
            }
            // 让出time片，让其他协程have机willexecute
            $millSeconds = 10 * 1000; // 10 毫秒
            usleep($millSeconds * $alertCount);
        });
    }
}
