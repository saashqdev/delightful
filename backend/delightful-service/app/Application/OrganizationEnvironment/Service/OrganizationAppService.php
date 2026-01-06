<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\OrganizationEnvironment\Service;

use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Service\OrganizationDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\Di\Annotation\Inject;

class OrganizationAppService
{
    #[Inject]
    protected OrganizationDomainService $organizationDomainService;

    #[Inject]
    protected DelightfulUserDomainService $delightfulUserDomainService;

    #[Inject]
    protected DelightfulAccountDomainService $delightfulAccountDomainService;

    /**
     * @return array{total: int, list: array}
     */
    public function queries(Page $page, ?array $filters = null): array
    {
        return $this->organizationDomainService->queries($page, $filters);
    }

    /**
     * @param string[] $creatorIds
     * @return array<string, array{user_id: string, delightful_id: ?string, name: string, avatar: string, email: ?string, phone: ?string}>
     */
    public function getCreators(array $creatorIds): array
    {
        if ($creatorIds === []) {
            return [];
        }

        $users = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($creatorIds);
        if ($users === []) {
            return $this->buildFallbackCreators($creatorIds);
        }

        $creatorMap = [];
        $delightfulIdToUserId = [];

        foreach ($users as $user) {
            $userId = $user->getUserId();
            if ($userId === '') {
                continue;
            }
            $creatorMap[$userId] = [
                'user_id' => $userId,
                'delightful_id' => $user->getDelightfulId(),
                'name' => $user->getNickname(),
                'avatar' => $user->getAvatarUrl(),
            ];
            $delightfulId = $user->getDelightfulId();
            $delightfulIdToUserId[$delightfulId] = $userId;
        }

        if ($delightfulIdToUserId !== []) {
            $accounts = $this->delightfulAccountDomainService->getAccountByDelightfulIds(array_keys($delightfulIdToUserId));
            foreach ($accounts as $account) {
                $delightfulId = $account->getDelightfulId();
                if ($delightfulId === null || $delightfulId === '') {
                    continue;
                }
                $userId = $delightfulIdToUserId[$delightfulId] ?? null;
                if ($userId === null || ! array_key_exists($userId, $creatorMap)) {
                    continue;
                }
                $creator = $creatorMap[$userId];
                if ($account->getRealName()) {
                    $creator['name'] = $account->getRealName();
                }
                $creator['email'] = $account->getEmail();
                $creator['phone'] = $account->getPhone();
                $creatorMap[$userId] = $creator;
            }
        }

        foreach ($creatorIds as $creatorId) {
            if (! array_key_exists($creatorId, $creatorMap)) {
                $creatorMap[$creatorId] = [
                    'user_id' => $creatorId,
                    'delightful_id' => null,
                    'name' => '',
                    'avatar' => '',
                    'email' => null,
                    'phone' => null,
                ];
            }
        }

        return $creatorMap;
    }

    /**
     * @param string[] $creatorIds
     * @return array<string, array{user_id: string, delightful_id: ?string, name: string, avatar: string, email: ?string, phone: ?string}>
     */
    private function buildFallbackCreators(array $creatorIds): array
    {
        $fallback = [];
        foreach ($creatorIds as $creatorId) {
            $fallback[$creatorId] = [
                'user_id' => $creatorId,
                'delightful_id' => null,
                'name' => '',
                'avatar' => '',
                'email' => null,
                'phone' => null,
            ];
        }
        return $fallback;
    }
}
