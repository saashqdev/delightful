<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * AI 能力code枚举.
 */
enum AiAbilityCode: string
{
    case Unknown = 'unknown';                          // unknown能力
    case Ocr = 'ocr';                                      // OCR 识别
    case WebSearch = 'web_search';                         // 互联网search
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实o clockvoice识别
    case AudioFileRecognition = 'audio_file_recognition';  // audiofile识别
    case AutoCompletion = 'auto_completion';               // from动补all
    case ContentSummary = 'content_summary';               // content总结
    case VisualUnderstanding = 'visual_understanding';     // 视觉理解
    case SmartRename = 'smart_rename';                     // 智能重命名
    case AiOptimization = 'ai_optimization';               // AI optimize

    /**
     * get能力name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR 识别',
            self::WebSearch => '互联网search',
            self::RealtimeSpeechRecognition => '实o clockvoice识别',
            self::AudioFileRecognition => 'audiofile识别',
            self::AutoCompletion => 'from动补all',
            self::ContentSummary => 'content总结',
            self::VisualUnderstanding => '视觉理解',
            self::SmartRename => '智能重命名',
            self::AiOptimization => 'AI optimize',
            default => 'Unknown',
        };
    }

    /**
     * get能力description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Ocr => '本能力覆盖平台所have OCR application场景，精准捕捉andextract PDF、扫描itemandeachcategoryimagemiddletextinfo。',
            self::WebSearch => '本能力覆盖平台 AI 大model互联网search场景，精准getand整合mostnew新闻、事实anddatainfo。',
            self::RealtimeSpeechRecognition => '本能力覆盖平台所havevoice转textapplication场景，实o clocklisteneraudiostreamand逐步outputaccuratetextcontent。',
            self::AudioFileRecognition => '本能力覆盖平台所haveaudiofile转textapplication场景，精准识别说话person、audiotextetcinfo。',
            self::AutoCompletion => '本能力覆盖平台所haveinputcontentfrom动补allapplication场景，according to理解updown文foruserfrom动补allcontent，byuserchoosewhether采纳。',
            self::ContentSummary => '本能力覆盖平台所havecontent总结application场景，to长篇document、报告orwebpage文chapterconduct深degreeanalyze。',
            self::VisualUnderstanding => '本能力覆盖平台所haveneedlet大modelconduct视觉理解application场景，精准理解eachtypegraphlikemiddlecontentbyand复杂close系。',
            self::SmartRename => '本能力覆盖平台所havesupport AI 重命名application场景，according to理解updown文foruserfrom动conductcontenttitle命名。',
            self::AiOptimization => '本能力覆盖平台所havesupport AI optimizecontentapplication场景，according to理解updown文foruserfrom动tocontentconductoptimize。',
            default => 'Unknown',
        };
    }
}
