<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 重sort模式枚举类.
 *
 * 定义了两种重sort模式：
 * - RERANKING_MODEL: use重sortmodel对检索resultconduct重sort
 * - WEIGHTED_SCORE: use加权分数对检索resultconduct重sort
 */
class RerankMode
{
    /**
     * 重sortmodel.
     *
     * use专门的重sortmodel（如 BAAI/bge-reranker-large）对检索resultconduct重sort。
     * 重sortmodelwillaccording toquery和document的相关性给出more准确的sort。
     * 该模式passconfigurationparameter `reranking_mode` fieldset，
     * fromdatabase中的 retrieve_config configurationget。
     */
    public const RERANKING_MODEL = 'reranking_model';

    /**
     * 加权分数.
     *
     * usedifferent检索method的分数加权计算final分数，对检索resultconduct重sort。
     * for example，cansetto量检索result的权重为 0.7，关键词检索result的权重为 0.3。
     * 该模式passconfigurationparameter `reranking_mode` fieldset，
     * fromdatabase中的 retrieve_config configurationget。
     */
    public const WEIGHTED_SCORE = 'weighted_score';

    /**
     * get所have可use的重sort模式.
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
     * check给定的重sort模式whethervalid.
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::getAll(), true);
    }
}
