<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

use function Hyperf\Translation\__;

/**
 * Update project pin status request DTO
 * Used to receive request parameters for pinning/unpinning a project.
 */
class UpdateProjectPinRequestDTO extends AbstractRequestDTO
{
    /**
     * Whether to pin: false-unpin, true-pin.
     */
    public bool $isPin = false;

    /**
     * Get whether to pin.
     */
    public function getIsPin(): bool
    {
        return $this->isPin;
    }

    /**
     * Set whether to pin.
     */
    public function setIsPin(bool $isPin): void
    {
        $this->isPin = $isPin;
    }

    /**
     * Check if it is a pin operation.
     */
    public function isPinOperation(): bool
    {
        return $this->isPin;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'is_pin' => 'required|boolean',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'is_pin.required' => __('project.pin.is_pin_required'),
            'is_pin.boolean' => __('project.pin.is_pin_must_be_boolean'),
        ];
    }
}
