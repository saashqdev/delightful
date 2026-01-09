<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\Service\DelightfulSeqDomainService;
use Throwable;

/**
 * chatmessage相关.
 */
class DelightfulSeqAppService extends AbstractAppService
{
    public function __construct(protected DelightfulSeqDomainService $delightfulSeqDomainService)
    {
    }

    /**
     * messagepush
     * @throws Throwable
     */
    public function pushSeq(string $seqId): void
    {
        $this->delightfulSeqDomainService->pushSeq($seqId);
    }
}
