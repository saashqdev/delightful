<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Interfaces\Agent\FormRequest;

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
            'optimization_type.required' => '优化类型不能为空',
            'optimization_type.in' => '优化类型无效',
            'agent.required' => '智能体数据不能为空',
            'agent.array' => '智能体数据必须是数组格式',
        ];
    }
}
