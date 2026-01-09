<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Chat;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use HyperfTest\Cases\BaseTest;
use Throwable;

/**
 * @internal
 */
class DelightfulMessageTest extends BaseTest
{
    /**
     * testaito群withinhairmessage.
     * @throws Throwable
     */
    public function testAgentSendMessage()
    {
        $appMessageId = IdGenerator::getUniqueId32();
        $receiveSeqDTO = new DelightfulSeqEntity();
        $messageContent = new TextMessage();
        $messageContent->setContent('testmessage');
        $receiveSeqDTO->setContent($messageContent);
        $receiveSeqDTO->setSeqType(ChatMessageType::Text);
        $receiveSeqDTO->setReferMessageId('');
        $groupId = '732608035268567040';
        $aiUserId = 'usi_054efed931890913cf7c0acfdc9e5831';
        di(DelightfulChatMessageAppService::class)->agentSendMessage($receiveSeqDTO, $aiUserId, $groupId, $appMessageId, receiverType: ConversationType::Group);
        $this->assertTrue(true);
    }

    /**
     * test模拟usergiveagent hairmessage.
     * @throws Throwable
     */
    public function testUserSendMessageToAgent()
    {
        $appMessageId = IdGenerator::getUniqueId32();
        $receiveSeqDTO = new DelightfulSeqEntity();
        $messageContent = new TextMessage();
        $messageContent->setContent('testmessage123123123213');
        $receiveSeqDTO->setContent($messageContent);
        $receiveSeqDTO->setSeqType(ChatMessageType::Text);
        $receiveSeqDTO->setReferMessageId('');
        $senderUserId = 'usi_3715ce50bc02d7e72ba7891649b7f1da';
        $receiveUserId = 'usi_155aa8a654422fae6672e5c9faf1f48e';
        di(DelightfulChatMessageAppService::class)->userSendMessageToAgent($receiveSeqDTO, $senderUserId, $receiveUserId, $appMessageId, receiverType: ConversationType::Ai);
        $this->assertTrue(true);
    }

    /*
    * test ai streammessagesend.
     * @throws Throwable
     */
    public function testAgentStreamSendMessage()
    {
        $receiveUserId = 'usi_7839078ce6af2d3249b82e7aaed643b8';
        $aiUserId = 'usi_8e4bde5582491a6cabfe0d0ba8b7ae8e';
        $chatAppService = di(DelightfulChatMessageAppService::class);
        // will多segmentstreammessage，passthis id associateupcome
        // ai searchcardmessage多segmentresponse，已经will app_message_id asforassociate id，streamresponseneed另outside id come做associate
        $appMessageId = IdGenerator::getUniqueId32();
        $streamOptions = (new StreamOptions())->setStream(true)->setStreamAppMessageId($appMessageId)->setStatus(StreamMessageStatus::Start);
        $messageContent = new TextMessage();
        $messageContent->setContent('hello world');
        $messageContent->setStreamOptions($streamOptions);
        $receiveSeqDTO = (new DelightfulSeqEntity())
            ->setSeqType(ChatMessageType::Text)
            ->setReferMessageId('')
            ->setContent($messageContent);
        $chatAppService->agentSendMessage($receiveSeqDTO, $aiUserId, $receiveUserId, $appMessageId, receiverType: ConversationType::User);
        for ($i = 0; $i < 2; ++$i) {
            $streamOptions->setStatus(StreamMessageStatus::Processing);
            $messageContent->setStreamOptions($streamOptions);
            $messageContent->setContent((string) $i);
            $receiveSeqDTO->setContent($messageContent);
            $chatAppService->agentSendMessage($receiveSeqDTO, $aiUserId, $receiveUserId, $appMessageId, receiverType: ConversationType::User);
        }
        // sendend
        $streamOptions->setStatus(StreamMessageStatus::Completed);
        $messageContent->setContent('end');
        $messageContent->setStreamOptions($streamOptions);
        $receiveSeqDTO->setContent($messageContent);
        $chatAppService->agentSendMessage($receiveSeqDTO, $aiUserId, $receiveUserId, $appMessageId, receiverType: ConversationType::User);
        $this->assertTrue(true);
    }
}
