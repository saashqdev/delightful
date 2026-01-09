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
 * permission校验annotation，useatmethodorcategoryupstatement所需permission。
 *
 * example：
 * #[CheckPermission(DelightfulResourceEnum::CONSOLE_API_ASSISTANT, DelightfulOperationEnum::QUERY)]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CheckPermission extends AbstractAnnotation
{
    /**
     * resource标识（support单or多）。
     */
    public array|string $resource;

    /**
     * 操as标识（仅support单）。
     */
    public string $operation;

    /**
     * @param array|BackedEnum|string $resource resource，string/枚举or其array
     * @param BackedEnum|string $operation 操as，仅stringor枚举
     */
    public function __construct(array|BackedEnum|string $resource, BackedEnum|string $operation)
    {
        $this->resource = $this->normalizeToValues($resource);
        $this->operation = $operation instanceof BackedEnum ? $operation->value : $operation;
    }

    /**
     * group合for完整permission键，如 "console.api.assistant.query".
     */
    public function getPermissionKey(): string
    {
        // forcompatible旧逻辑，returnfirstgroup合键
        $keys = $this->getPermissionKeys();
        return $keys[0] ?? '';
    }

    /**
     * return所havepermission键group合（resources x operations 笛卡尔积）。
     * whenstatement多resourceor多操aso clock，permissionpass任意一键即can。
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
     * willstring/枚举or其array统一forstringarray。
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
