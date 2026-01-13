<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Chat\Entity\ValueObject\PlatformRootDepartmentId;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class organizationstructureseeder extends Seeder
{
    public function run(): void
    {
        $organizationCodes = ['test001', 'test002'];
        $organizationNames = ['Test Org', 'Demo Org'];
        $departmentStructure = [
            [
                'name' => '', // Will be replaced by $organizationNames
                'level' => 0,
                'department_id' => PlatformRootDepartmentId::Delightful,
                'parent_department_id' => PlatformRootDepartmentId::Delightful, // Marks itself as the root department
                'children' => [
                    [
                        'name' => 'Technology Department',
                        'level' => 1,
                        'children' => [
                            ['name' => 'Frontend Team', 'level' => 2],
                            ['name' => 'Backend Team', 'level' => 2],
                            ['name' => 'QA Team', 'level' => 2],
                        ],
                    ],
                    [
                        'name' => 'Product Department',
                        'level' => 1,
                        'children' => [
                            ['name' => 'Design Team', 'level' => 2],
                            ['name' => 'Product Team', 'level' => 2],
                        ],
                    ],
                    [
                        'name' => 'Marketing Department',
                        'level' => 1,
                        'children' => [
                            ['name' => 'Marketing Team', 'level' => 2],
                            ['name' => 'Sales Team', 'level' => 2],
                        ],
                    ],
                    [
                        'name' => 'HR Department',
                        'level' => 1,
                    ],
                ],
            ],
        ];

        try {
            // Begin transaction
            Db::beginTransaction();

            // Ensure the environment record exists
            $environmentId = env('DELIGHTFUL_ENV_ID');

            // 1. Create/check the relationship between organization and environment
            foreach ($organizationCodes as $index => $orgCode) {
                // Check if the org-environment association already exists
                $existingOrgEnv = Db::table('delightful_organizations_environment')
                    ->where('delightful_organization_code', $orgCode)
                    ->first();

                if ($existingOrgEnv) {
                    echo "Org-environment association already exists: {$existingOrgEnv['delightful_organization_code']}, login code: {$existingOrgEnv['login_code']}" . PHP_EOL;
                } else {
                    // Create org-environment association
                    $orgEnvData = [
                        'login_code' => random_int(100000, 999999),
                        'delightful_organization_code' => $orgCode,
                        'origin_organization_code' => $orgCode,
                        'environment_id' => $environmentId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    $orgEnvId = Db::table('delightful_organizations_environment')->insertGetId($orgEnvData);
                    echo "Created org-environment association: {$orgCode}, ID: {$orgEnvId}" . PHP_EOL;
                }

                // 2. Create department structure
                $departmentStructure[0]['name'] = $organizationNames[$index];
                $this->createDepartments($departmentStructure, $orgCode);

                // 3. Assign users to departments
                $this->assignUsersToDepartments($orgCode);
            }

            // Commit transaction
            Db::commit();
            echo 'Organization structure seeding completed' . PHP_EOL;
        } catch (Throwable $e) {
            // Roll back transaction
            Db::rollBack();
            echo 'Data seeding failed: ' . $e->getMessage() . PHP_EOL;
            echo 'file: ' . $e->getFile() . PHP_EOL;
            echo 'line: ' . $e->getLine() . PHP_EOL;
            echo 'trace: ' . $e->getTraceAsString() . PHP_EOL;

            // Rethrow to stop execution
            throw $e;
        }
    }

    /**
     * Recursively create departments.
     */
    private function createDepartments(array $departments, string $orgCode, ?string $parentDepartmentId = null, ?string $path = null): void
    {
        foreach ($departments as $dept) {
            // Use a preset department ID or generate a new one
            $departmentId = isset($dept['department_id']) ? $dept['department_id'] : IdGenerator::getSnowId();

            // Use a preset parent department ID or the provided parent ID
            $currentParentDepartmentId = isset($dept['parent_department_id']) ? $dept['parent_department_id'] : $parentDepartmentId;

            // Build the department path
            if (isset($dept['department_id']) && $dept['department_id'] === PlatformRootDepartmentId::Delightful) {
                // If this is the org level (root dept), use the special path
                $currentPath = PlatformRootDepartmentId::Delightful;
            } else {
                // Otherwise use the regular path logic
                $currentPath = $path ? $path . '/' . $departmentId : PlatformRootDepartmentId::Delightful . '/' . $departmentId;
            }

            // Build department data
            $departmentData = [
                'department_id' => $departmentId,
                'parent_department_id' => $currentParentDepartmentId,
                'name' => $dept['name'],
                'i18n_name' => json_encode([
                    'en-US' => $dept['name'],
                    'en-US' => $this->translateDepartmentName($dept['name']),
                ]),
                'order' => '0',
                'leader_user_id' => '', // Placeholder, will be updated later
                'organization_code' => $orgCode,
                'status' => json_encode(['is_deleted' => false]),
                'document_id' => IdGenerator::getSnowId(),
                'level' => $dept['level'],
                'path' => $currentPath,
                'employee_sum' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Check if the department already exists
            $existingDept = Db::table('delightful_contact_departments')
                ->where('organization_code', $orgCode)
                ->where('name', $dept['name'])
                ->where('parent_department_id', $currentParentDepartmentId)
                ->first();

            if ($existingDept) {
                echo "Department already exists: {$dept['name']}, ID: {$existingDept['department_id']}" . PHP_EOL;
                $departmentId = $existingDept['department_id'];
                $currentPath = $existingDept['path'];
            } else {
                // Create department
                Db::table('delightful_contact_departments')->insert($departmentData);
                echo "Created department: {$dept['name']}, ID: {$departmentId}, org: {$orgCode}" . PHP_EOL;
            }

            // Recursively create child departments
            if (isset($dept['children']) && ! empty($dept['children'])) {
                $this->createDepartments($dept['children'], $orgCode, (string) $departmentId, (string) $currentPath);
            }
        }
    }

    /**
     * English department name translations.
     */
    private function translateDepartmentName(string $name): string
    {
        $translations = [
            'Headquarters' => 'Headquarters',
            'Technology Department' => 'Technology Department',
            'Product Department' => 'Product Department',
            'Marketing Department' => 'Marketing Department',
            'HR Department' => 'HR Department',
            'Frontend Team' => 'Frontend Team',
            'Backend Team' => 'Backend Team',
            'QA Team' => 'QA Team',
            'Design Team' => 'Design Team',
            'Product Team' => 'Product Team',
            'Marketing Team' => 'Marketing Team',
            'Sales Team' => 'Sales Team',
        ];

        return $translations[$name] ?? $name;
    }

    /**
     * Assign users to departments.
     */
    private function assignUsersToDepartments(string $orgCode): void
    {
        // Get users under the organization
        $users = Db::table('delightful_contact_users')
            ->where('organization_code', $orgCode)
            ->get()
            ->toArray();

        if (empty($users)) {
            echo "No users under org {$orgCode}; skipping department assignment" . PHP_EOL;
            return;
        }

        // Get departments under the organization
        $departments = Db::table('delightful_contact_departments')
            ->where('organization_code', $orgCode)
            ->get()
            ->toArray();

        if (empty($departments)) {
            echo "No departments under org {$orgCode}; skipping department assignment" . PHP_EOL;
            return;
        }

        // Track department leaders
        $leaderInfo = [];

        // Find an admin user to lead headquarters
        $adminUser = null;
        foreach ($users as $user) {
            if (str_contains($user['nickname'], 'admin')) {
                $adminUser = $user;
                break;
            }
        }

        if ($adminUser) {
            // Get the HQ department (root)
            $hqDept = null;
            foreach ($departments as $dept) {
                if ($dept['parent_department_id'] === PlatformRootDepartmentId::Delightful
                    || $dept['department_id'] === PlatformRootDepartmentId::Delightful
                    || $dept['parent_department_id'] === ''
                    || $dept['parent_department_id'] === null) {
                    $hqDept = $dept;
                    break;
                }
            }

            if ($hqDept) {
                // Update HQ department leader
                Db::table('delightful_contact_departments')
                    ->where('id', $hqDept['id'])
                    ->update(['leader_user_id' => $adminUser['user_id']]);

                $leaderInfo[$hqDept['department_id']] = $adminUser['user_id'];

                // Assign admin to HQ
                $this->assignUserToDepartment($adminUser, $hqDept, true, null, $orgCode);
                echo "Set user {$adminUser['nickname']}(ID: {$adminUser['user_id']}) as HQ leader" . PHP_EOL;

                // Ensure the admin is in at least 2 departments
                $adminAssignedDepartments = 1; // Already assigned to HQ

                // Assign leaders for each level-1 department
                $level1Depts = array_filter($departments, function ($dept) {
                    return $dept['level'] === 1;
                });

                // First assign the admin to the first level-1 department
                if (! empty($level1Depts)) {
                    $firstL1Dept = reset($level1Depts);
                    $this->assignUserToDepartment($adminUser, $firstL1Dept, true, null, $orgCode);
                    echo "Assigned admin {$adminUser['nickname']}(ID: {$adminUser['user_id']}) to {$firstL1Dept['name']}" . PHP_EOL;
                    ++$adminAssignedDepartments;
                }

                // Assign other users to different departments
                $assignedUsers = 1; // Admin already assigned
                $totalUsers = count($users);

                foreach ($level1Depts as $dept) {
                    // If there are unassigned regular users, assign one as department leader
                    if ($assignedUsers < $totalUsers) {
                        // Find a non-admin user
                        $deptLeader = null;
                        foreach ($users as $user) {
                            if ($user['user_id'] !== $adminUser['user_id'] && str_contains($user['nickname'], 'user')) {
                                $deptLeader = $user;
                                break;
                            }
                        }

                        if ($deptLeader) {
                            // Update department leader
                            Db::table('delightful_contact_departments')
                                ->where('id', $dept['id'])
                                ->update(['leader_user_id' => $deptLeader['user_id']]);

                            $leaderInfo[$dept['department_id']] = $deptLeader['user_id'];

                            // Assign the user to the department and set as leader
                            $this->assignUserToDepartment($deptLeader, $dept, false, $adminUser['user_id'], $orgCode);
                            echo "Assigned user {$deptLeader['nickname']}(ID: {$deptLeader['user_id']}) to {$dept['name']}" . PHP_EOL;

                            // Remove this user from consideration
                            $users = array_filter($users, function ($user) use ($deptLeader) {
                                return $user['user_id'] !== $deptLeader['user_id'];
                            });

                            ++$assignedUsers;
                        }
                    }
                }

                // Assign remaining users to level-2 departments
                $level2Depts = array_filter($departments, function ($dept) {
                    return $dept['level'] === 2;
                });

                foreach ($level2Depts as $dept) {
                    // Find the parent department
                    $parentDept = null;
                    foreach ($departments as $d) {
                        if ($d['department_id'] === $dept['parent_department_id']) {
                            $parentDept = $d;
                            break;
                        }
                    }

                    // Use the parent department's leader as the leader
                    $leaderUserId = $parentDept ? ($leaderInfo[$parentDept['department_id']] ?? null) : null;

                    // Assign remaining users to the department
                    foreach ($users as $user) {
                        if ($assignedUsers >= $totalUsers) {
                            break; // All users assigned
                        }

                        $this->assignUserToDepartment($user, $dept, false, $leaderUserId, $orgCode);
                        echo "Assigned user {$user['nickname']}(ID: {$user['user_id']}) to {$dept['name']}" . PHP_EOL;

                        ++$assignedUsers;
                    }
                }
            }
        }
    }

    /**
     * Assign a user to a department.
     */
    private function assignUserToDepartment(array $user, array $department, bool $isLeader = false, ?string $leaderUserId = null, string $orgCode = ''): void
    {
        // Check if already assigned
        $existingAssignment = Db::table('delightful_contact_department_users')
            ->where('user_id', $user['user_id'])
            ->where('department_id', $department['department_id'])
            ->where('organization_code', $orgCode)
            ->first();

        if ($existingAssignment) {
            echo "User {$user['nickname']} is already assigned to department {$department['name']}" . PHP_EOL;
            return;
        }

        // Create department-user relation
        $deptUserData = [
            'delightful_id' => $user['delightful_id'],
            'user_id' => $user['user_id'],
            'department_id' => $department['department_id'],
            'is_leader' => $isLeader ? 1 : 0,
            'organization_code' => $orgCode,
            'city' => 'Beijing',
            'country' => 'CN',
            'join_time' => (string) time(),
            'employee_no' => 'EMP' . substr($user['user_id'], -4),
            'employee_type' => 1, // Full-time employee
            'orders' => '0',
            'custom_attrs' => json_encode(['Skills' => 'Programming', 'Hobbies' => 'Reading']),
            'is_frozen' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Only set job title for leaders
        if ($isLeader) {
            $deptUserData['job_title'] = 'Department Manager';
        }

        // Set direct leader
        if ($leaderUserId) {
            $deptUserData['leader_user_id'] = $leaderUserId;
        }

        Db::table('delightful_contact_department_users')->insert($deptUserData);

        // Update the department headcount
        Db::table('delightful_contact_departments')
            ->where('department_id', $department['department_id'])
            ->increment('employee_sum');
    }
}
