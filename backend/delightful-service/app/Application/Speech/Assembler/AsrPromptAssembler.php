<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use App\Application\Speech\DTO\NoteDTO;

/**
 * ASRhintwordassembler
 * responsiblebuildASR相closehintwordtemplate.
 */
class AsrPromptAssembler
{
    /**
     * generaterecordingsummarytitlehintword.
     *
     * @param string $asrStreamContent voiceidentifycontent
     * @param null|NoteDTO $note notecontent(optional)
     * @param string $language outputlanguage(like:zh_CN, en_US)
     * @return string completehintword
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // buildcontent:use XML tagformatexplicitregionminutevoiceidentifycontentandnotecontent
        $contentParts = [];

        // ifhavenote,firstaddnotecontent
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<notecontent>%s</notecontent>', $note->content);
        }

        // addvoiceidentifycontent
        $contentParts[] = sprintf('<voiceidentifycontent>%s</voiceidentifycontent>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
youisoneprofessionalrecordingcontenttitlegeneratehelphand.

## backgroundinstruction
usersubmitonesegmentrecordingcontent,recordingcontentalreadypassvoiceidentifytransferfortext,usermaybealsowillprovidehand写noteasforsupplementinstruction.showinneedyouaccording tothisthesecontentgenerateoneconciseaccuratetitle.

## contentcomesourceinstruction
- <notecontent>:userhand写notecontent,istorecording重pointrecordandsummary,usuallycontainclosekeyinfo
- <voiceidentifycontent>:passvoiceidentifytechnologywillrecordingconvertbecometext,reflectrecordingactualcontent

## titlegeneraterequire

### priorityleveloriginalthen(重want)
1. **notepriority**:ifexistsin<notecontent>,titleshould侧重notecontent
2. **attach importancenotetitle**:ifnoteis Markdown formatandcontaintitle(# openheadline),priority采usenotemiddletitlecontent
3. **comprehensiveconsider**:meanwhilereferencevoiceidentifycontent,ensuretitlecompleteaccurate
4. **keywordextract**:fromnoteandvoiceidentifycontentmiddleextractmost核corekeyword

### formatrequire
1. **lengthlimit**:notexceedspass 20 character(Chinese characters by 1 charactercalculate)
2. **languagestyle**:usestatementpropertylanguagesentence,avoidquestionsentence
3. **conciseexplicit**:directlysummarize核coretheme,notwantaddmodificationword
4. **纯textoutput**:onlyoutputtitlecontent,notwantaddanymarkpoint符number,importnumberorothermodification

### forbidlinefor
- notwantreturn答contentmiddleissue
- notwantconductquotaoutsideexplain
- notwantadd"recording","note"etcfrontconjunction
- notwantoutputtitlebyoutsideanycontent

## recordingcontent
{textContent}

## outputlanguage
pleaseuse {language} languageoutputtitle.

## output
pleasedirectlyoutputtitle:
PROMPT;

        return str_replace(['{textContent}', '{language}'], [$textContent, $language], $template);
    }

    /**
     * generatefileuploadscenariorecordingtitlehintword(emphasizefilename重wantproperty).
     *
     * @param string $userRequestMessage userinchat框sendrequestmessage
     * @param string $language outputlanguage(like:zh_CN, en_US)
     * @return string completehintword
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
youisoneprofessionalrecordingcontenttitlegeneratehelphand.

## backgroundinstruction
useruploadoneaudiofiletosystemmiddle,andinchat框middlesendsummaryrequest.showinneedyouaccording touserrequestmessage(itsmiddlecontainfilename),forthistimerecordingsummarygenerateoneconciseaccuratetitle.

## userinchat框request
usersendoriginalmessagelikedown:
```
{userRequestMessage}
```

## titlegeneraterequire

### priorityleveloriginalthen(nonoften重want)
1. **filenamepriority**:filenameusuallyisuser精corenaming,containmost核corethemeinfo,please重pointreferenceusermessagemiddle @ backsurfacefilename
2. **智canjudge**:
   - iffilenamesemanticclear(like"2024yearQ4productplanwill议.mp3","customerrequirementdiscussion.wav"),prioritybased onfilenamegeneratetitle
   - iffilenameisdatetimestamp(like"20241112_143025.mp3")ornomeaningcharacter(like"recording001.mp3"),thenuse通usedescription
3. **extractkeyword**:fromfilenamemiddleextractmost核corekeywordandtheme

### formatrequire
1. **lengthlimit**:notexceedspass 20 character(Chinese characters by 1 charactercalculate)
2. **languagestyle**:usestatementpropertylanguagesentence,avoidquestionsentence
3. **conciseexplicit**:directlysummarize核coretheme,notwantaddmodificationword
4. **纯textoutput**:onlyoutputtitlecontent,notwantaddanymarkpoint符number,importnumberorothermodification

### forbidlinefor
- notwantretainfileextensionname(.mp3,.wav,.webm etc)
- notwantoutputtitlebyoutsideanycontent
- notwantaddimportnumber,book titlenumberetcmarkpoint符number

## outputlanguage
pleaseuse {language} languageoutputtitle.

## output
pleasedirectlyoutputtitle:
PROMPT;

        return str_replace(
            ['{userRequestMessage}', '{language}'],
            [$userRequestMessage, $language],
            $template
        );
    }
}
