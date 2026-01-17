<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Event;

class CancelShareAfterEvent
{
    public function __construct(
        private string $organizationCode,
        private string $userId,
        private string $resourceId,
    ) {
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }
}
