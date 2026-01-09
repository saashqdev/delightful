<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

/**
 * AI cancapabilitycodeenum.
 */
enum AiAbilityCode: string
{
    case Unknown = 'unknown';                          // unknowncancapability
    case Ocr = 'ocr';                                      // OCR identify
    case WebSearch = 'web_search';                         // internetsearch
    case RealtimeSpeechRecognition = 'realtime_speech_recognition';  // actualo clockvoiceidentify
    case AudioFileRecognition = 'audio_file_recognition';  // audiofileidentify
    case AutoCompletion = 'auto_completion';               // fromauto supplementall
    case ContentSummary = 'content_summary';               // contentsummary
    case VisualUnderstanding = 'visual_understanding';     // visualcomprehend
    case SmartRename = 'smart_rename';                     // intelligencecanrename
    case AiOptimization = 'ai_optimization';               // AI optimize

    /**
     * getcancapabilityname.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR identify',
            self::WebSearch => 'internetsearch',
            self::RealtimeSpeechRecognition => 'actualo clockvoiceidentify',
            self::AudioFileRecognition => 'audiofileidentify',
            self::AutoCompletion => 'fromauto supplementall',
            self::ContentSummary => 'contentsummary',
            self::VisualUnderstanding => 'visualcomprehend',
            self::SmartRename => 'intelligencecanrename',
            self::AiOptimization => 'AI optimize',
            default => 'Unknown',
        };
    }

    /**
     * getcancapabilitydescription.
     */
    public function description(): string
    {
        return match ($this) {
            self::Ocr => 'thiscancoverageplatform have OCR applicationscenario,precisecaptureandextract PDF,scanitemandeachcategoryimagemiddletextinfo.',
            self::WebSearch => 'thiscancoverageplatform AI bigmodelinternetsearchscenario,precisegetandintegrationmostnewnewheard,factanddatainfo.',
            self::RealtimeSpeechRecognition => 'thiscancoverageplatform havevoicetransfertextapplicationscenario,actualo clocklisteneraudiostreamandgraduallyoutputaccuratetextcontent.',
            self::AudioFileRecognition => 'thiscancoverageplatform haveaudiofiletransfertextapplicationscenario,preciseidentifyspeakperson,audiotextetcinfo.',
            self::AutoCompletion => 'thiscancoverageplatform haveinputcontentfromauto supplementallapplicationscenario,according tocomprehendupdowntextforuserfromauto supplementallcontent,byuserchoosewhetheradopt.',
            self::ContentSummary => 'thiscancoverageplatform havecontentsummaryapplicationscenario,tolongarticledocument,reportorwebpagetextchapterconductdeepdegreeanalyze.',
            self::VisualUnderstanding => 'thiscancoverageplatform haveneedletbigmodelconductvisualcomprehendapplicationscenario,precisecomprehendeachtypegraphlikemiddlecontentbyandcomplexclosesystem.',
            self::SmartRename => 'thiscancoverageplatform havesupport AI renameapplicationscenario,according tocomprehendupdowntextforuserfromautoconductcontenttitlenaming.',
            self::AiOptimization => 'thiscancoverageplatform havesupport AI optimizecontentapplicationscenario,according tocomprehendupdowntextforuserfromautotocontentconductoptimize.',
            default => 'Unknown',
        };
    }
}
