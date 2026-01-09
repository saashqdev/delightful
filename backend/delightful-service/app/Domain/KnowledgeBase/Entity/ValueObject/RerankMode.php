<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 重sort模type枚举category.
 *
 * 定义了两type重sort模type：
 * - RERANKING_MODEL: use重sortmodel对检索resultconduct重sort
 * - WEIGHTED_SCORE: use加权minute数对检索resultconduct重sort
 */
class RerankMode
{
    /**
     * 重sortmodel.
     *
     * use专门的重sortmodel（如 BAAI/bge-reranker-large）对检索resultconduct重sort。
     * 重sortmodelwillaccording toquery和document的相关property给出more准确的sort。
     * 该模typepassconfigurationparameter `reranking_mode` fieldset，
     * fromdatabasemiddle的 retrieve_config configurationget。
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * 加权minute数.
     *
     * usedifferent检索method的minute数加权计算finalminute数，对检索resultconduct重sort。
     * for example，cansettoquantity检索result的权重为 0.7，keyword检索result的权重为 0.3。
     * 该模typepassconfigurationparameter `reranking_mode` fieldset，
     * fromdatabasemiddle的 retrieve_config configurationget。
     */
    public const WEIGHTED_SCORE = 'weighted_score';

    /**
     * get所have可use的重sort模type.
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
     * check给定的重sort模typewhethervalid.
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::getAll(), true);
    }
}
