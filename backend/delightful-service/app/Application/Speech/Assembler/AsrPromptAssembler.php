<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use App\Application\Speech\DTO\NoteDTO;

/**
 * ASRhint词装配器
 * 负责buildASR相closehint词template.
 */
class AsrPromptAssembler
{
    /**
     * generaterecordingsummarytitlehint词.
     *
     * @param string $asrStreamContent voiceidentifycontent
     * @param null|NoteDTO $note notecontent(optional)
     * @param string $language outputlanguage(如:zh_CN, en_US)
     * @return string completehint词
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // buildcontent:use XML tagformatexplicit区minutevoiceidentifycontentandnotecontent
        $contentParts = [];

        // ifhavenote,先addnotecontent
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<notecontent>%s</notecontent>', $note->content);
        }

        // addvoiceidentifycontent
        $contentParts[] = sprintf('<voiceidentifycontent>%s</voiceidentifycontent>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
youisone专业recordingcontenttitlegenerate助hand.

## backgroundinstruction
usersubmitonesegmentrecordingcontent,recordingcontent经passvoiceidentify转fortext,usermaybealsowillprovidehand写noteasfor补充instruction.现inneedyouaccording tothisthesecontentgenerateoneconciseaccuratetitle.

## contentcome源instruction
- <notecontent>:userhand写notecontent,istorecording重pointrecordandsummary,usuallycontainclosekeyinfo
- <voiceidentifycontent>:passvoiceidentify技术willrecordingconvertbecometext,反映recordingactualcontent

## titlegeneraterequire

### prioritylevel原then(重want)
1. **notepriority**:if存in<notecontent>,titleshould侧重notecontent
2. **重视notetitle**:ifnoteis Markdown formatandcontaintitle(# openheadline),priority采usenotemiddletitlecontent
3. **综合consider**:meanwhilereferencevoiceidentifycontent,ensuretitlecompleteaccurate
4. **keywordextract**:fromnoteandvoiceidentifycontentmiddleextractmost核corekeyword

### formatrequire
1. **lengthlimit**:not超pass 20 character(汉字按 1 charactercalculate)
2. **languagestyle**:use陈述property语sentence,avoid疑问sentence
3. **conciseexplicit**:directlysummarize核coretheme,notwantaddmodification词
4. **纯textoutput**:onlyoutputtitlecontent,notwantaddany标point符number,引numberorothermodification

### forbidlinefor
- notwantreturn答contentmiddleissue
- notwantconduct额outsideexplain
- notwantadd"recording","note"etcfront缀词
- notwantoutputtitlebyoutsideanycontent

## recordingcontent
{textContent}

## outputlanguage
请use {language} languageoutputtitle.

## output
请directlyoutputtitle:
PROMPT;

        return str_replace(['{textContent}', '{language}'], [$textContent, $language], $template);
    }

    /**
     * generatefileuploadscenariorecordingtitlehint词(emphasizefile名重wantproperty).
     *
     * @param string $userRequestMessage userinchat框sendrequestmessage
     * @param string $language outputlanguage(如:zh_CN, en_US)
     * @return string completehint词
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
youisone专业recordingcontenttitlegenerate助hand.

## backgroundinstruction
useruploadoneaudiofiletosystemmiddle,andinchat框middlesendsummaryrequest.现inneedyouaccording touserrequestmessage(itsmiddlecontainfile名),forthistimerecordingsummarygenerateoneconciseaccuratetitle.

## userinchat框request
usersendoriginalmessage如down:
```
{userRequestMessage}
```

## titlegeneraterequire

### prioritylevel原then(non常重want)
1. **file名priority**:file名usuallyisuser精core命名,containmost核corethemeinfo,请重pointreferenceusermessagemiddle @ backsurfacefile名
2. **智canjudge**:
   - iffile名语义clear(如"2024yearQ4product规划will议.mp3","customerrequirementdiscussion.wav"),prioritybased onfile名generatetitle
   - iffile名isdatetime戳(如"20241112_143025.mp3")ornomeaningcharacter(如"recording001.mp3"),thenuse通usedescription
3. **extractkeyword**:fromfile名middleextractmost核corekeywordandtheme

### formatrequire
1. **lengthlimit**:not超pass 20 character(汉字按 1 charactercalculate)
2. **languagestyle**:use陈述property语sentence,avoid疑问sentence
3. **conciseexplicit**:directlysummarize核coretheme,notwantaddmodification词
4. **纯textoutput**:onlyoutputtitlecontent,notwantaddany标point符number,引numberorothermodification

### forbidlinefor
- notwant保留fileextension名(.mp3,.wav,.webm etc)
- notwantoutputtitlebyoutsideanycontent
- notwantadd引number,书名numberetc标point符number

## outputlanguage
请use {language} languageoutputtitle.

## output
请directlyoutputtitle:
PROMPT;

        return str_replace(
            ['{userRequestMessage}', '{language}'],
            [$userRequestMessage, $language],
            $template
        );
    }
}
