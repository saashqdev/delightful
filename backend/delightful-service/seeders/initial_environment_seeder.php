<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\OrganizationEnvironment\Entity\ValueObject\DeploymentEnum;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class InitialEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $envId = 10000;

        // Check if environment data already exists
        $existingEnvironment = Db::table('delightful_environments')->where('id', $envId)->first();

        if ($existingEnvironment) {
            echo "Environment config with ID {$envId} already exists; skipping creation" . PHP_EOL;
            return;
        }

        // Production environment config
        $productionConfig = [
            'id' => $envId,
            'environment_code' => '',
            'deployment' => DeploymentEnum::OpenSource->value,
            'environment' => 'production',
            'open_platform_config' => '{}',
            'private_config' => json_encode([
                'name' => 'Delightful Open Source',
                'domain' => [
                    [
                        'type' => PlatformType::Delightful, // token is issued by Delightful and validated internally
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'extra' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Insert environment config
        Db::table('delightful_environments')->insert($productionConfig);

        echo "Created environment config: production ID {$envId}" . PHP_EOL;
    }
}
