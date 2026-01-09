<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

/**
 * 跨organization超levelpermissionenumcategory.
 */
enum SuperPermissionEnum: string
{
    // all局administrator
    case GLOBAL_ADMIN = 'global_admin';

    // processadministrator,目frontonly queryToolSets useto
    case FLOW_ADMIN = 'flow_admin';

    // (thethree方platform)assistantadministrator
    case ASSISTANT_ADMIN = 'assistant_admin';

    // bigmodelconfigurationmanage
    case MODEL_CONFIG_ADMIN = 'model_config_admin';

    // hiddendepartmentor者user
    case HIDE_USER_OR_DEPT = 'hide_user_or_dept';

    // privilegehairmessage
    case PRIVILEGE_SEND_MESSAGE = 'privilege_send_message';

    // Magic多environmentmanage
    case DELIGHTFUL_ENV_MANAGEMENT = 'delightful_env_management';

    // servicequotientadministrator
    case SERVICE_PROVIDER_ADMIN = 'service_provider_admin';

    // 超levelMagicinvitationuseuser
    case SUPER_INVITE_USER = 'be_delightful_invite_use_user';

    // 超levelMagickanbanmanageperson员
    case SUPER_DELIGHTFUL_BOARD_ADMIN = 'be_delightful_board_manager';

    // 超levelMagickanbanoperationperson员
    case SUPER_DELIGHTFUL_BOARD_OPERATOR = 'be_delightful_board_operator';
}
