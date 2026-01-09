<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\MiracleVision;

use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractEntity;

class MiracleVisionModelResponse extends AbstractEntity
{
    // completestatus
    protected bool $finishStatus = false;

    // image
    protected array $urls = [];

    // 进degreeitem
    protected float $progress = 0;

    protected string $error = '';

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function isFinishStatus(): bool
    {
        return $this->finishStatus;
    }

    public function setFinishStatus(bool $finishStatus): void
    {
        $this->finishStatus = $finishStatus;
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function setUrls(array $urls): void
    {
        $this->urls = $urls;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function setProgress(float $progress): void
    {
        $this->progress = $progress;
    }
}
