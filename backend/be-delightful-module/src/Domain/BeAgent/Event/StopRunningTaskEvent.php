<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\delete DataType;
/** * StopRunningTaskEvent * Whenworkspace Itemor topic delete trigger for AsyncStoprelated RunningTask */

class StopRunningTaskEvent extends AbstractEvent 
{
 /** * Function. * * @param delete DataType $dataType DataTypeworkspace Itemtopic  * @param int $dataId DataID * @param string $userId user ID * @param string $organizationCode organization code * @param string $reason Stop */ 
    public function __construct( 
    private delete DataType $dataType, 
    private int $dataId, 
    private string $userId, 
    private string $organizationCode, 
    private string $reason = '' ) 
{
 // Call parent constructor to generate snowflake ID parent::__construct(); // Set default reason if not provided if (empty($this->reason)) 
{
 $this->reason = Related 
{
$this->dataType->getDescription()
}
 has been deleted ; 
}
 
}
 /** * FromArrayCreateEvent. * * @param array $data EventDataArray */ 
    public 
    static function fromArray(array $data): self 
{
 $dataType = delete DataType::from($data['data_type'] ?? delete DataType::TOPIC->value); $dataId = (int) ($data['data_id'] ?? 0); $userId = (string) ($data['user_id'] ?? ''); $organizationCode = (string) ($data['organization_code'] ?? ''); $reason = (string) ($data['reason'] ?? ''); return new self($dataType, $dataId, $userId, $organizationCode, $reason); 
}
 /** * Convert toArray. * * @return array EventDataArray */ 
    public function toArray(): array 
{
 return [ 'event_id' => $this->getEventId(), 'data_type' => $this->dataType->value, 'data_id' => $this->dataId, 'user_id' => $this->userId, 'organization_code' => $this->organizationCode, 'reason' => $this->reason, 'timestamp' => time(), ]; 
}
 /** * GetDataType. */ 
    public function getDataType(): delete DataType 
{
 return $this->dataType; 
}
 /** * GetDataID. */ 
    public function getDataId(): int 
{
 return $this->dataId; 
}
 /** * Getuser ID. */ 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 /** * Getorganization code . */ 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 /** * GetStop. */ 
    public function getReason(): string 
{
 return $this->reason; 
}
 
}
 
