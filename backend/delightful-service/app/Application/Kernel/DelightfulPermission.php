<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel;

use App\Application\Kernel\Contract\DelightfulPermissionInterface;
use App\Application\Kernel\Enum\DelightfulAdminResourceEnum;
use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use BackedEnum;
use Exception;
use InvalidArgumentException;

class DelightfulPermission implements DelightfulPermissionInterface
{
    // ========== 全局permission ==========
    public const string ALL_PERMISSIONS = DelightfulAdminResourceEnum::ORGANIZATION_ADMIN->value;

    /**
     * 获取所有操作type.
     */
    public function getOperations(): array
    {
        return array_map(static fn (DelightfulOperationEnum $op) => $op->value, DelightfulOperationEnum::cases());
    }

    /**
     * 获取所有资源.
     */
    public function getResources(): array
    {
        return array_map(static fn (DelightfulResourceEnum $res) => $res->value, DelightfulResourceEnum::cases());
    }

    /**
     * 获取资源的国际化tag（由 DelightfulResourceEnum 提供）.
     */
    public function getResourceLabel(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        $translated = $enum->label();
        // 如果语言包缺失，return的仍然是原始 key，此时抛出exceptionreminder
        if ($translated === $enum->translationKey()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $enum->translationKey());
        }

        return $translated;
    }

    /**
     * build完整permission标识.
     */
    public function buildPermission(string $resource, string $operation): string
    {
        if ($resource === self::ALL_PERMISSIONS) {
            return self::ALL_PERMISSIONS . '.' . $operation;
        }

        if (! in_array($resource, $this->getResources()) || ! in_array($operation, $this->getOperationsByResource($resource), true)) {
            throw new InvalidArgumentException('Invalid resource or operation type');
        }

        return $resource . '.' . $operation;
    }

    /**
     * parsepermission标识.
     */
    public function parsePermission(string $permissionKey): array
    {
        $parts = explode('.', $permissionKey);
        if (count($parts) < 2) {
            throw new InvalidArgumentException('Invalid permission key format');
        }

        $operation = array_pop($parts);
        $resourceKey = implode('.', $parts);

        return [
            'resource' => $resourceKey,
            'operation' => $operation,
        ];
    }

    /**
     * 检查是否为资源type.
     */
    public function isResource(string $value): bool
    {
        return in_array($value, $this->getResources());
    }

    /**
     * 检查是否为操作type.
     */
    public function isOperation(string $value): bool
    {
        return in_array($value, $this->getOperations());
    }

    /**
     * 获取操作的国际化tag.
     */
    public function getOperationLabel(string $operation): string
    {
        $enum = DelightfulOperationEnum::tryFrom($operation);
        if (! $enum) {
            throw new InvalidArgumentException('Not an operation type: ' . $operation);
        }

        $translated = $enum->label();
        if ($translated === $enum->translationKey()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $enum->translationKey());
        }

        return $translated;
    }

    /**
     * 获取资源的模块.
     */
    public function getResourceModule(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        // 模块层定义为二级资源（即平台的直接子资源）
        if ($enum->parent() === null) {
            // 顶级资源（平台本身）
            $moduleEnum = $enum;
        } else {
            $parent = $enum->parent();
            if ($parent->parent() === null) {
                // 当前资源已经是二级层级，直接作为模块
                $moduleEnum = $enum;
            } else {
                // 更深层级，模块取父级（二级）
                $moduleEnum = $parent;
            }
        }

        $moduleLabel = $moduleEnum->label();
        if ($moduleLabel === $moduleEnum->translationKey()) {
            // 如果缺失翻译，手动兼容已知模块
            return match ($moduleEnum) {
                DelightfulResourceEnum::ADMIN_AI => 'AI管理',
                default => $moduleEnum->value,
            };
        }

        return $moduleLabel;
    }

    /**
     * generate所有可能的permission组合.
     */
    public function generateAllPermissions(): array
    {
        $permissions = [];
        $resources = $this->getResources();
        $operations = $this->getOperations();

        foreach ($resources as $resource) {
            // 仅handle三级及以上资源，filter平台和模块级
            if (substr_count($resource, '.') < 2) {
                continue;
            }
            foreach ($this->getOperationsByResource($resource) as $operation) {
                $permissionKey = $this->buildPermission($resource, $operation);
                $permissions[] = [
                    'permission_key' => $permissionKey,
                    'resource' => $resource,
                    'operation' => $operation,
                    'resource_label' => $this->getResourceLabel($resource),
                    'operation_label' => $this->getOperationLabelByResource($resource, $operation),
                ];
            }
        }

        return $permissions;
    }

    /**
     * 获取层级结构的permission树
     * generate无限极permission树,规则：according topermission资源string（如 Admin.ai.model_management）逐段split，逐层构造树。
     *
     * return格式：
     * [
     *   [
     *     'label' => '管理后台',
     *     'permission_key' => 'Admin',
     *     'children' => [ ... ]
     *   ],
     * ]
     *
     * @param bool $isPlatformOrganization 是否平台organization；仅当为 true 时，contain platform 平台的资源树
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $tree = [];

        foreach ($this->generateAllPermissions() as $permission) {
            // 将资源路径按 '.' split
            $segments = explode('.', $permission['resource']);
            if (count($segments) < 2) {
                // 至少应contain平台 + 资源两级，若不足则跳过
                continue;
            }

            $platformKey = array_shift($segments); // 平台，如 Admin

            // 平台organization独有：非平台organization时，filter掉 platform 平台的资源
            if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
                continue;
            }
            // initialize平台根节点
            if (! isset($tree[$platformKey])) {
                $tree[$platformKey] = [
                    'label' => $this->getPlatformLabel($platformKey),
                    'permission_key' => $platformKey,
                    'children' => [],
                ];
            }

            // 自顶向下逐段构造
            $current = &$tree[$platformKey];
            $accumKey = $platformKey;
            foreach ($segments as $index => $segment) {
                $accumKey .= '.' . $segment;
                $isLastSegment = $index === array_key_last($segments);

                // 取 label：第一段use模块中文名，其余按规则
                $label = match (true) {
                    $index === 0 => $this->getResourceModule($permission['resource']),                // 模块层
                    $isLastSegment => $permission['resource_label'],      // 资源层
                    default => ucfirst($segment),                        // 其他中间层
                };

                // 确保 children array存在并检查 segment
                if (! isset($current['children'])) {
                    $current['children'] = [];
                }

                if (! array_key_exists($segment, $current['children'])) {
                    $current['children'][$segment] = [
                        'label' => $label,
                        'permission_key' => $accumKey,
                        'children' => [],
                    ];
                }

                $current = &$current['children'][$segment];
            }

            // 此时 $current 指向资源节点，为其添加操作叶子
            $current['children'][] = [
                'label' => $permission['operation_label'],
                'permission_key' => $permission['permission_key'],
                'full_label' => $permission['resource_label'] . '-' . $permission['operation_label'],
                'is_leaf' => true,
            ];
        }

        // 将关联array children 转为索引array，保持return格式
        return array_values($this->normalizeTree($tree));
    }

    /**
     * 检查permission键是否有效.
     */
    public function isValidPermission(string $permissionKey): bool
    {
        // 全局permission特殊handle
        if ($permissionKey === self::ALL_PERMISSIONS) {
            return true;
        }

        try {
            $parsed = $this->parsePermission($permissionKey);

            // 检查资源是否存在
            $resourceExists = in_array($parsed['resource'], $this->getResources());

            // 检查操作是否存在（按资源）
            $operationExists = in_array($parsed['operation'], $this->getOperationsByResource($parsed['resource']), true);

            return $resourceExists && $operationExists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 判断userpermission集合中是否拥有指定permission（考虑隐式contain）。
     *
     * 规则：
     *   1. 如直接命中permission键，return true；
     *   2. 如果拥有全局permission ALL_PERMISSIONS，return true；
     *   3. 若未命中，则检查由该permission隐式contain的permission集合（for example *edit* 隐式contain *query*）。
     *
     * @param string $permissionKey 目标permission键
     * @param string[] $userPermissions user已拥有的permission键集合
     * @param bool $isPlatformOrganization 是否平台organization
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool
    {
        // 平台organization校验：非平台organization不允许访问 platform 平台资源
        $parsed = $this->parsePermission($permissionKey);
        $platformKey = explode('.', $parsed['resource'])[0];
        if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
            return false;
        }

        // 命中全局permission直接放行
        if (in_array(self::ALL_PERMISSIONS, $userPermissions, true)) {
            return true;
        }

        // 直接命中
        if (in_array($permissionKey, $userPermissions, true)) {
            return true;
        }

        $parsed = $this->parsePermission($permissionKey);
        // 默认隐式：edit -> query（若两操作均存在）
        $ops = $this->getOperationsByResource($parsed['resource']);
        if (in_array(DelightfulOperationEnum::EDIT->value, $ops, true) && in_array(DelightfulOperationEnum::QUERY->value, $ops, true)) {
            if ($parsed['operation'] === DelightfulOperationEnum::QUERY->value) {
                $permissionKey = $this->buildPermission($parsed['resource'], DelightfulOperationEnum::EDIT->value);
                if (in_array($permissionKey, $userPermissions, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * parse资源绑定的 Operation Enum，return该资源可用的操作集合（stringarray）。
     */
    protected function getOperationsByResource(string $resource): array
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        $opEnumClass = $enum
            ? $this->resolveOperationEnumClass($enum)
            : $this->resolveOperationEnumClassFromUnknownResource($resource);
        if (! enum_exists($opEnumClass)) {
            throw new InvalidArgumentException('Operation enum not found for resource: ' . $resource);
        }
        // 仅支持 BackedEnum，因为后续需要读取 ->value
        if (! is_subclass_of($opEnumClass, BackedEnum::class)) {
            throw new InvalidArgumentException('Operation enum for resource must be BackedEnum: ' . $opEnumClass);
        }

        /** @var class-string<BackedEnum> $opEnumClass */
        $cases = $opEnumClass::cases();
        /* @var array<int, \BackedEnum> $cases */
        return array_map(static fn (BackedEnum $case) => $case->value, $cases);
    }

    /**
     * return资源绑定的 Operation Enum 类名，默认读取 `DelightfulResourceEnum::operationEnumClass()`。
     * 企业版可override本method，将企业资源映射到customize的 Operation Enum。
     */
    protected function resolveOperationEnumClass(DelightfulResourceEnum $resourceEnum): string
    {
        return $resourceEnum->operationEnumClass();
    }

    /**
     * 对于非 DelightfulResourceEnum 定义的资源，子类可override该method以parse到相应的 Operation Enum。
     * 开源默认抛错。
     */
    protected function resolveOperationEnumClassFromUnknownResource(string $resource): string
    {
        throw new InvalidArgumentException('Not a resource type: ' . $resource);
    }

    /**
     * 获取按资源的操作tag。
     */
    protected function getOperationLabelByResource(string $resource, string $operation): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        $opEnumClass = $enum
            ? $this->resolveOperationEnumClass($enum)
            : $this->resolveOperationEnumClassFromUnknownResource($resource);
        if (method_exists($opEnumClass, 'tryFrom')) {
            $opEnum = $opEnumClass::tryFrom($operation);
            if (! $opEnum) {
                throw new InvalidArgumentException('Not an operation type: ' . $operation);
            }
            // 要求customize OperationEnum implement label()/translationKey() 与 DelightfulOperationEnum 对齐
            if (method_exists($opEnum, 'label') && method_exists($opEnum, 'translationKey')) {
                $translated = $opEnum->label();
                if ($translated === $opEnum->translationKey()) {
                    ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $opEnum->translationKey());
                }
                return $translated;
            }
            // 兼容：若未implement label/translationKey，则退回通用 getOperationLabel 逻辑
        }
        return $this->getOperationLabel($operation);
    }

    /**
     * 递归将 child map 转为索引array.
     */
    private function normalizeTree(array $branch): array
    {
        foreach ($branch as &$node) {
            if (isset($node['children']) && is_array($node['children'])) {
                $node['children'] = array_values($this->normalizeTree($node['children']));
            }
        }
        return $branch;
    }

    /**
     * according to平台 key 获取显示名称，可按需extension.
     */
    private function getPlatformLabel(string $platformKey): string
    {
        $enum = DelightfulResourceEnum::tryFrom($platformKey);
        if ($enum) {
            $label = $enum->label();
            if ($label !== $enum->translationKey()) {
                return $label;
            }
        }

        return ucfirst($platformKey);
    }
}
