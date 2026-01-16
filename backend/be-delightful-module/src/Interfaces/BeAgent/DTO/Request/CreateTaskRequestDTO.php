<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use Hyperf\Validate \Request\FormRequest;

class CreateTaskRequestDTO extends FormRequest 
{
 /** * @var string topic ID */ 
    protected string $chatTopicId = ''; /** * @var string TaskNotice */ 
    protected string $prompt = ''; /** * @var null|string info JSONFormat */ protected ?string $attachments = null; /** * Validate Rule. */ 
    public function rules(): array 
{
 return [ 'chat_topic_id' => 'required|string', 'prompt' => 'required|string', 'attachments' => 'nullable|string', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'chat_topic_id' => 'topic ID', 'prompt' => 'Notice', 'attachments' => 'info ', ]; 
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
 /** * Data. */ 
    protected function prepareForValidate (): void 
{
 $this->chatTopicId = (string) $this->input('chat_topic_id', ''); $this->prompt = (string) $this->input('prompt', ''); $this->attachments = $this->has('attachments') ? (string) $this->input('attachments') : null; 
}
 
}
 
