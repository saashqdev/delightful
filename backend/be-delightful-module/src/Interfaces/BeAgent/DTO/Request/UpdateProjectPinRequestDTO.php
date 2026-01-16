<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;
use function Hyperf\Translation\__;
/** * UpdateItempinned StatusRequest DTO * for Receivepinned /cancel pinned ItemRequestParameter. */

class UpdateProjectPinRequestDTO extends AbstractRequestDTO 
{
 /** * whether pinned false-cancel pinned true-pinned . */ 
    public bool $isPin = false; /** * Getwhether pinned . */ 
    public function getIsPin(): bool 
{
 return $this->isPin; 
}
 /** * Set whether pinned . */ 
    public function setIsPin(bool $isPin): void 
{
 $this->isPin = $isPin; 
}
 /** * check whether as pin operation . */ 
    public function isPinOperation(): bool 
{
 return $this->isPin; 
}
 /** * Get validation rules. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'is_pin' => 'required|boolean', ]; 
}
 /** * Get custom error messages for validation failures. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'is_pin.required' => __('project.pin.is_pin_required'), 'is_pin.boolean' => __('project.pin.is_pin_must_be_boolean'), ]; 
}
 
}
 
