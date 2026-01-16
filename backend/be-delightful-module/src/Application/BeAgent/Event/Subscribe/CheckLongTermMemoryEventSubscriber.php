<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Application\Chat\Service\MagicChatMessageAppService;
use App\Application\LongTermMemory\DTO\EvaluateConversationRequestDTO;
use App\Application\LongTermMemory\Enum\AppCodeEnum;
use App\Application\LongTermMemory\Service\LongTermMemoryAppService as MagicServiceLongTermMemoryAppService;
use App\Application\ModelGateway\Service\ModelConfigAppService;
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Event\check LongTermMemoryEvent;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * long-term memory check Eventlist ener * process long-term memory check EventConcrete. */

class check LongTermMemoryEventSubscriber implements list enerInterface 
{
 /** * list en to events. * * @return array Array of event classes to listen to */ 
    public function listen(): array 
{
 return [ check LongTermMemoryEvent::class, ]; 
}
 /** * process the event. * * @param object $event Event object */ 
    public function process(object $event): void 
{
 // Type check if (! $event instanceof check LongTermMemoryEvent) 
{
 return; 
}
 try 
{
 $this->getLogger()->info('Start processing long-term memory check event', [ 'event_id' => $event->getEventId(), 'organization_code' => $event->getOrganizationCode(), 'user_id' => $event->getuser Id(), 'conversation_id' => $event->getConversationId(), 'chat_topic_id' => $event->getChatTopicId(), 'prompt_length' => mb_strlen($event->getPrompt()), 'has_attachments' => ! empty($event->getAttachments()), 'instructions_count' => count($event->getInstructions()), ]); // directly FromEventin Get conversationId $conversationId = $event->getConversationId(); if (empty($conversationId)) 
{
 $this->getLogger()->warning('conversation_id in event is empty', [ 'chat_topic_id' => $event->getChatTopicId(), 'event_id' => $event->getEventId(), ]); return; 
}
 // BuildAuthorizeObject $authorization = $this->createuser Authorization($event->getOrganizationCode(), $event->getuser Id()); // GetHistoryMessage $historyMessages = $this->getConversationHistory($authorization, $conversationId, $event->getChatTopicId()); // Buildcomplete PairContent $conversationContent = $this->buildConversationContentWithHistory($event, $historyMessages); // ThroughGetModelName $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain( $event->getOrganizationCode(), LLMModelEnum::DEEPSEEK_V3->value ); // CreateRequestDTO $dto = new EvaluateConversationRequestDTO([ 'modelName' => $modelName, 'conversationContent' => $conversationContent, 'appId' => AppCodeEnum::SUPER_MAGIC->value, ]); // call magic-service long-term memory Service $this->getLongTermMemoryApp()->evaluateAndCreateMemory($dto, $authorization); 
}
 catch (Throwable $e) 
{
 $this->getLogger()->error('Exception occurred when processing long-term memory check event', [ 'event_id' => $event->getEventId(), 'error' => $e->getMessage(), 'organization_code' => $event->getOrganizationCode(), 'user_id' => $event->getuser Id(), 'conversation_id' => $event->getConversationId(), 'chat_topic_id' => $event->getChatTopicId(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 /** * Getlong-term memory ApplyService */ 
    private function getLongTermMemoryApp(): MagicServiceLongTermMemoryAppService 
{
 return \Hyperf\Support\make(MagicServiceLongTermMemoryAppService::class); 
}
 /** * GetMessageApplyService */ 
    private function getMagicChatMessageApp(): MagicChatMessageAppService 
{
 return \Hyperf\Support\make(MagicChatMessageAppService::class); 
}
 /** * GetLog. */ 
    private function getLogger(): LoggerInterface 
{
 return \Hyperf\Support\make(LoggerFactory::class)->get(static::class); 
}
 /** * Createuser AuthorizeObject */ 
    private function createuser Authorization(string $organizationCode, string $userId): Magicuser Authorization 
{
 $authorization = new Magicuser Authorization(); $authorization->setId($userId); $authorization->setOrganizationCode($organizationCode); $authorization->setApplicationCode(AppCodeEnum::SUPER_MAGIC->value); return $authorization; 
}
 /** * GetSessionHistoryMessage. */ 
    private function getConversationHistory(Magicuser Authorization $authorization, string $conversationId, string $topicId): array 
{
 try 
{
 return $this->getMagicChatMessageApp()->getConversationChatCompletionsHistory( $authorization, $conversationId, 50, // Getmost recently 50Message $topicId, false // Using role Formatuser/assistantIs notuser ); 
}
 catch (Throwable $e) 
{
 $this->getLogger()->error('GetSessionHistoryMessageFailed', [ 'conversation_id' => $conversationId, 'topic_id' => $topicId, 'error' => $e->getMessage(), ]); return []; 
}
 
}
 /** * Buildincluding HistoryMessagePairContent. */ 
    private function buildConversationContentWithHistory(check LongTermMemoryEvent $event, array $historyMessages): string 
{
 $content = []; // AddHistoryMessage if (! empty($historyMessages)) 
{
 $content[] = '=== History Dialog ==='; foreach ($historyMessages as $message) 
{
 if (is_array($message) && isset($message['role'], $message['content'])) 
{
 $content[] = $message['role'] . ': ' . $message['content']; 
}
 
}
 $content[] = === History DialogEnd ===\n ; 
}
 // Addcurrent user Message $content[] = '=== current Message ==='; $content[] = user Message: 
{
$event->getPrompt()
}
 ; // Addinfo IfHave if (! empty($event->getMentions())) 
{
 $mentionsData = json_decode($event->getMentions(), true); if (is_array($mentionsData) && ! empty($mentionsData)) 
{
 $content[] = 'Mentions: ' . json_encode($mentionsData, JSON_UNESCAPED_UNICODE); 
}
 
}
 return implode( \n , $content); 
}
 
}
 
