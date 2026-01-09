<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Routine;

use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Cron\CronExpression;
use DateTime;

class RoutineConfig
{
    private string $crontabRule = '';

    public function __construct(
        // scheduletype
        private readonly RoutineType $type,
        // specificdate
        private ?string $day = null,
        // specifictime
        private readonly ?string $time = null,
        // customizeperiod的time，between隔unit day / week / month / year
        private ?IntervalUnit $unit = null,
        // customizeperiod的time，between隔frequency，如eachday，eachweek，eachmonth，eachyear
        private ?int $interval = null,
        // unit=weeko clock为[1~7]，unit=montho clock为[1~31]
        private ?array $values = null,
        // enddate，该datebacknotgeneratedata
        private readonly ?DateTime $deadline = null,
        // 话题configuration
        private readonly ?TopicConfig $topicConfig = null
    ) {
        // saveconfigurationo clocknotagain强line检测，放togeneraterule处检测
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
            'topic' => $this->topicConfig?->toConfigArray() ?? [],
        ];
    }

    public function getDatetime(): DateTime
    {
        return new DateTime($this->day . ' ' . $this->time);
    }

    public function getCrontabRule(): string
    {
        if (! empty($this->crontabRule)) {
            return $this->crontabRule;
        }
        if ($this->type === RoutineType::NoRepeat) {
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'currenttype无需generateschedulerule');
        }
        $minute = $hour = $dayOfMonth = $month = $dayOfWeek = '*';
        if (! empty($this->time)) {
            $hour = date('H', strtotime($this->time));
            $minute = date('i', strtotime($this->time));
        }

        switch ($this->type) {
            case RoutineType::DailyRepeat:
                break;
            case RoutineType::WeeklyRepeat:
                // 0-6 table示week一toweekday，所by得compatible一down crontab 的rule 0 table示weekday
                $dayOfWeek = (int) $this->day + 1;
                if ($dayOfWeek === 7) {
                    $dayOfWeek = 0;
                }
                break;
            case RoutineType::MonthlyRepeat:
                $dayOfMonth = (int) $this->day;
                break;
            case RoutineType::AnnuallyRepeat:
                $dayOfMonth = date('d', strtotime($this->day));
                $month = date('m', strtotime($this->day));
                break;
            case RoutineType::WeekdayRepeat:
                $dayOfWeek = '1-5';
                break;
            case RoutineType::CustomRepeat:
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
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'generateschedulerulefail');
        }
        return $this->crontabRule;
    }

    public function getType(): RoutineType
    {
        return $this->type;
    }

    public function getDeadline(): ?DateTime
    {
        return $this->deadline;
    }

    public function validate(): void
    {
        if (! empty($this->values)) {
            $this->values = array_values(array_unique($this->values));
        }
        if ($this->type === RoutineType::CustomRepeat) {
            if (empty($this->unit)) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔unit not能为空');
            }
            if (empty($this->interval)) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency not能为空');
            }
            // onlyeachday的time，才能customize interval，其余all是 1
            if (in_array($this->unit, [IntervalUnit::Week, IntervalUnit::Month, IntervalUnit::Year])) {
                $this->interval = 1;
            }
            if ($this->interval < 1 || $this->interval > 30) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency 只能in1~30between');
            }
            // only是weekor者month的time，才能have values
            if (in_array($this->unit, [IntervalUnit::Week, IntervalUnit::Month])) {
                if (empty($this->values)) {
                    ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency not能为空');
                }
                if ($this->unit === IntervalUnit::Week) {
                    foreach ($this->values as $value) {
                        if (! is_int($value)) {
                            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency 只能是整数');
                        }
                        if ($value < 0 || $value > 6) {
                            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency 只能in0~6between');
                        }
                    }
                }
                if ($this->unit === IntervalUnit::Month) {
                    foreach ($this->values as $value) {
                        if (! is_int($value)) {
                            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency 只能是整数');
                        }
                        if ($value < 1 || $value > 31) {
                            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'customizeperiodbetween隔frequency 只能in1~31between');
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
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'date not能为空');
        }
        if ($this->type->needTime() && is_null($this->time)) {
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'time not能为空');
        }

        // eachweek的time，day table示week几 0-6  0是week一
        if ($this->type === RoutineType::WeeklyRepeat) {
            if (! is_numeric($this->day) || $this->day < 0 || $this->day > 6) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'date 只能in0~6between');
            }
            $this->day = (string) ((int) $this->day);
        }

        // eachmonth的time，day table示the几day
        if ($this->type === RoutineType::MonthlyRepeat) {
            if (! is_numeric($this->day) || $this->day < 1 || $this->day > 31) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'date 只能in1~31between');
            }
            $this->day = (string) ((int) $this->day);
        }

        // not重复、eachyear、eachmonth的time，day table示date
        if (in_array($this->type, [RoutineType::NoRepeat, RoutineType::AnnuallyRepeat])) {
            if (! is_string($this->day) || empty($this->day) || ! strtotime($this->day)) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'date formaterror');
            }
        }

        $dayTimestamp = strtotime($this->day ?? '');
        if ($dayTimestamp) {
            // time只能是未来的，havebug， whendayalsowill认为是未来的
            // if (! is_null($this->day) && $dayTimestamp < time()) {
            //
            //     ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'date not能是过去的');
            // }
            if (! is_null($this->time) && ! is_null($this->day) && strtotime($this->day . ' ' . $this->time) < time()) {
                ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'time not能是过去的');
            }
        }

        // deadlinetime只能是未来的
        if (! is_null($this->deadline) && $this->deadline->getTimestamp() < time()) {
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'deadlinedate not能是过去的');
        }
    }
}
