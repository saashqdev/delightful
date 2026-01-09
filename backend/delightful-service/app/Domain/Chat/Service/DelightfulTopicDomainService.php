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
     * customerclientactiveoperationasback,minutehairthisoperationasgivereceiveside.
     * noticethiso clockmessagestructure(eachtypeidetc)allishairupsidevalue.
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
                    # fortosidecreateonenewtopic
                    /** @var TopicCreateMessage $senderTopicCreateMessage */
                    $senderTopicCreateMessage = $senderSeqEntity->getContent();
                    $conversationId = $senderTopicCreateMessage->getConversationId();
                    // sessiondoublehairtopic id maintainoneto
                    $topicId = $senderTopicCreateMessage->getId();
                    $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($conversationId);
                    if ($receiveConversationEntity === null) {
                        return null;
                    }
                    $receiveTopicDTO = new DelightfulTopicEntity();
                    $receiveTopicDTO->setTopicId($topicId);
                    $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
                    // queryreceiveitemsidetopicwhetherexistsin
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->getTopicEntity($receiveTopicDTO);
                    // ifnotexistsin,forreceiveitemsidecreatetopic
                    if ($receiveTopicEntity === null) {
                        $receiveTopicEntity = $this->createReceiveTopic($topicId, senderConversationId: $conversationId);
                    }
                    break;
                case ControlMessageType::UpdateTopic:
                    // updatetosidetopic
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
                    // deletedoublesidetopic
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
                // getreceiveitemside delightful_id
                $receiveUserId = $receiveConversationEntity->getUserId();
                $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveUserId);
                if (! $receiveUserEntity?->getDelightfulId()) {
                    return null;
                }
                $senderSeqEntity = SeqAssembler::generateTopicChangeSeqEntity($senderSeqEntity, $receiveTopicEntity, $receiveUserEntity);
                // forreceiveitemsidegenerateoneseq,informreceiveitemside,topichavechange
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
     * activeoperationastopic.
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
                // judgesessionwhetherexistsin,whetherbelongatcurrentuser
                $this->checkAndGetSelfConversation($messageStruct->getConversationId(), $dataIsolation);
                // todo topicnamecreateo clockallowforempty,back続 ai summarytopicname,pushgivecustomerclient
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
        // return写entercontrolmessagemiddle,便atcustomerclientprocess
        $contentChange = MessageAssembler::getControlMessageStruct($messageDTO->getMessageType(), $seqContent);
        $messageDTO->setContent($contentChange);
        $messageDTO->setMessageType($contentChange->getMessageTypeEnum());
        return $seqContent['conversation_id'] ?? '';
    }

    /**
     * according toreceiveitemsideorpersonhairitemsidesession id + topic id,forreceiveitemsidecreateonenewtopic.
     */
    public function createReceiveTopic(string $topicId, string $senderConversationId = '', string $receiveConversationId = ''): ?DelightfulTopicEntity
    {
        // formessagereceivesidecreatetopic
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
        // forreceiveitemsidecreateonenewtopic
        return $this->delightfulChatTopicRepository->createTopic($receiveTopicDTO);
    }

    // updatetopic
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
     * agent sendmessageo clockgettopic id.
     * @param int $getType todo 0:defaulttopic 1:most近topic 2:智cancertaintopic,暫o clockonlysupportdefaulttopic 3 newtopic
     * @throws Throwable
     */
    public function agentSendMessageGetTopicId(DelightfulConversationEntity $senderConversationEntity, int $getType): string
    {
        $receiverConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($senderConversationEntity->getId());
        // forreceiveitemsidecreatesession,butisnotagaintouchhair ConversationCreatedEvent event,avoideventloop
        if (($receiverConversationEntity === null) && in_array($senderConversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            $conversationDTO = new DelightfulConversationEntity();
            $conversationDTO->setUserId($senderConversationEntity->getReceiveId());
            $conversationDTO->setReceiveId($senderConversationEntity->getUserId());
            # createsessionwindow
            $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
            # preparegenerateonesessionwindow
            $receiverConversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);
        }
        $senderTopicId = $this->checkDefaultTopicExist($senderConversationEntity);
        $receiverTopicId = $this->checkDefaultTopicExist($receiverConversationEntity);
        $defaultTopicId = $senderTopicId;
        // if $getType fornewtopic,thendefaultcreatetopic,whilenotisdefaulttopic
        if ($getType === 3) {
            $senderTopicId = '';
        }
        // receivehairdoublesideas long ashaveonedefaulttopicnotexistsin,orpersonnotinsameonedefaulttopic,thenneedcreate
        if (empty($senderTopicId) || empty($receiverTopicId) || $senderTopicId !== $receiverTopicId) {
            Db::beginTransaction();
            try {
                // forreceivehairdoublesidemeanwhilecreateonedefaulttopic
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
        // judgetopicidbelong tosessionidwhetheriscurrentuser
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }
        $this->checkAndGetSelfConversation($topicEntity->getConversationId(), $dataIsolation);
    }

    /**
     * checkdefaulttopicwhetherexistsin.
     */
    private function checkDefaultTopicExist(DelightfulConversationEntity $conversationEntity): ?string
    {
        // judgehavenothavedefaulttopictag
        $topicId = $conversationEntity->getExtra()?->getDefaultTopicId();
        if (empty($topicId)) {
            return null;
        }
        // judgedefaulttopicbe删nothave
        $topicEntities = $this->delightfulChatTopicRepository->getTopicsByConversationId($conversationEntity->getId(), [$topicId]);
        return ($topicEntities[0] ?? null)?->getTopicId();
    }

    /**
     * createandupdatedefaulttopic.
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
        // willdefaulttopicidreturn写entersessionwindow
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
