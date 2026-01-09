<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

/**
 * sectionpointtype
 * 1 ~ 99 原子sectionpoint
 * 100 ~ 199 group合sectionpoint的硬encodingimplement.
 */
enum NodeType: int
{
    /*
     * Start Node
     * use作触hair器。窗口openo clock、have新messageo clock、schedule;parametercall（仅子process可use）
     */
    case Start = 1;

    /*
     * LLM Chat 这within是historyreason的group合sectionpoint
     * 大languagemodel optionalmodel、prompt、temperature
     */
    case LLM = 2;

    /*
     * Reply Message
     * replymessagesectionpoint
     */
    case ReplyMessage = 3;

    /*
     * If
     * itemitem判断sectionpoint
     */
    case If = 4;

    /*
     * Code
     * codeexecutesectionpoint
     */
    case Code = 5;

    /*
     * Vector
     * text转toquantity
     * data匹配
     * toquantitydatastorage
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
     * dataload。来源：toquantitydatabase、file、network
     */
    case Loader = 8;

    /*
     * variable
     * set get
     */
    //    case Variable = 9;

    /*
     * Http
     * interfacerequest
     */
    case Http = 10;

    /*
     * 子process
     */
    case Sub = 11;

    /*
     * End Node
     * endsectionpoint
     */
    case End = 12;

    /*
     * History Message
     * historymessage query
     */
    case HistoryMessage = 13;

    /*
     * text切割
     */
    case TextSplitter = 14;

    /*
     * text嵌入
     */
    case TextEmbedding = 15;

    /*
     * toquantitystorage knowledge baseslicesegment
     */
    case KnowledgeFragmentStore = 16;

    /*
     * 知识similardegree
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
     * historymessagestorage
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
     * 意graph识别
     */
    case IntentRecognition = 24;

    /**
     * LLM Call.
     */
    case LLMCall = 25;

    /**
     * toolsectionpoint.
     */
    case Tool = 26;

    /**
     * knowledge baseslicesegmentdelete.
     */
    case KnowledgeFragmentRemove = 27;

    /**
     * person员检索.
     */
    case UserSearch = 28;

    /**
     * etc待message.
     */
    case WaitMessage = 29;

    /**
     * 循环sectionpoint.
     */
    case LoopMain = 30;

    /**
     * 循环sectionpointbody.
     */
    case LoopBody = 31;

    /**
     * 循环end.
     */
    case LoopStop = 32;

    /**
     * Excel fileload器.
     */
    case ExcelLoader = 51;

    /**
     * knowledge base 检索.
     */
    case KnowledgeSearch = 52;

    /**
     * graph像generate.
     */
    case ImageGenerate = 53;

    /**
     * creategroup chat.
     */
    case CreateGroup = 54;
}
