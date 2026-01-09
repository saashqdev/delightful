<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * retrievemethodenumcategory.
 *
 * definitionfourtyperetrievemethod:
 * - SEMANTIC_SEARCH: semanticretrieve,based ontoquantitysimilardegreeretrievemethod
 * - FULL_TEXT_SEARCH: all文retrieve,based onkeywordmatchretrievemethod
 * - HYBRID_SEARCH: hybridretrieve,combinesemanticretrieveandall文retrievemethod
 * - GRAPH_SEARCH: graphretrieve,based onknowledgegraph谱retrievemethod
 */
class RetrievalMethod
{
    /**
     * semanticretrieve.
     *
     * based ontoquantitysimilardegreeretrievemethod.
     * willquerytextconvertfortoquantity,然backintoquantitydatabasemiddlefindmostsimilardocument.
     * suitablecomprehendsemanticclose系,多languagecomprehend,processblurdescription.
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * all文retrieve.
     *
     * based onkeywordmatchretrievemethod.
     * indexdocumentmiddle所havesingle词,returncontainquery词textslicesegment.
     * suitableprecisematch(如productname,person名,ID)andlow频词match.
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * hybridretrieve.
     *
     * combinesemanticretrieveandall文retrievemethod.
     * meanwhileexecuteall文retrieveandtoquantityretrieve,然backpass重sortstepchoosemost佳result.
     * combine两typeretrievetechnologyadvantage,弥补eachfromnot足.
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * graphretrieve.
     *
     * based onknowledgegraph谱retrievemethod.
     * 利use实bodybetweenclose系conductretrieve,suitableprocesscomplexassociatequery.
     * canhair现隐含close系andconnect.
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
