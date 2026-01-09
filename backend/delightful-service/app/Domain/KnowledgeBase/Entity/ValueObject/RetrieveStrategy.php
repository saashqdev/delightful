<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * retrievestrategyenumcategory.
 *
 * definition两typeretrievestrategy:
 * - SINGLE: singleretrieve,fromsingleknowledge basemiddleretrieveinfo
 * - MULTIPLE: multipleretrieve,meanwhilefrommultipleknowledge basemiddleretrieveinfo,然backtoresultconduct重newsort
 */
class RetrieveStrategy
{
    /**
     * singleretrieve.
     *
     * fromsingleknowledge basemiddleretrieveinfo.
     * thestrategypassconfigurationparameter `retrieve_strategy` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const SINGLE = 'single';

    /**
     * multipleretrieve.
     *
     * meanwhilefrommultipleknowledge basemiddleretrieveinfo,然backtoresultconduct重newsort.
     * thestrategypassconfigurationparameter `retrieve_strategy` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     * itsupportdifferent重sortstrategy,likeuse重sortmodeloradd权minutecount.
     */
    public const MULTIPLE = 'multiple';

    /**
     * get havecanuseretrievestrategy.
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
     * checkgivesetstrategywhethervalid.
     */
    public static function isValid(string $strategy): bool
    {
        return in_array($strategy, self::getAll(), true);
    }
}
