<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute must be accepted',
    'active_url' => ':attribute must be a valid URL',
    'after' => ':attribute must be a date after :date',
    'after_or_equal' => ':attribute must be a date after or equal to :date',
    'alpha' => ':attribute may only contain letters',
    'alpha_dash' => ':attribute may only contain letters, numbers, dashes and underscores',
    'alpha_num' => ':attribute may only contain letters and numbers',
    'array' => ':attribute must be an array',
    'before' => ':attribute must be a date before :date',
    'before_or_equal' => ':attribute must be a date before or equal to :date',
    'between' => [
        'numeric' => ':attribute must be between :min and :max',
        'file' => ':attribute must be between :min and :max kilobytes',
        'string' => ':attribute must be between :min and :max characters',
        'array' => ':attribute must have between :min and :max items',
    ],
    'boolean' => ':attribute field must be true or false',
    'confirmed' => ':attribute confirmation does not match',
    'date' => ':attribute is not a valid date',
    'date_format' => ':attribute does not match the format :format',
    'decimal' => ':attribute must have :decimal decimal places',
    'different' => ':attribute and :other must be different',
    'digits' => ':attribute must be :digits digits',
    'digits_between' => ':attribute must be between :min and :max digits',
    'dimensions' => ':attribute has invalid image dimensions',
    'distinct' => ':attribute field has a duplicate value',
    'email' => ':attribute must be a valid email address',
    'exists' => 'The selected :attribute is invalid',
    'file' => ':attribute must be a file',
    'filled' => ':attribute field must have a value',
    'gt' => [
        'numeric' => ':attribute must be greater than :value',
        'file' => ':attribute must be greater than :value kilobytes',
        'string' => ':attribute must be greater than :value characters',
        'array' => ':attribute must have more than :value items',
    ],
    'gte' => [
        'numeric' => ':attribute must be greater than or equal to :value',
        'file' => ':attribute must be greater than or equal to :value kilobytes',
        'string' => ':attribute must be greater than or equal to :value characters',
        'array' => ':attribute must have :value items or more',
    ],
    'image' => ':attribute must be an image',
    'in' => 'The selected :attribute is invalid',
    'in_array' => ':attribute field does not exist in :other',
    'integer' => ':attribute must be an integer',
    'ip' => ':attribute must be a valid IP address',
    'ipv4' => ':attribute must be a valid IPv4 address',
    'ipv6' => ':attribute must be a valid IPv6 address',
    'json' => ':attribute must be a valid JSON string',
    'list' => ':attribute must be a list',
    'lt' => [
        'numeric' => ':attribute must be less than :value',
        'file' => ':attribute must be less than :value kilobytes',
        'string' => ':attribute must be less than :value characters',
        'array' => ':attribute must have less than :value items',
    ],
    'lte' => [
        'numeric' => ':attribute must be less than or equal to :value',
        'file' => ':attribute must be less than or equal to :value kilobytes',
        'string' => ':attribute must be less than or equal to :value characters',
        'array' => ':attribute must not have more than :value items',
    ],
    'max' => [
        'numeric' => ':attribute may not be greater than :max',
        'file' => ':attribute may not be greater than :max kilobytes',
        'string' => ':attribute may not be greater than :max characters',
        'array' => ':attribute may not have more than :max items',
    ],
    'mimes' => ':attribute must be a file of type: :values',
    'mimetypes' => ':attribute must be a file of type: :values',
    'min' => [
        'numeric' => ':attribute must be at least :min',
        'file' => ':attribute must be at least :min kilobytes',
        'string' => ':attribute must be at least :min characters',
        'array' => ':attribute must have at least :min items',
    ],
    'not_in' => 'The selected :attribute is invalid',
    'not_regex' => ':attribute format is invalid',
    'numeric' => ':attribute must be a number',
    'present' => ':attribute field must be present',
    'prohibits' => ':attribute field must be provided',
    'regex' => ':attribute format is invalid',
    'required' => ':attribute field is required',
    'required_if' => ':attribute field is required when :other is :value',
    'required_unless' => ':attribute field is required unless :other is in :values',
    'required_with' => ':attribute field is required when :values is present',
    'required_with_all' => ':attribute field is required when :values are present',
    'required_without' => ':attribute field is required when :values is not present',
    'required_without_all' => ':attribute field is required when none of :values are present',
    'exclude' => ':attribute field is excluded',
    'exclude_if' => ':attribute field is excluded when :other is :value',
    'exclude_unless' => ':attribute field is excluded unless :other is in :values',
    'exclude_with' => ':attribute field is excluded when :values is present',
    'exclude_without' => ':attribute field is excluded when :values is not present',
    'same' => ':attribute and :other must match',
    'size' => [
        'numeric' => ':attribute must be :size',
        'file' => ':attribute must be :size kilobytes',
        'string' => ':attribute must be :size characters',
        'array' => ':attribute must contain :size items',
    ],
    'starts_with' => ':attribute must start with one of the following: :values',
    'string' => ':attribute must be a string',
    'timezone' => ':attribute must be a valid zone',
    'unique' => ':attribute has already been taken',
    'uploaded' => ':attribute failed to upload',
    'url' => ':attribute format is invalid',
    'uuid' => ':attribute must be a valid UUID',
    'max_if' => [
        'numeric' => ':attribute must not be greater than :max when :other is :value',
        'file' => ':attribute must not be greater than :max kilobytes when :other is :value',
        'string' => ':attribute must not be greater than :max characters when :other is :value',
        'array' => ':attribute must not have more than :max items when :other is :value',
    ],
    'min_if' => [
        'numeric' => ':attribute must be at least :min when :other is :value',
        'file' => ':attribute must be at least :min kilobytes when :other is :value',
        'string' => ':attribute must be at least :min characters when :other is :value',
        'array' => ':attribute must have at least :min items when :other is :value',
    ],
    'between_if' => [
        'numeric' => ':attribute must be between :min - :max when :other is :value',
        'file' => ':attribute must be between :min - :max kilobytes when :other is :value',
        'string' => ':attribute must be between :min - :max characters when :other is :value',
        'array' => ':attribute must have between :min - :max items when :other is :value',
    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],
    'phone_number' => ':attribute must be a valid phone number',
    'telephone_number' => ':attribute must be a valid mobile phone number',

    'chinese_word' => ':attribute must contain valid characters (Chinese/English, numbers, underscores)',
    'sequential_array' => ':attribute must be a sequential array',
];
