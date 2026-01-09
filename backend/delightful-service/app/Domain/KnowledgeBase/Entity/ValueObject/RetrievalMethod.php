<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索method枚举category.
 *
 * definitionfourtype检索method：
 * - SEMANTIC_SEARCH: 语义检索，based ontoquantitysimilardegree检索method
 * - FULL_TEXT_SEARCH: all文检索，based onkeyword匹配检索method
 * - HYBRID_SEARCH: 混合检索，结合语义检索andall文检索method
 * - GRAPH_SEARCH: graph检索，based on知识graph谱检索method
 */
class RetrievalMethod
{
    /**
     * 语义检索.
     *
     * based ontoquantitysimilardegree检索method。
     * willquerytextconvertfortoquantity，然backintoquantitydatabasemiddlefindmostsimilardocument。
     * 适合理解语义close系、多language理解、processblurdescription。
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * all文检索.
     *
     * based onkeyword匹配检索method。
     * 索引documentmiddle所havesingle词，returncontainquery词textslicesegment。
     * 适合精确匹配（如productname、person名、ID）and低频词匹配。
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * 混合检索.
     *
     * 结合语义检索andall文检索method。
     * meanwhileexecuteall文检索andtoquantity检索，然backpass重sortstepchoosemost佳result。
     * 结合两type检索技术advantage，弥补eachfromnot足。
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * graph检索.
     *
     * based on知识graph谱检索method。
     * 利use实bodybetweenclose系conduct检索，适合process复杂associatequery。
     * canhair现隐含close系andconnect。
     */
    public const string GRAPH_SEARCH = 'graph_search';

    /**
     * get所havecanuse检索method.
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
     * checkgive定检索methodwhethervalid.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAll(), true);
    }
}
