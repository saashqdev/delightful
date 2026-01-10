<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

/**
 * crossorganizationexceedslevelpermissionenumcategory.
 */
enum SuperPermissionEnum: string
{
    // alllocaladministrator
    case GLOBAL_ADMIN = 'global_admin';

    // processadministrator,itemfrontonly queryToolSets useto
    case FLOW_ADMIN = 'flow_admin';

    // (thethreesideplatform)assistantadministrator
    case ASSISTANT_ADMIN = 'assistant_admin';

    // bigmodelconfigurationmanage
    case MODEL_CONFIG_ADMIN = 'model_config_admin';

    // hiddendepartmentorpersonuser
    case HIDE_USER_OR_DEPT = 'hide_user_or_dept';

    // privilegehairmessage
    case PRIVILEGE_SEND_MESSAGE = 'privilege_send_message';

    // Delightfulmultipleenvironmentmanage
    case DELIGHTFUL_ENV_MANAGEMENT = 'delightful_env_management';

    // servicequotientadministrator
    case SERVICE_PROVIDER_ADMIN = 'service_provider_admin';

    // exceedslevelDelightfulinvitationuseuser
    case SUPER_INVITE_USER = 'be_delightful_invite_use_user';

    // exceedslevelDelightfulkanbanmanagepersonmember
    case SUPER_DELIGHTFUL_BOARD_ADMIN = 'be_delightful_board_manager';

    // exceedslevelDelightfulkanbanoperationpersonmember
    case SUPER_DELIGHTFUL_BOARD_OPERATOR = 'be_delightful_board_operator';
}
