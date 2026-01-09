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
     * passconfigurationID和organizationencodinggetservice商configuration实body.
     *
     * @param string $serviceProviderConfigId service商configurationID
     * @param string $organizationCode organizationencoding
     * @return null|ProviderConfigEntity service商configuration实body
     */
    public function getProviderConfigEntityById(string $serviceProviderConfigId, string $organizationCode): ?ProviderConfigEntity;

    /**
     * according toservice商IDfindconfiguration（按ID升序取first）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param int $serviceProviderId service商ID
     * @return null|ProviderConfigEntity configuration实body
     */
    public function findFirstByServiceProviderId(ProviderDataIsolation $dataIsolation, int $serviceProviderId): ?ProviderConfigEntity;

    /**
     * according toIDgetconfiguration实body（not按organizationfilter，all局query）.
     *
     * @param int $id configurationID
     * @return null|ProviderConfigEntity configuration实body
     */
    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity;

    /**
     * according toIDarraygetconfiguration实bodylist（not按organizationfilter，all局query）.
     *
     * @param array<int> $ids configurationIDarray
     * @return array<int, ProviderConfigEntity> returnbyid为key的configuration实bodyarray
     */
    public function getByIdsWithoutOrganizationFilter(array $ids): array;

    /**
     * getorganizationdown所haveenable的service商configuration.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @return array<ProviderConfigEntity> service商configuration实bodyarray
     */
    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array;
}
