<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

use App\Domain\Chat\Entity\AbstractEntity;

class StreamOptions extends AbstractEntity
{
    protected ?StreamMessageStatus $status;

    protected bool $stream;

    // useatidentifierstreammessageassociateproperty。多segmentstreammessage stream_app_message_id same
    // ai searchcardmessage多segmentresponse，already经will app_message_id asforassociate id，streamresponseneed另outside id come做associate
    protected string $streamAppMessageId;

    /**
     * messageapplicationoption：0:覆盖 1：append（stringthensplice，arraytheninsert）.
     */
    protected MessageAppendOptions $append;

    /**
     * issuesearchendidentifier，useatfront端渲染endanimation。or者pushexceptioninfo。
     * @var StepFinishedDTO[]
     */
    protected array $stepsFinished;

    public function getStreamAppMessageId(): ?string
    {
        return $this->streamAppMessageId ?? null;
    }

    public function setStreamAppMessageId(?string $streamAppMessageId): static
    {
        $this->streamAppMessageId = $streamAppMessageId;
        return $this;
    }

    // messagewhetherisstreammessage
    public function isStream(): bool
    {
        return $this->stream ?? true;
    }

    public function setStream(bool $stream): static
    {
        $this->stream = $stream;
        return $this;
    }

    public function getStatus(): ?StreamMessageStatus
    {
        return $this->status ?? null;
    }

    public function setStatus(null|int|StreamMessageStatus|string $status): static
    {
        if (is_numeric($status)) {
            $this->status = StreamMessageStatus::from((int) $status);
        } elseif ($status instanceof StreamMessageStatus) {
            $this->status = $status;
        }
        return $this;
    }

    public function getAppend(): MessageAppendOptions
    {
        return $this->append ?? MessageAppendOptions::Append;
    }

    public function setAppend(int|MessageAppendOptions|string $append): void
    {
        if ($append instanceof MessageAppendOptions) {
            $this->append = $append;
        } else {
            $this->append = MessageAppendOptions::from((int) $append);
        }
    }

    /**
     * @return StepFinishedDTO[]
     */
    public function getStepsFinished(): array
    {
        return $this->stepsFinished;
    }

    /**
     * @param StepFinishedDTO[] $stepsFinished
     */
    public function setStepsFinished(array $stepsFinished): void
    {
        $this->stepsFinished = $stepsFinished;
    }
}
