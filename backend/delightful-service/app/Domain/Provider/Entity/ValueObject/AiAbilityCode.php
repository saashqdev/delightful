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
    case Unknown = 'unknown';                          // 未知能力
    case Ocr = 'ocr';                                      // OCR 识别
    case WebSearch = 'web_search';                         // 互联网search
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实o clockvoice识别
    case AudioFileRecognition = 'audio_file_recognition';  // audiofile识别
    case AutoCompletion = 'auto_completion';               // 自动补all
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
            self::AutoCompletion => '自动补all',
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
            self::Ocr => '本能力覆盖平台所have OCR application场景，精准捕捉并提取 PDF、扫描item及eachcategoryimagemiddle的文字info。',
            self::WebSearch => '本能力覆盖平台 AI 大model的互联网search场景，精准get并整合mostnew新闻、事实和datainfo。',
            self::RealtimeSpeechRecognition => '本能力覆盖平台所havevoice转文字的application场景，实o clocklisteneraudiostream并逐步output准确的文字content。',
            self::AudioFileRecognition => '本能力覆盖平台所haveaudiofile转文字的application场景，精准识别说话人、audio文字etcinfo。',
            self::AutoCompletion => '本能力覆盖平台所haveinputcontent自动补all的application场景，according to理解updown文为user自动补allcontent，由user选择whether采纳。',
            self::ContentSummary => '本能力覆盖平台所havecontent总结的application场景，对长篇document、报告or网页文chapterconduct深degreeanalyze。',
            self::VisualUnderstanding => '本能力覆盖平台所haveneed让大modelconduct视觉理解的application场景，精准理解eachtypegraph像middle的contentby及复杂关系。',
            self::SmartRename => '本能力覆盖平台所havesupport AI 重命名的application场景，according to理解updown文为user自动conductcontenttitle的命名。',
            self::AiOptimization => '本能力覆盖平台所havesupport AI optimizecontent的application场景，according to理解updown文为user自动对contentconductoptimize。',
            default => 'Unknown',
        };
    }
}
