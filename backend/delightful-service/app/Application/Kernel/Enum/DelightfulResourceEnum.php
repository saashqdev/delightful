<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Enum;

use function Hyperf\Translation\__;

/**
 * Delightful 资源枚举.
 *
 * 1. use Backed Enum 将each资源mapping为唯一string key。
 * 2. passmethod提供 label / parent  etcyuaninfo，方便back续generatepermission树、做 i18n etc。
 * 3. 仅定义资源本身，not涉及操作type（如 query / edit）。
 *
 * 注意：if你修改了这file，请execute单yuantest PermissionApiTest.testGetPermissionTree.
 */
enum DelightfulResourceEnum: string
{
    // ===== toplevel =====
    case PLATFORM = 'platform'; # 平台管理back台
    case ADMIN = 'admin'; # organization管理back台
    case ADMINPLUS = 'admin_plus'; # organization管理back台plus

    // ===== 二level：模piece =====
    case ADMIN_AI = 'admin.ai'; # 平台管理back台-AI管理
    case ADMIN_SAFE = 'admin.safe'; # security管控
    case PLATFORM_AI = 'platform.ai'; # 平台管理back台-AI管理
    case PLATFORM_SETTING = 'platform.setting'; # 系统set
    case PLATFORM_ORGANIZATION = 'platform.organization'; # organization管理
    case ADMINPLUS_AI = 'admin_plus.ai'; # organization管理back台plus-AI管理

    // ===== 三level：specific资源 (useatspecificbindinterface）=====
    case ADMIN_AI_MODEL = 'platform.ai.model_management'; # AI管理-model管理
    case ADMIN_AI_IMAGE = 'platform.ai.image_generation'; # AI管理-智能绘图管理
    case ADMIN_AI_MODE = 'platform.ai.mode_management'; # AI管理-模type管理管理
    case ADMIN_AI_ABILITY = 'platform.ai.ability'; # AI管理-能力管理
    case SAFE_SUB_ADMIN = 'admin.safe.sub_admin';  # security管控-子管理员
    case PLATFORM_SETTING_PLATFORM_INFO = 'platform.setting.platform_info'; # 平台管理 - 系统set - 平台info
    case PLATFORM_SETTING_MAINTENANCE = 'platform.setting.maintenance'; # 平台管理 - 系统info - 维护管理
    case PLATFORM_ORGANIZATION_LIST = 'platform.organization.list'; # 平台管理 - organization管理 - organizationlist
    case ADMINPLUS_AI_MODEL = 'admin_plus.ai.model_management'; # organization管理back台plus-AI管理-model管理

    /**
     * 对应 i18n key.
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::ADMINPLUS => 'permission.resource.admin_plus',
            self::ADMIN => 'permission.resource.admin',
            self::ADMIN_AI => 'permission.resource.admin_ai',
            self::ADMINPLUS_AI => 'permission.resource.admin_plus_ai',
            self::ADMIN_SAFE => 'permission.resource.admin_safe', # security与permission
            self::ADMIN_AI_MODEL => 'permission.resource.ai_model',
            self::ADMINPLUS_AI_MODEL => 'permission.resource.ai_model',
            self::ADMIN_AI_IMAGE => 'permission.resource.ai_image',
            self::ADMIN_AI_MODE => 'permission.resource.ai_mode',
            self::ADMIN_AI_ABILITY => 'permission.resource.ai_ability',
            self::SAFE_SUB_ADMIN => 'permission.resource.safe_sub_admin', # 子管理员
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
     * uplevel资源.
     * 注意：新增操作资源back要补充这configuration.
     */
    public function parent(): ?self
    {
        return match ($this) {
            // 平台
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
            // 操作资源
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
     * return与该资源bind的 Operation Enum category名。
     * defaultuse DelightfulOperationEnum。
     * 如需为特定资源customize操作集，可in此returncustomize Enum::class。
     */
    public function operationEnumClass(): string
    {
        return DelightfulOperationEnum::class;
    }
}
