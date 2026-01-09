<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch;

use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageTrait;
use App\Domain\Chat\DTO\Message\Trait\LLMMessageTrait;
use App\Infrastructure\Core\AbstractObject;

/**
 * @property string $questionId issue id
 * @property string $content 总结content
 * @property string $reasoningContent 思考process
 */
class SummaryItem extends AbstractObject
{
    use LLMMessageTrait;
    use StreamMessageTrait;

    /**
     * issue id.
     */
    protected string $questionId;

    public function getQuestionId(): string
    {
        return $this->questionId;
    }

    public function setQuestionId(string $questionId): void
    {
        $this->questionId = $questionId;
    }
}
