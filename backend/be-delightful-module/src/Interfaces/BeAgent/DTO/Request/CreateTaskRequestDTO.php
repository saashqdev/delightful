<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use Hyperf\Validation\Request\FormRequest;

class CreateTaskRequestDTO extends FormRequest
{
    /**
     * @var string Chat topic ID
     */
    protected string $chatTopicId = '';

    /**
     * @var string Task prompt
     */
    protected string $prompt = '';

    /**
     * @var null|string Attachment information (JSON format)
     */
    protected ?string $attachments = null;

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'chat_topic_id' => 'required|string',
            'prompt' => 'required|string',
            'attachments' => 'nullable|string',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'chat_topic_id' => 'Chat topic ID',
            'prompt' => 'Prompt',
            'attachments' => 'Attachment information',
        ];
    }

    public function getChatTopicId(): string
    {
        return $this->chatTopicId;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getAttachments(): ?string
    {
        return $this->attachments;
    }

    /**
     * Prepare data.
     */
    protected function prepareForValidation(): void
    {
        $this->chatTopicId = (string) $this->input('chat_topic_id', '');
        $this->prompt = (string) $this->input('prompt', '');
        $this->attachments = $this->has('attachments') ? (string) $this->input('attachments') : null;
    }
}
