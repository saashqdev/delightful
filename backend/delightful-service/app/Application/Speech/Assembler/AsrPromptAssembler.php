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
     * generate录音总结titlehint词.
     *
     * @param string $asrStreamContent voice识别content
     * @param null|NoteDTO $note 笔记content（optional）
     * @param string $language outputlanguage（如：zh_CN, en_US）
     * @return string completehint词
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // buildcontent：use XML tagformatexplicit区minutevoice识别contentand笔记content
        $contentParts = [];

        // ifhave笔记，先add笔记content
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<笔记content>%s</笔记content>', $note->content);
        }

        // addvoice识别content
        $contentParts[] = sprintf('<voice识别content>%s</voice识别content>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
你isone专业录音contenttitlegenerate助hand。

## backgroundinstruction
usersubmitonesegment录音content，录音content经passvoice识别转fortext，usermaybealsowill提供hand写笔记asfor补充instruction。现inneed你according tothisthesecontentgenerateone简洁accuratetitle。

## contentcome源instruction
- <笔记content>：userhand写笔记content，isto录音重pointrecordand总结，usuallycontainclosekeyinfo
- <voice识别content>：passvoice识别技术will录音convertbecometext，反映录音actualcontent

## titlegeneraterequire

### 优先level原then（重want）
1. **笔记优先**：if存in<笔记content>，titleshould侧重笔记content
2. **重视笔记title**：if笔记is Markdown formatandcontaintitle（# openheadline），优先采use笔记middletitlecontent
3. **综合考虑**：meanwhile参考voice识别content，ensuretitlecompleteaccurate
4. **keywordextract**：from笔记andvoice识别contentmiddleextractmost核corekeyword

### formatrequire
1. **lengthlimit**：not超pass 20 character（汉字按 1 charactercalculate）
2. **languagestyle**：use陈述property语sentence，avoid疑问sentence
3. **简洁explicit**：直接概括核coretheme，notwantaddmodification词
4. **纯textoutput**：只outputtitlecontent，notwantaddany标point符number、引numberorothermodification

### forbidlinefor
- notwantreturn答contentmiddleissue
- notwantconduct额outside解释
- notwantadd"录音"、"笔记"etcfront缀词
- notwantoutputtitlebyoutsideanycontent

## 录音content
{textContent}

## outputlanguage
请use {language} languageoutputtitle。

## output
请直接outputtitle：
PROMPT;

        return str_replace(['{textContent}', '{language}'], [$textContent, $language], $template);
    }

    /**
     * generatefileupload场景录音titlehint词（emphasizefile名重wantproperty）.
     *
     * @param string $userRequestMessage userinchat框sendrequestmessage
     * @param string $language outputlanguage（如：zh_CN, en_US）
     * @return string completehint词
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
你isone专业录音contenttitlegenerate助hand。

## backgroundinstruction
useruploadoneaudiofiletosystemmiddle，andinchat框middlesend总结request。现inneed你according touserrequestmessage（itsmiddlecontainfile名），forthistime录音总结generateone简洁accuratetitle。

## userinchat框request
usersendoriginalmessage如down：
```
{userRequestMessage}
```

## titlegeneraterequire

### 优先level原then（non常重want）
1. **file名优先**：file名usuallyisuser精core命名，containmost核corethemeinfo，请重point参考usermessagemiddle @ backsurfacefile名
2. **智can判断**：
   - iffile名语义清晰（如"2024yearQ4product规划will议.mp3"、"customer需求discussion.wav"），优先based onfile名generatetitle
   - iffile名isdatetime戳（如"20241112_143025.mp3"）ornomeaningcharacter（如"录音001.mp3"），thenuse通usedescription
3. **extractkeyword**：fromfile名middleextractmost核corekeywordandtheme

### formatrequire
1. **lengthlimit**：not超pass 20 character（汉字按 1 charactercalculate）
2. **languagestyle**：use陈述property语sentence，avoid疑问sentence
3. **简洁explicit**：直接概括核coretheme，notwantaddmodification词
4. **纯textoutput**：只outputtitlecontent，notwantaddany标point符number、引numberorothermodification

### forbidlinefor
- notwant保留fileextension名（.mp3、.wav、.webm etc）
- notwantoutputtitlebyoutsideanycontent
- notwantadd引number、书名numberetc标point符number

## outputlanguage
请use {language} languageoutputtitle。

## output
请直接outputtitle：
PROMPT;

        return str_replace(
            ['{userRequestMessage}', '{language}'],
            [$userRequestMessage, $language],
            $template
        );
    }
}
