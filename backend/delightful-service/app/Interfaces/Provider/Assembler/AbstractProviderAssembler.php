<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Assembler;

use Hyperf\Contract\TranslatorInterface;

/**
 * Provider Assemblerabstract基category
 * extractpublicconvert逻辑，decreasecode重复.
 */
abstract class AbstractProviderAssembler
{
    /**
     * batchquantityconvertarrayto实body.
     * @template T of object
     * @param class-string<T> $entityClass 实bodycategory名
     * @param array $dataArray dataarray
     * @return T[]
     */
    protected static function batchToEntities(string $entityClass, array $dataArray): array
    {
        if (empty($dataArray)) {
            return [];
        }

        $entities = [];
        foreach ($dataArray as $data) {
            $entities[] = static::createEntityFromArray($entityClass, (array) $data);
        }
        return $entities;
    }

    /**
     * batchquantityconvert实bodytoarray.
     * @param array $entities 实bodyarray
     */
    protected static function batchToArrays(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }

        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }

    /**
     * create带国际化support实body.
     * @template T of object
     * @param class-string<T> $entityClass 实bodycategory名
     * @param array $data dataarray
     * @param bool $enableI18n whetherenable国际化
     * @return T
     */
    protected static function createEntityFromArray(string $entityClass, array $data, bool $enableI18n = true): object
    {
        $entity = new $entityClass($data);

        if ($enableI18n && method_exists($entity, 'i18n')) {
            $translator = di(TranslatorInterface::class);
            $entity->i18n($translator->getLocale());
        }

        return $entity;
    }

    /**
     * nullvaluecheck助handmethod.
     */
    protected static function isEmptyArray(?array $data): bool
    {
        return $data === null || empty($data);
    }
}
