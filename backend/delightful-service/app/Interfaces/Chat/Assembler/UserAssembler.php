<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\DelightfulDepartmentUserEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Entity\ValueObject\EmployeeType;
use App\Interfaces\Chat\DTO\UserDepartmentDetailDTO;
use App\Interfaces\Chat\DTO\UserDetailDTO;
use Hyperf\Logger\LoggerFactory;

class UserAssembler
{
    public function __construct()
    {
    }

    /**
     * @param AccountEntity[] $accounts
     */
    public static function getAgentList(array $agents, array $accounts): array
    {
        /** @var AccountEntity[] $accounts */
        $accounts = array_column($accounts, null, 'delightful_id');
        $agentList = [];
        foreach ($agents as $agent) {
            $agentAccount = $accounts[$agent['delightful_id']] ?? null;
            if ($agentAccount instanceof AccountEntity) {
                $agentAccount = $agentAccount->toArray();
            } else {
                $agentAccount = [];
            }
            $label = explode(',', $agentAccount['extra']['label'] ?? '');
            $label = empty($label[0]) ? [] : $label;
            $agentList[] = [
                'id' => $agent['user_id'],
                'label' => $label,
                'like_num' => $agentAccount['extra']['like_num'] ?? 0,
                'friend_num' => $agentAccount['extra']['friend_num'] ?? 0,
                'nickname' => $agent['nickname'],
                'description' => $agent['description'],
                'avatar_url' => $agent['avatar_url'],
            ];
        }
        return $agentList;
    }

    public static function getUserInfos(array $userInfos): array
    {
        // strong转user id typefor string
        foreach ($userInfos as &$user) {
            // notreturn delightful_id and id
            unset($user['delightful_id'], $user['id']);
        }
        return $userInfos;
    }

    public static function getUserEntity(array $user): DelightfulUserEntity
    {
        return new DelightfulUserEntity($user);
    }

    public static function getUserEntities(array $users): array
    {
        $userEntities = [];
        foreach ($users as $user) {
            $userEntities[] = self::getUserEntity($user);
        }
        return $userEntities;
    }

    public static function getAccountEntity(array $account): AccountEntity
    {
        return new AccountEntity($account);
    }

    public static function getAccountEntities(array $accounts): array
    {
        $accountEntities = [];
        foreach ($accounts as $account) {
            $accountEntities[] = self::getAccountEntity($account);
        }
        return $accountEntities;
    }

    /**
     * @param AccountEntity[] $accounts
     * @param DelightfulUserEntity[] $users
     * @return array<UserDetailDTO>
     */
    public static function getUsersDetail(array $users, array $accounts): array
    {
        $logger = di(LoggerFactory::class)->get('UserAssembler');
        /** @var array<AccountEntity> $accounts */
        $accounts = array_column($accounts, null, 'delightful_id');
        $userDetailDTOList = [];
        foreach ($users as $user) {
            $account = $accounts[$user['delightful_id']] ?? null;
            if (empty($account)) {
                $logger->warning("user[delightful_id: {$user['delightful_id']} ]not存in, skip!");
                continue;
            }
            // if存inhand机number,willhand机numbermiddlebetweenfour位replacefor*
            $phone = $account->getPhone();
            if (! empty($phone)) {
                $phone = substr_replace($phone, '****', 3, 4);
            }
            $userDetailAdd = [
                'country_code' => $account->getCountryCode(),
                'phone' => $phone,
                'email' => empty($account->getEmail()) ? null : $account->getEmail(),
                'real_name' => $account->getRealName(),
                'account_type' => $account->getType()->value,
                'ai_code' => $account->getAiCode(),
            ];

            foreach ($user->toArray() as $key => $value) {
                if (isset($userDetailAdd[$key])) {
                    // ifalready经存in,skip
                    continue;
                }
                $userDetailAdd[$key] = $value;
            }
            $userDetailDTOList[] = new UserDetailDTO($userDetailAdd);
        }
        return $userDetailDTOList;
    }

