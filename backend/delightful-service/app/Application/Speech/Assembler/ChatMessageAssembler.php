<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Assembler;

use App\Application\Speech\DTO\ProcessSummaryTaskDTO;
use App\Application\Speech\DTO\Response\AsrFileDataDTO;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Codec\Json;
use Hyperf\Contract\TranslatorInterface;

/**
 * 聊天message装配器
 * 负责构建ASR总结相关的聊天message.
 */
readonly class ChatMessageAssembler
{
    public function __construct(
    ) {
    }

    /**
     * 构建聊天请求object用于总结task
     *
     * @param ProcessSummaryTaskDTO $dto 处理总结taskDTO
     * @param AsrFileDataDTO $audioFileData 音频文件数据
     * @param null|AsrFileDataDTO $noteFileData 笔记文件数据，可选
     * @return ChatRequest 聊天请求object
     */
    public function buildSummaryMessage(ProcessSummaryTaskDTO $dto, AsrFileDataDTO $audioFileData, ?AsrFileDataDTO $noteFileData = null): ChatRequest
    {
        // 在协程环境中，使用 di() get translator 实例以确保协程上下文正确
        $translator = di(TranslatorInterface::class);
        $translator->setLocale(CoContext::getLanguage());
        // 构建messagecontent
        $messageContent = $this->buildMessageContent($dto->modelId, $audioFileData, $noteFileData);

        // 构建聊天请求数据
        $chatRequestData = [
            'context' => [
                'language' => $translator->getLocale(),
            ],
            'data' => [
                'conversation_id' => $dto->conversationId,
                'message' => [
                    'type' => 'rich_text',
                    'app_message_id' => (string) IdGenerator::getSnowId(),
                    'send_time' => time() * 1000,
                    'topic_id' => $dto->chatTopicId,
                    'rich_text' => $messageContent,
                ],
            ],
        ];
        return new ChatRequest($chatRequestData);
    }

    /**
     * 构建rich_textmessagecontent.
     *
     * @param string $modelId modelID
     * @param AsrFileDataDTO $fileData 文件数据
     * @param null|AsrFileDataDTO $noteData 笔记文件数据，可选
     * @return array messagecontentarray
     */
    public function buildMessageContent(string $modelId, AsrFileDataDTO $fileData, ?AsrFileDataDTO $noteData = null): array
    {
        // 在协程环境中，使用 di() get translator 实例以确保协程上下文正确
        $translator = di(TranslatorInterface::class);
        $translator->setLocale(CoContext::getLanguage());
        // 构建messagecontent
        if ($noteData !== null && ! empty($noteData->fileName) && ! empty($noteData->filePath)) {
            // 有笔记时的messagecontent：同时提到录音文件和笔记文件

            $messageContent = [
                [
                    'type' => 'text',
                    'text' => $translator->trans('asr.messages.summary_prefix_with_note'),
                ],
                [
                    'type' => 'mention',
                    'attrs' => [
                        'id' => null,
                        'label' => null,
                        'mentionSuggestionChar' => '@',
                        'type' => 'project_file',
                        'data' => $fileData->toArray(),
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $translator->trans('asr.messages.summary_middle_with_note'),
                ],
                [
                    'type' => 'mention',
                    'attrs' => [
                        'id' => null,
                        'label' => null,
                        'mentionSuggestionChar' => '@',
                        'type' => 'project_file',
                        'data' => $noteData->toArray(),
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $translator->trans('asr.messages.summary_suffix_with_note'),
                ],
            ];
        } else {
            // 无笔记时的messagecontent：只提到录音文件
            $messageContent = [
                [
                    'type' => 'text',
                    'text' => $translator->trans('asr.messages.summary_prefix'),
                ],
                [
                    'type' => 'mention',
                    'attrs' => [
                        'id' => null,
                        'label' => null,
                        'mentionSuggestionChar' => '@',
                        'type' => 'project_file',
                        'data' => $fileData->toArray(),
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => $translator->trans('asr.messages.summary_suffix'),
                ],
            ];
        }

        return [
            'content' => Json::encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'attrs' => ['suggestion' => ''],
                        'content' => $messageContent,
                    ],
                ],
            ]),
            'instructs' => [
                ['value' => 'plan'],
            ],
            'attachments' => [],
            'extra' => [
                'super_agent' => [
                    'mentions' => $noteData !== null && ! empty($noteData->fileName) && ! empty($noteData->filePath) ? [
                        [
                            'type' => 'mention',
                            'attrs' => [
                                'type' => 'project_file',
                                'data' => $fileData->toArray(),
                            ],
                        ],
                        [
                            'type' => 'mention',
                            'attrs' => [
                                'type' => 'project_file',
                                'data' => $noteData->toArray(),
                            ],
                        ],
                    ] : [
                        [
                            'type' => 'mention',
                            'attrs' => [
                                'type' => 'project_file',
                                'data' => $fileData->toArray(),
                            ],
                        ],
                    ],
                    'input_mode' => 'plan',
                    'chat_mode' => 'normal',
                    'topic_pattern' => 'summary',
                    'model' => [
                        'model_id' => $modelId,
                    ],
                    'dynamic_params' => [
                        'summary_task' => true,
                    ],
                ],
            ],
        ];
    }
}
