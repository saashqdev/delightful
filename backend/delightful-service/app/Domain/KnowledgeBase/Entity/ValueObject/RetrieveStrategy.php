<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * retrievestrategy枚举category.
 *
 * definition两typeretrievestrategy：
 * - SINGLE: singleretrieve，fromsingleknowledge basemiddleretrieveinfo
 * - MULTIPLE: 多重retrieve，meanwhilefrom多knowledge basemiddleretrieveinfo，然backtoresultconduct重新sort
 */
class RetrieveStrategy
{
    /**
     * singleretrieve.
     *
     * fromsingleknowledge basemiddleretrieveinfo。
     * thestrategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
     */
    public const SINGLE = 'single';

    /**
     * 多重retrieve.
     *
     * meanwhilefrom多knowledge basemiddleretrieveinfo，然backtoresultconduct重新sort。
     * thestrategypassconfigurationparameter `retrieve_strategy` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
     * itsupportdifferent重sortstrategy，如use重sortmodeloradd权minute数。
     */
    public const MULTIPLE = 'multiple';

    /**
     * get所havecanuseretrievestrategy.
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
