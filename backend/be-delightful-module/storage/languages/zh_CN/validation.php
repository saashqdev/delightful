<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'file_key_required' => 'File key cannot be empty',
    'file_name_required' => 'File name cannot be empty',
    'file_size_required' => 'File size cannot be empty',
    'project' => [
        'id' => [
            'required' => 'Project ID cannot be empty',
            'string' => 'Project ID must be a string',
        ],
        'members' => [
            'required' => 'Member list cannot be empty',
            'array' => 'Member list must be in array format',
            'min' => 'At least one member must be added',
            'max' => 'Number of members cannot exceed :max',
        ],
        'target_type' => [
            'required' => 'Member type cannot be empty',
            'string' => 'Member type must be a string',
            'in' => 'Member type can only be User or Department',
        ],
        'target_id' => [
            'required' => 'Member ID cannot be empty',
            'string' => 'Member ID must be a string',
            'max' => 'Member ID length cannot exceed :max characters',
        ],
    ],
    // Schedule time validation
    'schedule_time' => [
        'no_repeat' => [
            'day_required' => 'Non-repeating scheduled task must specify a date',
            'time_required' => 'Non-repeating scheduled task must specify a time',
            'must_be_future' => 'Scheduled task execution time must be a future time',
            'must_be_at_least_5_minutes' => 'Scheduled task execution time must be at least 5 minutes from current time',
            'invalid_date_time_format' => 'Date or time format is invalid',
        ],
        'daily_repeat' => [
            'time_required' => 'Daily repeating scheduled task must specify a time',
        ],
        'weekly_repeat' => [
            'day_required' => 'Weekly repeating scheduled task must specify a date',
            'time_required' => 'Weekly repeating scheduled task must specify a time',
            'day_range' => 'Weekly repeat day must be between 0-6',
        ],
        'monthly_repeat' => [
            'day_required' => 'Monthly repeating scheduled task must specify a date',
            'time_required' => 'Monthly repeating scheduled task must specify a time',
            'day_range' => 'Monthly repeat day must be between 1-31',
        ],
        'annually_repeat' => [
            'day_required' => 'Annually repeating scheduled task must specify a date',
            'time_required' => 'Annually repeating scheduled task must specify a time',
        ],
        'weekday_repeat' => [
            'time_required' => 'Weekday repeating scheduled task must specify a time',
        ],
        'custom_repeat' => [
            'day_required' => 'Custom repeating scheduled task must specify a date',
            'time_required' => 'Custom repeating scheduled task must specify a time',
            'unit_required' => 'Custom repeating scheduled task must specify a unit',
            'interval_required' => 'Custom repeating scheduled task must specify an interval',
            'values_required' => 'Custom repeat week and month units must specify values',
        ],
    ],
];
