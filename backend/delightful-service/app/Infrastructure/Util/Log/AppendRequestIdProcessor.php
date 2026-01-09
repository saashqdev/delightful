<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Log;

use App\Infrastructure\Util\Context\CoContext;
use Hyperf\Engine\Coroutine;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

class AppendRequestIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        try {
            $coroutineId = Coroutine::id();
        } catch (Throwable $e) {
            // when协程context未initializeo clock，return -1 asfor标识
            $coroutineId = -1;
        }

        try {
            $requestId = CoContext::getOrSetRequestId();
            $traceId = CoContext::getTraceId();
        } catch (Throwable $e) {
            // whencontextnotcanuseo clock，usenullvalue
            $requestId = '';
            $traceId = '';
        }

        $context['system_info'] = [
            'request_id' => $requestId,
            'coroutine_id' => $coroutineId,
            'trace_id' => $traceId,
        ];

        return $record->with(context: $context);
    }
}
