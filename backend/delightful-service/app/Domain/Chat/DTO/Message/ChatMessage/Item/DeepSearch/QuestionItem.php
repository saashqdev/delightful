<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch;

use App\Infrastructure\Core\AbstractObject;

class QuestionItem extends AbstractObject
{
    /**
     * @var null|string bysomeissue引申outissue。if parentQuestionId fornull(0)，thenindicatetheassociateissueisbyuserinputissue产生。
     */
    protected ?string $parentQuestionId = null;

    /**
     * issue id.
     */
    protected string $questionId;

    /**
     * issuecontent.
     */
    protected string $question;

    public function getParentQuestionId(): ?string
    {
        return $this->parentQuestionId;
    }

    public function setParentQuestionId(?string $parentQuestionId): void
    {
        $this->parentQuestionId = $parentQuestionId;
    }

    public function getQuestionId(): string
    {
        return $this->questionId;
    }

    public function setQuestionId(string $questionId): void
    {
        $this->questionId = $questionId;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }
}
