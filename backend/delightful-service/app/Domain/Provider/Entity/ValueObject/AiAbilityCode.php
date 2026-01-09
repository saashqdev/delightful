<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * AI 能力代码枚举.
 */
enum AiAbilityCode: string
{
    case Unknown = 'unknown';                          // 未知能力
    case Ocr = 'ocr';                                      // OCR 识别
    case WebSearch = 'web_search';                         // 互联网search
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // 实时语音识别
    case AudioFileRecognition = 'audio_file_recognition';  // 音频文件识别
    case AutoCompletion = 'auto_completion';               // 自动补全
    case ContentSummary = 'content_summary';               // content总结
    case VisualUnderstanding = 'visual_understanding';     // 视觉理解
    case SmartRename = 'smart_rename';                     // 智能重命名
    case AiOptimization = 'ai_optimization';               // AI 优化

    /**
     * get能力name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR 识别',
            self::WebSearch => '互联网search',
            self::RealtimeSpeechRecognition => '实时语音识别',
            self::AudioFileRecognition => '音频文件识别',
            self::AutoCompletion => '自动补全',
            self::ContentSummary => 'content总结',
            self::VisualUnderstanding => '视觉理解',
            self::SmartRename => '智能重命名',
            self::AiOptimization => 'AI 优化',
            default => 'Unknown',
        };
    }

    /**
     * get能力description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Ocr => '本能力覆盖平台所有 OCR 应用场景，精准捕捉并提取 PDF、扫描件及各类图片中的文字info。',
            self::WebSearch => '本能力覆盖平台 AI 大model的互联网search场景，精准get并整合最新的新闻、事实和数据info。',
            self::RealtimeSpeechRecognition => '本能力覆盖平台所有语音转文字的应用场景，实时listener音频流并逐步输出准确的文字content。',
            self::AudioFileRecognition => '本能力覆盖平台所有音频文件转文字的应用场景，精准识别说话人、音频文字等info。',
            self::AutoCompletion => '本能力覆盖平台所有输入content自动补全的应用场景，according to理解上下文为user自动补全content，由user选择是否采纳。',
            self::ContentSummary => '本能力覆盖平台所有content总结的应用场景，对长篇文档、报告或网页文章进行深度分析。',
            self::VisualUnderstanding => '本能力覆盖平台所有需要让大model进行视觉理解的应用场景，精准理解各种图像中的content以及复杂关系。',
            self::SmartRename => '本能力覆盖平台所有支持 AI 重命名的应用场景，according to理解上下文为user自动进行content标题的命名。',
            self::AiOptimization => '本能力覆盖平台所有支持 AI 优化content的应用场景，according to理解上下文为user自动对content进行优化。',
            default => 'Unknown',
        };
    }
}
