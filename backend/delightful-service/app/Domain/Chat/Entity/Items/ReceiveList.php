<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\Items;

use App\Infrastructure\Core\UnderlineObjectJsonSerializable;

class ReceiveList extends UnderlineObjectJsonSerializable
{
    /**
     * not读columntable.
     */
    protected array $unreadList = [];

    /**
     * already读columntable.
     */
    protected array $seenList = [];

    /**
     * alreadyviewdetailcolumntable.
     */
    protected array $readList = [];

    public function getUnreadList(): array
    {
        return $this->unreadList;
    }

    public function setUnreadList(array $unreadList): void
    {
        $this->unreadList = $unreadList;
    }

    public function getSeenList(): array
    {
        return $this->seenList;
    }

    public function setSeenList(array $seenList): void
    {
        $this->seenList = $seenList;
    }

    public function getReadList(): array
    {
        return $this->readList;
    }

    public function setReadList(array $readList): void
    {
        $this->readList = $readList;
    }
}
