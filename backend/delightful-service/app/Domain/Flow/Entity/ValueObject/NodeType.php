<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

/**
 * sectionpointtype
 * 1 ~ 99 atomicsectionpoint
 * 100 ~ 199 groupcombinesectionpointhardencodingimplement.
 */
enum NodeType: int
{
    /*
     * Start Node
     * useastouchhairdevice.windowopeno clock,havenewmessageo clock,schedule;parametercall(onlychildprocesscanuse)
     */
    case Start = 1;

    /*
     * LLM Chat thiswithinishistoryreasongroupcombinesectionpoint
     * biglanguagemodel optionalmodel,prompt,temperature
     */
    case LLM = 2;

    /*
     * Reply Message
     * replymessagesectionpoint
     */
    case ReplyMessage = 3;

    /*
     * If
     * itemitemjudgesectionpoint
     */
    case If = 4;

    /*
     * Code
     * codeexecutesectionpoint
     */
    case Code = 5;

    /*
     * Vector
     * texttransfertoquantity
     * datamatch
     * toquantitydatastorage
     */
    //    case Vector = 6;

    /*
     * memory
     * Short-term Memory
     * Long-term Memory
     */
    //    case Memory = 7;

    /*
     * Loader
     * dataload.comesource:toquantitydatabase,file,network
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
     * childprocess
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
     * textsplit
     */
    case TextSplitter = 14;

    /*
     * textembedding
     */
    case TextEmbedding = 15;

    /*
     * toquantitystorage knowledge baseslicesegment
     */
    case KnowledgeFragmentStore = 16;

    /*
     * knowledgesimilardegree
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
     * intentiongraphidentify
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
     * personmemberretrieve.
     */
    case UserSearch = 28;

    /**
     * etcpendingmessage.
     */
    case WaitMessage = 29;

    /**
     * loopsectionpoint.
     */
    case LoopMain = 30;

    /**
     * loopsectionpointbody.
     */
    case LoopBody = 31;

    /**
     * loopend.
     */
    case LoopStop = 32;

    /**
     * Excel fileloaddevice.
     */
    case ExcelLoader = 51;

    /**
     * knowledge base retrieve.
     */
    case KnowledgeSearch = 52;

    /**
     * graphlikegenerate.
     */
    case ImageGenerate = 53;

    /**
     * creategroup chat.
     */
    case CreateGroup = 54;
}
