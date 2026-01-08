<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'fields' => [
        'code' => 'Code',
        'name' => 'Name',
        'description' => 'Description',
        'status' => 'Status',
        'external_sse_url' => 'MCP service address',
        'url' => 'URL',
        'command' => 'Command',
        'arguments' => 'Arguments',
        'headers' => 'Request headers',
        'env' => 'Environment variables',
        'oauth2_config' => 'OAuth2 configuration',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'client_url' => 'Client URL',
        'scope' => 'Authorization scope',
        'authorization_url' => 'Authorization URL',
        'authorization_content_type' => 'Authorization content type',
        'issuer_url' => 'Issuer URL',
        'redirect_uri' => 'Redirect URI',
        'use_pkce' => 'Use PKCE',
        'response_type' => 'Response type',
        'grant_type' => 'Grant type',
        'additional_params' => 'Additional parameters',
        'created_at' => 'Creation time',
        'updated_at' => 'Update time',
    ],
    'auth_type' => [
        'none' => 'No authentication',
        'oauth2' => 'OAuth2 authentication',
    ],

    // Error messages
    'validate_failed' => 'Validation failed',
    'not_found' => 'Data does not exist',

    // Service related errors
    'service' => [
        'already_exists' => 'MCP service already exists',
        'not_enabled' => 'MCP service not enabled',
    ],

    // Server related errors
    'server' => [
        'not_support_check_status' => 'Server status check not supported for this type',
    ],

    // Resource relation errors
    'rel' => [
        'not_found' => 'Related resource not found',
        'not_enabled' => 'Related resource not enabled',
    ],
    'rel_version' => [
        'not_found' => 'Related resource version not found',
    ],

    // Tool errors
    'tool' => [
        'execute_failed' => 'Tool execution failed',
    ],

    // OAuth2 authentication errors
    'oauth2' => [
        'authorization_url_generation_failed' => 'Failed to generate OAuth2 authorization URL',
        'callback_handling_failed' => 'Failed to handle OAuth2 callback',
        'token_refresh_failed' => 'Failed to refresh OAuth2 token',
        'invalid_response' => 'Invalid OAuth2 provider response',
        'provider_error' => 'OAuth2 provider returned error',
        'missing_access_token' => 'No access token received from OAuth2 provider',
        'invalid_service_configuration' => 'Invalid OAuth2 service configuration',
        'missing_configuration' => 'Missing OAuth2 configuration',
        'not_authenticated' => 'No OAuth2 authentication found for this service',
        'no_refresh_token' => 'No refresh token available for token refresh',
        'binding' => [
            'code_empty' => 'Authorization code cannot be empty',
            'state_empty' => 'State parameter cannot be empty',
            'mcp_server_code_empty' => 'MCP service code cannot be empty',
        ],
    ],

    // Command validation errors
    'command' => [
        'not_allowed' => 'Unsupported command ":command", currently only supports: :allowed_commands',
    ],

    // Required field validation errors
    'required_fields' => [
        'missing' => 'Required fields missing: :fields',
        'empty' => 'Required fields cannot be empty: :fields',
    ],

    // STDIO executor related errors
    'executor' => [
        'stdio' => [
            'connection_failed' => 'STDIO executor connection failed',
            'access_denied' => 'STDIO executor functionality temporarily not supported',
        ],
        'http' => [
            'connection_failed' => 'HTTP executor connection failed',
        ],
    ],
];
