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
 * permissionvalidationannotation，useatmethodorcategoryupstatement所需permission。
 *
 * example：
 * #[CheckPermission(DelightfulResourceEnum::CONSOLE_API_ASSISTANT, DelightfulOperationEnum::QUERY)]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CheckPermission extends AbstractAnnotation
{
    /**
     * resourceidentifier（supportsingleor多）。
     */
    public array|string $resource;

    /**
     * 操asidentifier（仅supportsingle）。
     */
    public string $operation;

    /**
     * @param array|BackedEnum|string $resource resource，string/枚举oritsarray
     * @param BackedEnum|string $operation 操as，仅stringor枚举
     */
    public function __construct(array|BackedEnum|string $resource, BackedEnum|string $operation)
    {
        $this->resource = $this->normalizeToValues($resource);
        $this->operation = $operation instanceof BackedEnum ? $operation->value : $operation;
    }

    /**
     * group合forcompletepermissionkey，如 "console.api.assistant.query".
     */
    public function getPermissionKey(): string
    {
        // forcompatible旧逻辑，returnfirstgroup合key
        $keys = $this->getPermissionKeys();
        return $keys[0] ?? '';
    }

    /**
     * return所havepermissionkeygroup合（resources x operations 笛卡尔product）。
     * whenstatement多resourceor多操aso clock，permissionpass任意onekey即can。
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
     * willstring/枚举oritsarray统oneforstringarray。
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
