<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core;

use DateTime;

class Operator extends AbstractValueObject
{
    protected string $uid;

    protected string $name;

    protected DateTime $time;

    public function __construct(?string $uid = '', ?string $name = '', ?DateTime $time = null)
    {
        $this->uid = (string) $uid;
        $this->name = $name;
        $this->time = $time ?: new DateTime('now');
    }

    /**
     * get操作者ID.
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * get操作者name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * get操作time.
     */
    public function getTime(): DateTime
    {
        return $this->time;
    }

    /**
     * setting操作者ID.
     */
    public function setUid(string $uid): self
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * setting操作者name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * setting操作time.
     */
    public function setTime(DateTime $time): self
    {
        $this->time = $time;
        return $this;
    }

    /**
     * createsystemuser.
     */
    public static function createSystemUser(): self
    {
        return new self('100', 'SYSTEM');
    }

    /**
     * create单yuantestuser.
     */
    public static function createUnitUser(): self
    {
        return new self('unit_100', 'UNIT');
    }
}
