<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索strategy枚举类.
 *
 * 定义了两种检索strategy：
 * - SINGLE: 单一检索，从单个knowledge base中检索info
 * - MULTIPLE: 多重检索，同时从多个knowledge base中检索info，然后对result进行重新sort
 */
class RetrieveStrategy
{
    /**
     * 单一检索.
     *
     * 从单个knowledge base中检索info。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * 从database中的 retrieve_config configurationget。
     */
    public const SINGLE = 'single';

    /**
     * 多重检索.
     *
     * 同时从多个knowledge base中检索info，然后对result进行重新sort。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * 从database中的 retrieve_config configurationget。
     * 它supportdifferent的重sortstrategy，如use重sortmodel或加权分数。
     */
    public const MULTIPLE = 'multiple';

    /**
     * get所有可用的检索strategy.
     *
     * @return array<string>
     */
    public static function getAll(): array
    {
        return [
            self::SINGLE,
            self::MULTIPLE,
        ];
    }

    /**
     * check给定的strategy是否valid.
     */
    public static function isValid(string $strategy): bool
    {
        return in_array($strategy, self::getAll(), true);
    }
}
