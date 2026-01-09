<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * retrievemethod枚举category.
 *
 * definitionfourtyperetrievemethod：
 * - SEMANTIC_SEARCH: 语义retrieve，based ontoquantitysimilardegreeretrievemethod
 * - FULL_TEXT_SEARCH: all文retrieve，based onkeywordmatchretrievemethod
 * - HYBRID_SEARCH: 混合retrieve，结合语义retrieveandall文retrievemethod
 * - GRAPH_SEARCH: graphretrieve，based on知识graph谱retrievemethod
 */
class RetrievalMethod
{
    /**
     * 语义retrieve.
     *
     * based ontoquantitysimilardegreeretrievemethod。
     * willquerytextconvertfortoquantity，然backintoquantitydatabasemiddlefindmostsimilardocument。
     * 适合理解语义close系、多language理解、processblurdescription。
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * all文retrieve.
     *
     * based onkeywordmatchretrievemethod。
     * 索引documentmiddle所havesingle词，returncontainquery词textslicesegment。
     * 适合precisematch（如productname、person名、ID）and低频词match。
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * 混合retrieve.
     *
     * 结合语义retrieveandall文retrievemethod。
     * meanwhileexecuteall文retrieveandtoquantityretrieve，然backpass重sortstepchoosemost佳result。
     * 结合两typeretrieve技术advantage，弥补eachfromnot足。
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * graphretrieve.
     *
     * based on知识graph谱retrievemethod。
     * 利use实bodybetweenclose系conductretrieve，适合process复杂associatequery。
     * canhair现隐含close系andconnect。
     */
    public const string GRAPH_SEARCH = 'graph_search';

    /**
     * get所havecanuseretrievemethod.
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
     * checkgive定retrievemethodwhethervalid.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAll(), true);
    }
}
