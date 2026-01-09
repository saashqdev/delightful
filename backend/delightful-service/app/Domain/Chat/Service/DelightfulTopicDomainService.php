<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\Message\ControlMessage\TopicCreateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicDeleteMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicUpdateMessage;
use App\Domain\Chat\Entity\Items\ConversationExtra;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Throwable;

use function Hyperf\Translation\__;

/**
 * processmessagestream(seq)相close.
 */
class DelightfulTopicDomainService extends AbstractDomainService
{
    public function getDelightfulApiAccessToken(string $modelName)
    {
        $delightfulFlowAIModelEntity = $this->delightfulFlowAIModelRepository->getByName(FlowDataIsolation::create(), $modelName);
        if ($delightfulFlowAIModelEntity === null) {
            return '';
        }
        return $delightfulFlowAIModelEntity->getActualImplementationConfig()['access_token'] ?? '';
    }

    /**
     * customer端主动操asback,minutehair此操asgivereceive方.
     * 注意此o clockmessage结构(eachtypeidetc)allishairup方value.
     * @throws Throwable
     */
    public function dispatchMQTopicOperation(DelightfulSeqEntity $senderSeqEntity): ?DelightfulSeqEntity
    {
        Db::beginTransaction();
        try {
            $controlMessageType = $senderSeqEntity->getSeqType();
            $receiveTopicEntity = null;
            $receiveConversationEntity = null;
            switch ($controlMessageType) {
                case ControlMessageType::CreateTopic:
                    # forto方createonenew话题
                    /** @var TopicCreateMessage $senderTopicCreateMessage */
                    $senderTopicCreateMessage = $senderSeqEntity->getContent();
                    $conversationId = $senderTopicCreateMessage->getConversationId();
                    // sessiondoublehair话题 id 保持one致
                    $topicId = $senderTopicCreateMessage->getId();
                    $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($conversationId);
                    if ($receiveConversationEntity === null) {
                        return null;
                    }
                    $receiveTopicDTO = new DelightfulTopicEntity();
                    $receiveTopicDTO->setTopicId($topicId);
                    $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
                    // query收item方话题whether存in
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->getTopicEntity($receiveTopicDTO);
                    // ifnot存in，for收item方create话题
                    if ($receiveTopicEntity === null) {
                        $receiveTopicEntity = $this->createReceiveTopic($topicId, senderConversationId: $conversationId);
                    }
                    break;
                case ControlMessageType::UpdateTopic:
                    // updateto方话题
                    /** @var TopicUpdateMessage $senderTopicUpdateMessage */
                    $senderTopicUpdateMessage = $senderSeqEntity->getContent();
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->getPrivateChatReceiveTopicEntity(
                        $senderTopicUpdateMessage->getId(),
                        $senderTopicUpdateMessage->getConversationId()
                    );
                    if ($receiveTopicEntity === null) {
                        return null;
                    }
                    $receiveTopicEntity->setName($senderTopicUpdateMessage->getName());
                    $receiveTopicEntity->setDescription($senderTopicUpdateMessage->getDescription());
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->updateTopic($receiveTopicEntity);
                    break;
                case ControlMessageType::DeleteTopic:
                    // deletedouble方话题
                    /** @var TopicDeleteMessage $senderTopicDeleteMessage */
                    $senderTopicDeleteMessage = $senderSeqEntity->getContent();
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->getPrivateChatReceiveTopicEntity(
                        $senderTopicDeleteMessage->getId(),
                        $senderTopicDeleteMessage->getConversationId()
                    );
                    if ($receiveTopicEntity === null) {
                        return null;
                    }
                    $this->delightfulChatTopicRepository->deleteTopic($receiveTopicEntity);
                    break;
                default:
                    break;
            }
            if ($receiveTopicEntity && $receiveConversationEntity === null) {
                $receiveConversationEntity = $this->delightfulConversationRepository->getConversationById($receiveTopicEntity->getConversationId());
            }
            if ($receiveTopicEntity && $receiveConversationEntity) {
                // get收item方 delightful_id
                $receiveUserId = $receiveConversationEntity->getUserId();
                $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveUserId);
                if (! $receiveUserEntity?->getDelightfulId()) {
                    return null;
                }
                $senderSeqEntity = SeqAssembler::generateTopicChangeSeqEntity($senderSeqEntity, $receiveTopicEntity, $receiveUserEntity);
                // for收item方generateoneseq,告知收item方,话题have变动
                $receiveSeqEntity = $this->delightfulSeqRepository->createSequence($senderSeqEntity->toArray());
            }
            return $receiveSeqEntity ?? null;
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        } finally {
            if (! isset($exception)) {
                Db::commit();
            }
        }
    }

    /**
     * 主动操as话题.
     * @return string sessionid
     * @throws Throwable
     */
    public function clientOperateTopic(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): string
    {
        $messageTypeEnum = $messageDTO->getMessageType();
        if (! in_array(
            $messageTypeEnum,
            [
                ControlMessageType::CreateTopic,
                ControlMessageType::UpdateTopic,
                ControlMessageType::DeleteTopic,
            ],
            true
        )) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }
        $seqContent = [];
        switch ($messageTypeEnum) {
            case ControlMessageType::CreateTopic:
                /** @var TopicCreateMessage $messageStruct */
                $messageStruct = $messageDTO->getContent();
                // 判断sessionwhether存in,whether属atcurrentuser
                $this->checkAndGetSelfConversation($messageStruct->getConversationId(), $dataIsolation);
                // todo 话题namecreateo clockallowforempty,back续 ai 总结话题name,pushgivecustomer端
                $topicEntity = $this->userCreateTopicHandler($messageStruct, $dataIsolation);
                break;
            case ControlMessageType::UpdateTopic:
                /** @var TopicUpdateMessage $messageStruct */
                $messageStruct = $messageDTO->getContent();
                $topicEntity = $this->updateTopic($messageStruct, $dataIsolation);
                break;
            case ControlMessageType::DeleteTopic:
                /** @var TopicDeleteMessage $messageStruct */
                $messageStruct = $messageDTO->getContent();
                $topicDTO = new DelightfulTopicEntity();
                $topicDTO->setTopicId($messageStruct->getId());
                $topicDTO->setConversationId($messageStruct->getConversationId());
                $this->checkTopicBelong($topicDTO, $dataIsolation);
                $this->delightfulChatTopicRepository->deleteTopic($topicDTO);
                $seqContent = [
                    'id' => $messageStruct->getId(),
                    'conversation_id' => $messageStruct->getConversationId(),
                ];
                break;
            default:
                break;
        }
        if (isset($topicEntity)) {
            $seqContent = [
                'conversation_id' => $topicEntity->getConversationId(),
                'description' => $topicEntity->getDescription(),
                'id' => $topicEntity->getTopicId(),
                'name' => $topicEntity->getName(),
            ];
        }
        // return写enter控制messagemiddle,便atcustomer端process
        $contentChange = MessageAssembler::getControlMessageStruct($messageDTO->getMessageType(), $seqContent);
        $messageDTO->setContent($contentChange);
        $messageDTO->setMessageType($contentChange->getMessageTypeEnum());
        return $seqContent['conversation_id'] ?? '';
    }

    /**
     * according to收item方or者hairitem方session id + 话题 id，for收item方createonenew话题.
     */
    public function createReceiveTopic(string $topicId, string $senderConversationId = '', string $receiveConversationId = ''): ?DelightfulTopicEntity
    {
        // formessagereceive方create话题
        if ($senderConversationId) {
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($senderConversationId);
        }
        if ($receiveConversationId) {
            $receiveConversationEntity = $this->delightfulConversationRepository->getConversationById($receiveConversationId);
        }
        if (! isset($receiveConversationEntity)) {
            return null;
        }
        $receiveTopicDTO = new DelightfulTopicEntity();
        $receiveTopicDTO->setTopicId($topicId);
        $receiveTopicDTO->setName('');
        $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
        $receiveTopicDTO->setOrganizationCode($receiveConversationEntity->getUserOrganizationCode());
        $receiveTopicDTO->setDescription('');
        // for收item方createonenew话题
        return $this->delightfulChatTopicRepository->createTopic($receiveTopicDTO);
    }

    // update话题
    public function updateTopic(TopicUpdateMessage $messageStruct, DataIsolation $dataIsolation): DelightfulTopicEntity
    {
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $topicDTO->setTopicId($messageStruct->getId());
        $topicDTO->setConversationId($messageStruct->getConversationId());
        $topicDTO->setName($messageStruct->getName());
        $topicDTO->setDescription($messageStruct->getDescription());
        $this->checkTopicBelong($topicDTO, $dataIsolation);
        return $this->delightfulChatTopicRepository->updateTopic($topicDTO);
    }

    /**
     * agent sendmessageo clockget话题 id.
     * @param int $getType todo 0:default话题 1:most近话题 2:智能确定话题，暂o clock只supportdefault话题 3 new话题
     * @throws Throwable
     */
    public function agentSendMessageGetTopicId(DelightfulConversationEntity $senderConversationEntity, int $getType): string
    {
        $receiverConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($senderConversationEntity->getId());
        // for收item方createsession，butisnotagain触hair ConversationCreatedEvent event，避免eventloop
        if (($receiverConversationEntity === null) && in_array($senderConversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            $conversationDTO = new DelightfulConversationEntity();
            $conversationDTO->setUserId($senderConversationEntity->getReceiveId());
            $conversationDTO->setReceiveId($senderConversationEntity->getUserId());
            # createsessionwindow
            $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
            # 准备generateonesessionwindow
            $receiverConversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);
        }
        $senderTopicId = $this->checkDefaultTopicExist($senderConversationEntity);
        $receiverTopicId = $this->checkDefaultTopicExist($receiverConversationEntity);
        $defaultTopicId = $senderTopicId;
        // if $getType fornew话题，thendefaultcreate话题，whilenotisdefault话题
        if ($getType === 3) {
            $senderTopicId = '';
        }
        // 收hairdouble方as long ashaveonedefault话题not存in,or者notin同onedefault话题，thenneedcreate
        if (empty($senderTopicId) || empty($receiverTopicId) || $senderTopicId !== $receiverTopicId) {
            Db::beginTransaction();
            try {
                // for收hairdouble方meanwhilecreateonedefault话题
                $defaultTopicId = (string) IdGenerator::getSnowId();
                $this->createAndUpdateDefaultTopic($senderConversationEntity, $defaultTopicId);
                $this->createAndUpdateDefaultTopic($receiverConversationEntity, $defaultTopicId);
                Db::commit();
            } catch (Throwable $e) {
                Db::rollBack();
                throw $e;
            }
        }
        return $defaultTopicId;
    }

    private function checkTopicBelong(DelightfulTopicEntity $topicDTO, DataIsolation $dataIsolation): void
    {
        // 判断话题id所属sessionidwhetheriscurrentuser
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }
        $this->checkAndGetSelfConversation($topicEntity->getConversationId(), $dataIsolation);
    }

    /**
     * checkdefault话题whether存in.
     */
    private function checkDefaultTopicExist(DelightfulConversationEntity $conversationEntity): ?string
    {
        // 判断havenothavedefault话题tag
        $topicId = $conversationEntity->getExtra()?->getDefaultTopicId();
        if (empty($topicId)) {
            return null;
        }
        // 判断default话题be删nothave
        $topicEntities = $this->delightfulChatTopicRepository->getTopicsByConversationId($conversationEntity->getId(), [$topicId]);
        return ($topicEntities[0] ?? null)?->getTopicId();
    }

    /**
     * createandupdatedefault话题.
     */
    private function createAndUpdateDefaultTopic(DelightfulConversationEntity $conversationEntity, string $defaultTopicId): void
    {
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setConversationId($conversationEntity->getId());
        $topicDTO->setTopicId($defaultTopicId);
        $topicDTO->setOrganizationCode($conversationEntity->getUserOrganizationCode());
        $topicDTO->setName(__('chat.topic.system_default_topic'));
        $topicDTO->setDescription('');
        $this->delightfulChatTopicRepository->createTopic($topicDTO);
        // willdefault话题idreturn写entersessionwindow
        $senderConversationExtra = $conversationEntity->getExtra();
        if ($senderConversationExtra === null) {
            $senderConversationExtra = new ConversationExtra();
        }
        $senderConversationExtra->setDefaultTopicId($defaultTopicId);
        $this->delightfulConversationRepository->updateConversationById($conversationEntity->getId(), [
            'extra' => Json::encode($senderConversationExtra->toArray()),
        ]);
    }
}
