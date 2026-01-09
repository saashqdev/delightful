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
 * - SEMANTIC_SEARCH: 语义检索，based on向量相似度的检索method
 * - FULL_TEXT_SEARCH: 全文检索，based on关键词匹配的检索method
 * - HYBRID_SEARCH: 混合检索，结合语义检索和全文检索的method
 * - GRAPH_SEARCH: 图检索，based on知识图谱的检索method
 */
class RetrievalMethod
{
    /**
     * 语义检索.
     *
     * based on向量相似度的检索method。
     * 将query文本转换为向量，然后在向量database中查找最相似的文档。
     * 适合理解语义关系、多语言理解、处理模糊description。
     */
    public const string SEMANTIC_SEARCH = 'semantic_search';

    /**
     * 全文检索.
     *
     * based on关键词匹配的检索method。
     * 索引文档中的所有单词，returncontainquery词的文本片段。
     * 适合精确匹配（如产品name、人名、ID）和低频词匹配。
     */
    public const string FULL_TEXT_SEARCH = 'full_text_search';

    /**
     * 混合检索.
     *
     * 结合语义检索和全文检索的method。
     * 同时执行全文检索和向量检索，然后pass重sort步骤选择最佳result。
     * 结合了两种检索技术的优势，弥补各自的不足。
     */
    public const string HYBRID_SEARCH = 'hybrid_search';

    /**
     * 图检索.
     *
     * based on知识图谱的检索method。
     * 利用实体间的关系进行检索，适合处理复杂的关联query。
     * can发现隐含的关系和连接。
     */
    public const string GRAPH_SEARCH = 'graph_search';

    /**
     * get所有可用的检索method.
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
     * check给定的检索method是否valid.
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::getAll(), true);
    }
}
