<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationAction;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationScenario;

/**
 * 超级麦吉创建/更新记忆的工具调用，可以在消息卡片下方点击按钮进行快速操作。
 */
class MemoryOperation extends AbstractDTO
{
    // 记忆操作
    protected MemoryOperationAction $action;

    protected string $memoryId;

    protected MemoryOperationScenario $scenario;

    public function __construct(?array $data)
    {
        parent::__construct($data);
    }

    public function getAction(): ?MemoryOperationAction
    {
        return $this->action ?? null;
    }

    public function getMemoryId(): ?string
    {
        return $this->memoryId ?? null;
    }

    public function setAction(MemoryOperationAction|string $action): void
    {
        if (is_string($action)) {
            $this->action = MemoryOperationAction::from($action);
        } else {
            $this->action = $action;
        }
    }

    public function setMemoryId(string $memoryId): void
    {
        $this->memoryId = $memoryId;
    }

    public function getScenario(): ?MemoryOperationScenario
    {
        return $this->scenario ?? null;
    }

    public function setScenario(MemoryOperationScenario|string $scenario): void
    {
        if (is_string($scenario)) {
            $this->scenario = MemoryOperationScenario::from($scenario);
        } else {
            $this->scenario = $scenario;
        }
    }
}
