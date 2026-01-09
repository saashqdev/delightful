<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

/**
 * delightful api error码range:4000, 4999.
 */
enum DelightfulApiErrorCode: int
{
    // token不存在
    #[ErrorMessage(message: 'api.token.not_exist')]
    case TOKEN_NOT_EXIST = 4000;

    #[ErrorMessage(message: 'api.model.not_support')]
    case MODEL_NOT_SUPPORT = 4001;

    #[ErrorMessage(message: 'api.token.model_not_support')]
    case TOKEN_MODEL_NOT_SUPPORT = 4002;

    #[ErrorMessage(message: 'api.token.organization_not_support')]
    case TOKEN_ORGANIZATION_NOT_SUPPORT = 4003;

    // ip不在白名单
    #[ErrorMessage(message: 'api.token.ip_not_in_white_list')]
    case TOKEN_IP_NOT_IN_WHITE_LIST = 4004;

    // tokenexpire
    #[ErrorMessage(message: 'api.token.expired')]
    case TOKEN_EXPIRED = 4005;

    // organization的quota不足
    #[ErrorMessage(message: 'api.organization.quota_not_enough')]
    case ORGANIZATION_QUOTA_NOT_ENOUGH = 4006;

    // accessToken quota不足
    #[ErrorMessage(message: 'api.token.quota_not_enough')]
    case TOKEN_QUOTA_NOT_ENOUGH = 4007;

    // message为null
    #[ErrorMessage(message: 'api.message.empty')]
    case MESSAGE_EMPTY = 4008;

    // 限stream
    #[ErrorMessage(message: 'api.rate_limit')]
    case RATE_LIMIT = 4009;

    // message为null
    #[ErrorMessage(message: 'api.msg_empty')]
    case MSG_EMPTY = 4010;

    // user不存在
    #[ErrorMessage(message: 'api.user_id_not_exist')]
    case USER_ID_NOT_EXIST = 4011;

    // token 计算exception
    #[ErrorMessage(message: 'api.token.calculate_error')]
    case TOKEN_CALCULATE_ERROR = 4012;

    // token createfailed
    #[ErrorMessage(message: 'api.token.create_error')]
    case TOKEN_CREATE_ERROR = 4013;

    // usercreate的tokenquantity超过限制
    #[ErrorMessage(message: 'api.user.create_access_token_limit')]
    case USER_CREATE_ACCESS_TOKEN_LIMIT = 4014;

    // useruse的tokenquantity超过限制
    #[ErrorMessage(message: 'api.user.use_access_token_limit')]
    case USER_USE_ACCESS_TOKEN_LIMIT = 4015;

    // usercreateaccessToken频率限stream
    #[ErrorMessage(message: 'api.user.create_access_token_rate_limit')]
    case USER_CREATE_ACCESS_TOKEN_RATE_LIMIT = 4016;

    // 大modelresponsefailed
    #[ErrorMessage(message: 'api.model.response_fail')]
    case MODEL_RESPONSE_FAIL = 4017;

    // 通用verifyfailed
    #[ErrorMessage(message: 'api.validate_failed')]
    case ValidateFailed = 4018;

    // token被disable
    #[ErrorMessage(message: 'api.token.disabled')]
    case TOKEN_DISABLED = 4019;
}
