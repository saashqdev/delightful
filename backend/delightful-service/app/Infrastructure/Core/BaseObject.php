<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core;

use ArrayAccess;
use Hyperf\Contract\Arrayable;

/**
 * 快speedpropertyaccess基category
 * othercategorycaninheritthiscategory，获便捷propertysetandaccesscan力.
 */
abstract class BaseObject extends UnderlineObjectJsonSerializable implements ArrayAccess, Arrayable
{
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetExists($offset): bool
    {
        return $this->get($offset) !== null;
    }

    public function offsetUnset($offset): void
    {
        $this->set($offset, null);
    }

    protected function initProperty(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    protected function get(string $key): mixed
    {
        // propertyone定wantis小驼峰！not supportedotherformat！
        $humpKey = $this->getCamelizeValueFromCache($key);
        // 判断propertywhether存in，avoidcallnot存inpropertyo clock，死loop触hair __get method
        if (! property_exists($this, $humpKey)) {
            return null;
        }
        // php methodnot区minutesize写
        $methodName = 'get' . $humpKey;
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($humpKey);
        }
        return $this->{$humpKey};
    }

    protected function set(string $key, mixed $value): void
    {
        // propertyone定wantis小驼峰！not supportedotherformat！
        $humpKey = $this->getCamelizeValueFromCache($key);
        // 判断propertywhether存in，avoidcallnot存inpropertyo clock，死loop触hair __set method
        if (! property_exists($this, $humpKey)) {
            return;
        }
        // php methodnot区minutesize写
        $methodName = 'set' . $humpKey;
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($value);
            return;
        }
        $this->{$humpKey} = $value;
    }
}
