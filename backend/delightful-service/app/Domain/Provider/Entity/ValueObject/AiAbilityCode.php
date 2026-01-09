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
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实时voice识别
    case AudioFileRecognition = 'audio_file_recognition';  // audiofile识别
    case AutoCompletion = 'auto_completion';               // 自动补全
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
            self::RealtimeSpeechRecognition => '实时voice识别',
            self::AudioFileRecognition => 'audiofile识别',
            self::AutoCompletion => '自动补全',
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
            self::Ocr => '本能力覆盖平台所有 OCR application场景，精准捕捉并提取 PDF、扫描件及各类image中的文字info。',
            self::WebSearch => '本能力覆盖平台 AI 大model的互联网search场景，精准get并整合最new新闻、事实和datainfo。',
            self::RealtimeSpeechRecognition => '本能力覆盖平台所有voice转文字的application场景，实时listeneraudiostream并逐步output准确的文字content。',
            self::AudioFileRecognition => '本能力覆盖平台所有audiofile转文字的application场景，精准识别说话人、audio文字等info。',
            self::AutoCompletion => '本能力覆盖平台所有inputcontent自动补全的application场景，according to理解上下文为user自动补全content，由user选择是否采纳。',
            self::ContentSummary => '本能力覆盖平台所有content总结的application场景，对长篇document、报告或网页文章进行深度analyze。',
            self::VisualUnderstanding => '本能力覆盖平台所有need让大model进行视觉理解的application场景，精准理解各种图像中的content以及复杂关系。',
            self::SmartRename => '本能力覆盖平台所有支持 AI 重命名的application场景，according to理解上下文为user自动进行contenttitle的命名。',
            self::AiOptimization => '本能力覆盖平台所有支持 AI optimizecontent的application场景，according to理解上下文为user自动对content进行optimize。',
            default => 'Unknown',
        };
    }
}
