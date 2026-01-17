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
     * 验证规则.
     */
    public function rules(): array
    {
        return [
            // 常用智能体排序列表
            'frequent' => 'nullable|array',
            'frequent.*' => 'string|max:50', // 智能体code

            // 全部智能体排序列表
            'all' => 'nullable|array',
            'all.*' => 'string|max:50', // 智能体code
        ];
    }

    /**
     * 字段别名.
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
     * 自定义验证错误消息.
     */
    public function messages(): array
    {
        return [
            // 常用智能体排序验证
            'frequent.array' => trans('be_delightful.agent.validation.frequent_array'),
            'frequent.*.string' => trans('be_delightful.agent.validation.frequent_code_string'),
            'frequent.*.max' => trans('be_delightful.agent.validation.frequent_code_max'),

            // 全部智能体排序验证
            'all.array' => trans('be_delightful.agent.validation.all_array'),
            'all.*.string' => trans('be_delightful.agent.validation.all_code_string'),
            'all.*.max' => trans('be_delightful.agent.validation.all_code_max'),
        ];
    }

    /**
     * 授权验证.
     */
    public function authorize(): bool
    {
        return true;
    }
}
