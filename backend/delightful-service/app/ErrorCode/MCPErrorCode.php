<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

enum MCPErrorCode: int
{
    #[ErrorMessage(message: 'mcp.validate_failed')]
    case ValidateFailed = 51500; // verifyfail

    #[ErrorMessage(message: 'mcp.not_found')]
    case NotFound = 51501; // datanot存in

    // MCPservice相closeerror码
    #[ErrorMessage(message: 'mcp.service.already_exists')]
    case ServiceAlreadyExists = 51510; // MCPservicealready存in

    #[ErrorMessage(message: 'mcp.service.not_enabled')]
    case ServiceNotEnabled = 51511; // MCPservicenotenable

    // toolassociate相closeerror码
    #[ErrorMessage(message: 'mcp.rel.not_found')]
    case RelNotFound = 51520; // associateresource not found

    #[ErrorMessage(message: 'mcp.rel_version.not_found')]
    case RelVersionNotFound = 51521; // associateresourceversionnot存in

    #[ErrorMessage(message: 'mcp.rel.not_enabled')]
    case RelNotEnabled = 51522; // associateresourcenotenable

    #[ErrorMessage(message: 'mcp.tool.execute_failed')]
    case ToolExecuteFailed = 51523; // toolexecutefail

    // OAuth2auth相closeerror码
    #[ErrorMessage(message: 'mcp.oauth2.authorization_url_generation_failed')]
    case OAuth2AuthorizationUrlGenerationFailed = 51530; // OAuth2authorizationURLgeneratefail

    #[ErrorMessage(message: 'mcp.oauth2.callback_handling_failed')]
    case OAuth2CallbackHandlingFailed = 51531; // OAuth2callbackprocessfail

    #[ErrorMessage(message: 'mcp.oauth2.token_refresh_failed')]
    case OAuth2TokenRefreshFailed = 51532; // OAuth2tokenrefreshfail

    #[ErrorMessage(message: 'mcp.oauth2.invalid_response')]
    case OAuth2InvalidResponse = 51533; // OAuth2providequotientresponseinvalid

    #[ErrorMessage(message: 'mcp.oauth2.provider_error')]
    case OAuth2ProviderError = 51534; // OAuth2providequotientreturnerror

    #[ErrorMessage(message: 'mcp.oauth2.missing_access_token')]
    case OAuth2MissingAccessToken = 51535; // OAuth2responsemiddlemissingaccesstoken

    // OAuth2bindverify相closeerror码
    #[ErrorMessage(message: 'mcp.oauth2.binding.code_empty')]
    case OAuth2BindingCodeEmpty = 51540; // OAuth2bindauthorization码forempty

    #[ErrorMessage(message: 'mcp.oauth2.binding.state_empty')]
    case OAuth2BindingStateEmpty = 51541; // OAuth2bindstatusparameterforempty

    #[ErrorMessage(message: 'mcp.oauth2.binding.mcp_server_code_empty')]
    case OAuth2BindingMcpServerCodeEmpty = 51542; // OAuth2bindMCPservicecodeforempty

    // 必填fieldverify相closeerror码
    #[ErrorMessage(message: 'mcp.required_fields.missing')]
    case RequiredFieldsMissing = 51550; // 必填fieldmissing

    #[ErrorMessage(message: 'mcp.required_fields.empty')]
    case RequiredFieldsEmpty = 51551; // 必填fieldforempty

    // STDIOexecute器相closeerror码
    #[ErrorMessage(message: 'mcp.executor.stdio.connection_failed')]
    case ExecutorStdioConnectionFailed = 51560; // STDIOexecute器connectfail

    #[ErrorMessage(message: 'mcp.executor.stdio.access_denied')]
    case ExecutorStdioAccessDenied = 51561; // STDIOexecute器accessbereject

    // HTTPexecute器相closeerror码
    #[ErrorMessage(message: 'mcp.executor.http.connection_failed')]
    case ExecutorHttpConnectionFailed = 51562; // HTTPexecute器connectfail
}
