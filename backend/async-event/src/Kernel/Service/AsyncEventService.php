<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Service;

use BeDelightful\AsyncEvent\Kernel\Constants\Status;
use BeDelightful\AsyncEvent\Kernel\Persistence\AsyncEventRepository;
use BeDelightful\AsyncEvent\Kernel\Persistence\Model\AsyncEventModel;
use Hyperf\Snowflake\IdGeneratorInterface;

class AsyncEventService
{
    private AsyncEventRepository $asyncEventRepository;

    private IdGeneratorInterface $generator;

    public function __construct(AsyncEventRepository $asyncEventRepository, IdGeneratorInterface $generator)
    {
        $this->asyncEventRepository = $asyncEventRepository;
        $this->generator = $generator;
    }

    public function create(array $data): AsyncEventModel
    {
        return $this->asyncEventRepository->create($data);
    }

    public function buildAsyncEventData(string $eventClassName, string $listenerClassName, object $event): array
    {
        $now = date('Y-m-d H:i:s');
        return [
            'id' => $this->generator->generate(),
            'event' => $eventClassName,
            'listener' => $listenerClassName,
            'status' => Status::STATE_WAIT,
            'args' => serialize($event),
            'retry_times' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function exists(int $recordId): bool
    {
        return $this->asyncEventRepository->exists($recordId);
    }

    public function getById(int $recordId): ?AsyncEventModel
    {
        return $this->asyncEventRepository->getById($recordId);
    }

    public function markAsExecuting(int $recordId): void
    {
        $this->asyncEventRepository->updateById($recordId, [
            'status' => Status::STATE_IN_EXECUTION,
        ]);
    }

    public function complete(int $recordId)
    {
        $this->asyncEventRepository->updateById($recordId, [
            'status' => Status::STATE_COMPLETE,
        ]);
    }

    public function retry(int $recordId)
    {
        $this->asyncEventRepository->retryById($recordId);
    }

    public function fail(int $recordId)
    {
        $this->asyncEventRepository->updateById($recordId, [
            'status' => Status::STATE_EXCEEDED,
        ]);
    }

    public function delete(int $recordId): int
    {
        return $this->asyncEventRepository->deleteById($recordId);
    }

    public function clearHistory(): void
    {
        // Clear successfully consumed messages and transaction data from 1 day ago
        $this->clearSuccessHistoryRecord();
        // Clear messages and transaction data from 30 days ago (regardless of success)
        $this->clearAllHistoryRecord();
    }

    public function getTimeoutRecordIds(string $datetime): array
    {
        return $this->asyncEventRepository->getTimeoutRecordIds($datetime);
    }

    private function clearSuccessHistoryRecord(): void
    {
        $time = time() - 86400;
        $date = date('Y-m-d H:i:s', $time);
        $this->asyncEventRepository->deleteHistory([
            ['updated_at', '<=', $date],
            ['status', '=', Status::STATE_COMPLETE],
        ]);
    }

    private function clearAllHistoryRecord()
    {
        $time = time() - (86400 * 30);
        $date = date('Y-m-d H:i:s', $time);
        $this->asyncEventRepository->deleteHistory([
            ['updated_at', '<=', $date],
        ]);
    }
}
