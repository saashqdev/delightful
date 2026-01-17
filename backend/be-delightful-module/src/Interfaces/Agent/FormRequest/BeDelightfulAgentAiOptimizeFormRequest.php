<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\FormRequest;

use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentOptimizationType;
use Hyperf\Validation\Request\FormRequest;

class BeDelightfulAgentAiOptimizeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'optimization_type' => ['required', 'string', function ($attribute, $value, $fail) {
                if (BeDelightfulAgentOptimizationType::fromString($value)->isNone()) {
                    $fail('The ' . $attribute . ' is invalid.');
                }
            }],
            'agent' => 'required|array',
            'agent.name' => 'nullable|string',
            'agent.description' => 'nullable|string',
            'agent.prompt' => 'nullable|array',
            'agent.tools' => 'nullable|array',
            'agent.icon' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'optimization_type.required' => 'Optimization type is required',
            'optimization_type.in' => 'Invalid optimization type',
            'agent.required' => 'Agent data is required',
            'agent.array' => 'Agent data must be in array format',
        ];
    }
}
