<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索strategy枚举category.
 *
 * 定义了两type检索strategy：
 * - SINGLE: 单一检索，from单knowledge basemiddle检索info
 * - MULTIPLE: 多重检索，meanwhilefrom多knowledge basemiddle检索info，然back对resultconduct重新sort
 */
class RetrieveStrategy
{
    /**
     * 单一检索.
     *
     * from单knowledge basemiddle检索info。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle的 retrieve_config configurationget。
     */
    public const SINGLE = 'single';

    /**
     * 多重检索.
     *
     * meanwhilefrom多knowledge basemiddle检索info，然back对resultconduct重新sort。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle的 retrieve_config configurationget。
     * 它supportdifferent的重sortstrategy，如use重sortmodelor加权minute数。
     */
    public const MULTIPLE = 'multiple';

    /**
     * get所have可use的检索strategy.
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
     * check给定的strategywhethervalid.
     */
    public static function isValid(string $strategy): bool
    {
        return in_array($strategy, self::getAll(), true);
    }
}
