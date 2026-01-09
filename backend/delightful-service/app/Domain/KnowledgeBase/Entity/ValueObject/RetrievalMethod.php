<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索method枚举category.
 *
 * definition了四type检索method：
 * - SEMANTIC_SEARCH: 语义检索，based ontoquantitysimilardegree的检索method
 * - FULL_TEXT_SEARCH: all文检索，based onkeyword匹配的检索method
 * - HYBRID_SEARCH: 混合检索，结合语义检索和all文检索的method
 * - GRAPH_SEARCH: graph检索，based on知识graph谱的检索method
 */
class RetrievalMethod
{
    /**
     * 语义检索.
     *
     * based ontoquantitysimilardegree的检索method。
     * 将query文本convert为toquantity，然backintoquantitydatabasemiddlefindmostsimilar的document。
     * 适合理解语义关系、多语言理解、processblurdescription。
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * all文检索.
     *
     * based onkeyword匹配的检索method。
     * 索引documentmiddle的所have单词，returncontainquery词的文本slicesegment。
     * 适合精确匹配（如productname、person名、ID）和低频词匹配。
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * 混合检索.
     *
     * 结合语义检索和all文检索的method。
     * meanwhileexecuteall文检索和toquantity检索，然backpass重sortstepchoosemost佳result。
     * 结合了两type检索技术的advantage，弥补each自的not足。
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * graph检索.
     *
     * based on知识graph谱的检索method。
     * 利use实bodybetween的关系conduct检索，适合process复杂的associatequery。
     * canhair现隐含的关系和connect。
     */
    public const string GRAPH_SEARCH = 'graph_search';

    /**
     * get所have可use的检索method.
     *
     * @return array<string>
     */
    public static function getAll(): array
    {
        return [
            self::SEMANTIC_SEARCH,
            self::FULL_TEXT_SEARCH,
            self::HYBRID_SEARCH,
            self::GRAPH_SEARCH,
        ];
    }

    /**
     * check给定的检索methodwhethervalid.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAll(), true);
    }
}
