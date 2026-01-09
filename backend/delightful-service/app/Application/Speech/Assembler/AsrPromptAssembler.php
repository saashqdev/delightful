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
     * @return string 完整hint词
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // buildcontent：use XML tagformat明确区minutevoice识别contentand笔记content
        $contentParts = [];

        // ifhave笔记，先add笔记content
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<笔记content>%s</笔记content>', $note->content);
        }

        // addvoice识别content
        $contentParts[] = sprintf('<voice识别content>%s</voice识别content>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
你is一专业录音contenttitlegenerate助hand。

## backgroundinstruction
usersubmit一segment录音content，录音content经passvoice识别转fortext，usermaybealsowill提供hand写笔记asfor补充instruction。现inneed你according to这些contentgenerate一简洁准确title。

## contentcome源instruction
- <笔记content>：userhand写笔记content，isto录音重pointrecordand总结，usuallycontainclose键info
- <voice识别content>：passvoice识别技术will录音convertbecometext，反映录音actualcontent

## titlegenerate要求

### 优先level原then（重要）
1. **笔记优先**：if存in<笔记content>，titleshould侧重笔记content
2. **重视笔记title**：if笔记is Markdown formatandcontaintitle（# openheadline），优先采use笔记middletitlecontent
3. **综合考虑**：meanwhile参考voice识别content，ensuretitle完整准确
4. **keywordextract**：from笔记andvoice识别contentmiddleextractmost核corekeyword

### format要求
1. **length限制**：not超pass 20 character（汉字按 1 character计算）
2. **languagestyle**：use陈述property语sentence，避免疑问sentence
3. **简洁明确**：直接概括核coretheme，not要addmodification词
4. **纯textoutput**：只outputtitlecontent，not要add任何标point符number、引numberor其他modification

### forbidlinefor
- not要return答contentmiddleissue
- not要conduct额outside解释
- not要add"录音"、"笔记"etcfront缀词
- not要outputtitlebyoutside任何content

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
     * generatefileupload场景录音titlehint词（emphasizefile名重要property）.
     *
     * @param string $userRequestMessage userinchat框sendrequestmessage
     * @param string $language outputlanguage（如：zh_CN, en_US）
     * @return string 完整hint词
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
你is一专业录音contenttitlegenerate助hand。

## backgroundinstruction
userupload一audiofiletosystemmiddle，andinchat框middlesend总结request。现inneed你according touserrequestmessage（其middlecontainfile名），for这time录音总结generate一简洁准确title。

## userinchat框request
usersendoriginalmessage如down：
```
{userRequestMessage}
```

## titlegenerate要求

### 优先level原then（non常重要）
1. **file名优先**：file名usuallyisuser精core命名，containmost核corethemeinfo，请重point参考usermessagemiddle @ backsurfacefile名
2. **智能判断**：
   - iffile名语义清晰（如"2024yearQ4product规划will议.mp3"、"customer需求discussion.wav"），优先based onfile名generatetitle
   - iffile名isdatetime戳（如"20241112_143025.mp3"）or无意义character（如"录音001.mp3"），thenuse通usedescription
3. **extractkeyword**：fromfile名middleextractmost核corekeywordandtheme

### format要求
1. **length限制**：not超pass 20 character（汉字按 1 character计算）
2. **languagestyle**：use陈述property语sentence，避免疑问sentence
3. **简洁明确**：直接概括核coretheme，not要addmodification词
4. **纯textoutput**：只outputtitlecontent，not要add任何标point符number、引numberor其他modification

### forbidlinefor
- not要保留fileextension名（.mp3、.wav、.webm etc）
- not要outputtitlebyoutside任何content
- not要add引number、书名numberetc标point符number

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
