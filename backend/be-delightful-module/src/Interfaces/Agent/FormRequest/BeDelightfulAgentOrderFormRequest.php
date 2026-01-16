<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\FormRequest;

use Hyperf\Validate \Request\FormRequest;
use function Hyperf\Translation\trans;

class BeDelightfulAgentOrderFormRequest extends FormRequest 
{
 /** * Validate Rule. */ 
    public function rules(): array 
{
 return [ // Sortlist 'frequent' => 'nullable|array', 'frequent.*' => 'string|max:50', // code // AllSortlist 'all' => 'nullable|array', 'all.*' => 'string|max:50', // code ]; 
}
 /** * Field. */ 
    public function attributes(): array 
{
 return [ 'frequent' => trans('super_magic.agent.order.frequent'), 'frequent.*' => trans('super_magic.agent.fields.code'), 'all' => trans('super_magic.agent.order.all'), 'all.*' => trans('super_magic.agent.fields.code'), ]; 
}
 /** * CustomValidate errorMessage. */ 
    public function messages(): array 
{
 return [ // SortValidate 'frequent.array' => trans('super_magic.agent.validation.frequent_array'), 'frequent.*.string' => trans('super_magic.agent.validation.frequent_code_string'), 'frequent.*.max' => trans('super_magic.agent.validation.frequent_code_max'), // AllSortValidate 'all.array' => trans('super_magic.agent.validation.all_array'), 'all.*.string' => trans('super_magic.agent.validation.all_code_string'), 'all.*.max' => trans('super_magic.agent.validation.all_code_max'), ]; 
}
 /** * AuthorizeValidate . */ 
    public function authorize(): bool 
{
 return true; 
}
 
}
 
