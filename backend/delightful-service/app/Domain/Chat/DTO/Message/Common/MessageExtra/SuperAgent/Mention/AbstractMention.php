<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

use App\Infrastructure\Core\AbstractDTO;

abstract class AbstractMention extends AbstractDTO implements MentionInterface
{
    /**
     * mention object固定类型.
     */
    protected string $type = 'mention';

    protected MentionAttrs $attrs;

    public function __construct(?array $mention)
    {
        parent::__construct($mention);
    }

    public function setAttrs(array|MentionAttrs $attrs): void
    {
        if ($attrs instanceof MentionAttrs) {
            $this->attrs = $attrs;
        } else {
            $this->attrs = new MentionAttrs($attrs);
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAttrs(): ?MentionAttrs
    {
        return $this->attrs ?? null;
    }

    abstract public function getMentionTextStruct(): string;

    abstract public function getMentionJsonStruct(): array;
}
