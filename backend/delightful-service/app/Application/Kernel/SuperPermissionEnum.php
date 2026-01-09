<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

/**
 * 跨organization的超levelpermission枚举category.
 */
enum SuperPermissionEnum: string
{
    // all局管理员
    case GLOBAL_ADMIN = 'global_admin';

    // process管理员,目frontonly queryToolSets useto
    case FLOW_ADMIN = 'flow_admin';

    // （the三方平台的）助理管理员
    case ASSISTANT_ADMIN = 'assistant_admin';

    // 大modelconfiguration管理
    case MODEL_CONFIG_ADMIN = 'model_config_admin';

    // 隐藏departmentor者user
    case HIDE_USER_OR_DEPT = 'hide_user_or_dept';

    // 特权hairmessage
    case PRIVILEGE_SEND_MESSAGE = 'privilege_send_message';

    // 麦吉多环境管理
    case DELIGHTFUL_ENV_MANAGEMENT = 'delightful_env_management';

    // service商的管理员
    case SERVICE_PROVIDER_ADMIN = 'service_provider_admin';

    // 超level麦吉邀请useuser
    case SUPER_INVITE_USER = 'be_delightful_invite_use_user';

    // 超level麦吉看板管理人员
    case SUPER_DELIGHTFUL_BOARD_ADMIN = 'be_delightful_board_manager';

    // 超level麦吉看板运营人员
    case SUPER_DELIGHTFUL_BOARD_OPERATOR = 'be_delightful_board_operator';
}
