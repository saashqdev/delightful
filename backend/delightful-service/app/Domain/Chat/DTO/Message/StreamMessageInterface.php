<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use Hyperf\Contract\Arrayable;
use JsonSerializable;

/**
 * streampush大模型的响应message.
 */
interface StreamMessageInterface extends JsonSerializable, Arrayable
{
    // message是否是streammessage
    public function isStream(): bool;

    public function getStreamOptions(): ?StreamOptions;

    public function setStreamOptions(null|array|StreamOptions $streamOptions): static;
}
