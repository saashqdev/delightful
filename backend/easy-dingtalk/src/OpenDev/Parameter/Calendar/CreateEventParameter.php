<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Parameter\Calendar;

use DateTime;
use Dtyq\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use Dtyq\EasyDingTalk\OpenDev\Parameter\AbstractParameter;

/**
 * @see https://open.dingtalk.com/document/orgapp/create-event
 */
class CreateEventParameter extends AbstractParameter
{
    /**
     * The unionId of the event organizer.
     */
    private string $userId;

    /**
     * The calendar ID to which the event belongs, uniformly primary, representing the user's primary calendar.
     */
    private string $calendarId;

    /**
     * Event title, maximum 2048 characters.
     */
    private string $summary;

    /**
     * Event description, maximum 5000 characters.
     */
    private string $description = '';

    /**
     * Event start time.
     */
    private DateTime $start;

    /**
     * Event end time.
     */
    private ?DateTime $end = null;

    /**
     * Whether it is an all-day event.
     */
    private bool $isAllDay = false;

    /**
     * Event recurrence rule.
     */
    private array $recurrence = [];

    /**
     * List of event participants, supports up to 500 participants.
     */
    private array $attendees = [];

    /**
     * Event location.
     */
    private string $location = '';

    /**
     * Event reminders.
     */
    private array $reminders = [];

    /**
     * Create online meeting while creating event.
     */
    private array $onlineMeetingInfo = [];

    /**
     * JSON format extension capability switch.
     */
    private array $extra = [];

    /**
     * UI configuration, controls the display of components in the event details page.
     */
    private array $uiConfigs = [];

    /**
     * Rich text description.
     */
    private array $richTextDescription = [];

    public function toBody(): array
    {
        $body = [
            'summary' => $this->summary,
            'description' => $this->description,
            'start' => $this->formatDatetime($this->start),
        ];

        if (! empty($this->end)) {
            $body['end'] = $this->formatDatetime($this->end);
        }
        $body['isAllDay'] = $this->isAllDay;

        if (! empty($this->recurrence)) {
            $body['recurrence'] = $this->recurrence;
        }
        if (! empty($this->attendees)) {
            $body['attendees'] = $this->attendees;
        }
        if (! empty($this->location)) {
            $body['location'] = [
                'displayName' => $this->location,
            ];
        }
        if (! empty($this->reminders)) {
            $body['reminders'] = $this->reminders;
        }
        if (! empty($this->onlineMeetingInfo)) {
            $body['onlineMeetingInfo'] = $this->onlineMeetingInfo;
        }
        if (! empty($this->extra)) {
            $body['extra'] = $this->extra;
        }
        if (! empty($this->uiConfigs)) {
            $body['uiConfigs'] = $this->uiConfigs;
        }
        if (! empty($this->richTextDescription)) {
            $body['richTextDescription'] = $this->richTextDescription;
        }

        return $body;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getCalendarId(): string
    {
        return $this->calendarId;
    }

    public function setCalendarId(string $calendarId): void
    {
        $this->calendarId = $calendarId;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): void
    {
        $this->summary = $summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function setStart(DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    public function setIsAllDay(bool $isAllDay): void
    {
        $this->isAllDay = $isAllDay;
    }

    public function getRecurrence(): array
    {
        return $this->recurrence;
    }

    public function setRecurrence(array $recurrence): void
    {
        $this->recurrence = $recurrence;
    }

    public function getAttendees(): array
    {
        return $this->attendees;
    }

    public function setAttendees(array $attendees): void
    {
        $this->attendees = $attendees;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getReminders(): array
    {
        return $this->reminders;
    }

    public function setReminders(array $reminders): void
    {
        $this->reminders = $reminders;
    }

    public function getOnlineMeetingInfo(): array
    {
        return $this->onlineMeetingInfo;
    }

    public function setOnlineMeetingInfo(array $onlineMeetingInfo): void
    {
        $this->onlineMeetingInfo = $onlineMeetingInfo;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
    }

    public function getUiConfigs(): array
    {
        return $this->uiConfigs;
    }

    public function setUiConfigs(array $uiConfigs): void
    {
        $this->uiConfigs = $uiConfigs;
    }

    public function getRichTextDescription(): array
    {
        return $this->richTextDescription;
    }

    public function setRichTextDescription(array $richTextDescription): void
    {
        $this->richTextDescription = $richTextDescription;
    }

    protected function validateParams(): void
    {
        if (empty($this->userId)) {
            throw new InvalidParameterException('userId cannot be empty');
        }
        if (empty($this->summary)) {
            throw new InvalidParameterException('Event title cannot be empty');
        }
        if (empty($this->start)) {
            throw new InvalidParameterException('Event start time cannot be empty');
        }

        if (empty($this->calendarId)) {
            $this->calendarId = 'primary';
        }
    }

    private function formatDatetime(DateTime $dateTime): array
    {
        if ($this->isAllDay) {
            return [
                'date' => $dateTime->format('Y-m-d'),
            ];
        }
        return [
            'dateTime' => $dateTime->format('Y-m-d\TH:i:sP'),
            'timeZone' => $dateTime->getTimezone()->getName(),
        ];
    }
}
