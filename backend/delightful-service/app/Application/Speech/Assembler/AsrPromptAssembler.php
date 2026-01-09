<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use App\Application\Speech\DTO\NoteDTO;

/**
 * ASRhint词装配器
 * 负责buildASR相关的hint词template.
 */
class AsrPromptAssembler
{
    /**
     * generate录音总结title的hint词.
     *
     * @param string $asrStreamContent voice识别content
     * @param null|NoteDTO $note 笔记content（optional）
     * @param string $language output语言（如：zh_CN, en_US）
     * @return string 完整的hint词
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // buildcontent：use XML tagformat明确区minutevoice识别content和笔记content
        $contentParts = [];

        // ifhave笔记，先添加笔记content
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<笔记content>%s</笔记content>', $note->content);
        }

        // 添加voice识别content
        $contentParts[] = sprintf('<voice识别content>%s</voice识别content>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
你是一专业的录音contenttitlegenerate助hand。

## 背景instruction
usersubmit了一segment录音content，录音content经过voice识别转为文字，user可能alsowill提供hand写的笔记作为补充instruction。现inneed你according to这些contentgenerate一简洁准确的title。

## content来源instruction
- <笔记content>：userhand写的笔记content，是对录音的重pointrecord和总结，usuallycontain关键info
- <voice识别content>：passvoice识别技术将录音convertbecome的文字，反映录音的actualcontent

## titlegenerate要求

### 优先level原then（重要）
1. **笔记优先**：if存in<笔记content>，titleshould侧重笔记content
2. **重视笔记title**：if笔记是 Markdown formatandcontaintitle（# 开head的line），优先采use笔记middle的titlecontent
3. **综合考虑**：meanwhile参考voice识别content，ensuretitle完整准确
4. **关键词提取**：from笔记和voice识别contentmiddle提取most核core的关键词

### format要求
1. **length限制**：not超过 20 字符（汉字按 1 字符计算）
2. **语言style**：use陈述property语sentence，避免疑问sentence
3. **简洁明确**：直接概括核coretheme，not要添加修饰词
4. **纯文本output**：只outputtitlecontent，not要添加任何标point符number、引numberor其他修饰

### forbidline为
- not要回答contentmiddle的issue
- not要conduct额outside解释
- not要添加"录音"、"笔记"etcfront缀词
- not要outputtitlebyoutside的任何content

## 录音content
{textContent}

## output语言
请use {language} 语言outputtitle。

## output
请直接outputtitle：
PROMPT;

        return str_replace(['{textContent}', '{language}'], [$textContent, $language], $template);
    }

    /**
     * generatefileupload场景的录音titlehint词（强调file名的重要property）.
     *
     * @param string $userRequestMessage userinchat框send的requestmessage
     * @param string $language output语言（如：zh_CN, en_US）
     * @return string 完整的hint词
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
你是一专业的录音contenttitlegenerate助hand。

## 背景instruction
userupload了一audiofileto系统middle，并inchat框middlesend了总结request。现inneed你according touser的requestmessage（其middlecontainfile名），为这time录音总结generate一简洁准确的title。

## userinchat框的request
usersend的originalmessage如down：
```
{userRequestMessage}
```

## titlegenerate要求

### 优先level原then（non常重要）
1. **file名优先**：file名usually是user精core命名的，contain了most核core的themeinfo，请重point参考usermessagemiddle @ backsurface的file名
2. **智能判断**：
   - iffile名语义清晰（如"2024yearQ4product规划will议.mp3"、"客户需求discussion.wav"），优先based onfile名generatetitle
   - iffile名是datetime戳（如"20241112_143025.mp3"）or无意义字符（如"录音001.mp3"），thenuse通usedescription
3. **提取关键词**：fromfile名middle提取most核core的关键词和theme

### format要求
1. **length限制**：not超过 20 字符（汉字按 1 字符计算）
2. **语言style**：use陈述property语sentence，避免疑问sentence
3. **简洁明确**：直接概括核coretheme，not要添加修饰词
4. **纯文本output**：只outputtitlecontent，not要添加任何标point符number、引numberor其他修饰

### forbidline为
- not要保留fileextension名（.mp3、.wav、.webm etc）
- not要outputtitlebyoutside的任何content
- not要添加引number、书名numberetc标point符number

## output语言
请use {language} 语言outputtitle。

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
