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

    // 用于标识streammessage的关联性。多段streammessage的 stream_app_message_id same
    // ai searchcardmessage的多段响应，已经将 app_message_id 作为关联 id，stream响应need另外的 id 来做关联
    protected string $streamAppMessageId;

    /**
     * message应用选项：0:覆盖 1：追加（string就拼接，array就插入）.
     */
    protected MessageAppendOptions $append;

    /**
     * issuesearch结束的标识，用于前端渲染结束动画。或者pushexceptioninfo。
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

    // message是否是streammessage
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
