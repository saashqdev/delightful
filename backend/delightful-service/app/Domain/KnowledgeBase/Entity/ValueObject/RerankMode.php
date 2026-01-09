<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 重sort模type枚举category.
 *
 * definition两type重sort模type:
 * - RERANKING_MODEL: use重sortmodeltoretrieveresultconduct重sort
 * - WEIGHTED_SCORE: useadd权minute数toretrieveresultconduct重sort
 */
class RerankMode
{
    /**
     * 重sortmodel.
     *
     * usespecialized重sortmodel(如 BAAI/bge-reranker-large)toretrieveresultconduct重sort.
     * 重sortmodelwillaccording toqueryanddocument相closepropertygiveoutmoreaccuratesort.
     * the模typepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * add权minute数.
     *
     * usedifferentretrievemethodminute数add权calculatefinalminute数,toretrieveresultconduct重sort.
     * for example,cansettoquantityretrieveresultweightfor 0.7,keywordretrieveresultweightfor 0.3.
     * the模typepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const WEIGHTED_SCORE = 'weighted_score';

    /**
     * get所havecanuse重sort模type.
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
     * checkgive定重sort模typewhethervalid.
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::getAll(), true);
    }
}
