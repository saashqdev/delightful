<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service;

use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatEvent;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatFactory;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatMessage;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformCreateGroup;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionType;
use App\Application\Flow\ExecuteManager\ExecutionData\TriggerData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Application\Kernel\EnvManager;
use App\Application\Kernel\SuperPermissionEnum;
use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulBotThirdPlatformChatQuery;
use App\Domain\Agent\Entity\ValueObject\ThirdPlatformChat\ThirdPlatformChatType;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\ConversationId;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Service\DelightfulFlowMemoryHistoryDomainService;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Context\CoContext;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use DateTime;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Nyholm\Psr7\Response;
use Qbhy\HyperfAuth\Authenticatable;
use Throwable;

class DelightfulBotThirdPlatformChatAppService extends AbstractAppService
{
    public function chat(string $key, array $params): ThirdPlatformChatMessage
    {
        if (empty($key)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.empty', ['label' => 'key']);
        }

        // 特殊逻辑，ifis飞书，andandischallenge
        $platform = $params['platform'] ?? '';
        if ($platform === ThirdPlatformChatType::FeiShuRobot->value && isset($params['challenge'])) {
            $chatMessage = new ThirdPlatformChatMessage();
            $chatMessage->setEvent(ThirdPlatformChatEvent::CheckServer);
            $response = new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['challenge' => $params['challenge']], JSON_UNESCAPED_UNICODE)
            );
            $chatMessage->setResponse($response);
            return $chatMessage;
        }

        $chatEntity = $this->delightfulBotThirdPlatformChatDomainService->getByKey($key);
        if (! $chatEntity || ! $chatEntity->isEnabled()) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.invalid', ['label' => $key]);
        }
        $dataIsolation = FlowDataIsolation::create('', '')->setEnabled(false);
        $delightfulFlow = $this->getFlowByBotId($dataIsolation, $chatEntity->getBotId());
        $dataIsolation->setCurrentOrganizationCode($delightfulFlow->getOrganizationCode());

        $thirdPlatformChat = ThirdPlatformChatFactory::make($chatEntity);
        $params['delightful_system'] = [
            'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
        ];
        $thirdPlatformChatMessage = $thirdPlatformChat->parseChatParam($params);
        switch ($thirdPlatformChatMessage->getEvent()) {
            case ThirdPlatformChatEvent::None:
                break;
            case ThirdPlatformChatEvent::CheckServer:
                return $thirdPlatformChatMessage;
            case ThirdPlatformChatEvent::ChatMessage:
                $thirdPlatformChatMessage->validate();
                $fromCoroutineId = Coroutine::id();
                Coroutine::defer(function () use ($dataIsolation, $delightfulFlow, $thirdPlatformChat, $thirdPlatformChatMessage, $chatEntity, $fromCoroutineId) {
                    CoContext::copy($fromCoroutineId);
                    try {
                        $originConversationId = $thirdPlatformChatMessage->getConversationId();
                        $conversationId = ConversationId::ThirdBotChat->gen($chatEntity->getType()->getConversationPrefix() . '-' . $originConversationId);

                        if ($thirdPlatformChatMessage->getMessage() === '/clear_memory') {
                            $this->clearMemory($conversationId);
                            $message = new TextMessage(['content' => $thirdPlatformChatMessage->getMessage() . ' success']);
                            $thirdPlatformChat->sendMessage($thirdPlatformChatMessage, $message);
                            return;
                        }

                        // thiswithiniseach平台user id，notis delightful  user_id
                        $userId = $thirdPlatformChatMessage->getUserId();
                        $dataIsolation->setCurrentUserId($userId);
                        EnvManager::initDataIsolationEnv($dataIsolation);

                        $operator = $this->createExecutionOperator($dataIsolation);
                        $operator->setNickname($thirdPlatformChatMessage->getNickname());
                        $operator->setSourceId($chatEntity->getType()->value);

                        $message = new TextMessage(['content' => $thirdPlatformChatMessage->getMessage()]);
                        $triggerData = new TriggerData(
                            triggerTime: new DateTime(),
                            userInfo: ['user_entity' => TriggerData::createUserEntity($operator->getUid(), $operator->getNickname())],
                            messageInfo: ['message_entity' => TriggerData::createMessageEntity($message)],
                            globalVariable: $delightfulFlow->getGlobalVariable(),
                            attachments: $thirdPlatformChatMessage->getAttachments(),
                            triggerDataUserExtInfo: $thirdPlatformChatMessage->getUserExtInfo(),
                        );

                        $executionData = new ExecutionData(
                            flowDataIsolation: $dataIsolation,
                            operator: $operator,
                            triggerType: TriggerType::ChatMessage,
                            triggerData: $triggerData,
                            conversationId: $conversationId,
                            originConversationId: $originConversationId,
                            executionType: ExecutionType::SKApi,
                        );
                        $executor = new DelightfulFlowExecutor($delightfulFlow, $executionData);
                        $executor->execute();

                        foreach ($executionData->getReplyMessages() as $message) {
                            if ($message->getIMMessage()) {
                                $message->replaceAttachmentUrl(true);
                                $thirdPlatformChat->sendMessage($thirdPlatformChatMessage, $message->getIMMessage());
                            }
                        }
                    } catch (Throwable $exception) {
                        simple_logger('DelightfulBotThirdPlatformChatAppService')->notice('ChatError', [
                            'exception' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'code' => $exception->getCode(),
                            'trace' => $exception->getTraceAsString(),
                        ]);
                        $message = new TextMessage(['content' => 'not好meaning，meanwhile问我issuepersontoo多啦，havepoint忙notpasscome，你canonewill儿againcome问我？感谢谅解！']);
                        $thirdPlatformChat->sendMessage($thirdPlatformChatMessage, $message);
                    }
                });
                break;
        }
        return $thirdPlatformChatMessage;
    }

    public function save(Authenticatable $authorization, DelightfulBotThirdPlatformChatEntity $entity): DelightfulBotThirdPlatformChatEntity
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::ASSISTANT_ADMIN);
        $entity->setAllUpdate(true);
        $entity = $this->delightfulBotThirdPlatformChatDomainService->save($entity);
        ThirdPlatformChatFactory::remove((string) $entity->getId());
        return $entity;
    }

    public function destroy(Authenticatable $authorization, string $id): void
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::ASSISTANT_ADMIN);
        $entity = $this->delightfulBotThirdPlatformChatDomainService->getById((int) $id);
        if ($entity) {
            $this->delightfulBotThirdPlatformChatDomainService->destroy($entity);
            ThirdPlatformChatFactory::remove($id);
        }
    }

    /**
     * @return array{total: int, list: DelightfulBotThirdPlatformChatEntity[]}
     */
    public function listByBotId(Authenticatable $authorization, string $botId, Page $page): array
    {
        if (empty($botId)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.empty', ['label' => 'bot_id']);
        }
        $permissionDataIsolation = $this->createPermissionDataIsolation($authorization);
        $this->getAgentOperation($permissionDataIsolation, $botId)->validate('r', $botId);
        $query = new DelightfulBotThirdPlatformChatQuery();
        $query->setBotId($botId);
        return $this->delightfulBotThirdPlatformChatDomainService->queries($query, $page);
    }

    /**
     * @return array{total: int, list: DelightfulBotThirdPlatformChatEntity[]}
     */
    public function queries(Authenticatable $authorization, DelightfulBotThirdPlatformChatQuery $query, Page $page): array
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::ASSISTANT_ADMIN);
        return $this->delightfulBotThirdPlatformChatDomainService->queries($query, $page);
    }

    public function createChatGroup(string $key, array $groupMemberIds, DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): string
    {
        // get助理configuration
        if (empty($key)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.empty', ['label' => 'key']);
        }
        $chatEntity = $this->delightfulBotThirdPlatformChatDomainService->getByKey($key);
        if (! $chatEntity || ! $chatEntity->isEnabled()) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.invalid', ['label' => $key]);
        }
        // pass $groupMemberIds getuserinfo，canuserlist
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $users = $this->delightfulUserDomainService->getUserByIds($groupMemberIds, $dataIsolation, ['delightful_id', 'nickname']);
        if (count($users) === 0) {
            ExceptionBuilder::throw(AgentErrorCode::CREATE_GROUP_USER_NOT_EXIST, 'user.not_exist', ['user_ids' => $groupMemberIds]);
        }
        $delightfulIds = array_column($users, 'delightful_id');
        /** @var array<string, AccountEntity> $accounts */
        $accounts = $this->delightfulAccountDomainService->getAccountByDelightfulIds($delightfulIds);
        if (count($accounts) === 0) {
            ExceptionBuilder::throw(AgentErrorCode::CREATE_GROUP_USER_ACCOUNT_NOT_EXIST, 'user.not_exist', ['delightful_ids' => $delightfulIds]);
        }
        // callinterface，换取thethree方user id
        $parallel = new Parallel(2);
        $thirdPlatformChat = ThirdPlatformChatFactory::make($chatEntity);
        $requestId = CoContext::getRequestId();
        foreach ($accounts as $account) {
            $parallel->add(function () use ($requestId, $thirdPlatformChat, $account) {
                CoContext::setRequestId($requestId);
                return ['delightful_id' => $account->getDelightfulId(), 'third_user_id' => $thirdPlatformChat->getThirdPlatformUserIdByMobiles($account->getPhone())];
            });
        }
        $thirdPlatformUserIds = [];
        $ownerThirdPlatformUserId = '';
        $result = $parallel->wait();
        // two位array转becomeone维
        foreach ($result as $item) {
            if ($item['delightful_id'] == $userAuthorization->getDelightfulId()) {
                $ownerThirdPlatformUserId = $item['third_user_id'];
            }
            $thirdPlatformUserIds[] = $item['third_user_id'];
        }
        if (count($thirdPlatformUserIds) == 0) {
            ExceptionBuilder::throw(AgentErrorCode::GET_THIRD_PLATFORM_USER_ID_FAILED, 'user.not_exist', ['delightful_ids' => $delightfulIds]);
        }

        // creategroup chat
        $createGroupParams = new ThirdPlatformCreateGroup();
        $createGroupParams->setName($delightfulGroupDTO->getGroupName());
        $createGroupParams->setOwner($ownerThirdPlatformUserId);
        $createGroupParams->setUseridlist($thirdPlatformUserIds);
        $createGroupParams->setShowHistoryType(1);
        $createGroupParams->setSearchable(0);
        $createGroupParams->setValidationType(0);
        $createGroupParams->setMentionAllAuthority(0);
        $createGroupParams->setManagementType(0);
        $createGroupParams->setChatBannedType(0);

        return $thirdPlatformChat->createGroup($createGroupParams);
    }

    private function clearMemory(string $conversationId): void
    {
        // cleanup flow from身记忆，仅more改原sessionforbackupsession
        di(DelightfulFlowMemoryHistoryDomainService::class)->removeByConversationId(
            FlowDataIsolation::create('', ''),
            $conversationId
        );
    }

    private function getFlowByBotId(FlowDataIsolation $dataIsolation, string $botId): DelightfulFlowEntity
    {
        $bot = $this->delightfulAgentDomainService->getAgentById($botId);
        if (! $bot->isAvailable()) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.invalid', ['label' => 'bot_id']);
        }
        if ($bot->getAgentVersionId()) {
            $botVersion = $this->delightfulAgentVersionDomainService->getById($bot->getAgentVersionId());
            $flowVersion = $this->delightfulFlowVersionDomainService->show($dataIsolation, $bot->getFlowCode(), $botVersion->getFlowVersion());
            $delightfulFlow = $flowVersion->getDelightfulFlow();
            $delightfulFlow->setVersionCode($flowVersion->getCode());
        } else {
            $delightfulFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $bot->getFlowCode());
        }
        $delightfulFlow->setAgentId((string) $bot->getId());

        // usecurrentprocessorganizationencoding
        $dataIsolation->setCurrentOrganizationCode($delightfulFlow->getOrganizationCode());
        return $delightfulFlow;
    }
}
