<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Application\Chat\Service\MagicAgentEventAppService;
use App\Application\Chat\Service\MagicChatMessageAppService;
use App\Application\LongTermMemory\Enum\AppCodeEnum;
use App\Domain\Chat\DTO\Message\MagicMessageStruct;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Event\Agent\user CallAgentEvent;
use App\Domain\Chat\Service\MagicConversationDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Delightful\BeDelightful\Application\SuperAgent\DTO\user MessageDTO;
use Delightful\BeDelightful\Application\SuperAgent\Service\Handleuser MessageAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskMode;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * Super Agent Service. * * Responsible for publishing agent messages based on AI code processing */

class SuperAgentMessageSubscriberV2 extends MagicAgentEventAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected readonly Handleuser MessageAppService $handleuser MessageAppService, 
    protected readonly MagicChatMessageAppService $magicChatMessageAppService, 
    protected readonly LoggerFactory $loggerFactory, MagicConversationDomainService $magicConversationDomainService, ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); parent::__construct($magicConversationDomainService); 
}
 
    public function agentExecEvent(user CallAgentEvent $userCallAgentEvent) 
{
 // Determine if Super Magic needs to be called if ($userCallAgentEvent->agentAccountEntity->getAiCode() === AppCodeEnum::SUPER_MAGIC->value) 
{
 $this->handlerBeDelightfulMessage($userCallAgentEvent); 
}
 else 
{
 // process messages through normal agent handling parent::agentExecEvent($userCallAgentEvent); 
}
 
}
 
    private function handlerBeDelightfulMessage(user CallAgentEvent $userCallAgentEvent): void 
{
 try 
{
 $this->logger->info(sprintf( 'Received super agent message, event: %s', json_encode($userCallAgentEvent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); /** @var null|MagicMessageStruct $messageStruct */ $messageStruct = $userCallAgentEvent->messageEntity?->getContent(); if ($messageStruct instanceof TextContentInterface) 
{
 // May beTextneed process @ $prompt = $messageStruct->getTextContent(); $chatMessageType = $userCallAgentEvent->messageEntity?->getMessageType()->value; 
}
 else 
{
 $prompt = ''; $chatMessageType = ChatMessageType::Text->value; 
}
 // yes user @ed File/mcp/agent $superAgentExtra = $messageStruct->getExtra()?->getSuperAgent(); $mentions = $superAgentExtra?->getMentionsJsonStruct(); $queueId = $superAgentExtra?->getQueueId() ?? ''; // Extract necessary information $conversationId = $userCallAgentEvent->seqEntity->getConversationId() ?? ''; $chatTopicId = $userCallAgentEvent->seqEntity->getExtra()?->getTopicId() ?? ''; $organizationCode = $userCallAgentEvent->senderuser Entity->getOrganizationCode() ?? ''; $userId = $userCallAgentEvent->senderuser Entity->getuser Id() ?? ''; $agentuser Id = $userCallAgentEvent->agentuser Entity->getuser Id() ?? ''; $attachments = $userCallAgentEvent->messageEntity?->getContent()?->getAttachments() ?? []; $instructions = $userCallAgentEvent->messageEntity?->getContent()?->getInstructs() ?? []; $language = $userCallAgentEvent->messageEntity?->getLanguage() ?? ''; // Get user Seq id $useSeqEntity = $this->magicChatMessageAppService->getMagicSeqEntity($userCallAgentEvent->seqEntity->getMagicMessageId(), ConversationType::user ); if ($useSeqEntity) 
{
 $messageId = $messageSeqId = $useSeqEntity->getId(); 
}
 else 
{
 $messageId = $messageSeqId = (string) IdGenerator::getSnowId(); 
}
 // Parameter validation if (empty($conversationId) || empty($chatTopicId) || empty($organizationCode) || empty($userId) || empty($agentuser Id)) 
{
 $this->logger->error(sprintf( 'Incomplete message parameters, conversation_id: %s, topic_id: %s, organization_code: %s, user_id: %s, agent_user_id: %s', $conversationId, $chatTopicId, $organizationCode, $userId, $agentuser Id )); return; 
}
 // Create data isolation object $dataIsolation = DataIsolation::create($organizationCode, $userId); $dataIsolation->setLanguage($language); // Convert attachments array to JSON $attachmentsJson = ! empty($attachments) ? json_encode($attachments, JSON_UNESCAPED_UNICODE) : ''; // Convert mentions array to JSON if not null $mentionsJson = ! empty($mentions) ? json_encode($mentions, JSON_UNESCAPED_UNICODE) : null; // raw content $rawContent = $this->getRawContent($userCallAgentEvent); // Parse instruction information [$chatInstructs, $taskMode] = $this->parseInstructions($instructions); // Parse topic mode from super agent extra (support custom strings) $topicMode = $superAgentExtra?->getTopicPattern() ?? 'general'; // Extract dynamic params from super agent extra (if present) $dynamicParams = $superAgentExtra?->getDynamicParams(); // Create user message DTO $userMessageDTO = new user MessageDTO( agentuser Id: $agentuser Id, chatConversationId: $conversationId, chatTopicId: $chatTopicId, topicId: (int) $chatTopicId, prompt: $prompt, attachments: $attachmentsJson, mentions: $mentionsJson, instruction: $chatInstructs, topicMode: $topicMode, taskMode: $taskMode, rawContent: $rawContent, mcpConfig: [], modelId: $superAgentExtra?->getModelId() ?? '', language: $language, queueId: $queueId, messageId: $messageId, messageSeqId: $messageSeqId, chatMessageType: $chatMessageType, dynamicParams: $dynamicParams, extra: $superAgentExtra, ); if ($chatInstructs == ChatInstruction::Interrupted) 
{
 $this->handleuser MessageAppService->handleInternalMessage($dataIsolation, $userMessageDTO); 
}
 else 
{
 $this->handleuser MessageAppService->handleChatMessage($dataIsolation, $userMessageDTO); 
}
 $this->logger->info('Super agent message processing completed'); return; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'Failed to process super agent message: %s,file:%s,line:%s, event: %s,trace:%s', $e->getMessage(), $e->getFile(), $e->getLine(), json_encode($userCallAgentEvent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $e->getTraceAsString() )); return; // Acknowledge message even on error to avoid message accumulation 
}
 
}
 
    private function getRawContent(user CallAgentEvent $userCallAgentEvent): string 
{
 $seqObject = SeqAssembler::getClientSeqStruct($userCallAgentEvent->seqEntity, $userCallAgentEvent->messageEntity); try 
{
 $type = $seqObject->getSeq()->getMessage()->getType() ?? 'undefined'; $data = [ 'type' => $type, $type => $seqObject->getSeq()->getMessage()->getContent(), ]; return json_encode($data, JSON_UNESCAPED_UNICODE); 
}
 catch (Throwable $e) 
{
 return ''; 
}
 
}
 /** * Parse instructions, extract chat instruction and task mode. * * @param array $instructions Instruction array * @return array Returns [ChatInstruction, string taskMode] */ 
    private function parseInstructions(array $instructions): array 
{
 // Default values $chatInstructs = ChatInstruction::Normal; $taskMode = ''; if (empty($instructions)) 
{
 return [$chatInstructs, $taskMode]; 
}
 // check for matching chat instructions or task modes foreach ($instructions as $instruction) 
{
 $value = $instruction['value'] ?? ''; // First try to match chat instruction $tempChatInstruct = ChatInstruction::tryFrom($value); if ($tempChatInstruct !== null) 
{
 $chatInstructs = $tempChatInstruct; continue; // Continue looking for task mode after finding chat instruction 
}
 // Try to match task mode $tempTaskMode = TaskMode::tryFrom($value); if ($tempTaskMode !== null) 
{
 $taskMode = $tempTaskMode->value; break; // Can end loop after finding task mode 
}
 
}
 return [$chatInstructs, $taskMode]; 
}
 
}
 
