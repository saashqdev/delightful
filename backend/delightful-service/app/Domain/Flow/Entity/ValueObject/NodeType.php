<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

/**
 * 节点type
 * 1 ~ 99 原子节点
 * 100 ~ 199 组合节点的硬编码implement.
 */
enum NodeType: int
{
    /*
     * Start Node
     * 用作触发器。窗口打开时、有新message时、schedule;parametercall（仅子process可用）
     */
    case Start = 1;

    /*
     * LLM Chat 这里是历史原因的组合节点
     * 大语言model 可选model、prompt、temperature
     */
    case LLM = 2;

    /*
     * Reply Message
     * replymessage节点
     */
    case ReplyMessage = 3;

    /*
     * If
     * 条件判断节点
     */
    case If = 4;

    /*
     * Code
     * 代码execute节点
     */
    case Code = 5;

    /*
     * Vector
     * 文本转向量
     * 数据匹配
     * 向量数据storage
     */
    //    case Vector = 6;

    /*
     * 记忆
     * Short-term Memory
     * Long-term Memory
     */
    //    case Memory = 7;

    /*
     * Loader
     * 数据load。来源：向量database、file、网络
     */
    case Loader = 8;

    /*
     * variable
     * set get
     */
    //    case Variable = 9;

    /*
     * Http
     * 接口request
     */
    case Http = 10;

    /*
     * 子process
     */
    case Sub = 11;

    /*
     * End Node
     * 结束节点
     */
    case End = 12;

    /*
     * History Message
     * 历史message query
     */
    case HistoryMessage = 13;

    /*
     * 文本切割
     */
    case TextSplitter = 14;

    /*
     * 文本嵌入
     */
    case TextEmbedding = 15;

    /*
     * 向量storage 知识库片段
     */
    case KnowledgeFragmentStore = 16;

    /*
     * 知识相似度
     */
    case KnowledgeSimilarity = 17;

    /*
     * cacheset
     */
    case CacheSet = 18;

    /*
     * cacheget
     */
    case CacheGet = 19;

    /*
     * 历史messagestorage
     */
    case HistoryMessageStore = 20;

    /*
     * variableset
     */
    case VariableSet = 21;

    /*
     * variablearrayshift
     */
    case VariableArrayShift = 22;

    /*
     * variablearraypush
     */
    case VariableArrayPush = 23;

    /*
     * 意图识别
     */
    case IntentRecognition = 24;

    /**
     * LLM Call.
     */
    case LLMCall = 25;

    /**
     * 工具节点.
     */
    case Tool = 26;

    /**
     * 知识库片段delete.
     */
    case KnowledgeFragmentRemove = 27;

    /**
     * 人员检索.
     */
    case UserSearch = 28;

    /**
     * 等待message.
     */
    case WaitMessage = 29;

    /**
     * 循环节点.
     */
    case LoopMain = 30;

    /**
     * 循环节点体.
     */
    case LoopBody = 31;

    /**
     * 循环结束.
     */
    case LoopStop = 32;

    /**
     * Excel fileload器.
     */
    case ExcelLoader = 51;

    /**
     * 知识库 检索.
     */
    case KnowledgeSearch = 52;

    /**
     * 图像generate.
     */
    case ImageGenerate = 53;

    /**
     * creategroup chat.
     */
    case CreateGroup = 54;
}
