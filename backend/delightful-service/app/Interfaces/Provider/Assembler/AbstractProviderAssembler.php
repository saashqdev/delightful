<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Provider\Assembler;

use Hyperf\Contract\TranslatorInterface;

/**
 * Provider Assemblerabstract基category
 * extractpublicconvertlogic,decreasecodeduplicate.
 */
abstract class AbstractProviderAssembler
{
    /**
     * batchquantityconvertarraytoactualbody.
     * @template T of object
     * @param class-string<T> $entityClass actualbodycategoryname
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
     * batchquantityconvertactualbodytoarray.
     * @param array $entities actualbodyarray
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
     * createwithinternationalizationsupportactualbody.
     * @template T of object
     * @param class-string<T> $entityClass actualbodycategoryname
     * @param array $data dataarray
     * @param bool $enableI18n whetherenableinternationalization
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
     * nullvaluecheckhelphandmethod.
     */
    protected static function isEmptyArray(?array $data): bool
    {
        return $data === null || empty($data);
    }
}
