<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\TaskScheduler\Entity;

use DateTime;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskSchedulerExecuteResult;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskSchedulerStatus;
use Dtyq\TaskScheduler\Exception\TaskSchedulerParamsSchedulerException;
use Dtyq\TaskScheduler\Util\Functions;
use Throwable;

use function Hyperf\Support\call;

class TaskScheduler
{
    private int $id;

    private string $environment;

    /**
     * Business id.
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
     * Schedule type: 1 cron-based, 2 specific time.
     */
    private int $type;

    /**
     * Remaining retry attempts.
     */
    private int $retryTimes;

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

    public function shouldCreate(): bool
    {
        return empty($this->id);
    }

    public function prepareForCreation(): void
    {
        if (empty($this->environment)) {
            $this->environment = Functions::getEnv();
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
        if (empty($this->type)) {
            $this->type = 1;
        }
        if (! isset($this->retryTimes) || $this->retryTimes < 0) {
            $this->retryTimes = 0;
        }
        $this->checkCallbackMethod();

        unset($this->id);
        $this->createdAt = new DateTime();
        $this->status = TaskSchedulerStatus::Pending;
    }

    public function prepareForExecution(): void
    {
        if (! in_array($this->status, [TaskSchedulerStatus::Pending, TaskSchedulerStatus::Retry])) {
            throw new TaskSchedulerParamsSchedulerException('Only pending or retry schedules can be executed');
        }
    }

    public function prepareForCancel(): void
    {
        if (! in_array($this->status, [TaskSchedulerStatus::Pending, TaskSchedulerStatus::Retry])) {
            throw new TaskSchedulerParamsSchedulerException('Only pending or retry schedules can be cancelled');
        }
    }

    public function toModelArray(): array
    {
        return [
            'environment' => $this->environment,
            'external_id' => $this->externalId,
            'name' => $this->name,
            'expect_time' => $this->expectTime,
            'type' => $this->type,
            'actual_time' => $this->actualTime,
            'cost_time' => $this->costTime,
            'retry_times' => $this->retryTimes,
            'status' => $this->status->value,
            'callback_method' => $this->callbackMethod,
            'callback_params' => $this->callbackParams,
            'remark' => $this->remark,
            'creator' => $this->creator,
            'created_at' => $this->createdAt,
        ];
    }

    public function toModelString(): array
    {
        return [
            'environment' => $this->environment,
            'external_id' => $this->externalId,
            'name' => $this->name,
            'expect_time' => $this->expectTime->format('Y-m-d H:i:s'),
            'type' => $this->type,
            'actual_time' => $this->actualTime,
            'cost_time' => $this->costTime,
            'retry_times' => $this->retryTimes,
            'status' => $this->status->value,
            'callback_method' => json_encode($this->callbackMethod),
            'callback_params' => json_encode($this->callbackParams),
            'remark' => $this->remark,
            'creator' => $this->creator,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    public function execute(): TaskSchedulerExecuteResult
    {
        $this->actualTime = new DateTime();

        $startTime = microtime(true);
        $result = new TaskSchedulerExecuteResult();
        try {
            $output = call($this->callbackMethod, $this->callbackParams);
            $result->setSuccess(true);
            $result->setOutput($output);
        } catch (Throwable $e) {
            $result->setSuccess(false);
            $result->setErrorMessage($e->getMessage());
        } finally {
            $elapsedTime = (int) round((microtime(true) - $startTime) * 1000, 2);
            $result->setCosTime($elapsedTime);
            $this->costTime = $elapsedTime;
            $this->status = $result->isSuccess() ? TaskSchedulerStatus::Success : TaskSchedulerStatus::Failed;
        }
        return $result;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getActualTime(): ?DateTime
    {
        return $this->actualTime;
    }

    public function setActualTime(?DateTime $actualTime): void
    {
        $this->actualTime = $actualTime;
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

    public function getRetryTimes(): int
    {
        return $this->retryTimes;
    }

    public function setRetryTimes(int $retryTimes): void
    {
        $this->retryTimes = $retryTimes;
    }

    private function checkCallbackMethod(): void
    {
        if (empty($this->callbackMethod)) {
            throw new TaskSchedulerParamsSchedulerException('Schedule method cannot be empty');
        }
        if (count($this->callbackMethod) !== 2) {
            throw new TaskSchedulerParamsSchedulerException('Schedule method format is invalid');
        }
        foreach ($this->callbackMethod as $method) {
            if (! is_string($method)) {
                throw new TaskSchedulerParamsSchedulerException('Schedule method format is invalid');
            }
        }
    }
}
