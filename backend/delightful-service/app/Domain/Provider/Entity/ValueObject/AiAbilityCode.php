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
    case ContentSummary = 'content_summary';               // content总结
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
            self::ContentSummary => 'content总结',
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
            self::Ocr => '本can力覆盖平台所have OCR application场景,精准捕捉andextract PDF、扫描itemandeachcategoryimagemiddletextinfo.',
            self::WebSearch => '本can力覆盖平台 AI bigmodel互联网search场景,精准getand整合mostnewnew闻、事实anddatainfo.',
            self::RealtimeSpeechRecognition => '本can力覆盖平台所havevoice转textapplication场景,实o clocklisteneraudiostreamand逐步outputaccuratetextcontent.',
            self::AudioFileRecognition => '本can力覆盖平台所haveaudiofile转textapplication场景,精准identify说话person、audiotextetcinfo.',
            self::AutoCompletion => '本can力覆盖平台所haveinputcontentfrom动补allapplication场景,according tocomprehendupdown文foruserfrom动补allcontent,byuserchoosewhether采纳.',
            self::ContentSummary => '本can力覆盖平台所havecontent总结application场景,tolong篇document、报告orwebpage文chapterconduct深degreeanalyze.',
            self::VisualUnderstanding => '本can力覆盖平台所haveneedletbigmodelconduct视觉comprehendapplication场景,精准comprehendeachtypegraphlikemiddlecontentbyandcomplexclose系.',
            self::SmartRename => '本can力覆盖平台所havesupport AI renameapplication场景,according tocomprehendupdown文foruserfrom动conductcontenttitle命名.',
            self::AiOptimization => '本can力覆盖平台所havesupport AI optimizecontentapplication场景,according tocomprehendupdown文foruserfrom动tocontentconductoptimize.',
            default => 'Unknown',
        };
    }
}
