<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\TaskScheduler\Service;

use Cron\CronExpression;
use DateTime;
use Dtyq\TaskScheduler\Entity\TaskSchedulerValue;
use Dtyq\TaskScheduler\Entity\ValueObject\IntervalUnit;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskType;
use Dtyq\TaskScheduler\Exception\TaskSchedulerParamsSchedulerException;
use InvalidArgumentException;

class TaskConfigDomainService
{
    private string $crontabRule = '';

    public function __construct(
        // Schedule type
        private readonly TaskType $type,
        // Specific date
        private ?string $day = null,
        // Specific time
        private readonly ?string $time = null,
        // Interval unit for custom cycles: day / week / month / year
        private ?IntervalUnit $unit = null,
        // Interval frequency for custom cycles, e.g., daily, weekly, monthly, yearly
        private ?int $interval = null,
        // When unit=week use [1~7]; when unit=month use [1~31]
        private ?array $values = null,
        // End date; no data generated after this date
        private readonly ?DateTime $deadline = null,
    ) {
        $this->validate();
    }

    public function toConfigArray(): array
    {
        return [
            'type' => $this->type->value,
            'day' => $this->day,
            'time' => $this->time,
            'value' => [
                'interval' => $this->interval,
                'unit' => $this->unit?->value,
                'values' => $this->values,
                'deadline' => $this->deadline?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function getDatetime(): DateTime
    {
        return new DateTime($this->day . ' ' . $this->time);
    }

    // Determine from parameters whether a non-repeating cron task is needed
    public function getCrontabRule(bool $isNoRepeat = false): string
    {
        if (! empty($this->crontabRule)) {
            return $this->crontabRule;
        }
        if ($this->type === TaskType::NoRepeat && $isNoRepeat == false) {
            throw new InvalidArgumentException('This type does not require generating a cron rule');
        }
        $minute = $hour = $dayOfMonth = $month = $dayOfWeek = '*';
        if (! empty($this->time)) {
            $hour = date('H', strtotime($this->time));
            $minute = date('i', strtotime($this->time));
        }

        switch ($this->type) {
            case TaskType::DailyRepeat:
                break;
            case TaskType::WeeklyRepeat:
                // 0-6 represents Monday to Sunday; crontab uses 0 for Sunday
                $dayOfWeek = (int) $this->day + 1;
                if ($dayOfWeek == 7) {
                    $dayOfWeek = 0;
                }
                break;
            case TaskType::MonthlyRepeat:
                $dayOfMonth = (int) $this->day;
                break;
            case TaskType::AnnuallyRepeat:
                $dayOfMonth = date('d', strtotime($this->day));
                $month = date('m', strtotime($this->day));
                break;
            case TaskType::WeekdayRepeat:
                $dayOfWeek = '1-5';
                break;
            case TaskType::CustomRepeat:
                if ($this->unit === IntervalUnit::Day) {
                    $dayOfMonth = '*/' . $this->interval;
                }
                if ($this->unit === IntervalUnit::Week) {
                    $dayOfWeek = implode(',', $this->values);
                }
                if ($this->unit === IntervalUnit::Month) {
                    $dayOfMonth = implode(',', $this->values);
                }
                if ($this->unit === IntervalUnit::Year) {
                    $dayOfMonth = date('d', strtotime($this->day));
                    $month = date('m', strtotime($this->day));
                }
                break;
            default:
        }

        $this->crontabRule = "{$minute} {$hour} {$dayOfMonth} {$month} {$dayOfWeek}";
        if (! CronExpression::isValidExpression($this->crontabRule)) {
            throw new InvalidArgumentException('Failed to generate cron rule');
        }
        return $this->crontabRule;
    }

    public function getType(): TaskType
    {
        return $this->type;
    }

    public function getDeadline(): ?DateTime
    {
        return $this->deadline;
    }

    public function getCustomRepeatTaskExpectTimes(TaskSchedulerValue $taskSchedulerValue)
    {
        $unitType = IntervalUnit::tryFrom($taskSchedulerValue->getUnit());
        if (! $unitType) {
            throw new TaskSchedulerParamsSchedulerException('Interval unit not found');
        }

        // Calculate the number of days between the deadline and now
        $deadline = $this->getDeadline();
        $time = new DateTime();

        // If the unit is year, month cannot be empty
        if ($unitType == IntervalUnit::Year && empty($taskSchedulerValue->getMonth())) {
            throw new TaskSchedulerParamsSchedulerException('Month cannot be empty when custom repeat is yearly');
        }

        // If the unit is year
        if ($unitType == IntervalUnit::Year && empty($deadline)) {
            // Next 10 years
            $deadline = $time->modify('+10 years');
        } elseif (! $deadline) {
            // Next two years
            $deadline = $time->modify('+2 years');
        }

        $days = $deadline->diff(new DateTime())->days;
        // Cannot exceed five years
        if ($days > 1825 && $unitType != IntervalUnit::Year) {
            throw new TaskSchedulerParamsSchedulerException('Interval cannot exceed five years');
        }
        // if ($days < $taskSchedulerValue->getInterval()) {
        //     throw new TaskSchedulerParamsSchedulerException('Daily interval cannot exceed the deadline');
        // }

        $number = 1;
        if ($taskSchedulerValue->getInterval() >= 1) {
            $time = new DateTime();
            switch ($unitType) {
                case IntervalUnit::Day:
                    ++$number;
                    $number += ceil($days / $taskSchedulerValue->getInterval());

                    break;
                case IntervalUnit::Week:
                    $weeks = $deadline->diff($time)->days / 7;
                    // if ($weeks < $taskSchedulerValue->getInterval()) {
                    //     throw new TaskSchedulerParamsSchedulerException('Weekly interval cannot exceed or equal the deadline');
                    // }

                    $number += ceil($weeks / $taskSchedulerValue->getInterval());
                    break;
                case IntervalUnit::Month:
                    $months = $deadline->diff($time)->days / 30;
                    if ($taskSchedulerValue->getInterval() == 1) {
                        $months = $months + 1;
                    }
                    // if ($months < $taskSchedulerValue->getInterval()) {
                    //     throw new TaskSchedulerParamsSchedulerException('Monthly interval cannot exceed or equal the deadline');
                    // }

                    $number += ceil($months / $taskSchedulerValue->getInterval());
                    break;
                case IntervalUnit::Year:
                    $years = $deadline->diff($time)->days / 365;

                    // if ($years < $taskSchedulerValue->getInterval()) {
                    //     throw new TaskSchedulerParamsSchedulerException('Yearly interval cannot exceed or equal the deadline');
                    // }

                    $number += ceil($years / $taskSchedulerValue->getInterval());
                    break;
                default:
                    $number = 1;
            }
        }

        $expectTimes = [];
        for ($i = 0; $i < $number; ++$i) {
            switch ($unitType) {
                case IntervalUnit::Day:
                    $modify = (int) $taskSchedulerValue->getInterval() * $i;
                    $newExpectTime = clone $this->getDatetime()->modify("+{$modify} {$taskSchedulerValue->getUnit()}");
                    if ($newExpectTime->getTimestamp() < time()) {
                        break;
                    }
                    $expectTimes[] = clone $newExpectTime;
                    break;
                case IntervalUnit::Week:
                    $modify = (int) $taskSchedulerValue->getInterval() * $i;
                    $expectTime = $this->getDatetime()->modify("+{$modify} {$taskSchedulerValue->getUnit()}");
                    $expectTime = $expectTime->setTime((int) $this->getDatetime()->format('H'), (int) $this->getDatetime()->format('i'));
                    foreach ($taskSchedulerValue->getValues() as $value) {
                        switch ($value) {
                            case 1:
                                $value = TaskType::Monday->value;
                                break;
                            case 2:
                                $value = TaskType::Tuesday->value;
                                break;
                            case 3:
                                $value = TaskType::Wednesday->value;
                                break;
                            case 4:
                                $value = TaskType::Thursday->value;
                                break;
                            case 5:
                                $value = TaskType::Friday->value;
                                break;
                            case 6:
                                $value = TaskType::Saturday->value;
                                break;
                            case 0:
                                $value = TaskType::Sunday->value;
                                break;
                        }
                        $newExpectTime = clone $expectTime;
                        $newExpectTime->modify("{$value}");
                        $newExpectTime = $newExpectTime->setTime((int) $this->getDatetime()->format('H'), (int) $this->getDatetime()->format('i'));
                        if ($newExpectTime->getTimestamp() < time()) {
                            continue;
                        }

                        $expectTimes[] = clone $newExpectTime;
                    }
                    break;
                case IntervalUnit::Month:
                    $modify = (int) $taskSchedulerValue->getInterval() * $i;

                    $newExpectTime = clone $this->getDatetime()->modify("+{$modify} {$taskSchedulerValue->getUnit()}");
                    // Get the total days of the current month
                    $days = $newExpectTime->format('t');
                    foreach ($taskSchedulerValue->getValues() as $value) {
                        if ($value > $days) {
                            continue;
                        }

                        $newExpectTime = $newExpectTime->setDate((int) $newExpectTime->format('Y'), (int) $newExpectTime->format('m'), (int) $value);
                        if ($newExpectTime->getTimestamp() < time()) {
                            continue;
                        }
                        $expectTimes[] = clone $newExpectTime;
                    }

                    break;
                case IntervalUnit::Year:
                    $modify = (int) $taskSchedulerValue->getInterval() * $i;
                    $newExpectTime = clone $this->getDatetime()->modify("+{$modify} {$taskSchedulerValue->getUnit()}");
                    $year = $newExpectTime->format('Y');
                    $month = $taskSchedulerValue->getMonth();

                    foreach ($taskSchedulerValue->getValues() as $value) {
                        $newExpectTime = $newExpectTime->setDate((int) $year, (int) $month, (int) $value);
                        var_dump($newExpectTime);
                        if ($newExpectTime->getTimestamp() < time()) {
                            continue;
                        }
                        $expectTimes[] = clone $newExpectTime;
                    }

                    break;
                default:
                    break;
            }
        }

        $h = $this->getDatetime()->format('H');
        $i = $this->getDatetime()->format('i');

        $deadlineDate = clone $deadline->setTime((int) $h, (int) $i);

        // var_dump($expectTimes);
        // Remove expected times that exceed the deadline
        foreach ($expectTimes as $key => $expectTime) {
            if ($expectTime->getTimestamp() > $deadlineDate->getTimestamp()) {
                unset($expectTimes[$key]);
            }
        }

        if (empty($expectTimes)) {
            throw new TaskSchedulerParamsSchedulerException('No scheduled tasks could be generated; please check configuration');
        }
        return $expectTimes;
    }

    private function validate(): void
    {
        if (! empty($this->values)) {
            $this->values = array_values(array_unique($this->values));
        }
        if ($this->type === TaskType::CustomRepeat) {
            if (empty($this->unit)) {
                throw new InvalidArgumentException('Custom interval unit cannot be empty');
            }
            if (empty($this->interval)) {
                throw new InvalidArgumentException('Custom interval frequency cannot be empty');
            }

            if ($this->interval < 1 || $this->interval > 30) {
                throw new InvalidArgumentException('Custom interval frequency must be between 1 and 30');
            }
            // Only week or month units can have values
            if (in_array($this->unit, [IntervalUnit::Week, IntervalUnit::Month])) {
                if (empty($this->values)) {
                    throw new InvalidArgumentException('Custom interval frequency cannot be empty');
                }
                if ($this->unit === IntervalUnit::Week) {
                    foreach ($this->values as $value) {
                        if (! is_int($value)) {
                            throw new InvalidArgumentException('Custom interval frequency must be an integer');
                        }
                        if ($value < 0 || $value > 6) {
                            throw new InvalidArgumentException('Custom interval frequency must be between 0 and 6');
                        }
                    }
                }
                if ($this->unit === IntervalUnit::Month) {
                    foreach ($this->values as $value) {
                        if (! is_int($value)) {
                            throw new InvalidArgumentException('Custom interval frequency must be an integer');
                        }
                        if ($value < 1 || $value > 31) {
                            throw new InvalidArgumentException('Custom interval frequency must be between 1 and 31');
                        }
                    }
                }
            } else {
                $this->values = null;
            }
        } else {
            $this->unit = null;
            $this->interval = null;
            $this->values = null;
        }
        if ($this->type->needDay() && is_null($this->day)) {
            throw new InvalidArgumentException('Date cannot be empty');
        }
        if ($this->type->needTime() && is_null($this->time)) {
            throw new InvalidArgumentException('Time cannot be empty');
        }

        // For weekly schedules, day represents the weekday 0-6 where 0 is Monday
        if ($this->type === TaskType::WeeklyRepeat) {
            if (! is_numeric($this->day) || $this->day < 0 || $this->day > 6) {
                throw new InvalidArgumentException('Date must be between 0 and 6');
            }
            $this->day = (string) ((int) $this->day);
        }

        // For monthly schedules, day represents the day of the month
        if ($this->type === TaskType::MonthlyRepeat) {
            if (! is_numeric($this->day) || $this->day < 1 || $this->day > 31) {
                throw new InvalidArgumentException('Date must be between 1 and 31');
            }
            $this->day = (string) ((int) $this->day);
        }

        // For non-repeating, yearly, or monthly schedules, day represents the date
        if (in_array($this->type, [TaskType::NoRepeat, TaskType::AnnuallyRepeat])) {
            if (! is_string($this->day) || empty($this->day) || ! strtotime($this->day)) {
                throw new InvalidArgumentException('Date format is invalid');
            }
        }

        $dayTimestamp = strtotime($this->day ?? '');

        if ($dayTimestamp) {
            // Time must be in the future
            // TODO bug: the same day is also considered future
            // if (! is_null($this->day) && $dayTimestamp < time()) {
            //
            //     ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'Date cannot be in the past');
            // }

            if (! is_null($this->time) && ! is_null($this->day)) {
                $dateTime = DateTime::createFromFormat('Y-m-d H:i', $this->day . ' ' . $this->time);
                if ($dateTime === false) {
                    throw new InvalidArgumentException('Invalid date/time format');
                }
                if ($dateTime->getTimestamp() < time() && $this->type != TaskType::CustomRepeat) {
                    throw new InvalidArgumentException('The time is too close to now; please adjust');
                }
            }
        }
        $deadlineTimeStamp = null;
        if (! empty($this->time) && ! empty($this->deadline)) {
            $deadline = DateTime::createFromFormat('Y-m-d H:i', $this->deadline->format('Y-m-d') . ' ' . $this->time);
            if ($deadline === false) {
                throw new InvalidArgumentException('Deadline format is invalid');
            }
            $deadlineTimeStamp = $deadline->getTimestamp();
        } elseif (! empty($this->deadline)) {
            $deadlineTimeStamp = $this->deadline->getTimestamp();
        }

        // For custom repeats with no deadline set, default to two years
        if ($this->type == TaskType::CustomRepeat && empty($this->deadline)) {
            $deadline = new DateTime();
            $deadline->modify('+2 years');
            $deadlineTimeStamp = $deadline->getTimestamp();
        }

        // Deadline must be in the future
        if ($deadlineTimeStamp && $deadlineTimeStamp < time()) {
            throw new InvalidArgumentException('The deadline is too close to the current time; please adjust');
        }
    }
}
