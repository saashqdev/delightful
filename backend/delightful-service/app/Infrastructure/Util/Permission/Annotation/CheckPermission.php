<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Permission\Annotation;

use App\Application\Kernel\Contract\DelightfulPermissionInterface;
use Attribute;
use BackedEnum;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * permission校验注解，用于method或类上声明所需的permission。
 *
 * 示例：
 * #[CheckPermission(DelightfulResourceEnum::CONSOLE_API_ASSISTANT, DelightfulOperationEnum::QUERY)]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CheckPermission extends AbstractAnnotation
{
    /**
     * 资源标识（支持单个或多个）。
     */
    public array|string $resource;

    /**
     * 操作标识（仅支持单个）。
     */
    public string $operation;

    /**
     * @param array|BackedEnum|string $resource 资源，string/枚举或其array
     * @param BackedEnum|string $operation 操作，仅string或枚举
     */
    public function __construct(array|BackedEnum|string $resource, BackedEnum|string $operation)
    {
        $this->resource = $this->normalizeToValues($resource);
        $this->operation = $operation instanceof BackedEnum ? $operation->value : $operation;
    }

    /**
     * 组合为完整permission键，如 "console.api.assistant.query".
     */
    public function getPermissionKey(): string
    {
        // 为了兼容旧逻辑，returnfirst组合键
        $keys = $this->getPermissionKeys();
        return $keys[0] ?? '';
    }

    /**
     * return所有permission键组合（resources x operations 的笛卡尔积）。
     * 当声明了多个资源或多个操作时，permissionpass任意一个键即可。
     *
     * @return array<string>
     */
    public function getPermissionKeys(): array
    {
        $permission = di(DelightfulPermissionInterface::class);

        $resources = is_array($this->resource) ? $this->resource : [$this->resource];

        $keys = [];
        foreach ($resources as $res) {
            $keys[] = $permission->buildPermission($res, $this->operation);
        }

        return $keys;
    }

    /**
     * 将string/枚举或其array统一为stringarray。
     * @return array<string>
     */
    private function normalizeToValues(array|BackedEnum|string $input): array
    {
        $toValue = static function ($item) {
            return $item instanceof BackedEnum ? $item->value : $item;
        };

        if (is_array($input)) {
            $values = [];
            foreach ($input as $item) {
                $values[] = $toValue($item);
            }
            return $values;
        }

        return [$toValue($input)];
    }
}
