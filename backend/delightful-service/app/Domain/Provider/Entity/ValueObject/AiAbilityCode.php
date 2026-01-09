<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * AI can力code枚举.
 */
enum AiAbilityCode: string
{
    case Unknown = 'unknown';                          // unknowncan力
    case Ocr = 'ocr';                                      // OCR identify
    case WebSearch = 'web_search';                         // 互联网search
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实o clockvoiceidentify
    case AudioFileRecognition = 'audio_file_recognition';  // audiofileidentify
    case AutoCompletion = 'auto_completion';               // from动补all
    case ContentSummary = 'content_summary';               // contentsummary
    case VisualUnderstanding = 'visual_understanding';     // 视觉comprehend
    case SmartRename = 'smart_rename';                     // 智canrename
    case AiOptimization = 'ai_optimization';               // AI optimize

    /**
     * getcan力name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR identify',
            self::WebSearch => '互联网search',
            self::RealtimeSpeechRecognition => '实o clockvoiceidentify',
            self::AudioFileRecognition => 'audiofileidentify',
            self::AutoCompletion => 'from动补all',
            self::ContentSummary => 'contentsummary',
            self::VisualUnderstanding => '视觉comprehend',
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
            self::Ocr => '本can力覆盖platform所have OCR applicationscenario,精准捕捉andextract PDF,扫描itemandeachcategoryimagemiddletextinfo.',
            self::WebSearch => '本can力覆盖platform AI bigmodel互联网searchscenario,精准getand整合mostnewnew闻,事实anddatainfo.',
            self::RealtimeSpeechRecognition => '本can力覆盖platform所havevoice转textapplicationscenario,实o clocklisteneraudiostreamand逐步outputaccuratetextcontent.',
            self::AudioFileRecognition => '本can力覆盖platform所haveaudiofile转textapplicationscenario,精准identify说话person,audiotextetcinfo.',
            self::AutoCompletion => '本can力覆盖platform所haveinputcontentfrom动补allapplicationscenario,according tocomprehendupdown文foruserfrom动补allcontent,byuserchoosewhether采纳.',
            self::ContentSummary => '本can力覆盖platform所havecontentsummaryapplicationscenario,tolong篇document,reportorwebpage文chapterconduct深degreeanalyze.',
            self::VisualUnderstanding => '本can力覆盖platform所haveneedletbigmodelconduct视觉comprehendapplicationscenario,精准comprehendeachtypegraphlikemiddlecontentbyandcomplexclose系.',
            self::SmartRename => '本can力覆盖platform所havesupport AI renameapplicationscenario,according tocomprehendupdown文foruserfrom动conductcontenttitle命名.',
            self::AiOptimization => '本can力覆盖platform所havesupport AI optimizecontentapplicationscenario,according tocomprehendupdown文foruserfrom动tocontentconductoptimize.',
            default => 'Unknown',
        };
    }
}
