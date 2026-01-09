<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Enum;

use function Hyperf\Translation\__;

/**
 * Delightful resourceenum.
 *
 * 1. use Backed Enum willeachresourcemappingforuniqueonestring key.
 * 2. passmethodprovide label / parent  etcyuaninfo,convenientbackcontinuegeneratepermissiontree,make i18n etc.
 * 3. onlydefinitionresourceitself,not涉andoperationastype(like query / edit).
 *
 * notice:ifyoumodifythisfile,pleaseexecutesingleyuantest PermissionApiTest.testGetPermissionTree.
 */
enum DelightfulResourceEnum: string
{
    // ===== toplevel =====
    case PLATFORM = 'platform'; # platformmanagebackplatform
    case ADMIN = 'admin'; # organizationmanagebackplatform
    case ADMINPLUS = 'admin_plus'; # organizationmanagebackplatformplus

    // ===== twolevel:modepiece =====
    case ADMIN_AI = 'admin.ai'; # platformmanagebackplatform-AImanage
    case ADMIN_SAFE = 'admin.safe'; # securitycontrol
    case PLATFORM_AI = 'platform.ai'; # platformmanagebackplatform-AImanage
    case PLATFORM_SETTING = 'platform.setting'; # systemset
    case PLATFORM_ORGANIZATION = 'platform.organization'; # organizationmanage
    case ADMINPLUS_AI = 'admin_plus.ai'; # organizationmanagebackplatformplus-AImanage

    // ===== threelevel:specificresource (useatspecificbindinterface)=====
    case ADMIN_AI_MODEL = 'platform.ai.model_management'; # AImanage-modelmanage
    case ADMIN_AI_IMAGE = 'platform.ai.image_generation'; # AImanage-智can绘graphmanage
    case ADMIN_AI_MODE = 'platform.ai.mode_management'; # AImanage-modetypemanagemanage
    case ADMIN_AI_ABILITY = 'platform.ai.ability'; # AImanage-cancapabilitymanage
    case SAFE_SUB_ADMIN = 'admin.safe.sub_admin';  # securitycontrol-childadministrator
    case PLATFORM_SETTING_PLATFORM_INFO = 'platform.setting.platform_info'; # platformmanage - systemset - platforminfo
    case PLATFORM_SETTING_MAINTENANCE = 'platform.setting.maintenance'; # platformmanage - systeminfo - maintainmanage
    case PLATFORM_ORGANIZATION_LIST = 'platform.organization.list'; # platformmanage - organizationmanage - organizationlist
    case ADMINPLUS_AI_MODEL = 'admin_plus.ai.model_management'; # organizationmanagebackplatformplus-AImanage-modelmanage

    /**
     * toshould i18n key.
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::ADMINPLUS => 'permission.resource.admin_plus',
            self::ADMIN => 'permission.resource.admin',
            self::ADMIN_AI => 'permission.resource.admin_ai',
            self::ADMINPLUS_AI => 'permission.resource.admin_plus_ai',
            self::ADMIN_SAFE => 'permission.resource.admin_safe', # securityandpermission
            self::ADMIN_AI_MODEL => 'permission.resource.ai_model',
            self::ADMINPLUS_AI_MODEL => 'permission.resource.ai_model',
            self::ADMIN_AI_IMAGE => 'permission.resource.ai_image',
            self::ADMIN_AI_MODE => 'permission.resource.ai_mode',
            self::ADMIN_AI_ABILITY => 'permission.resource.ai_ability',
            self::SAFE_SUB_ADMIN => 'permission.resource.safe_sub_admin', # childadministrator
            self::PLATFORM => 'permission.resource.platform',
            self::PLATFORM_AI => 'permission.resource.platform_ai',
            self::PLATFORM_SETTING => 'permission.resource.platform_setting',
            self::PLATFORM_SETTING_PLATFORM_INFO => 'permission.resource.platform_setting_platform_info',
            self::PLATFORM_SETTING_MAINTENANCE => 'permission.resource.platform_setting_maintenance',
            self::PLATFORM_ORGANIZATION => 'permission.resource.platform_organization',
            self::PLATFORM_ORGANIZATION_LIST => 'permission.resource.platform_organization_list',
        };
    }

    /**
     * uplevelresource.
     * notice:newoperationasresourcebackwantsupplementthisconfiguration.
     */
    public function parent(): ?self
    {
        return match ($this) {
            // platform
            self::ADMIN,
            self::ADMINPLUS,
            self::PLATFORM => null,
            // modepiece
            self::PLATFORM_AI,
            self::PLATFORM_SETTING,
            self::PLATFORM_ORGANIZATION => self::PLATFORM,
            self::ADMIN_AI,
            self::ADMIN_SAFE => self::ADMIN,
            self::ADMINPLUS_AI => self::ADMINPLUS,
            // operationasresource
            self::ADMIN_AI_MODEL,
            self::ADMIN_AI_IMAGE,
            self::ADMIN_AI_MODE => self::PLATFORM_AI,
            self::ADMIN_AI_ABILITY,
            self::SAFE_SUB_ADMIN => self::ADMIN_SAFE,
            self::PLATFORM_SETTING_PLATFORM_INFO => self::PLATFORM_SETTING,
            self::PLATFORM_SETTING_MAINTENANCE => self::PLATFORM_SETTING,
            self::PLATFORM_ORGANIZATION_LIST => self::PLATFORM_ORGANIZATION,
            self::ADMINPLUS_AI_MODEL => self::ADMINPLUS_AI,
        };
    }

    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * returnandtheresourcebind Operation Enum categoryname.
     * defaultuse DelightfulOperationEnum.
     * if neededforspecificresourcecustomizeoperationascollection,caninthisreturncustomize Enum::class.
     */
    public function operationEnumClass(): string
    {
        return DelightfulOperationEnum::class;
    }
}
