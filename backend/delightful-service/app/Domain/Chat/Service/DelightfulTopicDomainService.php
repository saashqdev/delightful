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
 * processmessagestream(seq)相关.
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
     * 客户端主动操作后,分发此操作给receive方.
     * 注意此时的message结构(each种idetc)all是发起方的value.
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
                    # 为对方create一个new话题
                    /** @var TopicCreateMessage $senderTopicCreateMessage */
                    $senderTopicCreateMessage = $senderSeqEntity->getContent();
                    $conversationId = $senderTopicCreateMessage->getConversationId();
                    // session双发的话题 id 保持一致
                    $topicId = $senderTopicCreateMessage->getId();
                    $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($conversationId);
                    if ($receiveConversationEntity === null) {
                        return null;
                    }
                    $receiveTopicDTO = new DelightfulTopicEntity();
                    $receiveTopicDTO->setTopicId($topicId);
                    $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
                    // query收件方的话题whether存in
                    $receiveTopicEntity = $this->delightfulChatTopicRepository->getTopicEntity($receiveTopicDTO);
                    // ifnot存in，为收件方create话题
                    if ($receiveTopicEntity === null) {
                        $receiveTopicEntity = $this->createReceiveTopic($topicId, senderConversationId: $conversationId);
                    }
                    break;
                case ControlMessageType::UpdateTopic:
                    // update对方的话题
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
                    // delete双方的话题
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
                // get收件方的 delightful_id
                $receiveUserId = $receiveConversationEntity->getUserId();
                $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveUserId);
                if (! $receiveUserEntity?->getDelightfulId()) {
                    return null;
                }
                $senderSeqEntity = SeqAssembler::generateTopicChangeSeqEntity($senderSeqEntity, $receiveTopicEntity, $receiveUserEntity);
                // 为收件方generate一个seq,告知收件方,话题have变动
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
     * 主动操作话题.
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
                // todo 话题namecreate时allow为空,后续 ai 总结话题name,push给客户端
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
        // 回写进控制message中,便at客户端process
        $contentChange = MessageAssembler::getControlMessageStruct($messageDTO->getMessageType(), $seqContent);
        $messageDTO->setContent($contentChange);
        $messageDTO->setMessageType($contentChange->getMessageTypeEnum());
        return $seqContent['conversation_id'] ?? '';
    }

    /**
     * according to收件方or者发件方的session id + 话题 id，为收件方create一个new话题.
     */
    public function createReceiveTopic(string $topicId, string $senderConversationId = '', string $receiveConversationId = ''): ?DelightfulTopicEntity
    {
        // 为messagereceive方create话题
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
        // 为收件方create一个new话题
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
     * agent sendmessage时get话题 id.
     * @param int $getType todo 0:default话题 1:most近的话题 2:智能确定话题，暂时只supportdefault话题 3 新增话题
     * @throws Throwable
     */
    public function agentSendMessageGetTopicId(DelightfulConversationEntity $senderConversationEntity, int $getType): string
    {
        $receiverConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($senderConversationEntity->getId());
        // 为收件方createsession，but是notagain触发 ConversationCreatedEvent event，避免event循环
        if (($receiverConversationEntity === null) && in_array($senderConversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            $conversationDTO = new DelightfulConversationEntity();
            $conversationDTO->setUserId($senderConversationEntity->getReceiveId());
            $conversationDTO->setReceiveId($senderConversationEntity->getUserId());
            # createsession窗口
            $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
            # 准备generate一个session窗口
            $receiverConversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);
        }
        $senderTopicId = $this->checkDefaultTopicExist($senderConversationEntity);
        $receiverTopicId = $this->checkDefaultTopicExist($receiverConversationEntity);
        $defaultTopicId = $senderTopicId;
        // if $getType 为新增话题，thendefaultcreate话题，而not是default话题
        if ($getType === 3) {
            $senderTopicId = '';
        }
        // 收发双方只要have一个的default话题not存in,or者notin同一个default话题，thenneedcreate
        if (empty($senderTopicId) || empty($receiverTopicId) || $senderTopicId !== $receiverTopicId) {
            Db::beginTransaction();
            try {
                // 为收发双方meanwhilecreate一个default话题
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
        // 判断话题id所属的sessionidwhether是currentuser的
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
        // 判断havenothavedefault话题的tag
        $topicId = $conversationEntity->getExtra()?->getDefaultTopicId();
        if (empty($topicId)) {
            return null;
        }
        // 判断default话题be删了nothave
        $topicEntities = $this->delightfulChatTopicRepository->getTopicsByConversationId($conversationEntity->getId(), [$topicId]);
        return ($topicEntities[0] ?? null)?->getTopicId();
    }

    /**
     * create并updatedefault话题.
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
        // 将default话题id回写进session窗口
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
