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
     * get所have操astype.
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
     * getresourceinternationalizationtag(by DelightfulResourceEnum provide).
     */
    public function getResourceLabel(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        $translated = $enum->label();
        // iflanguagepackagemissing,returnstillisoriginal key,thiso clockthrowexceptionreminder
        if ($translated === $enum->translationKey()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $enum->translationKey());
        }

        return $translated;
    }

    /**
     * buildcompletepermissionidentifier.
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
     * parsepermissionidentifier.
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
     * checkwhetherforresourcetype.
     */
    public function isResource(string $value): bool
    {
        return in_array($value, $this->getResources());
    }

    /**
     * checkwhetherfor操astype.
     */
    public function isOperation(string $value): bool
    {
        return in_array($value, $this->getOperations());
    }

    /**
     * get操asinternationalizationtag.
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
     * getresource模piece.
     */
    public function getResourceModule(string $resource): string
    {
        $enum = DelightfulResourceEnum::tryFrom($resource);
        if (! $enum) {
            throw new InvalidArgumentException('Not a resource type: ' . $resource);
        }

        // 模piecelayerdefinitionfortwolevelresource(即platformdirectly子resource)
        if ($enum->parent() === null) {
            // toplevelresource(platformitself)
            $moduleEnum = $enum;
        } else {
            $parent = $enum->parent();
            if ($parent->parent() === null) {
                // currentresourcealready经istwolevellayerlevel,directlyasfor模piece
                $moduleEnum = $enum;
            } else {
                // more深layerlevel,模piece取父level(twolevel)
                $moduleEnum = $parent;
            }
        }

        $moduleLabel = $moduleEnum->label();
        if ($moduleLabel === $moduleEnum->translationKey()) {
            // ifmissingtranslate,hand动compatibleknown模piece
            return match ($moduleEnum) {
                DelightfulResourceEnum::ADMIN_AI => 'AImanage',
                default => $moduleEnum->value,
            };
        }

        return $moduleLabel;
    }

    /**
     * generate所havemaybepermissiongroup合.
     */
    public function generateAllPermissions(): array
    {
        $permissions = [];
        $resources = $this->getResources();
        $operations = $this->getOperations();

        foreach ($resources as $resource) {
            // onlyhandlethreelevelandbyupresource,filterplatformand模piecelevel
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
     * getlayerlevelstructurepermissiontree
     * generateno限极permissiontree,rule:according topermissionresourcestring(如 Admin.ai.model_management)逐segmentsplit,逐layerconstructtree.
     *
     * returnformat:
     * [
     *   [
     *     'label' => 'manageback台',
     *     'permission_key' => 'Admin',
     *     'children' => [ ... ]
     *   ],
     * ]
     *
     * @param bool $isPlatformOrganization whetherplatformorganization;onlywhenfor true o clock,contain platform platformresourcetree
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $tree = [];

        foreach ($this->generateAllPermissions() as $permission) {
            // willresourcepath按 '.' split
            $segments = explode('.', $permission['resource']);
            if (count($segments) < 2) {
                // at least应containplatform + resource两level,若not足thenskip
                continue;
            }

            $platformKey = array_shift($segments); // platform,如 Admin

            // platformorganization独have:nonplatformorganizationo clock,filter掉 platform platformresource
            if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
                continue;
            }
            // initializeplatformrootsectionpoint
            if (! isset($tree[$platformKey])) {
                $tree[$platformKey] = [
                    'label' => $this->getPlatformLabel($platformKey),
                    'permission_key' => $platformKey,
                    'children' => [],
                ];
            }

            // fromtoptodown逐segmentconstruct
            $current = &$tree[$platformKey];
            $accumKey = $platformKey;
            foreach ($segments as $index => $segment) {
                $accumKey .= '.' . $segment;
                $isLastSegment = $index === array_key_last($segments);

                // 取 label:theonesegmentuse模piecemiddledocument name,itsremainder按rule
                $label = match (true) {
                    $index === 0 => $this->getResourceModule($permission['resource']),                // 模piecelayer
                    $isLastSegment => $permission['resource_label'],      // resourcelayer
                    default => ucfirst($segment),                        // othermiddlebetweenlayer
                };

                // ensure children array存inandcheck segment
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

            // thiso clock $current fingertoresourcesectionpoint,foritsadd操asleaf子
            $current['children'][] = [
                'label' => $permission['operation_label'],
                'permission_key' => $permission['permission_key'],
                'full_label' => $permission['resource_label'] . '-' . $permission['operation_label'],
                'is_leaf' => true,
            ];
        }

        // willassociatearray children 转forindexarray,maintainreturnformat
        return array_values($this->normalizeTree($tree));
    }

    /**
     * checkpermissionkeywhethervalid.
     */
    public function isValidPermission(string $permissionKey): bool
    {
        // all局permissionspecialhandle
        if ($permissionKey === self::ALL_PERMISSIONS) {
            return true;
        }

        try {
            $parsed = $this->parsePermission($permissionKey);

            // checkresourcewhether存in
            $resourceExists = in_array($parsed['resource'], $this->getResources());

            // check操aswhether存in(按resource)
            $operationExists = in_array($parsed['operation'], $this->getOperationsByResource($parsed['resource']), true);

            return $resourceExists && $operationExists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * judgeuserpermissionsetmiddlewhether拥havefinger定permission(consider隐typecontain).
     *
     * rule:
     *   1. 如directly命middlepermissionkey,return true;
     *   2. if拥haveall局permission ALL_PERMISSIONS,return true;
     *   3. 若not命middle,thencheckbythepermission隐typecontainpermissionset(for example *edit* 隐typecontain *query*).
     *
     * @param string $permissionKey goalpermissionkey
     * @param string[] $userPermissions useralready拥havepermissionkeyset
     * @param bool $isPlatformOrganization whetherplatformorganization
     */
    public function checkPermission(string $permissionKey, array $userPermissions, bool $isPlatformOrganization = false): bool
    {
        // platformorganizationvalidation:nonplatformorganizationnotallowaccess platform platformresource
        $parsed = $this->parsePermission($permissionKey);
        $platformKey = explode('.', $parsed['resource'])[0];
        if ($platformKey === DelightfulResourceEnum::PLATFORM->value && ! $isPlatformOrganization) {
            return false;
        }

        // 命middleall局permissiondirectly放line
        if (in_array(self::ALL_PERMISSIONS, $userPermissions, true)) {
            return true;
        }

        // directly命middle
        if (in_array($permissionKey, $userPermissions, true)) {
            return true;
        }

        $parsed = $this->parsePermission($permissionKey);
        // default隐type:edit -> query(if two operationsas均存in)
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
     * parseresourcebind Operation Enum,returntheresourcecanuse操asset(stringarray).
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
        // onlysupport BackedEnum,因forback续needread ->value
        if (! is_subclass_of($opEnumClass, BackedEnum::class)) {
            throw new InvalidArgumentException('Operation enum for resource must be BackedEnum: ' . $opEnumClass);
        }

        /** @var class-string<BackedEnum> $opEnumClass */
        $cases = $opEnumClass::cases();
        /* @var array<int, \BackedEnum> $cases */
        return array_map(static fn (BackedEnum $case) => $case->value, $cases);
    }

    /**
     * returnresourcebind Operation Enum category名,defaultread `DelightfulResourceEnum::operationEnumClass()`.
     * enterprise版canoverride本method,willenterpriseresourcemappingtocustomize Operation Enum.
     */
    protected function resolveOperationEnumClass(DelightfulResourceEnum $resourceEnum): string
    {
        return $resourceEnum->operationEnumClass();
    }

    /**
     * toatnon DelightfulResourceEnum definitionresource,子categorycanoverridethemethodbyparsetocorresponding Operation Enum.
     * open源default抛错.
     */
    protected function resolveOperationEnumClassFromUnknownResource(string $resource): string
    {
        throw new InvalidArgumentException('Not a resource type: ' . $resource);
    }

    /**
     * get按resource操astag.
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
            // requirecustomize OperationEnum implement label()/translationKey() and DelightfulOperationEnum alignment
            if (method_exists($opEnum, 'label') && method_exists($opEnum, 'translationKey')) {
                $translated = $opEnum->label();
                if ($translated === $opEnum->translationKey()) {
                    ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'Missing i18n for key: ' . $opEnum->translationKey());
                }
                return $translated;
            }
            // compatible:若notimplement label/translationKey,then退return通use getOperationLabel logic
        }
        return $this->getOperationLabel($operation);
    }

    /**
     * recursionwill child map 转forindexarray.
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
     * according toplatform key getdisplayname,canon demandextension.
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
