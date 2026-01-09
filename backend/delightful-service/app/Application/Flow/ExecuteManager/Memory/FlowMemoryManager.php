<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory;

use App\Application\Flow\ExecuteManager\Memory\Persistence\ChatMemory;
use App\Application\Flow\ExecuteManager\Memory\Persistence\FlowMemory;
use App\Application\Flow\ExecuteManager\Memory\Persistence\MemoryPersistenceInterface;
use App\Domain\Flow\Entity\ValueObject\MemoryType;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\Odin\Memory\MemoryManager;
use Hyperf\Odin\Memory\Policy\LimitCountPolicy;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Odin\Message\Role;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Odin\Message\UserMessageContent;

readonly class FlowMemoryManager
{
    public function createMemoryManagerByAuto(MemoryQuery $memoryQuery, array $ignoreMessageIds = []): MemoryManager
    {
        $messages = $this->queries($memoryQuery, $ignoreMessageIds);

        // each次all是新记忆，这里if要inprocess中共use一份记忆，will影响to其他节点，暂时each次all是new
        $memoryManager = new MemoryManager(policy: new LimitCountPolicy(['max_count' => $memoryQuery->getLimit()]));

        foreach ($messages as $message) {
            if (! $message instanceof LLMMemoryMessage) {
                continue;
            }
            $odinMessage = $message->toOdinMessage();
            if (! $odinMessage) {
                continue;
            }
            $memoryManager->addMessage($odinMessage);
        }
        return $memoryManager;
    }

    public function createMemoryManagerByArray(array $messageArrayList): MemoryManager
    {
        $memoryManager = new MemoryManager();
        foreach ($messageArrayList as $messageArray) {
            $role = Role::tryFrom($messageArray['role'] ?? '');
            if (! $role) {
                continue;
            }
            $message = null;
            switch ($role) {
                case Role::Assistant:
                    if (! isset($messageArray['content']) && ! is_string($messageArray['content'])) {
                        continue 2;
                    }
                    $message = new AssistantMessage($messageArray['content']);
                    break;
                case Role::User:
                    $message = new UserMessage();
                    if (isset($messageArray['content']) && is_string($messageArray['content'])) {
                        $message->setContent($messageArray['content']);
                    } elseif (isset($messageArray['content']) && is_array($messageArray['content'])) {
                        $content = '';
                        foreach ($messageArray['content'] as $item) {
                            if (isset($item['text'])) {
                                $message->addContent(UserMessageContent::text($item['text']));
                                $content .= $item['text'];
                            }
                            if (isset($item['image_url']['url'])) {
                                $message->addContent(UserMessageContent::imageUrl($item['image_url']['url']));
                            }
                        }
                        $message->setContent($content);
                    }
                    $message->setParams($messageArrayList['params'] ?? []);
                    break;
                default:
                    break;
            }
            if (! $message) {
                continue;
            }
            $memoryManager->addMessage($message);
        }
        return $memoryManager;
    }

    /**
     * according to记忆typequerychatrecord.
     * @return LLMMemoryMessage[]
     */
    public function queries(MemoryQuery $memoryQuery, array $ignoreMessageIds = []): array
    {
        if ($memoryQuery->getLimit() <= 0) {
            return [];
        }
        $memoryPersistence = $this->getMemoryPersistence($memoryQuery->getMemoryType());
        return $memoryPersistence->queries($memoryQuery, $ignoreMessageIds);
    }

    /**
     * 接受message.
     */
    public function receive(MemoryType $memoryType, LLMMemoryMessage $LLMMemoryMessage, bool $nodeDebug = false): void
    {
        if ($nodeDebug || $memoryType->isNone()) {
            return;
        }
        $memoryPersistence = $this->getMemoryPersistence($memoryType);
        $LLMMemoryMessage->setRole(Role::User);
        $memoryPersistence->store($LLMMemoryMessage);
    }

    /**
     * replymessage.
     */
    public function reply(MemoryType $memoryType, LLMMemoryMessage $LLMMemoryMessage, bool $nodeDebug = false): void
    {
        if ($nodeDebug || $memoryType->isNone()) {
            return;
        }
        $memoryPersistence = $this->getMemoryPersistence($memoryType);
        $LLMMemoryMessage->setRole(Role::Assistant);
        $memoryPersistence->store($LLMMemoryMessage);
    }

    private function getMemoryPersistence(MemoryType $memoryType): MemoryPersistenceInterface
    {
        return match ($memoryType) {
            MemoryType::Chat => di(FlowMemory::class),
            MemoryType::IMChat => di(ChatMemory::class),
            default => ExceptionBuilder::throw(GenericErrorCode::SystemError, 'Unsupported memory type'),
        };
    }
}
