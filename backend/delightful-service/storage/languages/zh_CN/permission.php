<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'resource' => [
        'admin' => 'Admin backend',
        'admin_plus' => 'Organization admin backend Plus',
        'admin_ai' => 'AI management',
        'admin_plus_ai' => 'AI management',
        'admin_safe' => 'Security and permissions',
        'safe_sub_admin' => 'Sub-administrator',
        'ai_model' => 'LLM',
        'ai_image' => 'Smart drawing',
        'ai_ability' => 'Capability management',
        'ai_mode' => 'Mode',
        'console' => 'Console',
        'api' => 'API',
        'api_assistant' => 'API assistant',
        'platform' => 'Platform management',
        'platform_ai' => 'AI management',
        'platform_setting' => 'System settings',
        'platform_setting_platform_info' => 'Platform information',
        'platform_setting_maintenance' => 'Maintenance management',
        'platform_organization' => 'Organization management',
        'platform_organization_list' => 'Organization list',
    ],
    // Top-level errors and validation
    'validate_failed' => 'Validation failed',
    'business_exception' => 'Business exception',
    'access_denied' => 'Access denied',
    // Organization related errors (PermissionErrorCode 42***)
    'organization_code_required' => 'Organization code required',
    'organization_name_required' => 'Organization name required',
    'organization_industry_type_required' => 'Organization industry type required',
    'organization_seats_invalid' => 'Organization seats invalid',
    'organization_code_exists' => 'Organization code already exists',
    'organization_name_exists' => 'Organization name already exists',
    'organization_not_exists' => 'Organization does not exist',
    'operation' => [
        'query' => 'Query',
        'edit' => 'Edit',
    ],
    'error' => [
        'role_name_exists' => 'Role name :name already exists',
        'role_not_found' => 'Role not found',
        'invalid_permission_key' => 'Permission key :key invalid',
        'access_denied' => 'Access denied',
        'user_already_organization_admin' => 'User :userId is already organization admin',
        'organization_admin_not_found' => 'Organization admin not found',
        'organization_creator_cannot_be_revoked' => 'Organization creator cannot be revoked',
        'organization_creator_cannot_be_disabled' => 'Organization creator cannot be disabled',
        'current_user_not_organization_creator' => 'Current user is not organization creator',
        'personal_organization_cannot_grant_admin' => 'Personal organization cannot grant admin',
    ],
];
