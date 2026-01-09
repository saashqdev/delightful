<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

/**
 * 跨organization超levelpermission枚举category.
 */
enum SuperPermissionEnum: string
{
    // all局administrator
    case GLOBAL_ADMIN = 'global_admin';

    // processadministrator,目frontonly queryToolSets useto
    case FLOW_ADMIN = 'flow_admin';

    // (thethree方platform)助理administrator
    case ASSISTANT_ADMIN = 'assistant_admin';

    // bigmodelconfigurationmanage
    case MODEL_CONFIG_ADMIN = 'model_config_admin';

    // hiddendepartmentor者user
    case HIDE_USER_OR_DEPT = 'hide_user_or_dept';

    // 特权hairmessage
    case PRIVILEGE_SEND_MESSAGE = 'privilege_send_message';

    // 麦吉多environmentmanage
    case DELIGHTFUL_ENV_MANAGEMENT = 'delightful_env_management';

    // servicequotientadministrator
    case SERVICE_PROVIDER_ADMIN = 'service_provider_admin';

    // 超level麦吉邀请useuser
    case SUPER_INVITE_USER = 'be_delightful_invite_use_user';

    // 超level麦吉看板manageperson员
    case SUPER_DELIGHTFUL_BOARD_ADMIN = 'be_delightful_board_manager';

    // 超level麦吉看板运营person员
    case SUPER_DELIGHTFUL_BOARD_OPERATOR = 'be_delightful_board_operator';
}
