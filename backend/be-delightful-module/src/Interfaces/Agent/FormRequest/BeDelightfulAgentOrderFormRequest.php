<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\FormRequest;

use Hyperf\Validation\Request\FormRequest;

use function Hyperf\Translation\trans;

class BeDelightfulAgentOrderFormRequest extends FormRequest
{
    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            // Frequently used agents order list
            'frequent' => 'nullable|array',
            'frequent.*' => 'string|max:50', // Agent code

            // All agents order list
            'all' => 'nullable|array',
            'all.*' => 'string|max:50', // Agent code
        ];
    }

    /**
     * Field aliases.
     */
    public function attributes(): array
    {
        return [
            'frequent' => trans('be_delightful.agent.order.frequent'),
            'frequent.*' => trans('be_delightful.agent.fields.code'),
            'all' => trans('be_delightful.agent.order.all'),
            'all.*' => trans('be_delightful.agent.fields.code'),
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            // Frequently used agents order validation
            'frequent.array' => trans('be_delightful.agent.validation.frequent_array'),
            'frequent.*.string' => trans('be_delightful.agent.validation.frequent_code_string'),
            'frequent.*.max' => trans('be_delightful.agent.validation.frequent_code_max'),

            // All agents order validation
            'all.array' => trans('be_delightful.agent.validation.all_array'),
            'all.*.string' => trans('be_delightful.agent.validation.all_code_string'),
            'all.*.max' => trans('be_delightful.agent.validation.all_code_max'),
        ];
    }

    /**
     * Authorization check.
     */
    public function authorize(): bool
    {
        return true;
    }
}
