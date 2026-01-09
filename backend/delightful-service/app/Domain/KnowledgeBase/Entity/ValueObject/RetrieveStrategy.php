<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索strategy枚举category.
 *
 * definition两type检索strategy：
 * - SINGLE: single检索，fromsingleknowledge basemiddle检索info
 * - MULTIPLE: 多重检索，meanwhilefrom多knowledge basemiddle检索info，然backtoresultconduct重新sort
 */
class RetrieveStrategy
{
    /**
     * single检索.
     *
     * fromsingleknowledge basemiddle检索info。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
     */
    public const SINGLE = 'single';

    /**
     * 多重检索.
     *
     * meanwhilefrom多knowledge basemiddle检索info，然backtoresultconduct重新sort。
     * 该strategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
     * 它supportdifferent重sortstrategy，如use重sortmodeloradd权minute数。
     */
    public const MULTIPLE = 'multiple';

    /**
     * get所havecanuse检索strategy.
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
     * checkgive定strategywhethervalid.
     */
    public static function isValid(string $strategy): bool
    {
        return in_array($strategy, self::getAll(), true);
    }
}
