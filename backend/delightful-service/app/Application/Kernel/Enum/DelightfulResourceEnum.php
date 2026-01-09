<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Enum;

use function Hyperf\Translation\__;

/**
 * Delightful resource枚举.
 *
 * 1. use Backed Enum willeachresourcemappingfor唯onestring key.
 * 2. passmethodprovide label / parent  etcyuaninfo,convenientback续generatepermissiontree,做 i18n etc.
 * 3. onlydefinitionresourceitself,not涉and操astype(如 query / edit).
 *
 * notice:ifyoumodifythisfile,请executesingleyuantest PermissionApiTest.testGetPermissionTree.
 */
enum DelightfulResourceEnum: string
{
    // ===== toplevel =====
    case PLATFORM = 'platform'; # platformmanageback台
    case ADMIN = 'admin'; # organizationmanageback台
    case ADMINPLUS = 'admin_plus'; # organizationmanageback台plus

    // ===== twolevel:模piece =====
    case ADMIN_AI = 'admin.ai'; # platformmanageback台-AImanage
    case ADMIN_SAFE = 'admin.safe'; # security管控
    case PLATFORM_AI = 'platform.ai'; # platformmanageback台-AImanage
    case PLATFORM_SETTING = 'platform.setting'; # systemset
    case PLATFORM_ORGANIZATION = 'platform.organization'; # organizationmanage
    case ADMINPLUS_AI = 'admin_plus.ai'; # organizationmanageback台plus-AImanage

    // ===== threelevel:specificresource (useatspecificbindinterface)=====
    case ADMIN_AI_MODEL = 'platform.ai.model_management'; # AImanage-modelmanage
    case ADMIN_AI_IMAGE = 'platform.ai.image_generation'; # AImanage-智can绘graphmanage
    case ADMIN_AI_MODE = 'platform.ai.mode_management'; # AImanage-模typemanagemanage
    case ADMIN_AI_ABILITY = 'platform.ai.ability'; # AImanage-can力manage
    case SAFE_SUB_ADMIN = 'admin.safe.sub_admin';  # security管控-子administrator
    case PLATFORM_SETTING_PLATFORM_INFO = 'platform.setting.platform_info'; # platformmanage - systemset - platforminfo
    case PLATFORM_SETTING_MAINTENANCE = 'platform.setting.maintenance'; # platformmanage - systeminfo - 维护manage
    case PLATFORM_ORGANIZATION_LIST = 'platform.organization.list'; # platformmanage - organizationmanage - organizationlist
    case ADMINPLUS_AI_MODEL = 'admin_plus.ai.model_management'; # organizationmanageback台plus-AImanage-modelmanage

    /**
     * to应 i18n key.
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
            self::SAFE_SUB_ADMIN => 'permission.resource.safe_sub_admin', # 子administrator
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
     * notice:new操asresourcebackwantsupplementthisconfiguration.
     */
    public function parent(): ?self
    {
        return match ($this) {
            // platform
            self::ADMIN,
            self::ADMINPLUS,
            self::PLATFORM => null,
            // 模piece
            self::PLATFORM_AI,
            self::PLATFORM_SETTING,
            self::PLATFORM_ORGANIZATION => self::PLATFORM,
            self::ADMIN_AI,
            self::ADMIN_SAFE => self::ADMIN,
            self::ADMINPLUS_AI => self::ADMINPLUS,
            // 操asresource
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
     * returnandtheresourcebind Operation Enum category名.
     * defaultuse DelightfulOperationEnum.
     * 如需for特定resourcecustomize操ascollection,caninthisreturncustomize Enum::class.
     */
    public function operationEnumClass(): string
    {
        return DelightfulOperationEnum::class;
    }
}
