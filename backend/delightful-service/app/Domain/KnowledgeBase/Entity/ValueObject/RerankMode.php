<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * reloadsortmodetypeenumcategory.
 *
 * definitiontwotypereloadsortmodetype:
 * - RERANKING_MODEL: usereloadsortmodeltoretrieveresultconductreloadsort
 * - WEIGHTED_SCORE: useaddpermissionminutecounttoretrieveresultconductreloadsort
 */
class RerankMode
{
    /**
     * reloadsortmodel.
     *
     * usespecializedreloadsortmodel(like BAAI/bge-reranker-large)toretrieveresultconductreloadsort.
     * reloadsortmodelwillaccording toqueryanddocumentrelatedclosepropertygiveoutmoreaccuratesort.
     * themodetypepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * addpermissionminutecount.
     *
     * usedifferentretrievemethodminutecountaddpermissioncalculatefinalminutecount,toretrieveresultconductreloadsort.
     * for example,cansettoquantityretrieveresultweightfor 0.7,keywordretrieveresultweightfor 0.3.
     * themodetypepassconfigurationparameter `reranking_mode` fieldset,
     * fromdatabasemiddle retrieve_config configurationget.
     */
    public const WEIGHTED_SCORE = 'weighted_score';

    /**
     * get havecanusereloadsortmodetype.
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
     * checkgivesetreloadsortmodetypewhethervalid.
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::getAll(), true);
    }
}
