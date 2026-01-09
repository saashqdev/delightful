<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * retrievestrategyenumcategory.
 *
 * definitiontwotyperetrievestrategy:
 * - SINGLE: singleretrieve,fromsingleknowledge basemiddleretrieveinfo
 * - MULTIPLE: multipleretrieve,meanwhilefrommultipleknowledge basemiddleretrieveinfo,thenbacktoresultconductreloadnewsort
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
     * meanwhilefrommultipleknowledge basemiddleretrieveinfo,thenbacktoresultconductreloadnewsort.
     * thestrategypassconfigurationparameter `retrieve_strategy` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     * itsupportdifferentreloadsortstrategy,likeusereloadsortmodeloraddpermissionminutecount.
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
