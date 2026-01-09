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
 * chatmessageassembler
 * responsiblebuildASRsummaryrelatedclosechatmessage.
 */
readonly class ChatMessageAssembler
{
    public function __construct(
    ) {
    }

    /**
     * buildchatrequestobjectuseatsummarytask
     *
     * @param ProcessSummaryTaskDTO $dto processsummarytaskDTO
     * @param AsrFileDataDTO $audioFileData audiofiledata
     * @param null|AsrFileDataDTO $noteFileData notefiledata,optional
     * @return ChatRequest chatrequestobject
     */
    public function buildSummaryMessage(ProcessSummaryTaskDTO $dto, AsrFileDataDTO $audioFileData, ?AsrFileDataDTO $noteFileData = null): ChatRequest
    {
        // incoroutineenvironmentmiddle,use di() get translator instancebyensurecoroutineupdowntextcorrect
        $translator = di(TranslatorInterface::class);
        $translator->setLocale(CoContext::getLanguage());
        // buildmessagecontent
        $messageContent = $this->buildMessageContent($dto->modelId, $audioFileData, $noteFileData);

        // buildchatrequestdata
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
     * buildrich_textmessagecontent.
     *
     * @param string $modelId modelID
     * @param AsrFileDataDTO $fileData filedata
     * @param null|AsrFileDataDTO $noteData notefiledata,optional
     * @return array messagecontentarray
     */
    public function buildMessageContent(string $modelId, AsrFileDataDTO $fileData, ?AsrFileDataDTO $noteData = null): array
    {
        // incoroutineenvironmentmiddle,use di() get translator instancebyensurecoroutineupdowntextcorrect
        $translator = di(TranslatorInterface::class);
        $translator->setLocale(CoContext::getLanguage());
        // buildmessagecontent
        if ($noteData !== null && ! empty($noteData->fileName) && ! empty($noteData->filePath)) {
            // havenoteo clockmessagecontent:meanwhilesubmittorecordingfileandnotefile

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
            // nonoteo clockmessagecontent:onlysubmittorecordingfile
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
