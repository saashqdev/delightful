<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Domain\Chat\DTO\Agent\SenderExtraDTO;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\MagicMessageEntity;
use App\Domain\Chat\Entity\MagicSeqEntity;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\Magicuser Entity;
use App\Infrastructure\Core\AbstractEvent;
use Hyperf\Codec\Json;
/** * check whether need long-term memory Event. */

class check LongTermMemoryEvent extends AbstractEvent 
{
 
    public function __construct( 
    public AccountEntity $agentAccountEntity, 
    public Magicuser Entity $agentuser Entity, 
    public AccountEntity $senderAccountEntity, 
    public Magicuser Entity $senderuser Entity, 
    public MagicSeqEntity $seqEntity, public ?MagicMessageEntity $messageEntity, 
    public SenderExtraDTO $senderExtraDTO, ) 
{
 
}
 
    public function getOrganizationCode(): string 
{
 return $this->senderuser Entity->getOrganizationCode(); 
}
 
    public function getuser Id(): string 
{
 return $this->senderuser Entity->getuser Id(); 
}
 
    public function getAgentuser Id(): string 
{
 return $this->agentuser Entity->getuser Id(); 
}
 
    public function getConversationId(): string 
{
 return $this->seqEntity->getConversationId() ?? ''; 
}
 
    public function getChatTopicId(): string 
{
 return $this->seqEntity->getExtra()?->getTopicId() ?? ''; 
}
 
    public function getPrompt(): string 
{
 $messageStruct = $this->messageEntity?->getContent(); if ($messageStruct instanceof TextContentInterface) 
{
 return $messageStruct->getTextContent(); 
}
 return ''; 
}
 
    public function getAttachments(): string 
{
 $attachments = $this->messageEntity?->getContent()?->getAttachments() ?? []; return ! empty($attachments) ? Json::encode($attachments) : ''; 
}
 
    public function getInstructions(): array 
{
 return $this->messageEntity?->getContent()?->getInstructs() ?? []; 
}
 
    public function getMentions(): ?string 
{
 // ImplementationComplexTypecheck return null; 
}
 
    public function getRawContent(): ?string 
{
 // original ContentBuildneed ComplexReturn basic info return Json::encode([ 'seq_id' => $this->seqEntity->getSeqId(), 'message_id' => $this->seqEntity->getMessageId(), 'conversation_id' => $this->seqEntity->getConversationId(), ]); 
}
 /** * GetEventIDUsingseq_idas EventUnique identifier. */ 
    public function getEventId(): string 
{
 return $this->seqEntity->getSeqId(); 
}
 /** * Convert toArrayFormat. * * @return array EventDataArray */ 
    public function toArray(): array 
{
 return [ 'seq_id' => $this->seqEntity->getSeqId(), 'organization_code' => $this->getOrganizationCode(), 'user_id' => $this->getuser Id(), 'agent_user_id' => $this->getAgentuser Id(), 'conversation_id' => $this->getConversationId(), 'chat_topic_id' => $this->getChatTopicId(), 'prompt' => $this->getPrompt(), 'attachments' => $this->getAttachments(), 'instructions' => $this->getInstructions(), 'mentions' => $this->getMentions(), 'raw_content' => $this->getRawContent(), ]; 
}
 
}
 
