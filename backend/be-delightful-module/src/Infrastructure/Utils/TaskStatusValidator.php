<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;

/**
 * Task status validation utility.
 *
 * Provides status transition rule validation to prevent state race conditions due to message reordering.
 *
 * Status categories:
 * - Waiting state: WAITING - can transition to any state
 * - Active state: RUNNING - can only transition to final or suspended states
 * - Resumable state: Suspended - can only transition to WAITING or final states
 * - Final states: FINISHED, ERROR, Stopped - can transition to WAITING or other final states
 */
class TaskStatusValidator
{
    /**
     * Validate whether a status transition is allowed.
     *
     * @param null|TaskStatus $currentStatus Current status
     * @param TaskStatus $newStatus New status
     * @return bool Whether transition is allowed
     */
    public static function isTransitionAllowed(?TaskStatus $currentStatus, TaskStatus $newStatus): bool
    {
        // If current status cannot be obtained, allow transition
        if ($currentStatus === null) {
            return true;
        }

        // Same status, allow (idempotent operation)
        if ($currentStatus === $newStatus) {
            return true;
        }

        // Apply status transition rules
        return self::applyTransitionRules($currentStatus, $newStatus);
    }

    /**
     * Get status type description.
     *
     * @param TaskStatus $status Status
     * @return string Status type
     */
    public static function getStatusType(TaskStatus $status): string
    {
        if (self::isWaitingStatus($status)) {
            return 'waiting';
        }

        if (self::isActiveStatus($status)) {
            return 'active';
        }

        if (self::isFinalStatus($status)) {
            return 'final';
        }

        if ($status === TaskStatus::Suspended) {
            return 'suspended';
        }

        return 'unknown';
    }

    /**
     * Get reason for rejection of transition.
     *
     * @param TaskStatus $currentStatus Current status
     * @param TaskStatus $newStatus New status
     * @return string Rejection reason
     */
    public static function getRejectReason(TaskStatus $currentStatus, TaskStatus $newStatus): string
    {
        if ($currentStatus === TaskStatus::RUNNING && $newStatus === TaskStatus::WAITING) {
            return 'Running task cannot return to waiting state';
        }

        if ($currentStatus === TaskStatus::RUNNING && ! self::isFinalStatus($newStatus) && $newStatus !== TaskStatus::Suspended) {
            return 'Running task can only transition to final or suspended states';
        }

        if (self::isFinalStatus($currentStatus) && $newStatus === TaskStatus::RUNNING) {
            return 'Final state task cannot transition directly to running state';
        }

        if (self::isFinalStatus($currentStatus) && $newStatus === TaskStatus::Suspended) {
            return 'Final state task cannot transition to suspended state';
        }

        if ($currentStatus === TaskStatus::Suspended && $newStatus === TaskStatus::RUNNING) {
            return 'Suspended task must transition to waiting state before it can execute';
        }

        if ($currentStatus === TaskStatus::Suspended && ! self::isFinalStatus($newStatus) && $newStatus !== TaskStatus::WAITING) {
            return 'Suspended task can only transition to waiting or final states';
        }

        return 'Status transition does not comply with business rules';
    }

    /**
     * Get allowed next states.
     *
     * @param TaskStatus $currentStatus Current status
     * @return TaskStatus[] List of allowed next states
     */
    public static function getAllowedNextStates(TaskStatus $currentStatus): array
    {
        $allowed = [];

        foreach (TaskStatus::cases() as $status) {
            if (self::isTransitionAllowed($currentStatus, $status)) {
                $allowed[] = $status;
            }
        }

        return $allowed;
    }

    /**
     * Validate whether a status transition chain is valid.
     *
     * @param TaskStatus[] $statusChain Status transition chain
     * @return array Validation result ['valid' => bool, 'invalid_step' => int|null, 'reason' => string]
     */
    public static function validateTransitionChain(array $statusChain): array
    {
        if (empty($statusChain)) {
            return ['valid' => true, 'invalid_step' => null, 'reason' => ''];
        }

        $currentStatus = null;

        foreach ($statusChain as $index => $status) {
            if (! $status instanceof TaskStatus) {
                return [
                    'valid' => false,
                    'invalid_step' => $index,
                    'reason' => "Step {$index} contains invalid status value",
                ];
            }

            if (! self::isTransitionAllowed($currentStatus, $status)) {
                $fromStatus = $currentStatus->value ?? 'null';
                return [
                    'valid' => false,
                    'invalid_step' => $index,
                    'reason' => "Step {$index}: {$fromStatus} → {$status->value} transition not allowed",
                ];
            }

            $currentStatus = $status;
        }

        return ['valid' => true, 'invalid_step' => null, 'reason' => ''];
    }

    /**
     * Apply status transition rules.
     *
     * @param TaskStatus $currentStatus Current status
     * @param TaskStatus $newStatus New status
     * @return bool Whether transition is allowed
     */
    private static function applyTransitionRules(TaskStatus $currentStatus, TaskStatus $newStatus): bool
    {
        // Rule 1: Waiting state can transition to any status
        if (self::isWaitingStatus($currentStatus)) {
            return true;
        }

        // Rule 2: Active state can only transition to final or suspended states
        if (self::isActiveStatus($currentStatus)) {
            return $newStatus === TaskStatus::Suspended || self::isFinalStatus($newStatus);
        }

        // Rule 3: Suspended state can only transition to WAITING or final states
        if ($currentStatus === TaskStatus::Suspended) {
            return $newStatus === TaskStatus::WAITING || self::isFinalStatus($newStatus);
        }

        // Rule 4: Final states can only transition to WAITING or other final states
        if (self::isFinalStatus($currentStatus)) {
            return $newStatus === TaskStatus::WAITING || self::isFinalStatus($newStatus);
        }

        // Default allow (unknown status combination)
        return true;
    }

    /**
     * Check whether it is a waiting status.
     *
     * @param TaskStatus $status Status
     * @return bool Whether it is a waiting status
     */
    private static function isWaitingStatus(TaskStatus $status): bool
    {
        return $status === TaskStatus::WAITING;
    }

    /**
     * Check whether it is an active status.
     *
     * @param TaskStatus $status Status
     * @return bool Whether it is an active status
     */
    private static function isActiveStatus(TaskStatus $status): bool
    {
        return $status === TaskStatus::RUNNING;
    }

    /**
     * Check whether it is a final status.
     *
     * @param TaskStatus $status Status
     * @return bool Whether it is a final status
     */
    private static function isFinalStatus(TaskStatus $status): bool
    {
        return in_array($status, [
            TaskStatus::FINISHED,
            TaskStatus::ERROR,
            TaskStatus::Stopped,
        ], true);
    }
}
