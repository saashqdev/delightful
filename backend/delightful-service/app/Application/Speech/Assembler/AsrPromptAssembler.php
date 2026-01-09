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
     * generate录音总结标题的hint词.
     *
     * @param string $asrStreamContent voice识别内容
     * @param null|NoteDTO $note 笔记内容（可选）
     * @param string $language 输出语言（如：zh_CN, en_US）
     * @return string 完整的hint词
     */
    public static function getTitlePrompt(string $asrStreamContent, ?NoteDTO $note, string $language): string
    {
        // build内容：use XML tagformat明确区分voice识别内容和笔记内容
        $contentParts = [];

        // 如果有笔记，先添加笔记内容
        if ($note !== null && $note->hasContent()) {
            $contentParts[] = sprintf('<笔记内容>%s</笔记内容>', $note->content);
        }

        // 添加voice识别内容
        $contentParts[] = sprintf('<voice识别内容>%s</voice识别内容>', $asrStreamContent);

        $textContent = implode("\n\n", $contentParts);

        $template = <<<'PROMPT'
你是一个专业的录音内容标题generate助手。

## 背景说明
usersubmit了一段录音内容，录音内容经过voice识别转为文字，user可能还will提供手写的笔记作为补充说明。现在need你according to这些内容generate一个简洁准确的标题。

## 内容来源说明
- <笔记内容>：user手写的笔记内容，是对录音的重点记录和总结，通常contain关键info
- <voice识别内容>：passvoice识别技术将录音convert成的文字，反映录音的actual内容

## 标题generate要求

### 优先级原则（重要）
1. **笔记优先**：如果存在<笔记内容>，标题should侧重笔记内容
2. **重视笔记标题**：如果笔记是 Markdown format且contain标题（# 开头的行），优先采用笔记中的标题内容
3. **综合考虑**：同时参考voice识别内容，ensure标题完整准确
4. **关键词提取**：从笔记和voice识别内容中提取最核心的关键词

### format要求
1. **length限制**：不超过 20 个字符（汉字按 1 个字符计算）
2. **语言风格**：use陈述性语句，避免疑问句
3. **简洁明确**：直接概括核心theme，不要添加修饰词
4. **纯文本输出**：只输出标题内容，不要添加任何标点符号、引号或其他修饰

### forbid行为
- 不要回答内容中的问题
- 不要进行额外解释
- 不要添加"录音"、"笔记"等前缀词
- 不要输出标题以外的任何内容

## 录音内容
{textContent}

## 输出语言
请use {language} 语言输出标题。

## 输出
请直接输出标题：
PROMPT;

        return str_replace(['{textContent}', '{language}'], [$textContent, $language], $template);
    }

    /**
     * generatefileupload场景的录音标题hint词（强调file名的重要性）.
     *
     * @param string $userRequestMessage user在chat框send的requestmessage
     * @param string $language 输出语言（如：zh_CN, en_US）
     * @return string 完整的hint词
     */
    public static function getTitlePromptForUploadedFile(
        string $userRequestMessage,
        string $language
    ): string {
        $template = <<<'PROMPT'
你是一个专业的录音内容标题generate助手。

## 背景说明
userupload了一个audiofile到系统中，并在chat框中send了总结request。现在need你according touser的requestmessage（其中containfile名），为这次录音总结generate一个简洁准确的标题。

## user在chat框的request
usersend的originalmessage如下：
```
{userRequestMessage}
```

## 标题generate要求

### 优先级原则（非常重要）
1. **file名优先**：file名通常是user精心命名的，contain了最核心的themeinfo，请重点参考usermessage中 @ 后面的file名
2. **智能判断**：
   - 如果file名语义清晰（如"2024年Q4产品规划will议.mp3"、"客户需求discussion.wav"），优先based onfile名generate标题
   - 如果file名是日期时间戳（如"20241112_143025.mp3"）或无意义字符（如"录音001.mp3"），则use通用描述
3. **提取关键词**：从file名中提取最核心的关键词和theme

### format要求
1. **length限制**：不超过 20 个字符（汉字按 1 个字符计算）
2. **语言风格**：use陈述性语句，避免疑问句
3. **简洁明确**：直接概括核心theme，不要添加修饰词
4. **纯文本输出**：只输出标题内容，不要添加任何标点符号、引号或其他修饰

### forbid行为
- 不要保留fileextension名（.mp3、.wav、.webm 等）
- 不要输出标题以外的任何内容
- 不要添加引号、书名号等标点符号

## 输出语言
请use {language} 语言输出标题。

## 输出
请直接输出标题：
PROMPT;

        return str_replace(
            ['{userRequestMessage}', '{language}'],
            [$userRequestMessage, $language],
            $template
        );
    }
}
