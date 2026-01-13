<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use App\Application\Speech\DTO\NoteDTO;

/**
 * ASRhintwordassembler
 * responsiblebuildASRrelatedclosehintwordtemplate.
 */
class AsrPromptAssembler
{
    /**
     * generaterecordingsummarytitlehintword.
     *
     * @param string $asrStreamContent voiceidentifycontent
     * @param null|NoteDTO $note notecontent(optional)
     * @param string $language outputlanguage(like:en_US, en_US)
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
usersubmitonesegmentrecordingcontent,recordingcontentalreadypassvoiceidentifytransferfortext,usermaybealsowillprovidehandwritenoteasforsupplementinstruction.showinneedyouaccording tothisthesecontentgenerateoneconciseaccuratetitle.

## contentcomesourceinstruction
- <notecontent>:userhandwritenotecontent,istorecordingreloadpointrecordandsummary,usuallycontainclosekeyinfo
- <voiceidentifycontent>:passvoiceidentifytechnologywillrecordingconvertbecometext,reflectrecordingactualcontent

## titlegeneraterequire

### priorityleveloriginalthen(reloadwant)
1. **notepriority**:ifexistsin<notecontent>,titleshouldsidereloadnotecontent
2. **attach importancenotetitle**:ifnoteis Markdown formatandcontaintitle(# openheadline),prioritycollectusenotemiddletitlecontent
3. **comprehensiveconsider**:meanwhilereferencevoiceidentifycontent,ensuretitlecompleteaccurate
4. **keywordextract**:fromnoteandvoiceidentifycontentmiddleextractmostcorecorekeyword

### formatrequire
1. **lengthlimit**:notexceedspass 20 character(Chinese characters by 1 charactercalculate)
2. **languagestyle**:usestatementpropertylanguagesentence,avoidquestionsentence
3. **conciseexplicit**:directlysummarizecorecoretheme,notwantaddmodificationword
4. **puretextoutput**:onlyoutputtitlecontent,notwantaddanymarkpointsymbolnumber,importnumberorothermodification

### forbidlinefor
- notwantreturnanswercontentmiddleissue
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
     * generatefileuploadscenariorecordingtitlehintword(emphasizefilenamereloadwantproperty).
     *
     * @param string $userRequestMessage userinchatframesendrequestmessage
     * @param string $language outputlanguage(like:en_US, en_US)
     * @return string completehintword
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
youisoneprofessionalrecordingcontenttitlegeneratehelphand.

## backgroundinstruction
useruploadoneaudiofiletosystemmiddle,andinchatframemiddlesendsummaryrequest.showinneedyouaccording touserrequestmessage(itsmiddlecontainfilename),forthistimerecordingsummarygenerateoneconciseaccuratetitle.

## userinchatframerequest
usersendoriginalmessagelikedown:
```
{userRequestMessage}
```

## titlegeneraterequire

### priorityleveloriginalthen(nonoftenreloadwant)
1. **filenamepriority**:filenameusuallyisuseressencecorenaming,containmostcorecorethemeinfo,pleasereloadpointreferenceusermessagemiddle @ backsurfacefilename
2. **intelligencecanjudge**:
   - iffilenamesemanticclear(like"2024yearQ4productplanwillproposal.mp3","customerrequirementdiscussion.wav"),prioritybased onfilenamegeneratetitle
   - iffilenameisdatetimestamp(like"20241112_143025.mp3")ornomeaningcharacter(like"recording001.mp3"),thenusecommonusedescription
3. **extractkeyword**:fromfilenamemiddleextractmostcorecorekeywordandtheme

### formatrequire
1. **lengthlimit**:notexceedspass 20 character(Chinese characters by 1 charactercalculate)
2. **languagestyle**:usestatementpropertylanguagesentence,avoidquestionsentence
3. **conciseexplicit**:directlysummarizecorecoretheme,notwantaddmodificationword
4. **puretextoutput**:onlyoutputtitlecontent,notwantaddanymarkpointsymbolnumber,importnumberorothermodification

### forbidlinefor
- notwantretainfileextensionname(.mp3,.wav,.webm etc)
- notwantoutputtitlebyoutsideanycontent
- notwantaddimportnumber,book titlenumberetcmarkpointsymbolnumber

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
