<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Entity;

use DateTime;
use BeDelightful\TaskScheduler\Entity\ValueObject\TaskSchedulerExecuteResult;
use BeDelightful\TaskScheduler\Entity\ValueObject\TaskSchedulerStatus;
use BeDelightful\TaskScheduler\Exception\TaskSchedulerParamsSchedulerException;
use BeDelightful\TaskScheduler\Util\Functions;

class TaskSchedulerLog
{
    private int $id;

    private int $taskId;

    private string $environment;

    /**
     * Schedule identifier.
     * Typically used for business identification.
     * Can serve as a source identifier.
     */
    private string $externalId;

    /**
     * Schedule name.
     */
    private string $name;

    /**
     * Expected schedule time.
     * Minute-level precision.
     */
    private DateTime $expectTime;

    /**
     * Actual schedule time.
     */
    private ?DateTime $actualTime = null;

    /**
     * Schedule duration.
     */
    private int $costTime = 0;

    /**
     * Schedule status
     */
    private TaskSchedulerStatus $status;

    /**
     * Schedule type.
     */
    private int $type;

    /**
     * Schedule method.
     * In [Class, Method] format.
     */
    private array $callbackMethod;

    /**
     * Schedule method parameters.
     */
    private array $callbackParams = [];

    /**
     * Remark.
     */
    private string $remark = '';

    /**
     * Created by.
     * Optional depending on business needs.
     */
    private string $creator = '';

    /**
     * Created at.
     */
    private DateTime $createdAt;

    /**
     * Schedule execution result.
     */
    private ?TaskSchedulerExecuteResult $result = null;

    public function prepareForCreation(): void
    {
        if (empty($this->environment)) {
            $this->environment = Functions::getEnv();
        }
        if (empty($this->taskId)) {
            throw new TaskSchedulerParamsSchedulerException('Task ID cannot be empty');
        }
        if (empty($this->externalId)) {
            throw new TaskSchedulerParamsSchedulerException('Business identifier cannot be empty');
        }
        if (empty($this->name)) {
            throw new TaskSchedulerParamsSchedulerException('Schedule name cannot be empty');
        }
        if (empty($this->expectTime)) {
            throw new TaskSchedulerParamsSchedulerException('Expected schedule time cannot be empty');
        }
        unset($this->id);
    }

    public function toModelArray(): array
    {
        return [
            'environment' => $this->environment,
            'task_id' => $this->taskId,
            'external_id' => $this->externalId,
            'name' => $this->name,
            'expect_time' => $this->expectTime,
            'actual_time' => $this->actualTime,
            'cost_time' => $this->costTime,
            'status' => $this->status->value,
            'callback_method' => $this->callbackMethod,
            'callback_params' => $this->callbackParams,
            'remark' => $this->remark,
            'creator' => $this->creator,
            'created_at' => $this->createdAt,
            'result' => $this->result?->toArray() ?? [],
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getExpectTime(): DateTime
    {
        return $this->expectTime;
    }

    public function setExpectTime(DateTime $expectTime): void
    {
        $this->expectTime = $expectTime;
    }

    public function getActualTime(): ?DateTime
    {
        return $this->actualTime;
    }

    public function setActualTime(?DateTime $actualTime): void
    {
        $this->actualTime = $actualTime;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getCostTime(): int
    {
        return $this->costTime;
    }

    public function setCostTime(int $costTime): void
    {
        $this->costTime = $costTime;
    }

    public function getStatus(): TaskSchedulerStatus
    {
        return $this->status;
    }

    public function setStatus(TaskSchedulerStatus $status): void
    {
        $this->status = $status;
    }

    public function getCallbackMethod(): array
    {
        return $this->callbackMethod;
    }

    public function setCallbackMethod(array $callbackMethod): void
    {
        $this->callbackMethod = $callbackMethod;
    }

    public function getCallbackParams(): array
    {
        return $this->callbackParams;
    }

    public function setCallbackParams(array $callbackParams): void
    {
        $this->callbackParams = $callbackParams;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function setRemark(string $remark): void
    {
        $this->remark = $remark;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getResult(): ?TaskSchedulerExecuteResult
    {
        return $this->result;
    }

    public function setResult(?TaskSchedulerExecuteResult $result): void
    {
        $this->result = $result;
    }
}