    /**
     * oneusermaybe存inat多department.
     * @param DelightfulDepartmentUserEntity[] $departmentUsers
     * @param UserDetailDTO[] $usersDetail
     * @param array<string, DelightfulDepartmentEntity[]> $departmentsInfo
     * @param bool $withDepartmentFullPath whetherreturndepartmentcompletepath
     * @return UserDepartmentDetailDTO[]
     */
    public static function getUserDepartmentDetailDTOList(
        array $departmentUsers,
        array $usersDetail,
        array $departmentsInfo,
        bool $withDepartmentFullPath = false
    ): array {
        /** @var array<UserDepartmentDetailDTO> $usersDepartmentDetailDTOList */
        $usersDepartmentDetailDTOList = [];

        // step1: builduserIDtodepartmentclose系mapping
        $userDepartmentMap = [];
        foreach ($departmentUsers as $departmentUser) {
            $userDepartmentMap[$departmentUser->getUserId()][] = $departmentUser;
        }

        // step2: foreachuserbuilddetailedinfo
        foreach ($usersDetail as $userInfo) {
            $userId = $userInfo->getUserId();
            $userDepartmentRelations = $userDepartmentMap[$userId] ?? [];

            // step2.1: 收collectiondepartmentpathinfo
            $allPathNodes = [];
            $fullPathNodes = [];

            foreach ($userDepartmentRelations as $departmentUser) {
                $userDepartmentId = $departmentUser['department_id'] ?? '';
                /** @var DelightfulDepartmentEntity[] $departments */
                $departments = $departmentsInfo[$userDepartmentId] ?? [];

                if (! empty($departments)) {
                    if ($withDepartmentFullPath) {
                        // completepath模type: foreachdepartmentsavecompletelayerlevelstructure
                        $pathNodes = array_map(
                            fn (DelightfulDepartmentEntity $department) => self::assemblePathNodeByDepartmentInfo($department),
                            $departments
                        );
                        $fullPathNodes[$userDepartmentId] = $pathNodes;
                    } else {
                        // brief模type: only取eachdepartmentmostnextsectionpoint
                        $departmentInfo = end($departments);
                        $pathNode = self::assemblePathNodeByDepartmentInfo($departmentInfo);
                        $allPathNodes[] = $pathNode;
                    }
                }
            }

            // step2.2: usedefaultdepartmentclose系asfor基础info
            $defaultDepartmentUser = $userDepartmentRelations[0] ?? [];

            // step2.3: updateorcreateuserdepartmentdetailobject
            if (! empty($usersDepartmentDetailDTOList[$userId])) {
                // updatealready存inuserdepartmentdetail
                $userDepartmentDetailDTO = $usersDepartmentDetailDTOList[$userId];

                if ($withDepartmentFullPath && ! empty($fullPathNodes)) {
                    $userDepartmentDetailDTO->setFullPathNodes($fullPathNodes);
                } elseif (! empty($allPathNodes)) {
                    $userDepartmentDetailDTO->setPathNodes($allPathNodes);
                }
            } else {
                // createnewuserdepartmentdetail
                $userDepartmentDetail = [
                    'employee_type' => $defaultDepartmentUser['employee_type'] ?? EmployeeType::Unknown->value,
                    'employee_no' => $defaultDepartmentUser['employee_no'] ?? '',
                    'job_title' => $defaultDepartmentUser['job_title'] ?? '',
                    'is_leader' => (bool) ($defaultDepartmentUser['is_leader'] ?? false),
                ];

                // addpathsectionpointinfo
                if ($withDepartmentFullPath) {
                    $userDepartmentDetail['full_path_nodes'] = $fullPathNodes;
                } else {
                    $userDepartmentDetail['path_nodes'] = $allPathNodes;
                }

                // mergeuser基本info
                $userInfoArray = $userInfo->toArray();
                foreach ($userInfoArray as $key => $value) {
                    $userDepartmentDetail[$key] = $value;
                }

                $userDepartmentDetailDTO = new UserDepartmentDetailDTO($userDepartmentDetail);
                $usersDepartmentDetailDTOList[$userId] = $userDepartmentDetailDTO;
            }
        }

        return array_values($usersDepartmentDetailDTOList);
    }

    private static function assemblePathNodeByDepartmentInfo(DelightfulDepartmentEntity $departmentInfo): array
    {
        return [
            // departmentname
            'department_name' => $departmentInfo->getName(),
            // departmentid
            'department_id' => $departmentInfo->getDepartmentId(),
            'parent_department_id' => $departmentInfo->getParentDepartmentId(),
            // departmentpath
            'path' => $departmentInfo->getPath(),
            // visibleproperty
            'visible' => ! ($departmentInfo->getOption() === DepartmentOption::Hidden),
            'option' => $departmentInfo->getOption(),
        ];
    }
}
