<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
/** * Task status validation utility class. * * Provides status transition rule validation to prevent state competition caused by message disorder * * Status categories: * - Waiting status: WAITING - can transition to any status * - Active status: RUNNING - can only transition to 
    final or suspended status * - Recoverable status: Suspended - can only transition to WAITING or 
    final status * - Final status: FINISHED, ERROR, Stopped - can transition to WAITING or other 
    final status */

class TaskStatusValidator 
{
 /** * Validate whether status transition is allowed. * * @param null|TaskStatus $currentStatus current status * @param TaskStatus $newStatus New status * @return bool whether transition is allowed */ 
    public 
    static function isTransitionAllowed(?TaskStatus $currentStatus, TaskStatus $newStatus): bool 
{
 // If current status cannot be obtained, allow transition if ($currentStatus === null) 
{
 return true; 
}
 // Same status, allow (idempotent operation) if ($currentStatus === $newStatus) 
{
 return true; 
}
 // Apply status transition rules return self::applyTransitionRules($currentStatus, $newStatus); 
}
 /** * Get status type description. * * @param TaskStatus $status Status * @return string Status type */ 
    public 
    static function getStatusType(TaskStatus $status): string 
{
 if (self::isWaitingStatus($status)) 
{
 return 'waiting'; 
}
 if (self::isActiveStatus($status)) 
{
 return 'active'; 
}
 if (self::isFinalStatus($status)) 
{
 return 'final'; 
}
 if ($status === TaskStatus::Suspended) 
{
 return 'suspended'; 
}
 return 'unknown'; 
}
 /** * Get reason for rejecting transition. * * @param TaskStatus $currentStatus current status * @param TaskStatus $newStatus New status * @return string Rejection reason */ 
    public 
    static function getRejectReason(TaskStatus $currentStatus, TaskStatus $newStatus): string 
{
 if ($currentStatus === TaskStatus::RUNNING && $newStatus === TaskStatus::WAITING) 
{
 return 'Running task cannot go back to waiting status'; 
}
 if ($currentStatus === TaskStatus::RUNNING && ! self::isFinalStatus($newStatus) && $newStatus !== TaskStatus::Suspended) 
{
 return 'Running task can only transition to 
    final or suspended status'; 
}
 if (self::isFinalStatus($currentStatus) && $newStatus === TaskStatus::RUNNING) 
{
 return 'Final status task cannot directly transition to running status'; 
}
 if (self::isFinalStatus($currentStatus) && $newStatus === TaskStatus::Suspended) 
{
 return 'Final status task cannot transition to suspended status'; 
}
 if ($currentStatus === TaskStatus::Suspended && $newStatus === TaskStatus::RUNNING) 
{
 return 'Suspended task needs to be converted to waiting status before execution'; 
}
 if ($currentStatus === TaskStatus::Suspended && ! self::isFinalStatus($newStatus) && $newStatus !== TaskStatus::WAITING) 
{
 return 'Suspended task can only transition to waiting status or 
    final status'; 
}
 return 'Status transition does not comply with business rules'; 
}
 /** * Get allowed next states * * @param TaskStatus $currentStatus current status * @return TaskStatus[] list of allowed next states */ 
    public 
    static function getAllowedNextStates(TaskStatus $currentStatus): array 
{
 $allowed = []; foreach (TaskStatus::cases() as $status) 
{
 if (self::isTransitionAllowed($currentStatus, $status)) 
{
 $allowed[] = $status; 
}
 
}
 return $allowed; 
}
 /** * Validate whether status transition chain is legal. * * @param TaskStatus[] $statusChain Status transition chain * @return array Validate result ['valid' => bool, 'invalid_step' => int|null, 'reason' => string] */ 
    public 
    static function validateTransitionChain(array $statusChain): array 
{
 if (empty($statusChain)) 
{
 return ['valid' => true, 'invalid_step' => null, 'reason' => '']; 
}
 $currentStatus = null; foreach ($statusChain as $index => $status) 
{
 if (! $status instanceof TaskStatus) 
{
 return [ 'valid' => false, 'invalid_step' => $index, 'reason' => Step 
{
$index
}
 contains invalid status value , ]; 
}
 if (! self::isTransitionAllowed($currentStatus, $status)) 
{
 $fromStatus = $currentStatus->value ?? 'null'; return [ 'valid' => false, 'invalid_step' => $index, 'reason' => Step 
{
$index
}
: 
{
$fromStatus
}
 → 
{
$status->value
}
 transition is not allowed , ]; 
}
 $currentStatus = $status; 
}
 return ['valid' => true, 'invalid_step' => null, 'reason' => '']; 
}
 /** * ApplyStatusConvertRule. * * @param TaskStatus $currentStatus current Status * @param TaskStatus $newStatus NewStatus * @return bool whether AllowConvert */ 
    private 
    static function applyTransitionRules(TaskStatus $currentStatus, TaskStatus $newStatus): bool 
{
 // Rule 1: Waiting state can convert to any status if (self::isWaitingStatus($currentStatus)) 
{
 return true; 
}
 // Rule 2: Active state can only convert to 
    final state or pending state if (self::isActiveStatus($currentStatus)) 
{
 return $newStatus === TaskStatus::Suspended || self::isFinalStatus($newStatus); 
}
 // Rule 3: Pending state can only convert to WAITING or 
    final state if ($currentStatus === TaskStatus::Suspended) 
{
 return $newStatus === TaskStatus::WAITING || self::isFinalStatus($newStatus); 
}
 // Rule 4: Final state can only convert to WAITING or other 
    final states if (self::isFinalStatus($currentStatus)) 
{
 return $newStatus === TaskStatus::WAITING || self::isFinalStatus($newStatus); 
}
 // Default allow (unknown status combinations) return true; 
}
 /** * Determine if it is waiting status * * @param TaskStatus $status Status * @return bool whether it is waiting status */ 
    private 
    static function isWaitingStatus(TaskStatus $status): bool 
{
 return $status === TaskStatus::WAITING; 
}
 /** * Determine if it is active status * * @param TaskStatus $status Status * @return bool whether it is active status */ 
    private 
    static function isActiveStatus(TaskStatus $status): bool 
{
 return $status === TaskStatus::RUNNING; 
}
 /** * Determine if it is 
    final status * * @param TaskStatus $status Status * @return bool whether it is 
    final status */ 
    private 
    static function isFinalStatus(TaskStatus $status): bool 
{
 return in_array($status, [ TaskStatus::FINISHED, TaskStatus::ERROR, TaskStatus::Stopped, ], true); 
}
 
}
 
