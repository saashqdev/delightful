<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderConfigQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface ProviderConfigRepositoryInterface
{
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?ProviderConfigEntity;

    /**
     * @param array<int> $ids
     * @return array<int, ProviderConfigEntity>
     */
    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array;

    /**
     * @return array{total: int, list: array<ProviderConfigEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderConfigQuery $query, Page $page): array;

    public function save(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity;

    public function delete(ProviderDataIsolation $dataIsolation, string $id): void;

    /**
     * passconfigurationIDandorganizationencodinggetservicequotientconfigurationactualbody.
     *
     * @param string $serviceProviderConfigId servicequotientconfigurationID
     * @param string $organizationCode organizationencoding
     * @return null|ProviderConfigEntity servicequotientconfigurationactualbody
     */
    public function getProviderConfigEntityById(string $serviceProviderConfigId, string $organizationCode): ?ProviderConfigEntity;

    /**
     * according toservicequotientIDfindconfiguration(按IDascendinggetfirst).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param int $serviceProviderId servicequotientID
     * @return null|ProviderConfigEntity configurationactualbody
     */
    public function findFirstByServiceProviderId(ProviderDataIsolation $dataIsolation, int $serviceProviderId): ?ProviderConfigEntity;

    /**
     * according toIDgetconfigurationactualbody(not按organizationfilter,alllocalquery).
     *
     * @param int $id configurationID
     * @return null|ProviderConfigEntity configurationactualbody
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity;

    /**
     * according toIDarraygetconfigurationactualbodylist(not按organizationfilter,alllocalquery).
     *
     * @param array<int> $ids configurationIDarray
     * @return array<int, ProviderConfigEntity> returnbyidforkeyconfigurationactualbodyarray
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;

    /**
     * getorganizationdown haveenableservicequotientconfiguration.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @return array<ProviderConfigEntity> servicequotientconfigurationactualbodyarray
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array;
}
