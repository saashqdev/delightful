<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 重sortmodetypeenumcategory.
 *
 * definition两type重sortmodetype:
 * - RERANKING_MODEL: use重sortmodeltoretrieveresultconduct重sort
 * - WEIGHTED_SCORE: useadd权minutecounttoretrieveresultconduct重sort
 */
class RerankMode
{
    /**
     * 重sortmodel.
     *
     * usespecialized重sortmodel(like BAAI/bge-reranker-large)toretrieveresultconduct重sort.
     * 重sortmodelwillaccording toqueryanddocument相closepropertygiveoutmoreaccuratesort.
     * themodetypepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * add权minutecount.
     *
     * usedifferentretrievemethodminutecountadd权calculatefinalminutecount,toretrieveresultconduct重sort.
     * for example,cansettoquantityretrieveresultweightfor 0.7,keywordretrieveresultweightfor 0.3.
     * themodetypepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const WEIGHTED_SCORE = 'weighted_score';

    /**
     * get havecanuse重sortmodetype.
     *
     * @return array<string>
     */
    public static function getAll(): array
    {
        return [
            self::RERANKING_MODEL,
            self::WEIGHTED_SCORE,
        ];
    }

    /**
     * checkgiveset重sortmodetypewhethervalid.
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::getAll(), true);
    }
}
