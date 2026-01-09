<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core;

use ArrayAccess;
use Hyperf\Contract\Arrayable;

/**
 * fastspeedpropertyaccessbasecategory
 * othercategorycaninheritthiscategory,convenient accesspropertysetandaccesscancapability.
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
        // propertyonesetwantissmallcamel case!not supportedotherformat!
        $humpKey = $this->getCamelizeValueFromCache($key);
        // judgepropertywhetherexistsin,avoidcallnotexistsinpropertyo clock,deadlooptouchhair __get method
        if (! property_exists($this, $humpKey)) {
            return null;
        }
        // php methodnotregionminutesizewrite
        $methodName = 'get' . $humpKey;
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($humpKey);
        }
        return $this->{$humpKey};
    }

    protected function set(string $key, mixed $value): void
    {
        // propertyonesetwantissmallcamel case!not supportedotherformat!
        $humpKey = $this->getCamelizeValueFromCache($key);
        // judgepropertywhetherexistsin,avoidcallnotexistsinpropertyo clock,deadlooptouchhair __set method
        if (! property_exists($this, $humpKey)) {
            return;
        }
        // php methodnotregionminutesizewrite
        $methodName = 'set' . $humpKey;
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($value);
            return;
        }
        $this->{$humpKey} = $value;
    }
}
