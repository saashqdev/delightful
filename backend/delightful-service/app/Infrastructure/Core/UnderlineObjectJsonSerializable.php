<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core;

use App\Infrastructure\Core\Traits\DelightfulCacheTrait;
use DateTime;
use Hyperf\Codec\Exception\InvalidArgumentException;
use Hyperf\Codec\Json;
use Hyperf\Contract\Arrayable;
use JsonSerializable;
use Throwable;

abstract class UnderlineObjectJsonSerializable implements JsonSerializable, Arrayable
{
    use DelightfulCacheTrait;

    public function jsonSerialize(): array
    {
        $json = [];
        /* @phpstan-ignore-next-line */
        foreach ($this as $key => $value) {
            $key = $this->getUnCamelizeValueFromCache($key);
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $json[$key] = $value;
        }
        return $json;
    }

    /**
     * getcategoryproperty，notinclude动stateproperty.
     */
    public function toArray(): array
    {
        return Json::decode($this->toJsonString());
    }

    public function toJsonString(): string
    {
        // 避免call toArray methodcall本methodo clock，againcall hyperf  Json::encode method造become死loop
        try {
            $json = json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
        return $json;
    }
}
