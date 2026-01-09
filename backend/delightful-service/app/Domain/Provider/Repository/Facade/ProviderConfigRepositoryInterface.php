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
     * passconfigurationIDandorganizationencodinggetservicequotientconfiguration实body.
     *
     * @param string $serviceProviderConfigId servicequotientconfigurationID
     * @param string $organizationCode organizationencoding
     * @return null|ProviderConfigEntity servicequotientconfiguration实body
     */
    public function getProviderConfigEntityById(string $serviceProviderConfigId, string $organizationCode): ?ProviderConfigEntity;

    /**
     * according toservicequotientIDfindconfiguration(按IDascending取first).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param int $serviceProviderId servicequotientID
     * @return null|ProviderConfigEntity configuration实body
     */
    public function findFirstByServiceProviderId(ProviderDataIsolation $dataIsolation, int $serviceProviderId): ?ProviderConfigEntity;

    /**
     * according toIDgetconfiguration实body(not按organizationfilter,all局query).
     *
     * @param int $id configurationID
     * @return null|ProviderConfigEntity configuration实body
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity;

    /**
     * according toIDarraygetconfiguration实bodylist(not按organizationfilter,all局query).
     *
     * @param array<int> $ids configurationIDarray
     * @return array<int, ProviderConfigEntity> returnbyidforkeyconfiguration实bodyarray
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;

    /**
     * getorganizationdown所haveenableservicequotientconfiguration.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @return array<ProviderConfigEntity> servicequotientconfiguration实bodyarray
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array;
}
