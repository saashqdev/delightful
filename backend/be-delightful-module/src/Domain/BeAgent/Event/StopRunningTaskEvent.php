<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\DeleteDataType;

/**
 * Stop running task event
 * Triggered when workspace, project or topic is deleted, used to asynchronously stop related running tasks
 */
class StopRunningTaskEvent extends AbstractEvent
{
    /**
     * Constructor.
     *
     * @param DeleteDataType $dataType Data type (workspace, project, topic)
     * @param int $dataId Data ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @param string $reason Stop reason
     */
    public function __construct(
        private DeleteDataType $dataType,
        private int $dataId,
        private string $userId,
        private string $organizationCode,
        private string $reason = ''
    ) {
        // Call parent constructor to generate snowflake ID
        parent::__construct();

        // Set default reason if not provided
        if (empty($this->reason)) {
            $this->reason = "Related {$this->dataType->getDescription()} has been deleted";
        }
    }

    /**
     * Create event from array.
     *
     * @param array $data Event data array
     */
    public static function fromArray(array $data): self
    {
        $dataType = DeleteDataType::from($data['data_type'] ?? DeleteDataType::TOPIC->value);
        $dataId = (int) ($data['data_id'] ?? 0);
        $userId = (string) ($data['user_id'] ?? '');
        $organizationCode = (string) ($data['organization_code'] ?? '');
        $reason = (string) ($data['reason'] ?? '');

        return new self($dataType, $dataId, $userId, $organizationCode, $reason);
    }

    /**
     * Convert to array.
     *
     * @return array Event data array
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'data_type' => $this->dataType->value,
            'data_id' => $this->dataId,
            'user_id' => $this->userId,
            'organization_code' => $this->organizationCode,
            'reason' => $this->reason,
            'timestamp' => time(),
        ];
    }

    /**
     * Get data type.
     */
    public function getDataType(): DeleteDataType
    {
        return $this->dataType;
    }

    /**
     * Get data ID.
     */
    public function getDataId(): int
    {
        return $this->dataId;
    }

    /**
     * Get user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Get organization code.
     */
    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    /**
     * Get stop reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
