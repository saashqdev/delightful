<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Application\Agent\Service\DelightfulAgentAppService;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class InitialAgentSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // Fetch all users within the organization
            $users = Db::table('delightful_contact_users')
                ->where('user_type', 1)
                ->get()
                ->toArray();
            if (empty($users)) {
                echo 'No users found; skip agent initialization';
                return;
            }
            foreach ($users as $user) {
                // Create user authorization object
                $authorization = new DelightfulUserAuthorization();
                $authorization->setId($user['user_id']);
                $authorization->setOrganizationCode($user['organization_code']);

                // Initialize assistants
                echo "Initializing agent for user {$user['user_id']}...\n";
                try {
                    /** @var DelightfulAgentAppService $agentService */
                    $agentService = di(DelightfulAgentAppService::class);
                    $agentService->initAgents($authorization);
                    echo "Agent initialization succeeded for user {$user['user_id']}\n";
                } catch (Throwable $e) {
                    echo 'Agent initialization failed: ' . $e->getMessage() . "\n";
                    echo 'file: ' . $e->getFile() . "\n";
                    echo 'line: ' . $e->getLine() . "\n";
                    // Continue to next user without interrupting the process
                    continue;
                }
            }
            echo "All organization agents initialized\n";
        } catch (Throwable $e) {
            echo 'Agent initialization process failed: ' . $e->getMessage() . "\n";
            // Do not throw to avoid interrupting the entire seeder
        }
    }
}
