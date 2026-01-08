<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace PHPSandbox\Runtime\Proxy;

use PHPSandbox\Options\SandboxOptions;

/**
 * Extracted from original PHPSandbox built-in methods.
 * @internal
 */
class DelightfulConstants implements RuntimeProxyInterface
{
    /**
     * @static
     * @var array A static array of delightful constant names used for redefining delightful constant values
     */
    public static array $delightful_constants = [
        '__LINE__',
        '__FILE__',
        '__DIR__',
        '__FUNCTION__',
        '__CLASS__',
        '__TRAIT__',
        '__METHOD__',
        '__NAMESPACE__',
    ];

    protected SandboxOptions $options;

    public function setOptions(SandboxOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    /** Get PHPSandbox redefined delightful constant. This is an internal PHPSandbox function but requires public access to work.
     *
     * @param string $name Requested delightful constant name (e.g. __FILE__, __LINE__, etc.)
     *
     * @return mixed Returns the redefined delightful constant
     */
    public function _get_delightful_const(string $name)
    {
        if ($this->options->definitions()->isDefinedDelightfulConst($name)) {
            $delightful_constant = $this->options->definitions()->getDefinedDelightfulConst($name);
            if (is_callable($delightful_constant)) {
                return call_user_func_array($delightful_constant, [$this]);
            }
            return $delightful_constant;
        }
        return null;
    }
}
