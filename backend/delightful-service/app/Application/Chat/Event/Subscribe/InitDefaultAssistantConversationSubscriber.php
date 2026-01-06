<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe;

use App\Application\Agent\Service\DelightfulAgentAppService;
use App\Application\Chat\Service\DelightfulAccountAppService;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Agent\Service\DelightfulAgentVersionDomainService;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\UserStatus;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Logger\LoggerFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

#[Consumer(exchange: 'init_default_assistant_conversation', routingKey: 'init_default_assistant_conversation', queue: 'init_default_assistant_conversation', nums: 1)]
class InitDefaultAssistantConversationSubscriber extends ConsumerMessage
{
    private LoggerInterface $logger;

    public function __construct(
        protected DelightfulAgentAppService $delightfulAgentAppService,
        protected DelightfulAgentDomainService $delightfulAgentDomainService,
        protected DelightfulUserDomainService $delightfulUserDomainService,
        protected DelightfulAgentVersionDomainService $delightfulAgentVersionDomainService,
        protected DelightfulUserAuthorization $delightfulUserAuthorization,
        protected DelightfulAccountAppService $delightfulAccountAppService,
    ) {
        $this->logger = di(LoggerFactory::class)->get(get_class($this));
    }

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            $data['user_entity']['user_type'] = UserType::tryFrom($data['user_entity']['user_type']);
            $data['user_entity']['status'] = UserStatus::tryFrom($data['user_entity']['status']);
            $data['user_entity']['like_num'] = (int) $data['user_entity']['like_num'];
            /** @var DelightfulUserEntity $userEntity */
            $userEntity = new DelightfulUserEntity($data['user_entity']);
            /** @var array<string> $defaultConversationAICodes */
            $defaultConversationAICodes = $data['default_conversation_ai_codes'];
            // 先批量注册，防止组织下没有该助理用户.
            $this->batchAiRegister($userEntity, $defaultConversationAICodes);
            // 初始化默认会话
            $this->delightfulAgentAppService->initDefaultAssistantConversation($userEntity, $defaultConversationAICodes);
            return Result::ACK;
        } catch (Throwable $exception) {
            $this->logger->error("初始化默认会话失败，错误信息: {$exception->getMessage()}, 堆栈: {$exception->getTraceAsString()}");
            return Result::ACK;
        }
    }

    /**
     * 注册助理，防止组织下没有该助理用户.
     */
    public function batchAiRegister(DelightfulUserEntity $userEntity, ?array $defaultConversationAICodes = null): void
    {
        $authorization = DelightfulUserAuthorization::fromUserEntity($userEntity);
        $defaultConversationAICodes = $defaultConversationAICodes ?? $this->delightfulAgentDomainService->getDefaultConversationAICodes();
        foreach ($defaultConversationAICodes as $aiCode) {
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentByFlowCode($aiCode);
            $agentName = $delightfulAgentVersionEntity->getAgentName();
            $this->logger->info("注册助理，aiCode: {$aiCode}, 名称: {$agentName}");
            try {
                $aiUserDTO = DelightfulUserEntity::fromDelightfulAgentVersionEntity($delightfulAgentVersionEntity);
                $this->delightfulAccountAppService->aiRegister($aiUserDTO, $authorization, $aiCode);
                $this->logger->info("注册助理成功，aiCode: {$aiCode}, 名称: {$agentName}");
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
                $trace = $e->getTraceAsString();
                $this->logger->error("注册助理失败，aiCode: {$aiCode}, 名称: {$agentName}\n错误信息: {$errorMessage}\n堆栈: {$trace} ");
            }
        }
    }
}
