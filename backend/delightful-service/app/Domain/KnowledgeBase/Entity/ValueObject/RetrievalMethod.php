<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

/**
 * 检索method枚举类.
 *
 * 定义了四种检索method：
 * - SEMANTIC_SEARCH: 语义检索，based onto量similar度的检索method
 * - FULL_TEXT_SEARCH: all文检索，based on关键词匹配的检索method
 * - HYBRID_SEARCH: 混合检索，结合语义检索和all文检索的method
 * - GRAPH_SEARCH: 图检索，based on知识图谱的检索method
 */
class RetrievalMethod
{
    /**
     * 语义检索.
     *
     * based onto量similar度的检索method。
     * 将query文本convert为to量，然后into量database中查找mostsimilar的document。
     * 适合理解语义关系、多语言理解、process模糊description。
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * all文检索.
     *
     * based on关键词匹配的检索method。
     * 索引document中的所have单词，returncontainquery词的文本片段。
     * 适合精确匹配（如productname、人名、ID）和低频词匹配。
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * 混合检索.
     *
     * 结合语义检索和all文检索的method。
     * meanwhileexecuteall文检索和to量检索，然后pass重sort步骤选择most佳result。
     * 结合了两种检索技术的advantage，弥补each自的not足。
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * 图检索.
     *
     * based on知识图谱的检索method。
     * 利use实体间的关系conduct检索，适合process复杂的associatequery。
     * can发现隐含的关系和connect。
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
