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
        // buildcontent：use XML tagformat明确区分voice识别content和笔记content
        $contentParts = [];

        // ifhave笔记，先添加笔记content
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<笔记content>%s</笔记content>', $note->content);
        }

        // 添加voice识别content
        $contentParts[] = sprintf('<voice识别content>%s</voice识别content>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
你是一个专业的录音contenttitlegenerate助手。

## 背景instruction
usersubmit了一段录音content，录音content经过voice识别转为文字，user可能alsowill提供手写的笔记作为补充instruction。现inneed你according to这些contentgenerate一个简洁准确的title。

## content来源instruction
- <笔记content>：user手写的笔记content，是对录音的重点record和总结，usuallycontain关键info
- <voice识别content>：passvoice识别技术将录音convert成的文字，反映录音的actualcontent

## titlegenerate要求

### 优先级原then（重要）
1. **笔记优先**：if存in<笔记content>，titleshould侧重笔记content
2. **重视笔记title**：if笔记是 Markdown formatandcontaintitle（# 开头的行），优先采use笔记中的titlecontent
3. **综合考虑**：meanwhile参考voice识别content，ensuretitle完整准确
4. **关键词提取**：from笔记和voice识别content中提取most核心的关键词

### format要求
1. **length限制**：not超过 20 个字符（汉字按 1 个字符计算）
2. **语言style**：use陈述性语句，避免疑问句
3. **简洁明确**：直接概括核心theme，not要添加修饰词
4. **纯文本output**：只outputtitlecontent，not要添加任何标点符号、引号or其他修饰

### forbid行为
- not要回答content中的issue
- not要conduct额外解释
- not要添加"录音"、"笔记"etc前缀词
- not要outputtitleby外的任何content

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
     * generatefileupload场景的录音titlehint词（强调file名的重要性）.
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
你是一个专业的录音contenttitlegenerate助手。

## 背景instruction
userupload了一个audiofileto系统中，并inchat框中send了总结request。现inneed你according touser的requestmessage（其中containfile名），为这次录音总结generate一个简洁准确的title。

## userinchat框的request
usersend的originalmessage如下：
```
{userRequestMessage}
```

## titlegenerate要求

### 优先级原then（non常重要）
1. **file名优先**：file名usually是user精心命名的，contain了most核心的themeinfo，请重点参考usermessage中 @ 后面的file名
2. **智能判断**：
   - iffile名语义清晰（如"2024年Q4product规划will议.mp3"、"客户需求discussion.wav"），优先based onfile名generatetitle
   - iffile名是datetime戳（如"20241112_143025.mp3"）or无意义字符（如"录音001.mp3"），thenuse通usedescription
3. **提取关键词**：fromfile名中提取most核心的关键词和theme

### format要求
1. **length限制**：not超过 20 个字符（汉字按 1 个字符计算）
2. **语言style**：use陈述性语句，避免疑问句
3. **简洁明确**：直接概括核心theme，not要添加修饰词
4. **纯文本output**：只outputtitlecontent，not要添加任何标点符号、引号or其他修饰

### forbid行为
- not要保留fileextension名（.mp3、.wav、.webm etc）
- not要outputtitleby外的任何content
- not要添加引号、书名号etc标点符号

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
