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
    // ========== all局permission ==========
    public const string ALL_PERMISSIONS = DelightfulAdminResourceEnum::ORGANIZATION_ADMIN->value;

    /**
     * get所have操作type.
     */
    public function getOperations(): array
    {
        return array_map(static fn (DelightfulOperationEnum $op) => $op->value, DelightfulOperationEnum::cases());
    }

    /**
     * get所haveresource.
     */
    public function getResources(): array
    {
        return array_map(static fn (DelightfulResourceEnum $res) => $res->value, DelightfulResourceEnum::cases());
    }

    /**
     * getresource的国际化tag（由 DelightfulResourceEnum 提供）.
     */
    public function getResourceLabel(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        $translated = $enum->label();
        // if语言package缺失，return的仍然是original key，此o clockthrowexceptionreminder
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
     * checkwhether为resourcetype.
     */
    public function isResource(string $value): bool
    {
        return in_array($value, $this->getResources());
    }

    /**
     * checkwhether为操作type.
     */
    public function isOperation(string $value): bool
    {
        return in_array($value, $this->getOperations());
    }

    /**
     * get操作的国际化tag.
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
     * getresource的模piece.
     */
    public function getResourceModule(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        // 模piecelayerdefinition为二levelresource（即平台的直接子resource）
        if ($enum->parent() === null) {
            // toplevelresource（平台本身）
            $moduleEnum = $enum;
        } else {
            $parent = $enum->parent();
            if ($parent->parent() === null) {
                // currentresource已经是二levellayerlevel，直接作为模piece
                $moduleEnum = $enum;
            } else {
                // more深layerlevel，模piece取父level（二level）
                $moduleEnum = $parent;
            }
        }

        $moduleLabel = $moduleEnum->label();
        if ($moduleLabel === $moduleEnum->translationKey()) {
            // if缺失翻译，hand动compatible已知模piece
            return match ($moduleEnum) {
                DelightfulResourceEnum::ADMIN_AI => 'AI管理',
                default => $moduleEnum->value,
            };
        }

        return $moduleLabel;
    }

    /**
     * generate所have可能的permissiongroup合.
     */
    public function generateAllPermissions(): array
    {
        $permissions = [];
        $resources = $this->getResources();
        $operations = $this->getOperations();

        foreach ($resources as $resource) {
            // 仅handle三level及byupresource，filter平台和模piecelevel
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
     * getlayerlevel结构的permissiontree
     * generate无限极permissiontree,rule：according topermissionresourcestring（如 Admin.ai.model_management）逐segmentsplit，逐layer构造tree。
     *
     * returnformat：
     * [
     *   [
     *     'label' => '管理back台',
     *     'permission_key' => 'Admin',
     *     'children' => [ ... ]
     *   ],
     * ]
     *
     * @param bool $isPlatformOrganization whether平台organization；仅when为 true o clock，contain platform 平台的resourcetree
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $tree = [];

        foreach ($this->generateAllPermissions() as $permission) {
            // 将resourcepath按 '.' split
            $segments = explode('.', $permission['resource']);
            if (count($segments) < 2) {
                // at least应contain平台 + resource两level，若not足thenskip
                continue;
            }

            $platformKey = array_shift($segments); // 平台，如 Admin

            // 平台organization独have：non平台organizationo clock，filter掉 platform 平台的resource
            if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
                continue;
            }
            // initialize平台rootsectionpoint
            if (! isset($tree[$platformKey])) {
                $tree[$platformKey] = [
                    'label' => $this->getPlatformLabel($platformKey),
                    'permission_key' => $platformKey,
                    'children' => [],
                ];
            }

            // 自toptodown逐segment构造
            $current = &$tree[$platformKey];
            $accumKey = $platformKey;
            foreach ($segments as $index => $segment) {
                $accumKey .= '.' . $segment;
                $isLastSegment = $index === array_key_last($segments);

                // 取 label：the一segmentuse模piecemiddle文名，其余按rule
                $label = match (true) {
                    $index === 0 => $this->getResourceModule($permission['resource']),                // 模piecelayer
                    $isLastSegment => $permission['resource_label'],      // resourcelayer
                    default => ucfirst($segment),                        // 其他middlebetweenlayer
                };

                // ensure children array存in并check segment
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

            // 此o clock $current fingertoresourcesectionpoint，为其add操作leaf子
            $current['children'][] = [
                'label' => $permission['operation_label'],
                'permission_key' => $permission['permission_key'],
                'full_label' => $permission['resource_label'] . '-' . $permission['operation_label'],
                'is_leaf' => true,
            ];
        }

        // 将associatearray children 转为索引array，保持returnformat
        return array_values($this->normalizeTree($tree));
    }

    /**
     * checkpermission键whethervalid.
     */
    public function isValidPermission(string $permissionKey): bool
    {
        // all局permission特殊handle
        if ($permissionKey === self::ALL_PERMISSIONS) {
            return true;
        }

        try {
            $parsed = $this->parsePermission($permissionKey);

            // checkresourcewhether存in
            $resourceExists = in_array($parsed['resource'], $this->getResources());

            // check操作whether存in（按resource）
            $operationExists = in_array($parsed['operation'], $this->getOperationsByResource($parsed['resource']), true);

            return $resourceExists && $operationExists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 判断userpermissionsetmiddlewhether拥havefinger定permission（考虑隐typecontain）。
     *
     * rule：
     *   1. 如直接命middlepermission键，return true；
     *   2. if拥haveall局permission ALL_PERMISSIONS，return true；
     *   3. 若未命middle，thencheck由该permission隐typecontain的permissionset（for example *edit* 隐typecontain *query*）。
     *
     * @param string $permissionKey goalpermission键
     * @param string[] $userPermissions user已拥have的permission键set
     * @param bool $isPlatformOrganization whether平台organization
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool
    {
        // 平台organization校验：non平台organizationnotallowaccess platform 平台resource
        $parsed = $this->parsePermission($permissionKey);
        $platformKey = explode('.', $parsed['resource'])[0];
        if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
            return false;
        }

        // 命middleall局permission直接放line
        if (in_array(self::ALL_PERMISSIONS, $userPermissions, true)) {
            return true;
        }

        // 直接命middle
        if (in_array($permissionKey, $userPermissions, true)) {
            return true;
        }

        $parsed = $this->parsePermission($permissionKey);
        // default隐type：edit -> query（若两操作均存in）
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
     * parseresourcebind的 Operation Enum，return该resource可use的操作set（stringarray）。
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
        // 仅support BackedEnum，因为back续needread ->value
        if (! is_subclass_of($opEnumClass, BackedEnum::class)) {
            throw new InvalidArgumentException('Operation enum for resource must be BackedEnum: ' . $opEnumClass);
        }

        /** @var class-string<BackedEnum> $opEnumClass */
        $cases = $opEnumClass::cases();
        /* @var array<int, \BackedEnum> $cases */
        return array_map(static fn (BackedEnum $case) => $case->value, $cases);
    }

    /**
     * returnresourcebind的 Operation Enum category名，defaultread `DelightfulResourceEnum::operationEnumClass()`。
     * 企业版可override本method，将企业resourcemappingtocustomize的 Operation Enum。
     */
    protected function resolveOperationEnumClass(DelightfulResourceEnum $resourceEnum): string
    {
        return $resourceEnum->operationEnumClass();
    }

    /**
     * 对atnon DelightfulResourceEnum definition的resource，子category可override该methodbyparseto相应的 Operation Enum。
     * 开源default抛错。
     */
    protected function resolveOperationEnumClassFromUnknownResource(string $resource): string
    {
        throw new InvalidArgumentException('Not a resource type: ' . $resource);
    }

    /**
     * get按resource的操作tag。
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
            // 要求customize OperationEnum implement label()/translationKey() 与 DelightfulOperationEnum alignment
            if (method_exists($opEnum, 'label') && method_exists($opEnum, 'translationKey')) {
                $translated = $opEnum->label();
                if ($translated === $opEnum->translationKey()) {
                    ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $opEnum->translationKey());
                }
                return $translated;
            }
            // compatible：若未implement label/translationKey，then退回通use getOperationLabel 逻辑
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
     * according to平台 key getdisplayname，可按需extension.
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
