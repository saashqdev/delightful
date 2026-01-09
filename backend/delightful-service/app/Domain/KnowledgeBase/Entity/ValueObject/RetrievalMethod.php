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
 * - FULL_TEXT_SEARCH: alltextretrieve,based onkeywordmatchretrievemethod
 * - HYBRID_SEARCH: hybridretrieve,combinesemanticretrieveandalltextretrievemethod
 * - GRAPH_SEARCH: graphretrieve,based onknowledgegraph谱retrievemethod
 */
class RetrievalMethod
{
    /**
     * semanticretrieve.
     *
     * based ontoquantitysimilardegreeretrievemethod.
     * willquerytextconvertfortoquantity,然backintoquantitydatabasemiddlefindmostsimilardocument.
     * suitablecomprehendsemanticclose系,multiplelanguagecomprehend,processblurdescription.
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * alltextretrieve.
     *
     * based onkeywordmatchretrievemethod.
     * indexdocumentmiddle havesingleword,returncontainquerywordtextslicesegment.
     * suitableprecisematch(likeproductname,personname,ID)andlowfrequent wordsmatch.
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * hybridretrieve.
     *
     * combinesemanticretrieveandalltextretrievemethod.
     * meanwhileexecutealltextretrieveandtoquantityretrieve,然backpass重sortstepchoosemost佳result.
     * combine两typeretrievetechnologyadvantage,compensateeachfromnot足.
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * graphretrieve.
     *
     * based onknowledgegraph谱retrievemethod.
     * 利useactualbodybetweenclose系conductretrieve,suitableprocesscomplexassociatequery.
     * canhairshow implicitclose系andconnect.
     */
    public const string GRAPH_SEARCH = 'graph_search';

    /**
     * get havecanuseretrievemethod.
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
     * checkgivesetretrievemethodwhethervalid.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAll(), true);
    }
}
