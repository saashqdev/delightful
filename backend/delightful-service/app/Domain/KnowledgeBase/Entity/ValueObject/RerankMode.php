<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 重sort模type枚举category.
 *
 * definition两type重sort模type：
 * - RERANKING_MODEL: use重sortmodelto检索resultconduct重sort
 * - WEIGHTED_SCORE: use加权minute数to检索resultconduct重sort
 */
class RerankMode
{
    /**
     * 重sortmodel.
     *
     * use专门重sortmodel（如 BAAI/bge-reranker-large）to检索resultconduct重sort。
     * 重sortmodelwillaccording toqueryanddocument相closepropertygiveoutmore准确sort。
     * 该模typepassconfigurationparameter `reranking_mode` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * 加权minute数.
     *
     * usedifferent检索methodminute数加权计算finalminute数，to检索resultconduct重sort。
     * for example，cansettoquantity检索result权重for 0.7，keyword检索result权重for 0.3。
     * 该模typepassconfigurationparameter `reranking_mode` fieldset，
     * fromdatabasemiddle retrieve_config configurationget。
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
