<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Contact\Entity\ValueObject\UserIdType;
use App\Domain\Contact\Repository\Facade\MagicUserRepositoryInterface;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class InitialAccountAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Define the three preset accounts
        $specifiedAccounts = [
            [
                'country_code' => '86',
                'phone' => '13812345678',
                'email' => 'admin@example.com',
                'real_name' => 'Administrator',
                'gender' => 1, // male
                'password' => 'bedelightful.ai', // default password
            ],
            [
                'country_code' => '86',
                'phone' => '13912345678',
                'email' => 'user@example.com',
                'real_name' => 'Standard User',
                'gender' => 2, // female
                'password' => 'bedelightful.ai', // default password
            ],
            [
                'country_code' => '86',
                'phone' => '13800138001',
                'email' => 'test@example.com',
                'real_name' => 'Test User',
                'gender' => 1, // male
                'password' => '123456', // test password
            ],
        ];

        $createdAccountIds = [];
        $allMagicIds = [];

        try {
            // Begin transaction
            Db::beginTransaction();

            // Check and create preset accounts
            foreach ($specifiedAccounts as $accountInfo) {
                // Check if this account already exists
                /** @var array $existingAccount */
                $existingAccount = Db::table('magic_contact_accounts')
                    ->where('type', 1)
                    ->where(function ($query) use ($accountInfo) {
                        $query->where('phone', $accountInfo['phone'])
                            ->orWhere('email', $accountInfo['email']);
                    })
                    ->first();

                if ($existingAccount) {
                    echo "Account already exists: {$existingAccount['real_name']}, ID: {$existingAccount['id']}, Magic ID: {$existingAccount['magic_id']}" . PHP_EOL;
                    $allMagicIds[] = $existingAccount['magic_id'];
                } else {
                    // Create a new account
                    $magicId = IdGenerator::getSnowId();
                    $accountData = [
                        'magic_id' => $magicId,
                        'type' => 1, // human account
                        'status' => 0, // active
                        'country_code' => $accountInfo['country_code'],
                        'phone' => $accountInfo['phone'],
                        'email' => $accountInfo['email'],
                        'password' => hash('sha256', $accountInfo['password']), // configured password
                        'real_name' => $accountInfo['real_name'],
                        'gender' => $accountInfo['gender'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    $accountId = Db::table('magic_contact_accounts')->insertGetId($accountData);
                    echo "Created new account: {$accountInfo['real_name']}, ID: {$accountId}, Magic ID: {$magicId}" . PHP_EOL;
                    $createdAccountIds[] = $accountId;
                    $allMagicIds[] = $magicId;
                }
            }

            if (empty($createdAccountIds)) {
                echo 'All preset accounts already exist; no new accounts created' . PHP_EOL;
            } else {
                echo 'Created ' . count($createdAccountIds) . ' new account(s), IDs: ' . implode(', ', $createdAccountIds) . PHP_EOL;
            }

            // Create two users under different organizations for each account
            $organizationCodes = ['test001', 'test002'];

            foreach ($allMagicIds as $index => $magicId) {
                $name = $index === 0 ? 'Administrator' : 'Standard User';

                foreach ($organizationCodes as $orgIndex => $orgCode) {
                    // Check whether a user record already exists for this account in the organization
                    $existingUser = Db::table('magic_contact_users')
                        ->where('magic_id', $magicId)
                        ->where('organization_code', $orgCode)
                        ->first();

                    if ($existingUser) {
                        echo "Account {$magicId} already has a user in org {$orgCode}; skipping" . PHP_EOL;
                        continue;
                    }

                    // Create the user record
                    $userId = di(MagicUserRepositoryInterface::class)->getUserIdByType(UserIdType::UserId, $orgCode);
                    $userData = [
                        'magic_id' => $magicId,
                        'organization_code' => $orgCode,
                        'user_id' => $userId,
                        'user_type' => 1, // human user
                        'status' => 1, // active
                        'nickname' => $name . ($orgIndex + 1),
                        'i18n_name' => json_encode(['zh-CN' => $name . ($orgIndex + 1), 'en-US' => ($index === 0 ? 'Admin' : 'User') . ($orgIndex + 1)]),
                        'avatar_url' => '',
                        'description' => 'User account for ' . $name,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    Db::table('magic_contact_users')->insert($userData);
                    echo "Created user for account {$magicId} in org {$orgCode}" . PHP_EOL;
                }
            }

            // Commit transaction
            Db::commit();
            echo 'Account and user seeding complete' . PHP_EOL;
        } catch (Throwable $e) {
            // Rollback transaction
            Db::rollBack();
            // Print file/line/trace
            echo 'Seeding failed: ' . $e->getMessage() . PHP_EOL;
            echo 'file: ' . $e->getFile() . PHP_EOL;
            echo 'line: ' . $e->getLine() . PHP_EOL;
            echo 'trace: ' . $e->getTraceAsString() . PHP_EOL;
        }
    }
}
