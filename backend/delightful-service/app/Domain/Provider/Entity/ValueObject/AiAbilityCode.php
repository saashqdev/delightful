<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * AI can力codeenum.
 */
enum AiAbilityCode: string
{
    case Unknown = 'unknown';                          // unknowncan力
    case Ocr = 'ocr';                                      // OCR identify
    case WebSearch = 'web_search';                         // internetsearch
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实o clockvoiceidentify
    case AudioFileRecognition = 'audio_file_recognition';  // audiofileidentify
    case AutoCompletion = 'auto_completion';               // fromauto supplementall
    case ContentSummary = 'content_summary';               // contentsummary
    case VisualUnderstanding = 'visual_understanding';     // visualcomprehend
    case SmartRename = 'smart_rename';                     // 智canrename
    case AiOptimization = 'ai_optimization';               // AI optimize

    /**
     * getcan力name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR identify',
            self::WebSearch => 'internetsearch',
            self::RealtimeSpeechRecognition => '实o clockvoiceidentify',
            self::AudioFileRecognition => 'audiofileidentify',
            self::AutoCompletion => 'fromauto supplementall',
            self::ContentSummary => 'contentsummary',
            self::VisualUnderstanding => 'visualcomprehend',
            self::SmartRename => '智canrename',
            self::AiOptimization => 'AI optimize',
            default => 'Unknown',
        };
    }

    /**
     * getcan力description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Ocr => '本cancoverageplatform所have OCR applicationscenario,precise捕捉andextract PDF,扫描itemandeachcategoryimagemiddletextinfo.',
            self::WebSearch => '本cancoverageplatform AI bigmodelinternetsearchscenario,precisegetand整合mostnewnew闻,事实anddatainfo.',
            self::RealtimeSpeechRecognition => '本cancoverageplatform所havevoice转textapplicationscenario,实o clocklisteneraudiostreamand逐步outputaccuratetextcontent.',
            self::AudioFileRecognition => '本cancoverageplatform所haveaudiofile转textapplicationscenario,preciseidentify说话person,audiotextetcinfo.',
            self::AutoCompletion => '本cancoverageplatform所haveinputcontentfromauto supplementallapplicationscenario,according tocomprehendupdown文foruserfromauto supplementallcontent,byuserchoosewhether采纳.',
            self::ContentSummary => '本cancoverageplatform所havecontentsummaryapplicationscenario,tolong篇document,reportorwebpage文chapterconduct深degreeanalyze.',
            self::VisualUnderstanding => '本cancoverageplatform所haveneedletbigmodelconductvisualcomprehendapplicationscenario,precisecomprehendeachtypegraphlikemiddlecontentbyandcomplexclose系.',
            self::SmartRename => '本cancoverageplatform所havesupport AI renameapplicationscenario,according tocomprehendupdown文foruserfrom动conductcontenttitlenaming.',
            self::AiOptimization => '本cancoverageplatform所havesupport AI optimizecontentapplicationscenario,according tocomprehendupdown文foruserfrom动tocontentconductoptimize.',
            default => 'Unknown',
        };
    }
}
